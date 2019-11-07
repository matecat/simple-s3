<?php
/**
 *  This file is part of the Simple S3 package.
 *
 * (c) Mauro Cassani<https://github.com/mauretto78>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SimpleS3;

use Aws\ResultInterface;
use Aws\S3\S3Client;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Components\Cache\CacheInterface;
use SimpleS3\Components\Encoders\SafeNameEncoderInterface;

/**
 * Class Client
 *
 * This class is a simple wrapper of Aws\S3\S3Client
 * -------------------------------------------------------------------------
 *
 * Method list:
 *
 * @method bool clearBucket(array $input)
 * @method bool copyFolder(array $input)
 * @method bool copyInBatch(array $input)
 * @method bool copyItem(array $input)
 * @method bool createBucketIfItDoesNotExist(array $input)
 * @method bool createFolder(array $input)
 * @method bool deleteBucket(array $input)
 * @method bool deleteBucketPolicy(array $input)
 * @method bool deleteFolder(array $input)
 * @method bool deleteItem(array $input)
 * @method bool downloadItem(array $input)
 * @method bool enableAcceleration(array $input)
 * @method ResultInterface|mixed getBucketLifeCycleConfiguration(array $input)
 * @method mixed getBucketPolicy(array $input)
 * @method int|mixed getBucketSize(array $input)
 * @method null|string getCurrentItemVersion(array $input)
 * @method ResultInterface|mixed getItem(array $input)
 * @method array|mixed getItemsInABucket(array $input)
 * @method array|mixed getItemsInAVersionedBucket(array $input)
 * @method mixed|UriInterface getPublicItemLink(array $input)
 * @method bool hasBucket(array $input)
 * @method bool hasFolder(array $input)
 * @method bool hasItem(array $input)
 * @method bool isBucketVersioned(array $input)
 * @method mixed|UriInterface openItem(array $input)
 * @method bool restoreItem(array $input)
 * @method bool setBucketLifecycleConfiguration(array $input)
 * @method bool setBucketPolicy(array $input)
 * @method bool setBucketVersioning(array $input)
 * @method bool transfer(array $input)
 * @method bool uploadItem(array $input)
 * @method bool uploadItemFromBody(array $input)
 *
 * @package SimpleS3
 */
final class Client
{
    /**
     * @var string
     */
    private $prefixSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SafeNameEncoderInterface
     */
    private $encoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var S3Client
     */
    private $s3;

    /**
     * @var bool
     */
    private $sslVerify = true;

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->s3 = ClientFactory::create($config);
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
     * @param CacheInterface $cache
     */
    public function addCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->cache->setPrefixSeparator($this->prefixSeparator);
    }

    /**
     * @return bool
     */
    public function hasCache()
    {
        return null !== $this->cache;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param SafeNameEncoderInterface $encoder
     */
    public function addEncoder(SafeNameEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @return bool
     */
    public function hasEncoder()
    {
        return null !== $this->encoder;
    }

    /**
     * @return SafeNameEncoderInterface
     */
    public function getEncoder()
    {
        return $this->encoder;
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

    /**
     * @var string
     */
    public function setPrefixSeparator($separator)
    {
        $this->prefixSeparator = $separator;
    }

    /**
     * @return string
     */
    public function getPrefixSeparator()
    {
        return $this->prefixSeparator;
    }
}
