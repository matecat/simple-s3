<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class ClearBucket extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $errors = [];

        if ($this->client->hasBucket(['bucket' => $bucketName])) {
            $results = $this->client->getConn()->getPaginator('ListObjects', [
                'Bucket' => $bucketName
            ]);

            foreach ($results as $result) {
                foreach ($result['Contents'] as $object) {
                    if (false === $delete = $this->client->deleteItem(['bucket' => $bucketName, 'key' => $object['Key']])) {
                        $errors[] = $delete;
                    }
                }
            }
        }

        if (count($errors) === 0) {
            $this->loggerWrapper->log(sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

            return true;
        }

        $this->loggerWrapper->log(sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');

        return false;
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
