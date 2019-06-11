<?php

namespace SimpleS3\Components\Cache;

interface CacheInterface
{
    const SAFE_DELIMITER = '::';

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return mixed
     */
    public function get($bucket, $keyname);

    /**
     * @param $bucket
     * @param $keyname
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
     * @param null $keyname
     *
     * @return array
     */
    public function search($bucket, $keyname = null);

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed $content
     * @param null $ttl
     */
    public function set($bucket, $keyname, $content, $ttl = null);
}