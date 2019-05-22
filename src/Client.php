<?php

namespace SimpleS3;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use SimpleS3\Exceptions\InvalidS3NameException;
use SimpleS3\Validators\S3BucketNameValidator;
use SimpleS3\Validators\S3ObjectSafeNameValidator;

/**
 * Class clientFactory
 *
 * User: Mauro Cassani
 * Date: 15/05/19
 * Time: 10:00
 *
 * This class is a simple wrapper of Aws\S3\S3Client
 * It provides direct methods for upload, delete and retrieve files from S3 buckets
 *
 * @package SimpleS3
 */
final class Client
{
    /**
     * @var S3Client
     */
    private $s3;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Client constructor.
     *
     * @param       $accessKeyId
     * @param       $secretKey
     * @param array $config
     */
    public function __construct(
        $accessKeyId,
        $secretKey,
        array $config
    ) {
        $this->s3 = ClientFactory::create($accessKeyId, $secretKey, $config);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $bucketName
     *
     * @return bool
     * @throws \Exception
     */
    public function clearBucket($bucketName)
    {
        $errors = [];

        if ($this->hasBucket($bucketName)) {
            $results = $this->s3->getPaginator('ListObjects', [
                    'Bucket' => $bucketName
            ]);

            foreach ($results as $result) {
                foreach ($result['Contents'] as $object) {
                    if(false === $delete = $this->deleteFile($bucketName, $object['Key'])){
                        $errors[] = $delete;
                    }
                }
            }
        }

        if(count($errors) === 0){
            $this->log(sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

            return true;
        }

        return false;
    }

    /**
     * @param array $input
     *
     * Example:
     * $input = [
     *      'source_bucket' => 'ORIGINAL-BUCKET',
     *      'target_bucket' => 'TARGET-BUCKET',
     *      'files' => [
     *          'source' => [
     *              'keyname-1',
     *              'keyname-2',
     *          ],
     *          'target' => [
     *              'keyname-3',
     *              'keyname-4',
     *          ],
     *      ],
     * ];
     *
     * @return bool
     * @throws \Exception
     */
    public function copyInBatch(array $input)
    {
        $this->validateInputArray($input);
        $this->createBucketIfItDoesNotExist($input['target_bucket']);

        $batch = [];
        $errors = [];

        foreach ($input['files']['source'] as $key => $file){
            $batch[] = $this->s3->getCommand('CopyObject', [
                'Bucket'     => $input['target_bucket'],
                'Key'        => (isset($input['files']['target'][$key])) ? $input['files']['target'][$key] : $file,
                'CopySource' => $input['source_bucket'].'/'.$file,
            ]);
        }

        try {
            $results = CommandPool::batch($this->s3, $batch);
            foreach($results as $result) {
                if ($result instanceof AwsException) {
                    $errors[] = $result;
                    $this->logExceptionOrContinue($result);
                }
            }

            if(count($errors) === 0){
                $this->log(sprintf('Copy in batch from %s to %s was succeded without errors', $input['source_bucket'], $input['target_bucket']));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $input
     */
    private function validateInputArray($input)
    {
        if (
            !isset($input['source_bucket']) or
            !isset($input['target_bucket']) or
            !isset($input['files']['source']
        )
        ) {
            throw new \InvalidArgumentException( 'malformed input array' );
        }
    }

    /**
     * @param $sourceBucket
     * @param $sourceKeyname
     * @param $targetBucketName
     * @param $targetKeyname
     *
     * @return bool
     * @throws \Exception
     */
    public function copyFile($sourceBucket, $sourceKeyname, $targetBucketName, $targetKeyname)
    {
        try {
            $copied = $this->s3->copyObject([
                'Bucket' => $targetBucketName,
                'Key'    => $targetKeyname,
                'CopySource'    => $sourceBucket.'/'.$sourceKeyname,
            ]);

            if(($copied instanceof ResultInterface) and $copied['@metadata']['statusCode'] === 200){
                $this->log(sprintf('File \'%s/%s\' was successfully copied to \'%s/%s\'', $sourceBucket, $sourceKeyname, $targetBucketName, $targetKeyname));

                return true;
            }

            $this->log(sprintf('Something went wrong in copying file \'%s/%s\'', $sourceBucket, $sourceKeyname), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param      $bucketName
     * @param null $lifeCycleDays
     *
     * @return bool
     * @throws \Exception
     */
    public function createBucketIfItDoesNotExist($bucketName, $lifeCycleDays = null)
    {
        if (false === S3BucketNameValidator::isValid($bucketName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid bucket name. ['.implode(', ', S3BucketNameValidator::validate($bucketName)).']', $bucketName));
        }

        if (false === $this->hasBucket($bucketName)) {
            try {
                $bucket = $this->s3->createBucket([
                    'Bucket' => $bucketName
                ]);

                if (null !== $lifeCycleDays) {
                    $this->setBucketLifecycle($bucketName, $lifeCycleDays);
                }

                if(($bucket instanceof ResultInterface) and $bucket['@metadata']['statusCode'] === 200){
                    $this->log(sprintf('Bucket \'%s\' was successfully created', $bucketName));

                    return true;
                }

                $this->log(sprintf('Something went wrong during creation of bucket \'%s\'', $bucketName),'warning');

                return false;
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
    }

    /**
     * @param $bucketName
     * @param $ttl
     *
     * @throws \Exception
     */
    private function setBucketLifecycle($bucketName, $lifeCycleDays)
    {
        try {
            $this->s3->putBucketLifecycle([
                'Bucket' => $bucketName,
                'LifecycleConfiguration' => [
                    'Rules' => [
                        [
                            'Expiration' => [
                                    'Date' => $this->getBucketExpiringDate($lifeCycleDays),
                            ],
                            'ID' => 'unique_id',
                            'Status' => 'Enabled',
                            'Prefix' => ''
                        ],
                    ],
                ],
            ]);
        } catch (\InvalidArgumentException $exception) {
            $this->logExceptionOrContinue($exception);
        }
    }

    /**
     * @param $ttl
     *
     * @return string (it MUST be at midnight GMT like '2019-12-31 T00:00:00.000Z')
     * @throws \Exception
     */
    private function getBucketExpiringDate( $lifeCycleDays)
    {
        $expiringDate = new \DateTime();
        $expiringDate->modify('+'.(int)$lifeCycleDays.'days');
        $expiringDate->setTimezone(new \DateTimeZone('GMT'));

        return $expiringDate->format('Y-m-d \T00:00:00.000\Z');
    }

    /**
     * @param $bucketName
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteBucket($bucketName)
    {
        if ($this->hasBucket($bucketName)) {
            try {
                $delete = $this->s3->deleteBucket([
                    'Bucket' => $bucketName
                ]);

                if(($delete instanceof ResultInterface) and $delete['@metadata']['statusCode'] === 204){
                    $this->log(sprintf('Bucket \'%s\' was successfully deleted', $bucketName));

                    return true;
                }

                $this->log(sprintf('Something went wrong in deleting bucket \'%s\'', $bucketName), 'warning');

                return false;
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
    }

    /**
     * @param $bucketName
     * @param $keyname
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteFile($bucketName, $keyname)
    {
        try {
            $delete = $this->s3->deleteObject([
                    'Bucket' => $bucketName,
                    'Key'    => $keyname
            ]);

            if(($delete instanceof ResultInterface) and $delete['DeleteMarker'] === false and $delete['@metadata']['statusCode'] === 204){
                $this->log(sprintf('File \'%s\' was successfully deleted from \'%s\' bucket', $keyname, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong in deleting file \'%s\' from \'%s\' bucket', $keyname, $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function getBucketLifeCycle($bucketName)
    {
        try {
            $result = $this->s3->getBucketLifecycle([
                    'Bucket' => $bucketName
            ]);

            $this->log(sprintf('LifeCycle of \'%s\' bucket was successfully obtained', $bucketName));

            return $result['Rules'][0]['Expiration']['Date'];
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function getBucketSize($bucketName)
    {
        $size = 0;

        foreach ($this->getFilesInABucket($bucketName) as $file) {
            $size += $file['@metadata']['headers']['content-length'];
        }

        $this->log(sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));

        return $size;
    }

    /**
     * @param $bucketName
     * @param $keyname
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function getFile($bucketName, $keyname)
    {
        try {
            $file = $this->s3->getObject([
                    'Bucket' => $bucketName,
                    'Key'    => $keyname
            ]);

            $this->log(sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyname, $bucketName));

            return $file;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     *
     * @return array
     * @throws \Exception
     */
    public function getFilesInABucket($bucketName)
    {
        try {
            $results = $this->s3->getPaginator('ListObjects', [
                    'Bucket' => $bucketName
            ]);

            $filesArray = [];

            foreach ($results as $result) {
                foreach ($result[ 'Contents' ] as $object) {
                    $filesArray[$object[ 'Key' ]] = $this->getFile($bucketName, $object[ 'Key' ]);
                }
            }

            $this->log(sprintf('Files were successfully obtained from \'%s\' bucket', $bucketName));

            return $filesArray;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param        $bucketName
     * @param        $keyname
     * @param string $expires
     *
     * @return \Psr\Http\Message\UriInterface
     * @throws \Exception
     */
    public function getPublicFileLink($bucketName, $keyname, $expires = '+1 hour')
    {
        try {
            $cmd = $this->s3->getCommand('GetObject', [
                    'Bucket' => $bucketName,
                    'Key'    => $keyname
            ]);

            $link = $this->s3->createPresignedRequest($cmd, $expires)->getUri();
            $this->log(sprintf('Public link of \'%s\' file was successfully obtained from \'%s\' bucket', $keyname, $bucketName));

            return $link;
        } catch (\InvalidArgumentException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     *
     * @return bool
     */
    public function hasBucket($bucketName)
    {
        return $this->s3->doesBucketExist($bucketName);
    }

    /**
     * @param $bucketName
     * @param $keyname
     *
     * @return bool
     */
    public function hasFile($bucketName, $keyname)
    {
        return $this->s3->doesObjectExist($bucketName, $keyname);
    }

    /**
     * @param      $bucketName
     * @param      $keyname
     * @param      $source
     * @param null $lifeCycleDays
     *
     * @return bool
     * @throws \Exception
     */
    public function uploadFile($bucketName, $keyname, $source, $lifeCycleDays = null)
    {
        $this->createBucketIfItDoesNotExist($bucketName, $lifeCycleDays);

        if (false === S3ObjectSafeNameValidator::isValid($keyname)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyname)).']', $keyname));
        }

        $uploader = new MultipartUploader(
            $this->s3,
            $source,
            [
                'bucket' => $bucketName,
                'key'    => $keyname,
            ]
        );

        try {
            $upload = $uploader->upload();

            if(($upload instanceof ResultInterface) and $upload['@metadata']['statusCode'] === 200){
                $this->log(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyname, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyname, $bucketName), 'warning');

            return false;
        } catch (MultipartUploadException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $message
     */
    private function log($message, $level = 'info')
    {
        if (null !== $this->logger) {
            $this->logger->{$level}($message);
        }
    }

    /**
     * Log the exception or continue with default behaviour
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    private function logExceptionOrContinue(\Exception $exception)
    {
        if (null !== $this->logger) {
            $this->logger->error($exception->getMessage());
        } else {
            throw $exception; // continue with the default behaviour
        }
    }
}
