<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class DeleteBucket extends CommandHandler
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

        if ($this->client->hasBucket(['bucket' => $bucketName])) {
            try {
                $delete = $this->client->getConn()->deleteBucket([
                    'Bucket' => $bucketName
                ]);

                if (($delete instanceof ResultInterface) and $delete['@metadata']['statusCode'] === 204) {
                    $this->log(sprintf('Bucket \'%s\' was successfully deleted', $bucketName));
                    $this->removeFromCache($bucketName);

                    return true;
                }

                $this->log(sprintf('Something went wrong in deleting bucket \'%s\'', $bucketName), 'warning');

                return false;
            } catch (S3Exception $e) {
                $this->logExceptionOrContinue($e);
            }
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (isset($params['bucket']));
    }
}
