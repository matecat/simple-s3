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

namespace Matecat\SimpleS3\Commands\Handlers;

use Matecat\SimpleS3\Commands\CommandHandler;

class ClearBucket extends CommandHandler
{
    /**
     * Clear a bucket.
     *
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $errors = [];

        if (false === $this->client->hasBucket(['bucket' => $bucketName])) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Bucket \'%s\' does not exists', $bucketName), 'warning');
            }

            return false;
        }

        $items = $this->client->getItemsInABucket(['bucket' => $bucketName]);

        if (count($items) === 0) {
            return true;
        }

        foreach ($items as $key) {
            $version = null;
            if (strpos($key, '<VERSION_ID:') !== false) {
                $v = explode('<VERSION_ID:', $key);
                $version = str_replace('>', '', $v[1]);
                $key = $v[0];
            }

            if (false === $delete = $this->client->deleteItem(['bucket' => $bucketName, 'key' => $key, 'version' => $version])) {
                $errors[] = $delete;
            }
        }

        if (count($errors) === 0) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Bucket \'%s\' was successfully cleared', $bucketName));
            }

            return true;
        }

        if (null !== $this->commandHandlerLogger) {
            $this->commandHandlerLogger->log($this, sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');
        }

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
