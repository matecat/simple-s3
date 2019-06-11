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
    const ENCRYPTION_ALGORITHM = 'md5';
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
     * @param string $prefix
     *
     * @return array
     */
    public function getKeysForAPrefix( $bucketName, $prefix)
    {
        if (null !== $this->client->getCache()) {
            if (true !== File::endsWithSlash($prefix)) {
                $prefix .= DIRECTORY_SEPARATOR;
            }

            return $this->_getKeysForAPrefix($bucketName, $prefix);
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
    public function setAKeyInAPrefix( $bucketName, $keyName, $ttl = self::TTL_STANDARD)
    {
        if ($this->client->hasCache()) {
            // set key in cache
            $valuesFromCache = $this->_getKeysForAPrefix($bucketName, $keyName);
            $valuesFromCache[] = $keyName;
            $this->cache->set($this->getKeyForAPrefix($bucketName, $keyName), serialize(array_unique($valuesFromCache)), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param bool   $isPrefix
     */
    public function removeAnItemOrPrefix( $bucketName, $keyName, $isPrefix = true)
    {
        if ($this->client->hasCache()) {
            if ($isPrefix) {
                $this->deletePrefix($bucketName, $keyName);
            } else {
                $this->deleteItemInAPrefix($bucketName, $keyName);
            }
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     */
    private function deletePrefix( $bucketName, $keyName)
    {
        if (true !== File::endsWithSlash($keyName)) {
            $keyName .= DIRECTORY_SEPARATOR;
        }

        $this->client->getCache()->remove($this->getKeyForAPrefix($bucketName, $keyName));
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     */
    private function deleteItemInAPrefix( $bucketName, $keyName)
    {
        $valuesFromCache = $this->_getKeysForAPrefix($bucketName, $keyName);

        if (($key = array_search($keyName, $valuesFromCache)) !== false) {
            unset($valuesFromCache[$key]);
        }

        $this->cache->set($this->getKeyForAPrefix($bucketName, $keyName), serialize(array_unique($valuesFromCache)));
    }

    /**
     * Gets the key stored in cache
     * Example:
     * md5(your-bucket::folder/to/path)
     *
     * @param string $bucketName
     * @param string $keyName
     *
     * @return string
     */
    private function getKeyForAPrefix($bucketName, $keyName)
    {
        return call_user_func(self::ENCRYPTION_ALGORITHM, $bucketName . self::SAFE_DELIMITER . $this->getDirName($keyName));
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     *
     * @return array
     */
    private function _getKeysForAPrefix( $bucketName, $keyName)
    {
        $values = unserialize($this->client->getCache()->get($this->getKeyForAPrefix($bucketName, $keyName)));

        return (false !== $values) ? $values : [];
    }





    public function getAnItem($bucketName, $keyName)
    {
        return unserialize($this->cache->get($this->getKeyForAnItem($bucketName, $keyName)));
    }

    public function setAnItem($bucketName, $keyName, $body, $ttl = self::TTL_STANDARD)
    {
        $this->cache->set($this->getKeyForAnItem($bucketName, $keyName), serialize($body), $ttl);
    }

    private function getKeyForAnItem($bucketName, $keyName)
    {
        return call_user_func(self::ENCRYPTION_ALGORITHM, $bucketName . '#####' . $keyName);
    }
}
