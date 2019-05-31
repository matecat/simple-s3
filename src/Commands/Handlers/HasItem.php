<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class HasItem extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        return $this->client->getConn()->doesObjectExist($bucketName, $keyName);
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (
                isset($params['bucket']) and
                isset($params['key'])
        );
    }
}
