<?php

namespace SimpleS3;

use Aws\Exception\MultipartUploadException;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

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
     * @return Result
     * @throws \Exception
     */
    public function createBucketIfItDoesNotExist($bucketName)
    {
        if (false === $this->hasBucket($bucketName)) {
            try {
                return $this->s3->createBucket([
                    'Bucket' => $bucketName
                ]);
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
    }

    /**
     * @param $bucketName
     *
     * @return bool
     */
    private function hasBucket($bucketName)
    {
        $buckets = $this->s3->listBuckets();

        foreach ($buckets[ 'Buckets' ] as $bucket) {
            if ($bucket[ 'Name' ] === $bucketName) {
                return true;
            }
        }

        return false;
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
                return $this->s3->deleteBucket([
                    'Bucket' => $bucketName
                ]);
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
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

        return $deleted;
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
            return $this->s3->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => $keyname
            ]);
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
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
            return $this->s3->getObject([
                'Bucket' => $bucketName,
                'Key'    => $keyname
            ]);
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
    public function getFilesFromABucket($bucketName)
    {
        try {
            $results = $this->s3->getPaginator('ListObjects', [
                'Bucket' => $bucketName
            ]);

            $filesArray = [];

            foreach ($results as $result) {
                foreach ($result[ 'Contents' ] as $object) {
                    $filesArray[] = $this->getFile($bucketName, $object[ 'Key' ]);
                }
            }

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
    public function getPublicFileLink( $bucketName, $keyname, $expires = '+1 hour')
    {
        try {
            $cmd = $this->s3->getCommand('GetObject', [
                'Bucket' => $bucketName,
                'Key'    => $keyname
            ]);

            return $this->s3->createPresignedRequest($cmd, $expires)->getUri();
        } catch (\InvalidArgumentException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param $bucketName
     * @param $keyname
     * @param $source
     *
     * @return Result
     * @throws \Exception
     */
    public function uploadFile($bucketName, $keyname, $source)
    {
        $this->createBucketIfItDoesNotExist($bucketName);

        $uploader = new MultipartUploader(
            $this->s3,
            $source,
            [
                'bucket' => $bucketName,
                'key'    => $keyname,
            ]
        );

        try {
            return $uploader->upload();
        } catch (MultipartUploadException $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * Log the exception or continue with default behaviour
     *
     * @param \Exception $e
     *
     * @throws \Exception
     */
    private function logExceptionOrContinue(\Exception $e)
    {
        if (null !== $this->logger) {
            $this->logger->error($e->getMessage());
        } else {
            throw $e; // default behaviour
        }
    }
}
