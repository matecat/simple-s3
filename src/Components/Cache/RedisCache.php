<?php

namespace SimpleS3\Components\Cache;

use Predis\Client as Redis;
use SimpleS3\Helpers\File;

class RedisCache implements CacheInterface
{
    /**
     * @var Redis
     */
    private $redisClient;

    /**
     * RedisCache constructor.
     *
     * @param Redis $redisClient
     */
    public function __construct(Redis $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @return bool
     */
    public function flushAll()
    {
        $flush = $this->redisClient->flushall();

        if ($flush->getPayload() === 'OK') {
            return true;
        }

        return false;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return array|mixed
     */
    public function get($bucket, $keyname, $version = null)
    {
        if (null != $version) {
            $keyname .= '<VERSION_ID:'.$version.'>';
        }

        return unserialize($this->redisClient->hget($this->getHashPrefix($bucket, $keyname), $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return bool
     */
    public function has($bucket, $keyname, $version = null)
    {
        if (null != $version) {
            $keyname .= '<VERSION_ID:'.$version.'>';
        }

        return (1 === $this->redisClient->hexists($this->getHashPrefix($bucket, $keyname), $keyname)) ? true : false;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     */
    public function remove($bucket, $keyname, $version = null)
    {
        if (null != $version) {
            $keyname .= '<VERSION_ID:'.$version.'>';
        }

        $this->redisClient->hdel($this->getHashPrefix($bucket, $keyname), [$keyname]);
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
     * @param null   $version
     * @param null   $ttl
     */
    public function set($bucket, $keyname, $content, $version = null, $ttl = null)
    {
        if (null != $version) {
            $keyname .= '<VERSION_ID:'.$version.'>';
        }

        $this->redisClient->hset($this->getHashPrefix($bucket, $keyname), $keyname, serialize($content));

        if ($this->ttl($bucket, $keyname) === -1) {
            $this->redisClient->expire($this->getHashPrefix($bucket, $keyname), (null != $ttl) ? $ttl * 60 : self::TTL_STANDARD);
        }
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param null $version
     *
     * @return int
     */
    public function ttl($bucket, $keyname, $version = null)
    {
        if (null != $version) {
            $keyname .= '<VERSION_ID:'.$version.'>';
        }

        return $this->redisClient->ttl($this->getHashPrefix($bucket, $keyname));
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
