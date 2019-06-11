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

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class DeleteBucket extends CommandHandler
{
    /**
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        if ($this->client->hasBucket(['bucket' => $bucketName])) {
            try {
                $items = $this->getItemsFromBucket($bucketName);
                $delete = $this->client->getConn()->deleteBucket([
                    'Bucket' => $bucketName
                ]);

                if (($delete instanceof ResultInterface) and $delete['@metadata']['statusCode'] === 204) {
                    $this->removeAllFromCache($bucketName, $items);
                    $this->loggerWrapper->log(sprintf('Bucket \'%s\' was successfully deleted', $bucketName));

                    return true;
                }

                $this->loggerWrapper->log(sprintf('Something went wrong in deleting bucket \'%s\'', $bucketName), 'warning');

                return false;
            } catch (S3Exception $e) {
                $this->loggerWrapper->logExceptionAndContinue($e);
            }
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
     *
     * @return array
     */
    private function getItemsFromBucket($bucketName)
    {
        $resultPaginator = $this->client->getConn()->getPaginator('ListObjects', [
            'Bucket' => $bucketName,
        ]);

        $items = [];

        foreach ($resultPaginator as $result) {
            if (is_array($contents = $result->get('Contents'))) {
                for ($i = 0; $i < count($contents); $i++) {
                    $items[] = $contents[$i]['Key'];
                }
            }
        }

        return $items;
    }

    /**
     * @param string $bucketName
     * @param array  $items
     */
    private function removeAllFromCache( $bucketName, $items)
    {
        foreach ($items as $key) {
            $this->cacheWrapper->removeAnItemOrPrefix($bucketName, $key, false);
            $this->cacheWrapper->removeItem($bucketName, $key);
        }
    }
}
