<?php

namespace SimpleS3\Components\Cache;

use \Redis;
use \RedisArray;
use \RedisCluster;

class RedisCache implements CacheInterface
{
    const MAX_TTL        = 180; // 3 hours

    /**
     * @var \Predis\Client|Redis|RedisArray|RedisCluster
     */
    private $redisClient;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $redisClient The redis client
     */
    public function __construct($redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return mixed
     */
    public function get($bucket, $keyname)
    {
        if($this->has($bucket, $keyname)){
            return unserialize($this->redisClient->get($this->getKeyName($bucket, $keyname)));
        }
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return bool
     */
    public function has($bucket, $keyname) {
        return $this->redisClient->exists($this->getKeyName($bucket, $keyname));
    }

    /**
     * @param string $bucket
     * @param string $keyname
     */
    public function remove($bucket, $keyname)
    {
        if($this->has($bucket, $keyname)){
            $this->redisClient->del($this->getKeyName($bucket, $keyname));
        }
    }

    /**
     * @param string $bucket
     * @param null $keyname
     *
     * @return array
     */
    public function search($bucket, $keyname = null)
    {
        $pattern = $bucket . self::SAFE_DELIMITER;

        if(null != $keyname){
            $pattern .= $keyname;
        }

        $pattern .= '*';

        return $this->redisClient->keys($pattern);
    }

    /**
     * @param string $bucket
     * @param string $keyname
     * @param mixed $content
     * @param null $ttl
     */
    public function set($bucket, $keyname, $content, $ttl = null)
    {
        if(false == $this->has($bucket, $keyname)){
            $ttl = (null != $ttl and $ttl < self::MAX_TTL) ? $ttl : self::MAX_TTL;
            $this->redisClient->set( $this->getKeyName($bucket, $keyname), serialize($content), 'ex', $ttl);
        }
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return string
     */
    private function getKeyName($bucket, $keyname)
    {
        return $bucket . self::SAFE_DELIMITER . $keyname;
    }
}