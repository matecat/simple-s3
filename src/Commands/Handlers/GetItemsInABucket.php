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

namespace SimpleS3\Commands\Handlers;

use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Components\Cache\CacheInterface;
use SimpleS3\Components\Cache\RedisCache;
use SimpleS3\Helpers\File;

class GetItemsInABucket extends CommandHandler
{
    /**
     * If cache is activated
     *
     * @param array $params
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        try {
            $config = [
                'Bucket' => $bucketName,
            ];

            if (isset($params['prefix'])) {

                // add a final slash to prefix
                if (false === File::endsWithSlash($params['prefix'])) {
                    $params['prefix'] .= DIRECTORY_SEPARATOR;
                }

                $config['Delimiter'] = DIRECTORY_SEPARATOR;
                $config['Prefix'] = $params['prefix'];
            }

            if ($this->client->hasCache()) {
                return $this->returnItemsFromCache($bucketName, $config, (isset($params['hydrate'])) ? $params['hydrate'] : null);
            }

            return $this->returnItemsFromS3($bucketName, $config, (isset($params['hydrate'])) ? $params['hydrate'] : null);
        } catch (S3Exception $e) {
            $this->loggerWrapper->logExceptionAndContinue($e);
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (isset($params['bucket']));
    }

    /**
     * @param string $bucketName
     * @param array $config
     * @param null $hydrate
     *
     * @return array
     */
    private function returnItemsFromCache($bucketName, $config, $hydrate = null)
    {
        $items = [];
        $itemsFromCache = $this->client->getCache()->search($bucketName, (isset($config['Prefix'])) ? $config['Prefix'] : null);

        // no data was found, try to retrieve data from S3
        if (count($itemsFromCache) == 0) {
            return $this->returnItemsFromS3($bucketName, $config, $hydrate);
        }

        foreach ($itemsFromCache as $key) {

            // key in cache are stored like:
            // bucket::key.ext
            // and we need to get back to
            // key.ext
            $key = str_replace($bucketName . CacheInterface::SAFE_DELIMITER,'', $key);

            if (null != $hydrate and true === $hydrate) {
                $items[$key] = $this->client->getItem(['bucket' => $bucketName, 'key' => $key]);
            } else {
                $items[] = $key;
            }
        }

        $this->loggerWrapper->log(sprintf('Files of \'%s\' bucket were successfully obtained from CACHE', $bucketName));

        return $items;
    }

    /**
     * @param string $bucketName
     * @param array $config
     *
     * @return array
     */
    private function returnItemsFromS3($bucketName, $config, $hydrate = null)
    {
        $resultPaginator = $this->client->getConn()->getPaginator('ListObjects', $config);
        $items = [];

        foreach ($resultPaginator as $result) {
            if (is_array($contents = $result->get('Contents'))) {
                for ($i = 0; $i < count($contents); $i++) {
                    $key = $contents[$i]['Key'];

                    if (null != $hydrate and true === $hydrate) {
                        $items[$key] = $this->client->getItem(['bucket' => $bucketName, 'key' => $key]);
                    } else {
                        $items[] = $key;
                    }

                    // send to cache, just to be sure that S3 is syncronized with cache
                    $this->client->getCache()->set($bucketName, $key, $this->client->getItem(['bucket' => $bucketName, 'key' => $key]));
                }
            }
        }

        $this->loggerWrapper->log(sprintf('Files were successfully obtained from \'%s\' bucket', $bucketName));

        return $items;
    }
}
