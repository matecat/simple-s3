<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class DeleteItem extends CommandHandler
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
        $keyName = $params['key'];

        try {
            $delete = $this->client->getConn()->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => $keyName
            ]);

            if (($delete instanceof ResultInterface) and $delete['DeleteMarker'] === false and $delete['@metadata']['statusCode'] === 204) {
                $this->log(sprintf('File \'%s\' was successfully deleted from \'%s\' bucket', $keyName, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong in deleting file \'%s\' from \'%s\' bucket', $keyName, $bucketName), 'warning');

            return false;
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
