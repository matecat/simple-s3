<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class GetBucketSize extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $size = 0;

        foreach ($this->client->getItemsInABucket(['bucket' => $bucketName, 'hydrate' => true]) as $file) {
            $size += $file['@metadata']['headers']['content-length'];
        }

        $this->log(sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));

        return $size;
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
