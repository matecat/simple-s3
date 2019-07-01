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
use SimpleS3\Helpers\File;

class GetItemsInAVersionedBucket extends GetItemsInABucket
{
    /**
     * Get the list of keys in a versioned bucket.
     * If 'hydrate' parameter is set to true, an array of hydrated Aws\Result is returned instead.
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
                if (false === File::endsWith($params['prefix'], $this->client->getPrefixSeparator())) {
                    $params['prefix'] .= $this->client->getPrefixSeparator();
                }

                $config['Delimiter'] = (isset($params['delimiter'])) ? $params['delimiter'] : $this->client->getPrefixSeparator();
                $config['Prefix'] = $params['prefix'];
            }

            if ($this->client->hasCache() and isset($config['Prefix'])) {
                return $this->returnItemsFromCache($bucketName, $config, (isset($params['hydrate'])) ? $params['hydrate'] : null);
            }

            return $this->returnVersionedItemsFromS3($bucketName, $config, (isset($params['hydrate'])) ? $params['hydrate'] : null);
        } catch (S3Exception $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }
}
