<?php

namespace SimpleS3\Commands\Handlers;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use SimpleS3\Commands\CommandHandler;

class CopyInBatch extends CommandHandler
{
    /**
     * @param array $params
     *
     * Example:
     * $input = [
     *      'source_bucket' => 'ORIGINAL-BUCKET',
     *      'target_bucket' => 'TARGET-BUCKET',
     *      'files' => [
     *          'source' => [
     *              'keyname-1',
     *              'keyname-2',
     *          ],
     *          'target' => [
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
        $this->client->createBucketIfItDoesNotExist(['bucket' => $params['target_bucket']]);

        $batch = [];
        $errors = [];

        foreach ($params['files']['source'] as $key => $file) {
            $batch[] = $this->client->getConn()->getCommand('CopyObject', [
                    'Bucket'     => $params['target_bucket'],
                    'Key'        => (isset($params['files']['target'][$key])) ? $params['files']['target'][$key] : $file,
                    'CopySource' => $params['source_bucket'] . DIRECTORY_SEPARATOR . $file,
            ]);
        }

        try {
            $results = CommandPool::batch($this->client->getConn(), $batch);
            foreach ($results as $result) {
                if ($result instanceof AwsException) {
                    $errors[] = $result;
                    $this->logExceptionOrContinue($result);
                }
            }

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
            isset($params['target_bucket']) and
            isset($params['files']['source'])
        );
    }
}
