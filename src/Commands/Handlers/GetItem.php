<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class GetItem extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return ResultInterface|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        try {
            $file = $this->client->getConn()->getObject([
                    'Bucket' => $bucketName,
                    'Key'    => $keyName
            ]);

            $this->log(sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $file;
        } catch (S3Exception $e) {
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
                isset($params['bucket']) and
                isset($params['key'])
        );
    }
}
