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

namespace Matecat\SimpleS3;

use Aws\ResultInterface;
use Aws\S3\S3Client;
use Exception;
use InvalidArgumentException;
use Matecat\SimpleS3\Commands\CommandHandler;
use Matecat\SimpleS3\Components\Cache\CacheInterface;
use Matecat\SimpleS3\Components\Encoders\SafeNameEncoderInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
/**
 * Class Client
 *
 * This class is a simple wrapper of Aws\S3\S3Client
 * -------------------------------------------------------------------------
 *
 * Method list:
 *
 * @method bool clearBucket(array<string, mixed> $input)
 * @method bool copyFolder(array<string, mixed> $input)
 * @method bool copyInBatch(array<string, mixed> $input)
 * @method bool copyItem(array<string, mixed> $input)
 * @method bool createBucketIfItDoesNotExist(array<string, mixed> $input)
 * @method bool createFolder(array<string, mixed> $input)
 * @method bool deleteBucket(array<string, mixed> $input)
 * @method bool deleteBucketPolicy(array<string, mixed> $input)
 * @method bool deleteFolder(array<string, mixed> $input)
 * @method bool deleteItem(array<string, mixed> $input)
 * @method bool downloadItem(array<string, mixed> $input)
 * @method bool enableAcceleration(array<string, mixed> $input)
 * @method ResultInterface|mixed getBucketLifeCycleConfiguration(array<string, mixed> $input)
 * @method mixed getBucketPolicy(array<string, mixed> $input)
 * @method int|mixed getBucketSize(array<string, mixed> $input)
 * @method null|string getCurrentItemVersion(array<string, mixed> $input)
 * @method ResultInterface|mixed getItem(array<string, mixed> $input)
 * @method array<int|string, mixed> getItemsInABucket(array<string, mixed> $input)
 * @method array<int|string, mixed> getItemsInAVersionedBucket(array<string, mixed> $input)
 * @method UriInterface getPublicItemLink(array<string, mixed> $input)
 * @method bool hasBucket(array<string, mixed> $input)
 * @method bool hasFolder(array<string, mixed> $input)
 * @method bool hasItem(array<string, mixed> $input)
 * @method bool isBucketVersioned(array<string, mixed> $input)
 * @method mixed|UriInterface openItem(array<string, mixed> $input)
 * @method bool restoreItem(array<string, mixed> $input)
 * @method bool setBucketLifecycleConfiguration(array<string, mixed> $input)
 * @method bool setBucketPolicy(array<string, mixed> $input)
 * @method bool setBucketVersioning(array<string, mixed> $input)
 * @method bool transfer(array<string, mixed> $input)
 * @method bool uploadItem(array<string, mixed> $input)
 * @method bool uploadItemFromBody(array<string, mixed> $input)
 *
 * @package SimpleS3
 */
final class Client implements ClientInterface
{
    /**
     * @var string
     */
    private string $prefixSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var CacheInterface|null
     */
    private ?CacheInterface $cache = null;

    /**
     * @var SafeNameEncoderInterface|null
     */
    private ?SafeNameEncoderInterface $encoder = null;

    /**
     * @var ?LoggerInterface
     */
    private ?LoggerInterface $logger = null;

    /**
     * @var S3Client
     */
    private S3Client $s3;

    /**
     * @var bool
     */
    private bool $sslVerify = true;

    /**
     * @var int
     */
    private int $filenameMaxSize;

    /**
     * Client constructor.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->s3              = ClientFactory::create($config);
        $this->filenameMaxSize = 255;
    }

    /**
     * Calls the invoked CommandHandler.
     * It checks if the class exists and
     * if the passed parameters are valid
     *
     * @param string                      $name
     * @param array<array<string,string>> $args
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $args)
    {
        $params = $args[ 0 ] ?? [];

        $commandHandler = 'Matecat\\SimpleS3\\Commands\\Handlers\\' . ucfirst($name);

        if (false === class_exists($commandHandler)) {
            throw new InvalidArgumentException($commandHandler . ' is not a valid command name. Please refer to README to get the complete command list.');
        }

        /** @var CommandHandler $commandHandler */
        $commandHandler = new $commandHandler($this);

        if ($commandHandler->validateParams($params)) {
            return $commandHandler->handle($params);
        }

        return null;
    }

    /**
     * @param CacheInterface $cache
     */
    public function addCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
        $this->cache->setPrefixSeparator($this->prefixSeparator);
    }

    /**
     * @phpstan-assert-if-true !null $this->cache
     * @phpstan-assert-if-true !null $this->getCache()
     *
     * @return bool
     */
    public function hasCache(): bool
    {
        return null !== $this->cache;
    }

    /**
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * @param SafeNameEncoderInterface $encoder
     */
    public function addEncoder(SafeNameEncoderInterface $encoder): void
    {
        $this->encoder = $encoder;
    }

    /**
     * @phpstan-assert-if-true !null $this->encoder
     * @phpstan-assert-if-true !null $this->getEncoder()
     *
     * @return bool
     */
    public function hasEncoder(): bool
    {
        return null !== $this->encoder;
    }

    /**
     * @return SafeNameEncoderInterface|null
     */
    public function getEncoder(): ?SafeNameEncoderInterface
    {
        return $this->encoder;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function addLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @phpstan-assert-if-true !null $this->logger
     * @phpstan-assert-if-true !null $this->getLogger()
     *
     * @return bool
     */
    public function hasLogger(): bool
    {
        return null !== $this->logger;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return S3Client
     */
    public function getConn(): S3Client
    {
        return $this->s3;
    }

    /**
     * Disable SSL verify
     */
    public function disableSslVerify(): void
    {
        $this->sslVerify = false;
    }

    /**
     * @return bool
     */
    public function hasSslVerify(): bool
    {
        return $this->sslVerify;
    }

    /**
     * @param string $separator
     */
    public function setPrefixSeparator(string $separator): void
    {
        $this->prefixSeparator = $separator;
    }

    /**
     * @return string
     */
    public function getPrefixSeparator(): string
    {
        return $this->prefixSeparator;
    }

    /**
     * @return int
     */
    public function getFilenameMaxSize(): int
    {
        return $this->filenameMaxSize;
    }

    /**
     * @param int $filenameMaxSize
     */
    public function setFilenameMaxSize(int $filenameMaxSize): void
    {
        $this->filenameMaxSize = $filenameMaxSize;
    }
}
