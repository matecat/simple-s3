<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class DeleteFolder extends CommandHandler
{
    /**
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $prefix = $params['prefix'];

        try {
            $this->client->getConn()->deleteMatchingObjects($bucketName, $prefix);
            $this->loggerWrapper->log(sprintf('Folder \'%s\' was successfully deleted from \'%s\' bucket', $prefix, $bucketName));
            $this->cacheWrapper->removeFromCache($bucketName, $prefix);

            return true;
        } catch (\Exception $e) {
            $this->loggerWrapper->logExceptionOrContinue($e);
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
            isset($params['bucket']) and
            isset($params['prefix'])
        );
    }
}
