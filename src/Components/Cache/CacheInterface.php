<?php

namespace Matecat\SimpleS3\Components\Cache;

interface CacheInterface {
    const string HASH_ALGORITHM      = 'crc32b'; // 8 chars
    const string HASH_SAFE_SEPARATOR = '::';
    const int    TTL_STANDARD        = 10800; // 3 hours

    /**
     * @return bool
     */
    public function flushAll(): bool;

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return mixed
     */
    public function get( string $bucket, string $keyname, ?string $version = null ): mixed;

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return bool
     */
    public function has( string $bucket, string $keyname, ?string $version = null ): bool;

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     */
    public function remove( string $bucket, string $keyname, ?string $version = null ): bool;

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array
     */
    public function search( string $bucket, string $keyname ): array;

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param mixed       $content
     * @param string|null $version
     * @param int         $ttl
     */
    public function set( string $bucket, string $keyname, mixed $content, ?string $version = null, int $ttl = 0 );

    /**
     * @param string $separator
     */
    public function setPrefixSeparator( string $separator );

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return int
     */
    public function ttl( string $bucket, string $keyname, ?string $version = null ): int;
}
