<?php

namespace SimpleS3\Components\Cache;

use SimpleS3\Helpers\File;

class RedisCache implements CacheInterface
{
    /**
     * @var \Predis\Client|\Redis|\RedisArray|\RedisCluster
     */
    private $redisClient;

    /**
     * RedisCache constructor.
     *
     * @param \Predis\Client|\Redis|\RedisArray|\RedisCluster $redisClient
     */
    public function __construct($redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array|mixed
     */
    public function get($bucket, $keyname)
    {
        return unserialize($this->redisClient->hget($this->getHashPrefix($bucket, $keyname), $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return bool
     */
    public function has($bucket, $keyname)
    {
        return (1 === $this->redisClient->hexists($this->getHashPrefix($bucket, $keyname), $keyname)) ? true : false;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     */
    public function remove($bucket, $keyname)
    {
        $this->redisClient->hdel($this->getHashPrefix($bucket, $keyname), $keyname);
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array
     */
    public function search($bucket, $keyname)
    {
        return $this->redisClient->hkeys($this->getHashPrefix($bucket, $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed  $content
     * @param null $ttl
     */
    public function set($bucket, $keyname, $content, $ttl = null)
    {
        $this->redisClient->hset($this->getHashPrefix($bucket, $keyname), $keyname, serialize($content));
        $this->redisClient->expire($this->getHashPrefix($bucket, $keyname), (null != $ttl) ? $ttl * 60 : self::TTL_STANDARD);
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     *
     * @return string
     */
    private function getHashPrefix($bucketName, $keyName)
    {
        return hash(self::HASH_ALGORITHM, $bucketName . self::HASH_SAFE_SEPARATOR . $this->getDirName($keyName));
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
