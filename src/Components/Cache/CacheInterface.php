<?php

namespace SimpleS3\Components\Cache;

interface CacheInterface
{
    const ENCRYPTION_ALGORITHM = 'crc32b'; // 16 chars
    const SAFE_DELIMITER = '::';
    const TTL_STANDARD = 180 * 60; // 3 hours in seconds

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
}
