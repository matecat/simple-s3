<?php

namespace SimpleS3\Commands\Handlers;

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
use SimpleS3\Commands\CommandHandler;

class CopyInBatch extends CommandHandler
{
    /**
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
        if(isset($params['target_bucket'])){
            $this->client->createBucketIfItDoesNotExist(['bucket' => $params['target_bucket']]);
        }

        $commands = [];
        $errors = [];

        foreach ($params['files']['source'] as $key => $file) {
            $commands[] = $this->client->getConn()->getCommand('CopyObject', [
                    'Bucket'     => (isset($params['target_bucket'])) ? $params['target_bucket'] : $params['source_bucket'],
                    'Key'        => (isset($params['files']['target'][$key])) ? $params['files']['target'][$key] : $file,
                    'CopySource' => $params['source_bucket'] . DIRECTORY_SEPARATOR . $file,
            ]);
        }

        try {
            // Create a pool and provide an optional array of configuration
            $pool = new CommandPool($this->client->getConn(), $commands, [
                'concurrency' => (isset($params['concurrency'])) ? $params['concurrency'] : 25,
                'before' => function (CommandInterface $cmd, $iterKey) {
                    $this->log(sprintf('About to send \'%s\'',$iterKey));
                },
                // Invoke this function for each successful transfer
                'fulfilled' => function (
                    ResultInterface $result,
                    $iterKey,
                    PromiseInterface $aggregatePromise
                ) {
                    $this->log(sprintf('Completed copy of \'%s\'',$iterKey));
                },
                // Invoke this function for each failed transfer
                'rejected' => function (
                    AwsException $reason,
                    $iterKey,
                    PromiseInterface $aggregatePromise
                ) {
                    $errors[] = $reason;
                    $this->logExceptionOrContinue($reason);
                },
            ]);

            // Initiate the pool transfers adn waits it ends
            $pool->promise()->wait();

            if (count($errors) === 0) {
                $this->log(sprintf('Copy in batch from \'%s\' to \'%s\' was succeded without errors', $params['source_bucket'], $params['target_bucket']));

                return true;
            }

            $this->log(sprintf('Something went wrong during copying in batch from \'%s\' to \'%s\'', $params['source_bucket'], $params['target_bucket']), 'warning');

            return false;
        } catch (\Exception $e) {
            $this->logExceptionOrContinue($e);
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
