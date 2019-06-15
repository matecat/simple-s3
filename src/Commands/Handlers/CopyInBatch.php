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

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Components\Encoders\S3ObjectSafeNameEncoder;

class CopyInBatch extends CommandHandler
{
    /**
     * Copy in batch items from a bucket to another one.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/copy-object.html?highlight=copy
     *
     * @param array $params
     *
     * Example:
     * $input = [
     *      'source_bucket' => 'ORIGINAL-BUCKET',
     *      'target_bucket' => 'TARGET-BUCKET', (OPTIONAL)
     *      'files' => [
     *          'source' => [
     *              'keyname-1',
     *              'keyname-2',
     *          ],
     *          'target' => [ (OPTIONAL)
     *              'keyname-3',
     *              'keyname-4',
     *          ],
     *      ],
     * ];
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        if (isset($params['target_bucket'])) {
            $this->client->createBucketIfItDoesNotExist(['bucket' => $params['target_bucket']]);
        }

        $commands = [];
        $errors = [];
        $targetKeys= [];
        $targetBucket = (isset($params['target_bucket'])) ? $params['target_bucket'] : $params['source_bucket'];

        foreach ($params['files']['source'] as $key => $file) {
            $targetKey  = (isset($params['files']['target'][$key])) ? $params['files']['target'][$key] : $file;
            $targetKeys[] = $targetKey;
            $commands[] = $this->client->getConn()->getCommand('CopyObject', [
                'Bucket'     => $targetBucket,
                'Key'        => S3ObjectSafeNameEncoder::encode($targetKey),
                'CopySource' => $params['source_bucket'] . DIRECTORY_SEPARATOR . S3ObjectSafeNameEncoder::encode($file),
            ]);
        }

        try {
            // Create a pool and provide an optional array of configuration
            $pool = new CommandPool($this->client->getConn(), $commands, [
                'concurrency' => (isset($params['concurrency'])) ? $params['concurrency'] : 25,
                'before' => function (CommandInterface $cmd, $iterKey) {
                    if(null !== $this->commandHandlerLogger){
                        $this->commandHandlerLogger->log($this, sprintf('About to send \'%s\'', $iterKey));
                    }
                },
                // Invoke this function for each successful transfer
                'fulfilled' => function (
                    ResultInterface $result,
                    $iterKey,
                    PromiseInterface $aggregatePromise
                ) use ($targetBucket, $targetKeys) {
                    if(null !== $this->commandHandlerLogger){
                        $this->commandHandlerLogger->log($this, sprintf('Completed copy of \'%s\'', $targetKeys[$iterKey]));
                    }

                    if ($this->client->hasCache()) {
                        $this->client->getCache()->set($targetBucket, $targetKeys[$iterKey], '');
                    }
                },
                // Invoke this function for each failed transfer
                'rejected' => function (
                    AwsException $reason,
                    $iterKey,
                    PromiseInterface $aggregatePromise
                ) {
                    $errors[] = $reason;

                    if(null !== $this->commandHandlerLogger){
                        $this->commandHandlerLogger->logExceptionAndReturnFalse($reason);
                    }

                    throw $reason;
                },
            ]);

            // Initiate the pool transfers and waits it ends
            $pool->promise()->wait();

            if (count($errors) === 0) {
                if(null !== $this->commandHandlerLogger){
                    $this->commandHandlerLogger->log($this, sprintf('Copy in batch from \'%s\' to \'%s\' was succeded without errors', $params['source_bucket'], $targetBucket));
                }

                return true;
            }

            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during copying in batch from \'%s\' to \'%s\'', $params['source_bucket'], (isset($params['target_bucket'])) ? $params['target_bucket'] : $params['source_bucket']), 'warning');
            }

            return false;
        } catch (\Exception $e) {
            $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
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
            isset($params['source_bucket']) and
            isset($params['files']['source'])
        );
    }
}
