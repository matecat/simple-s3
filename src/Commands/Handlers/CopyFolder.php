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
use Matecat\SimpleS3\Helpers\File;

class CopyFolder extends CommandHandler
{
    /**
     * @param array{
     *     target_bucket: ?string,
     *     target_folder: string,
     *     source_bucket: string,
     *     source_folder: string,
     *     delete_source: ?bool,
     * } $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = ['source_bucket' => '', 'source_folder' => '', 'target_bucket' => null, 'target_folder' => '', 'delete_source' => false]): bool
    {
        $targetBucketName = (isset($params[ 'target_bucket' ])) ? $params[ 'target_bucket' ] : $params[ 'source_bucket' ];
        $targetFolder     = $params[ 'target_folder' ];
        $sourceBucketName = $params[ 'source_bucket' ];
        $sourceFolder     = $params[ 'source_folder' ];

        try {
            $sourceItems = $this->client->getItemsInABucket([
                    'bucket' => $sourceBucketName,
                    'prefix' => $sourceFolder,
            ]);

            $success = true;

            foreach ($sourceItems as $sourceItem) {
                if (false === File::endsWith($sourceFolder, $this->client->getPrefixSeparator())) {
                    $sourceFolder = $sourceFolder . $this->client->getPrefixSeparator();
                }

                $targetKeyName = $targetFolder . $this->client->getPrefixSeparator() . str_replace($sourceFolder, "", $sourceItem);

                $copiedSourceItems = $this->client->copyItem([
                        'target_bucket' => $targetBucketName,
                        'target'        => $targetKeyName,
                        'source_bucket' => $sourceBucketName,
                        'source'        => $sourceItem,
                ]);

                if ($copiedSourceItems === false) {
                    $success = false;
                }
            }

            if (isset($params[ 'delete_source' ]) and true === $params[ 'delete_source' ]) {
                $deleteSource = $this->client->deleteFolder([
                        'bucket' => $sourceBucketName,
                        'prefix' => $sourceFolder,
                ]);

                if ($deleteSource === true) {
                    $success = false;
                }
            }

            return $success;
        } catch (Exception $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }

    /**
     * @param array<string, string> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return (
                isset($params[ 'target_folder' ]) and
                isset($params[ 'source_bucket' ]) and
                isset($params[ 'source_folder' ])
        );
    }
}
