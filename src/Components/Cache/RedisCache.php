<?php

namespace SimpleS3\Components\Cache;

use Predis\Client;
use Predis\Collection\Iterator\Keyspace;
use SimpleS3\Helpers\File;

class RedisCache implements CacheInterface
{
    /**
     * @var int
     */
    private $ttl;

    /**
     * @var \Predis\Client|\Redis|\RedisArray|\RedisCluster
     */
    private $redisClient;

    /**
     * RedisCache constructor.
     *
     * @param \Predis\Client|\Redis|\RedisArray|\RedisCluster $redisClient
     * @param null $ttl
     */
    public function __construct($redisClient, $ttl = null)
    {
        $this->redisClient = $redisClient;
        $this->ttl = (isset($ttl)) ? $ttl : self::TTL_STANDARD;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array|mixed
     */
    public function get($bucket, $keyname)
    {
        return unserialize($this->redisClient->hget($this->generateKeyForCache($bucket, $keyname), $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return bool
     */
    public function has($bucket, $keyname)
    {
        return (1 === $this->redisClient->hexists($this->generateKeyForCache($bucket, $keyname), $keyname)) ? true : false;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     */
    public function remove($bucket, $keyname)
    {
        $this->redisClient->hdel($this->generateKeyForCache($bucket, $keyname), $keyname);
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array
     */
    public function search($bucket, $keyname)
    {
        return $this->redisClient->hgetall($this->generateKeyForCache($bucket, $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed  $content
     */
    public function set($bucket, $keyname, $content)
    {
        $this->redisClient->hset($this->generateKeyForCache($bucket, $keyname), $keyname, serialize($content));
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     *
     * @return string
     */
    private function generateKeyForCache($bucketName, $keyName)
    {
        return call_user_func(self::ENCRYPTION_ALGORITHM, $bucketName . self::SAFE_DELIMITER . $this->getDirName($keyName));
    }

    /**
     * @param string $item
     *
     * @return string
     */
    private function getDirName($item)
    {
        if (File::endsWithSlash($item)) {
            return $item;
        }

        $fileInfo = File::getPathInfo($item);

        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR;
    }
}
