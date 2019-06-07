<?php

namespace SimpleS3\Wrappers;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use SimpleS3\Client;
use SimpleS3\Helpers\File;

class Cache
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Cache constructor.
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
            $keysInCache = $this->getKeysInCache($bucketName);

            // 1. If there is no prefix return the non-indexed array
            if (null === $prefix) {
                $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($keysInCache));

                return iterator_to_array($it, false);
            }

            // 2. check if isset $keysInCache[$prefix] and return the result
            if (true !== File::endsWithSlash($prefix)) {
                $prefix .= DIRECTORY_SEPARATOR;
            }

            if (isset($keysInCache[$prefix])) {
                return $keysInCache[$prefix];
            }

            // 3. No results, return empty array
            return [];
        }
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

        $fileInfo = File::getInfo($item);

        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR;
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

            $prefix = $this->getDirName($keyName);

            $keysInCache[$prefix][] = $keyName;
            array_unique($keysInCache[$prefix]);

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

                if (true !== File::endsWithSlash($keyName)) {
                    $keyName .= DIRECTORY_SEPARATOR;
                }

                if (isset($keysInCache[$keyName])) {
                    unset($keysInCache[$keyName]);
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
}
