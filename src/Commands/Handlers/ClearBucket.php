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

use SimpleS3\Commands\CommandHandler;

class ClearBucket extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $errors = [];

        if ($this->client->hasBucket(['bucket' => $bucketName])) {
            $items = $this->client->getItemsInABucket(['bucket' => $bucketName]);
            foreach ($items as $key){
                if (false === $delete = $this->client->deleteItem(['bucket' => $bucketName, 'key' => $key])) {
                    $errors[] = $delete;
                }
            }

            if (count($errors) === 0) {
                $this->loggerWrapper->log(sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

                return true;
            }

            $this->loggerWrapper->log(sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');

            return false;
        }

        $this->loggerWrapper->log(sprintf('Bucket \'%s\' was not found', $bucketName), 'warning');

        return false;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return isset($params['bucket']);
    }
}
