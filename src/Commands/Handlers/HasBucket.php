<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class HasBucket extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        return $this->client->getConn()->doesBucketExist($bucketName);
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
