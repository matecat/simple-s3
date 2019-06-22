<?php

namespace SimpleS3\Components\Cache;

interface CacheInterface
{
    const HASH_ALGORITHM      = 'crc32b'; // 8 chars
    const HASH_SAFE_SEPARATOR = '::';
    const TTL_STANDARD        = 10800; // 3 hours

    /**
     * @return bool
     */
    public function flushAll();

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return mixed
     */
    public function get($bucket, $keyname, $version = null);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return bool
     */
    public function has($bucket, $keyname, $version = null);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     */
    public function remove($bucket, $keyname, $version = null);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return array
     */
    public function search($bucket, $keyname);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed  $content
     * @param null   $version
     * @param null   $ttl
     */
    public function set($bucket, $keyname, $content, $version = null, $ttl = null);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return int
     */
    public function ttl($bucket, $keyname, $version = null);
}
