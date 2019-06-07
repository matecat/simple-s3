<?php

namespace SimpleS3\Wrappers;

use SimpleS3\Client;
use SimpleS3\Helpers\File;

class Cache
{
    const SAFE_DELIMITER = '::';

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
            // 1. If there is no prefix return all values for the bucket
            if (null === $prefix) {
                return $this->getValuesFromCache($bucketName);
            }

            // 2. check if isset $keysInCache[$prefix] and return the result
            if (true !== File::endsWithSlash($prefix)) {
                $prefix .= DIRECTORY_SEPARATOR;
            }

            return $this->getValuesFromCache($bucketName, $prefix);
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
            // set key in cache
            $valuesFromCache = $this->getValuesFromCache($bucketName, $keyName);
            $valuesFromCache[] = $keyName;
            $this->client->getCache()->set(md5($this->getCacheKey($bucketName, $keyName)), serialize(array_unique($valuesFromCache)), $ttl);

            // update bucket index keys
            $indexes = $this->getPrefixesFromCache($bucketName);
            $indexes[] = $this->getDirName($keyName);
            $this->client->getCache()->set(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'), serialize(array_unique($indexes)), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     */
    public function removeFromCache($bucketName, $keyName = null)
    {
        if ($this->client->hasCache()) {
            // remove the value stored in cache
            if (null != $keyName) {
                if (true !== File::endsWithSlash($keyName)) {
                    $keyName .= DIRECTORY_SEPARATOR;
                }

                $this->client->getCache()->remove(md5($bucketName . self::SAFE_DELIMITER . $keyName));
                $indexes = $this->getPrefixesFromCache($bucketName);

                if (($key = array_search($keyName, $indexes)) !== false) {
                    unset($indexes[$key]);
                }

                $indexes[] = $this->getDirName($keyName);
                $this->client->getCache()->set(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'), serialize(array_unique($indexes)));
            } else {
                // loop all prefixes and remove all values and the index
                foreach ($this->getPrefixesFromCache($bucketName) as $prefix){
                    $this->client->getCache()->remove(md5($bucketName . self::SAFE_DELIMITER . $prefix));
                }

                $this->client->getCache()->remove(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'));
            }
        }
    }

    /**
     * Gets the key stored in cache
     * Example:
     * your-bucket::folder/to/path
     *
     * @param string $bucketName
     * @param string $keyName
     *
     * @return string
     */
    private function getCacheKey($bucketName, $keyName)
    {
        return $bucketName . self::SAFE_DELIMITER . $this->getDirName($keyName);
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     *
     * @return array
     */
    private function getValuesFromCache($bucketName, $keyName = null)
    {
        // return the value stored in cache
        if(null != $keyName){
            $aa = unserialize($this->client->getCache()->get(md5($this->getCacheKey($bucketName, $keyName))));

            if(false !== $aa){
                return $aa;
            }

            return [];
        }

        // loop all prefixes and merge and return the array
        $array = [];

        foreach ($this->getPrefixesFromCache($bucketName) as $prefix){
            $values = unserialize($this->client->getCache()->get(md5($this->getCacheKey($bucketName, $prefix))));
            if(false !== $values){
                $array = array_merge($array, $values);
            }
        }

        return array_unique($array);
    }

    /**
     * @param string $bucketName
     *
     * @return mixed
     */
    private function getPrefixesFromCache($bucketName)
    {
        $prefixes = unserialize($this->client->getCache()->get(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX')));

        return (is_array($prefixes)) ? $prefixes : [];
    }
}
