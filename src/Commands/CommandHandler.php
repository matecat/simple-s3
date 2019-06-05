<?php

namespace SimpleS3\Commands;

use SimpleS3\Client;

abstract class CommandHandler implements CommandHandlerInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * CommandHandlerAbstract constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $bucketName
     * @param null $prefix
     *
     * @return mixed
     */
    public function getFromCache($bucketName, $prefix = null)
    {
        if (null !== $this->client->getCache()) {
            return unserialize($this->client->getCache()->get(md5($bucketName)));
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param int $ttl
     */
    public function setInCache($bucketName , $keyName, $ttl = 0)
    {
        if($this->client->hasCache()){
            $keysInCache = is_array($this->getFromCache($bucketName)) ? $this->getFromCache($bucketName) : [];
            array_push($keysInCache, $keyName);

            $this->client->getCache()->set(md5($bucketName), serialize($keysInCache), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     */
    public function removeFromCache($bucketName, $keyName = null)
    {
        if($this->client->hasCache()){
            if($keyName){
                $keysInCache = is_array($this->getFromCache($bucketName)) ? $this->getFromCache($bucketName) : [];
                $keysInCache = array_filter($keysInCache, function($element) use ($keyName) {
                    return $element === $keyName;
                });

                $this->client->getCache()->set(md5($bucketName), serialize($keysInCache));
            } else {
                $this->client->getCache()->remove(md5($bucketName));
            }
        }
    }

    /**
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = 'info')
    {
        if (null !== $this->client->getLogger()) {
            $this->client->getLogger()->{$level}($message);
        }
    }

    /**
     * Log the exception or continue with default behaviour
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function logExceptionOrContinue(\Exception $exception)
    {
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->error($exception->getMessage());
        } else {
            throw $exception; // continue with the default behaviour
        }
    }
}
