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
use Matecat\SimpleS3\Components\Cache\CacheInterface;
use Matecat\SimpleS3\Components\Encoders\SafeNameEncoderInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface ClientInterface
 *
 * Public contract of {@see Client}, useful to mock the client and
 * inject it into external software that depends on this library.
 *
 * The following dynamic methods are dispatched through {@see Client::__call()}
 * to the matching CommandHandler classes:
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
 * @package Matecat\SimpleS3
 */
interface ClientInterface
{
    /**
     * Dispatch a command to its matching CommandHandler.
     *
     * @param string                      $name
     * @param array<array<string,string>> $args
     *
     * @return mixed
     */
    public function __call(string $name, array $args);

    /**
     * @param CacheInterface $cache
     */
    public function addCache(CacheInterface $cache): void;

    /**
     * @phpstan-assert-if-true !null $this->getCache()
     *
     * @return bool
     */
    public function hasCache(): bool;

    /**
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface;

    /**
     * @param SafeNameEncoderInterface $encoder
     */
    public function addEncoder(SafeNameEncoderInterface $encoder): void;

    /**
     * @phpstan-assert-if-true !null $this->getEncoder()
     *
     * @return bool
     */
    public function hasEncoder(): bool;

    /**
     * @return SafeNameEncoderInterface|null
     */
    public function getEncoder(): ?SafeNameEncoderInterface;

    /**
     * @param LoggerInterface $logger
     */
    public function addLogger(LoggerInterface $logger): void;

    /**
     * @phpstan-assert-if-true !null $this->getLogger()
     *
     * @return bool
     */
    public function hasLogger(): bool;

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface;

    /**
     * @return S3Client
     */
    public function getConn(): S3Client;

    /**
     * Disable SSL verify.
     */
    public function disableSslVerify(): void;

    /**
     * @return bool
     */
    public function hasSslVerify(): bool;

    /**
     * @param string $separator
     */
    public function setPrefixSeparator(string $separator): void;

    /**
     * @return string
     */
    public function getPrefixSeparator(): string;

    /**
     * @return int
     */
    public function getFilenameMaxSize(): int;

    /**
     * @param int $filenameMaxSize
     */
    public function setFilenameMaxSize(int $filenameMaxSize): void;
}

