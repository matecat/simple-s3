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

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class DeleteItem extends CommandHandler
{
    /**
     * Delete an item.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/delete-object.html
     *
     * @param array<string, mixed> $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): bool
    {
        $bucketName = $params[ 'bucket' ];
        $keyName    = $params[ 'key' ];
        $version    = (isset($params[ 'version' ])) ? $params[ 'version' ] : null;

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        try {
            $config = [
                    'Bucket' => $bucketName,
                    'Key'    => $keyName,
            ];

            if (null != $version) {
                $config[ 'VersionId' ] = $version;
            }

            $delete = $this->client->getConn()->deleteObject($config);

            if (($delete instanceof ResultInterface) and $delete[ '@metadata' ][ 'statusCode' ] === 204) {
                $this->commandHandlerLogger?->log($this, sprintf('File \'%s\' was successfully deleted from \'%s\' bucket', $keyName, $bucketName));

                if ($this->client->hasCache()) {
                    $this->client->getCache()->remove($bucketName, $keyName, $version);
                }

                return true;
            }

            $this->commandHandlerLogger?->log($this, sprintf('Something went wrong in deleting file \'%s\' from \'%s\' bucket', $keyName, $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return (
                isset($params[ 'bucket' ]) and
                isset($params[ 'key' ])
        );
    }
}
