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
     * @param $key
     *
     * @return mixed|null
     */
    public function getFromCache($key)
    {
        if (null !== $this->client->getCache()) {
            return unserialize($this->client->getCache()->get($key));
        }
    }

    /**
     * @param $key
     */
    public function removeFromCache($key)
    {
        if (null !== $this->client->getCache()) {
            $this->client->getCache()->remove($key);
        }
    }

    /**
     * @param $key
     * @param $value
     * @param $ttl
     */
    public function setToCache($key, $value, $ttl)
    {
        if (null !== $this->client->getCache()) {
            $this->client->getCache()->set(md5($key), serialize($value), $ttl);
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
