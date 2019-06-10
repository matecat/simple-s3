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
    public function getFromCache($bucketName, $prefix)
    {
        if (null !== $this->client->getCache()) {
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
            $this->cache->set($this->getKeyInCache($bucketName, $keyName), serialize(array_unique($valuesFromCache)), $ttl);
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param bool $idDir
     */
    public function removeFromCache($bucketName, $keyName, $idDir = true)
    {
        if ($this->client->hasCache()) {
            if($idDir){
                $this->deleteFolder($bucketName, $keyName);
            } else {
                $this->deleteItem($bucketName, $keyName);
            }
        }
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     */
    private function deleteFolder($bucketName, $keyName)
    {
        if (true !== File::endsWithSlash($keyName)) {
            $keyName .= DIRECTORY_SEPARATOR;
        }

        $this->client->getCache()->remove($this->getKeyInCache($bucketName, $keyName));
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

        $this->cache->set($this->getKeyInCache($bucketName, $keyName), serialize(array_unique($valuesFromCache)));
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
    private function getKeyInCache( $bucketName, $keyName)
    {
        return call_user_func(self::ENCRYPTION_ALGORITHM, $bucketName . self::SAFE_DELIMITER . $this->getDirName($keyName));
    }

    /**
     * @param string $bucketName
     * @param null $keyName
     *
     * @return array
     */
    private function getValuesFromCache($bucketName, $keyName)
    {
        $values = unserialize($this->client->getCache()->get($this->getKeyInCache($bucketName, $keyName)));

        return (false !== $values) ? $values : [];
    }
}
