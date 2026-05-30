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

class IsBucketVersioned extends CommandHandler
{
    /**
     * Check if is enabled versioning for a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-versioning.html?highlight=versioning%20bucket
     *
     * @param array<string, mixed> $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): mixed
    {
        try {
            $ver = $this->client->getConn()->getBucketVersioning([
                    'Bucket' => $params[ 'bucket' ]
            ]);

            return $ver[ 'Status' ] === 'Enabled';
        } catch (Exception $e) {
            return $this->commandHandlerLogger?->logExceptionAndReturnFalse($e) ?? false;
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
