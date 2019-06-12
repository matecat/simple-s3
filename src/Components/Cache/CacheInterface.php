<?php

namespace SimpleS3\Components\Cache;

interface CacheInterface
{
    const ENCRYPTION_ALGORITHM = 'md5';
    const SAFE_DELIMITER = '::';
    const TTL_STANDARD = 180; // 3 hours

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
     */
    public function set($bucket, $keyname, $content);
}
