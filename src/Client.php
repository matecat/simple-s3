<?php

namespace SimpleS3;

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use Exception;
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
                    if (false === $delete = $this->deleteFile($bucketName, $object['Key'])) {
                        $errors[] = $delete;
                    }
                }
            }
        }

        if (count($errors) === 0) {
            $this->log(sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

            return true;
        }

        $this->log(sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');

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
        $this->validateCopyInBatchInputArray($input);
        $this->createBucketIfItDoesNotExist($input['target_bucket']);

        $batch = [];
        $errors = [];

        foreach ($input['files']['source'] as $key => $file) {
            $batch[] = $this->s3->getCommand('CopyObject', [
                'Bucket'     => $input['target_bucket'],
                'Key'        => (isset($input['files']['target'][$key])) ? $input['files']['target'][$key] : $file,
                'CopySource' => $input['source_bucket'].'/'.$file,
            ]);
        }

        try {
            $results = CommandPool::batch($this->s3, $batch);
            foreach ($results as $result) {
                if ($result instanceof AwsException) {
                    $errors[] = $result;
                    $this->logExceptionOrContinue($result);
                }
            }

            if (count($errors) === 0) {
                $this->log(sprintf('Copy in batch from \'%s\' to \'%s\' was succeded without errors', $input['source_bucket'], $input['target_bucket']));

                return true;
            }

            $this->log(sprintf('Something went wrong during copying in batch from \'%s\' to \'%s\'', $input['source_bucket'], $input['target_bucket']), 'warning');

            return false;
        } catch (\Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $input
     */
    private function validateCopyInBatchInputArray($input)
    {
        if (
            !isset($input['source_bucket']) or
            !isset($input['target_bucket']) or
            !isset($input['files']['source']
        )
        ) {
            throw new \InvalidArgumentException('malformed input array');
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
    public function copyItem($sourceBucket, $sourceKeyname, $targetBucketName, $targetKeyname)
    {
        try {
            $copied = $this->s3->copyObject([
                'Bucket' => $targetBucketName,
                'Key'    => $targetKeyname,
                'CopySource'    => $sourceBucket.'/'.$sourceKeyname,
            ]);

            if (($copied instanceof ResultInterface) and $copied['@metadata']['statusCode'] === 200) {
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
     * @param int  $lifeCycleDays
     * @param int  $objectLifeCycleDays
     * @param null $storageClass
     *
     * @return bool
     * @throws \Exception
     */
    public function createBucketIfItDoesNotExist($bucketName, $lifeCycleDays = -1, $objectLifeCycleDays = -1, $storageClass = null)
    {
        if (false === S3BucketNameValidator::isValid($bucketName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid bucket name. ['.implode(', ', S3BucketNameValidator::validate($bucketName)).']', $bucketName));
        }

        if (false === $this->hasBucket($bucketName)) {
            try {
                $bucket = $this->s3->createBucket([
                    'Bucket' => $bucketName
                ]);

                $this->setBucketLifecycleConfiguration($bucketName, $lifeCycleDays, $objectLifeCycleDays, $storageClass);

                if (($bucket instanceof ResultInterface) and $bucket['@metadata']['statusCode'] === 200) {
                    $this->log(sprintf('Bucket \'%s\' was successfully created', $bucketName));

                    return true;
                }

                $this->log(sprintf('Something went wrong during creation of bucket \'%s\'', $bucketName), 'warning');

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
    public function createFolder($bucketName, $keyname)
    {
        try {
            $folder = $this->s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => $keyname.'/',
                'Body'   => '',
                'ACL'    => 'public-read'
            ]);

            if (($folder instanceof ResultInterface) and $folder['@metadata']['statusCode'] === 200) {
                $this->log(sprintf('Folder \'%s\' was successfully created in \'%s\' bucket', $keyname, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during creation of \'%s\' folder inside \'%s\' bucket', $keyname, $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
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

                if (($delete instanceof ResultInterface) and $delete['@metadata']['statusCode'] === 204) {
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

            if (($delete instanceof ResultInterface) and $delete['DeleteMarker'] === false and $delete['@metadata']['statusCode'] === 204) {
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
    public function getBucketLifeCycleConfiguration($bucketName)
    {
        try {
            $result = $this->s3->getBucketLifecycle([
                'Bucket' => $bucketName
            ]);

            $this->log(sprintf('LifeCycleConfiguration of \'%s\' bucket was successfully obtained', $bucketName));

            return $result;
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

        foreach ($this->getItemsInABucket($bucketName) as $file) {
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
    public function getItem($bucketName, $keyname)
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
     * @param      $bucketName
     * @param null $prefix
     *
     * @return array
     * @throws Exception
     */
    public function getItemsInABucket($bucketName, $prefix = null)
    {
        try {
            $config = [
                'Bucket' => $bucketName,
            ];

            if($prefix){
                $config['Delimiter'] = '/';
                $config['Prefix'] = $prefix;
            }

            $results = $this->s3->getIterator('ListObjects', $config);

            $filesArray = [];

            foreach ($results as $result) {
                $filesArray[$result[ 'Key' ]] = $this->getItem($bucketName, $result[ 'Key' ]);
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
    public function getPublicItemLink($bucketName, $keyname, $expires = '+1 hour')
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
    public function hasItem($bucketName, $keyname)
    {
        return $this->s3->doesObjectExist($bucketName, $keyname);
    }

    /**
     * Send a basic restore request for an archived copy of an object back into Amazon S3
     *
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/restore-object.html
     *
     * @param        $bucketName
     * @param        $keyname
     * @param int    $days
     * @param string $tier
     *
     * @return bool
     * @throws Exception
     */
    public function restoreItem($bucketName, $keyname, $days = 5, $tier = 'Expedited')
    {
        $allowedTiers = [
            'Bulk',
            'Expedited',
            'Standard',
        ];

        if ($tier and !in_array($tier, $allowedTiers)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid tier value. Allowed values are: ['.implode(',', $allowedTiers).']', $tier));
        }

        try {
            $request = $this->s3->restoreObject([
                'Bucket'         => $bucketName,
                'Key'            => $keyname,
                'RestoreRequest' => [
                    'Days'       => $days,
                    'GlacierJobParameters' => [
                        'Tier'  => $tier,
                    ],
                ],
            ]);

            if (($request instanceof ResultInterface) and $request['@metadata']['statusCode'] === 202) {
                $this->log(sprintf('A request for restore \'%s\' item in \'%s\' bucket was successfully sended', $keyname, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during sending restore questo for \'%s\' item in \'%s\' bucket', $keyname, $bucketName), 'warning');

            return false;
        } catch (\Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * Set basic bucket lifecycle configuration
     *
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-lifecycle-configuration.html
     *
     * @param      $bucketName
     * @param int  $lifeCycleDays
     * @param int  $objectLifeCycleDays
     * @param null $storageClass
     *
     * @throws Exception
     */
    public function setBucketLifecycleConfiguration($bucketName, $lifeCycleDays = -1, $objectLifeCycleDays = -1, $storageClass = null)
    {
        if ($objectLifeCycleDays > $lifeCycleDays) {
            throw new \InvalidArgumentException('Object lifecycle CANNOT be greater than bucket lifecycle');
        }

        $allowedStorageClasses = [
            'GLACIER',
            'STANDARD_IA',
            'ONEZONE_IA',
            'INTELLIGENT_TIERING',
            'DEEP_ARCHIVE',
        ];

        if ($storageClass and !in_array($storageClass, $allowedStorageClasses)) {
            throw new \InvalidArgumentException('Invalid storage class name. Allowed values: ['.implode(',', $allowedStorageClasses).']');
        }

        try {
            $settings = [
                'Bucket' => $bucketName,
                'LifecycleConfiguration' => [
                    'Rules' => [
                        [
                            'ID' => 'Lifecycle configuration for bucket '.$bucketName,
                            'Status' => 'Enabled',
                            'Prefix' => '',
                        ],
                    ],
                ],
            ];

            if ($lifeCycleDays > 0) {
                $settings['LifecycleConfiguration']['Rules'][0]['Expiration'] = [
                    'Days' => $lifeCycleDays,
                ];
            }

            if ($objectLifeCycleDays > 0) {
                $settings['LifecycleConfiguration']['Rules'][0]['Transitions'][] = [
                    'Days' => $objectLifeCycleDays,
                    'StorageClass' => ($storageClass) ? $storageClass : 'GLACIER'
                ];
            }

            $this->s3->putBucketLifecycleConfiguration($settings);
        } catch (\InvalidArgumentException $exception) {
            $this->logExceptionOrContinue($exception);
        }
    }

    /**
     * @param      $bucketName
     * @param      $keyname
     * @param      $source
     * @param null $storageClass
     *
     * @return bool
     * @throws \Exception
     */
    public function uploadItem($bucketName, $keyname, $source, $storageClass = null)
    {
        $this->createBucketIfItDoesNotExist($bucketName);

        if (false === S3ObjectSafeNameValidator::isValid($keyname)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyname)).']', $keyname));
        }

        $this->throwExceptionIfAWrongStorageIsProvided($storageClass);

        $uploader = new MultipartUploader(
            $this->s3,
            $source,
            [
                'bucket' => $bucketName,
                'key'    => $keyname,
                'before_initiate' => function (CommandInterface $command) use ($source, $storageClass) {
                    if (extension_loaded('fileinfo')) {
                        $command['ContentType'] = mime_content_type($source);
                    }

                    if ($storageClass) {
                        $command['StorageClass'] = $storageClass;
                    }
                }
            ]
        );

        try {
            $upload = $uploader->upload();

            if (($upload instanceof ResultInterface) and $upload['@metadata']['statusCode'] === 200) {
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
     * @param      $bucketName
     * @param      $keyname
     * @param      $body
     * @param null $storageClass
     *
     * @return bool
     * @throws \Exception
     */
    public function uploadItemFromBody($bucketName, $keyname, $body, $storageClass = null)
    {
        $this->createBucketIfItDoesNotExist($bucketName);

        if (false === S3ObjectSafeNameValidator::isValid($keyname)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyname)).']', $keyname));
        }

        $this->throwExceptionIfAWrongStorageIsProvided($storageClass);

        try {
            $config = [
                'Bucket' => $bucketName,
                'Key'    => $keyname,
                'Body'   => $body
            ];

            if ($storageClass) {
                $config['StorageClass'] = $storageClass;
            }

            $result = $this->s3->putObject($config);

            if (($result instanceof ResultInterface) and $result['@metadata']['statusCode'] === 200) {
                $this->log(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyname, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyname, $bucketName), 'warning');

            return false;
        } catch (\InvalidArgumentException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $storageClass
     *
     * @return bool
     */
    private function throwExceptionIfAWrongStorageIsProvided($storageClass)
    {
        $allowedStorageClasses = [
                'STANDARD',
                'REDUCED_REDUNDANCY',
                'STANDARD_IA',
                'ONEZONE_IA',
                'INTELLIGENT_TIERING',
                'GLACIER',
                'DEEP_ARCHIVE',
        ];

        if ($storageClass and !in_array($storageClass, $allowedStorageClasses)) {
            throw new \InvalidArgumentException('is not a valid StorageClass. Allowed classes are: ['.implode(',', $allowedStorageClasses).']');
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
