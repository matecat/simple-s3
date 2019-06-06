<?php

namespace SimpleS3\Commands;

use SimpleS3\Client;
use SimpleS3\Helpers\File;

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
     * @return array
     */
    public function getFromCache($bucketName, $prefix = null)
    {
        if (null !== $this->client->getCache()) {

            // if there is no prefix return the non-indexed array
            $keysInCache = $this->getKeysInCache($bucketName);
            if (null === $prefix) {
                return $keysInCache;
            }

            $array = [];
            foreach ($keysInCache as $item) {
                $array[$this->getDirName($item)][] = $item;
            }

            if (isset($array[$prefix])) {
                return $array[$prefix];
            }

            return $keysInCache;
        }
    }

    /**
     * @param string $item
     *
     * @return string
     */
    private function getDirName($item)
    {
        $fileInfo = File::getInfo($item);
        if (isset($fileInfo['extension'])) {
            return $fileInfo['dirname'];
        }

        if ($fileInfo['dirname'] === '.') {
            return $fileInfo['filename'];
        }

        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileInfo['filename'];
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param int $ttl
     */
    public function setInCache($bucketName, $keyName, $ttl = 0)
    {
        if ($this->client->hasCache()) {
            $keysInCache = $this->getKeysInCache($bucketName);

            if (!in_array($keyName, $keysInCache)) {
                array_push($keysInCache, $keyName);
            }

            $this->client->getCache()->set(md5($bucketName), serialize($keysInCache), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     */
    public function removeFromCache($bucketName, $keyName = null)
    {
        if ($this->client->hasCache()) {
            if (null != $keyName) {
                $keysInCache = $this->getKeysInCache($bucketName);

                if (in_array($keyName, $keysInCache)) {
                    foreach ($keysInCache as $index => $key) {
                        if ($key == $keyName) {
                            unset($keysInCache[$index]);
                        }
                    }
                }

                $this->client->getCache()->set(md5($bucketName), serialize($keysInCache));
            } else {
                $this->client->getCache()->remove(md5($bucketName));
            }
        }
    }

    /**
     * @param string $bucketName
     *
     * @return array|mixed
     */
    private function getKeysInCache($bucketName)
    {
        $fromCache = unserialize($this->client->getCache()->get(md5($bucketName)));

        return is_array($fromCache) ? $fromCache : [];
    }

    /**
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = 'info')
    {
        if ($this->client->hasLogger()) {
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
