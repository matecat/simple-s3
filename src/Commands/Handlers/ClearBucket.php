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

use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class ClearBucket extends CommandHandler
{
    /**
     * Clear a bucket.
     *
     * @param array{bucket: string} $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = ['bucket' => 'no-bucket-provided']): bool
    {
        $bucketName = $params[ 'bucket' ];
        $errors     = [];

        if (false === $this->client->hasBucket(['bucket' => $bucketName])) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' does not exists', $bucketName), 'warning');

            return false;
        }

        $items = $this->client->getItemsInABucket(['bucket' => $bucketName]);

        if (count($items) === 0) {
            return true;
        }

        foreach ($items as $key) {
            $version = null;
            if (str_contains($key, '<VERSION_ID:')) {
                $v       = explode('<VERSION_ID:', $key);
                $version = str_replace('>', '', $v[ 1 ]);
                $key     = $v[ 0 ];
            }

            if (false === $delete = $this->client->deleteItem(['bucket' => $bucketName, 'key' => $key, 'version' => $version])) {
                $errors[] = $delete;
            }
        }

        if (count($errors) === 0) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

            return true;
        }

        $this->commandHandlerLogger?->log($this, sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');

        return false;
    }

    /**
     * @param array<string, string> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return isset($params[ 'bucket' ]);
    }
}
