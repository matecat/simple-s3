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

class DeleteBucket extends CommandHandler
{
    /**
     * Delete the entire bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/delete-bucket.html
     *
     * @param array $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): bool
    {
        $bucketName = $params[ 'bucket' ];

        if (false === $this->client->hasBucket(['bucket' => $bucketName])) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' does not exists', $bucketName), 'warning');

            return false;
        }

        try {
            $this->client->clearBucket(['bucket' => $bucketName]);
            $delete = $this->client->getConn()->deleteBucket(['Bucket' => $bucketName]);

            if (($delete instanceof ResultInterface) and $delete[ '@metadata' ][ 'statusCode' ] === 204) {
                $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' was successfully deleted', $bucketName));

                return true;
            }

            $this->commandHandlerLogger?->log($this, sprintf('Something went wrong in deleting bucket \'%s\'', $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return (isset($params[ 'bucket' ]));
    }
}
