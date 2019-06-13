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
     * Delete the entire bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/delete-bucket.html
     *
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
                $items = $this->client->getItemsInABucket(['Bucket' => $bucketName]);
                $delete = $this->client->getConn()->deleteBucket(['Bucket' => $bucketName]);

                if (($delete instanceof ResultInterface) and $delete['@metadata']['statusCode'] === 204) {
                    if (null != $items and count($items) > 0) {
                        $this->removeItemsInCache($bucketName, $items);
                    }

                    $this->loggerWrapper->log($this, sprintf('Bucket \'%s\' was successfully deleted', $bucketName));

                    return true;
                }

                $this->loggerWrapper->log($this, sprintf('Something went wrong in deleting bucket \'%s\'', $bucketName), 'warning');

                return false;
            } catch (S3Exception $e) {
                $this->loggerWrapper->logExceptionAndContinue($e);
            }
        }

        $this->loggerWrapper->log($this, sprintf('Bucket \'%s\' was not found', $bucketName), 'warning');

        return false;
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
     * @param array  $items
     */
    private function removeItemsInCache($bucketName, $items)
    {
        foreach ($items as $key) {
            $this->client->getCache()->remove($bucketName, $key);
        }
    }
}
