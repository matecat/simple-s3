<?php

namespace SimpleS3;

use Aws\ResultInterface;
use Aws\S3\S3Client;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SimpleS3\Commands\CommandHandler;

/**
 * Class Client
 *
 * This class is a simple wrapper of Aws\S3\S3Client
 * -------------------------------------------------------------------------
 *
 * Method list:
 *
 * @method bool clearBucket(array $input)
 * @method bool copyInBatch(array $input)
 * @method bool copyItem(array $input)
 * @method bool createBucketIfItDoesNotExist(array $input)
 * @method bool createFolder(array $input)
 * @method bool deleteBucket(array $input)
 * @method bool deleteItem(array $input)
 * @method ResultInterface|mixed getBucketLifeCycleConfiguration(array $input)
 * @method int|mixed getBucketSize(array $input)
 * @method ResultInterface|mixed getItem(array $input)
 * @method array|mixed getItemsInABucket(array $input)
 * @method mixed|UriInterface getPublicItemLink(array $input)
 * @method bool hasBucket(array $input)
 * @method bool hasItem(array $input)
 * @method mixed|UriInterface openItem(array $input)
 * @method bool restoreItem(array $input)
 * @method mixed|void setBucketLifecycleConfiguration(array $input)
 * @method bool uploadItem(array $input)
 * @method bool uploadItemFromBody(array $input)
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
     * @var bool
     */
    private $sslVerify = true;

    /**
     * Client constructor.
     *
     * @param string $accessKeyId
     * @param string $secretKey
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
     * Calls the invoked CommandHandler.
     * It checks if the class exists and
     * if the passed parameters are valid
     *
     * @param string $name
     * @param mixed $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        $params = isset($args[0]) ? $args[0] : [];

        $commandHandler = 'SimpleS3\\Commands\\Handlers\\'.ucfirst($name);

        if (false === class_exists($commandHandler)) {
            throw new \InvalidArgumentException($commandHandler . ' is not a valid command name. Please refer to README to get the complete command list.');
        }

        /** @var CommandHandler $commandHandler */
        $commandHandler = new $commandHandler($this);

        if ($commandHandler->validateParams($params)) {
            return $commandHandler->handle($params);
        }
    }

    /**
     * @param LoggerInterface $logger
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function hasLogger()
    {
        return null !== $this->logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return S3Client
     */
    public function getConn()
    {
        return $this->s3;
    }

    /**
     * Disable SSL verify
     */
    public function disableSslVerify()
    {
        $this->sslVerify = false;
    }

    /**
     * @return bool
     */
    public function hasSslVerify()
    {
        return $this->sslVerify;
    }
}
