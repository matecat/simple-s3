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
use SimpleS3\Helpers\File;

class DeleteFolder extends CommandHandler
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
        $prefix = $params['prefix'];

        if (false === File::endsWithSlash($prefix)) {
            $prefix .= DIRECTORY_SEPARATOR;
        }

        try {
            $this->client->getConn()->deleteMatchingObjects($bucketName, $prefix);
            $this->loggerWrapper->log($this, sprintf('Folder \'%s\' was successfully deleted from \'%s\' bucket', $prefix, $bucketName));

            if ($this->client->hasCache()) {
                $items = $this->client->getItemsInABucket([
                    'bucket' => $bucketName,
                    'prefix' => $prefix,
                ]);

                foreach ($items as $key) {
                    $this->client->getCache()->remove($bucketName, $key);
                }
            }

            return true;
        } catch (\Exception $e) {
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
        return (
            isset($params['bucket']) and
            isset($params['prefix'])
        );
    }
}
