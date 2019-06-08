<?php
/**
 *  This file is part of the Simple S3 package.
 *
 * (c) Mauro Cassani<https://github.com/mauretto78>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SimpleS3\Wrappers;

use Aws\PsrCacheAdapter;
use SimpleS3\Client;
use SimpleS3\Helpers\File;

class Cache
{
    const SAFE_DELIMITER = '::';
    const TTL_STANDARD = 180; // 3 hours

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var PsrCacheAdapter
     */
    private $cache;

    /**
     * Cache constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->cache = $this->client->getCache();
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
    public function setInCache($bucketName, $keyName, $ttl = self::TTL_STANDARD)
    {
        if ($this->client->hasCache()) {
            // set key in cache
            $valuesFromCache = $this->getValuesFromCache($bucketName, $keyName);
            $valuesFromCache[] = $keyName;
            $this->cache->set(md5($this->getCacheKey($bucketName, $keyName)), serialize(array_unique($valuesFromCache)), $ttl);

            // update bucket index keys
            $indexes = $this->getPrefixesFromCache($bucketName);
            $indexes[] = $this->getDirName($keyName);
            $this->cache->set(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'), serialize(array_unique($indexes)), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     * @param bool $idDir
     */
    public function removeFromCache($bucketName, $keyName = null, $idDir = true)
    {
        if ($this->client->hasCache()) {
            if (null != $keyName) {
                if($idDir){
                    $this->deleteFolder($bucketName, $keyName);
                } else {
                    $this->deleteItem($bucketName, $keyName);
                }
            } else {
                $this->deleteBucket($bucketName);
            }
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     */
    private function deleteFolder($bucketName, $keyName)
    {
        // delete prefix from cache
        if (true !== File::endsWithSlash($keyName)) {
            $keyName .= DIRECTORY_SEPARATOR;
        }

        $this->client->getCache()->remove(md5($bucketName . self::SAFE_DELIMITER . $keyName));
        $this->removeAPrefixFromIndex($bucketName, $keyName);
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     */
    private function deleteItem($bucketName, $keyName)
    {
        $valuesFromCache = $this->getValuesFromCache($bucketName, $keyName);

        if (($key = array_search($keyName, $valuesFromCache)) !== false) {
            unset($valuesFromCache[$key]);
        }

        $this->cache->set(md5($this->getCacheKey($bucketName, $keyName)), serialize(array_unique($valuesFromCache)));

        if(count(array_unique($valuesFromCache)) === 0){
            $this->removeAPrefixFromIndex($bucketName, $keyName);
        }
    }

    /**
     * @param string $bucketName
     */
    private function deleteBucket($bucketName)
    {
        // delete all prefixes and remove all values and the index
        foreach ($this->getPrefixesFromCache($bucketName) as $prefix) {
            $this->client->getCache()->remove(md5($bucketName . self::SAFE_DELIMITER . $prefix));
        }

        $this->client->getCache()->remove(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'));
    }

    /**
     * @param string $bucketName
     * @param string $index
     */
    private function removeAPrefixFromIndex($bucketName, $index)
    {
        $indexes = $this->getPrefixesFromCache($bucketName);

        if (($key = array_search($index, $indexes)) !== false) {
            unset($indexes[$key]);
        }

        $this->client->getCache()->set(md5('INDEX' . self::SAFE_DELIMITER . $bucketName . self::SAFE_DELIMITER . 'INDEX'), serialize(array_unique($indexes)));
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
        if (null != $keyName) {
            $values = unserialize($this->client->getCache()->get(md5($this->getCacheKey($bucketName, $keyName))));

            return (false !== $values) ? $values : [];
        }

        // loop all prefixes and merge and return the array
        $array = [];

        foreach ($this->getPrefixesFromCache($bucketName) as $prefix) {
            $values = unserialize($this->client->getCache()->get(md5($this->getCacheKey($bucketName, $prefix))));
            if (false !== $values) {
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
