<?php

namespace SimpleS3;

use Aws\Exception\MultipartUploadException;
use Aws\Result;
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
     * @return array
     * @throws \Exception
     */
    public function clearBucket($bucketName)
    {
        $deleted = [];

        if ($this->hasBucket($bucketName)) {
            $results = $this->s3->getPaginator('ListObjects', [
                    'Bucket' => $bucketName
            ]);

            foreach ($results as $result) {
                foreach ($result[ 'Contents' ] as $object) {
                    $deleted[] = $this->deleteFile($bucketName, $object[ 'Key' ]);
                }
            }
        }

        $this->logInfo(sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

        return $deleted;
    }

    /**
     * @param      $bucketName
     * @param null $lifeCycleDays
     *
     * @return Result
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

                $this->logInfo(sprintf('Bucket \'%s\' was successfully created', $bucketName));

                return $bucket;
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
     * @return Result
     * @throws \Exception
     */
    public function deleteBucket($bucketName)
    {
        if ($this->hasBucket($bucketName)) {
            try {
                $delete = $this->s3->deleteBucket([
                        'Bucket' => $bucketName
                ]);

                $this->logInfo(sprintf('Bucket \'%s\' was successfully deleted', $bucketName));

                return $delete;
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
    }

    /**
     * @param $bucketName
     * @param $keyname
     *
     * @return Result
     * @throws \Exception
     */
    public function deleteFile($bucketName, $keyname)
    {
        try {
            $delete = $this->s3->deleteObject([
                    'Bucket' => $bucketName,
                    'Key'    => $keyname
            ]);

            $this->logInfo(sprintf('File \'%s\' was successfully deleted from \'%s\' bucket', $keyname, $bucketName));

            return $delete;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     *
     * @return Result
     * @throws \Exception
     */
    public function getBucketLifeCycle($bucketName)
    {
        try {
            $result = $this->s3->getBucketLifecycle([
                    'Bucket' => $bucketName
            ]);

            $this->logInfo(sprintf('LifeCycle of \'%s\' bucket was successfully obtained', $bucketName));

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

        $this->logInfo(sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));

        return $size;
    }

    /**
     * @param $bucketName
     * @param $keyname
     *
     * @return Result
     * @throws \Exception
     */
    public function getFile($bucketName, $keyname)
    {
        try {
            $file = $this->s3->getObject([
                    'Bucket' => $bucketName,
                    'Key'    => $keyname
            ]);

            $this->logInfo(sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyname, $bucketName));

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

            $this->logInfo(sprintf('Files were successfully obtained from \'%s\' bucket', $bucketName));

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
            $this->logInfo(sprintf('Public link of \'%s\' file was successfully obtained from \'%s\' bucket', $keyname, $bucketName));

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
     * @return Result
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
            $this->logInfo(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyname, $bucketName));

            return $upload;
        } catch (MultipartUploadException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $message
     */
    private function logInfo($message)
    {
        if (null !== $this->logger) {
            $this->logger->info($message);
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
