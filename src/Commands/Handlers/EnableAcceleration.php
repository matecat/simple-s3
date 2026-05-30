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
use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class EnableAcceleration extends CommandHandler
{
    /**
     * Enable acceleration for a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-accelerate-configuration.html
     *
     * @param array<string, mixed> $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): bool
    {
        $bucketName = $params[ 'bucket' ];

        try {
            $accelerate = $this->client->getConn()->putBucketAccelerateConfiguration(
                    [
                            'AccelerateConfiguration' => [
                                    'Status' => 'Enabled',
                            ],
                            'Bucket'                  => $bucketName,
                    ]
            );

            if (($accelerate instanceof ResultInterface) and $accelerate[ '@metadata' ][ 'statusCode' ] === 200) {
                $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' was successfully set to transfer accelerated mode', $bucketName));

                return true;
            }

            $this->commandHandlerLogger?->log($this, sprintf('Something went wrong during setting of bucket \'%s\' to transfer accelerated mode', $bucketName), 'warning');

            return false;
        } catch (Exception $e) {
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
        return isset($params[ 'bucket' ]);
    }
}
