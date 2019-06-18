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

class GetBucketLifeCycleConfiguration extends CommandHandler
{
    /**
     * Get the lifecycle configuration of a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/get-bucket-lifecycle-configuration.html
     *
     * @param array $params
     *
     * @return ResultInterface|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        try {
            $result = $this->client->getConn()->getBucketLifecycle([
                'Bucket' => $bucketName
            ]);

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('LifeCycleConfiguration of \'%s\' bucket was successfully obtained', $bucketName));
            }

            return $result;
        } catch (S3Exception $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
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
