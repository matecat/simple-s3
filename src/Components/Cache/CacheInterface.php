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
     *
     * @return mixed
     */
    public function get($bucket, $keyname);

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return bool
     */
    public function has($bucket, $keyname);

    /**
     * @param string $bucket
     * @param string $keyname
     */
    public function remove($bucket, $keyname);

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array
     */
    public function search($bucket, $keyname);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed $content
     * @param null $ttl
     */
    public function set($bucket, $keyname, $content, $ttl = null);

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return int
     */
    public function ttl($bucket, $keyname);
}
