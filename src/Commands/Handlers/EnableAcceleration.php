<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Exceptions\InvalidS3NameException;
use SimpleS3\Validators\S3BucketNameValidator;

class EnableAcceleration extends CommandHandler
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

        try {
            $accelerate = $this->client->getConn()->putBucketAccelerateConfiguration(
                [
                    'AccelerateConfiguration' => [
                        'Status' => 'Enabled',
                    ],
                    'Bucket' => $bucketName,
                ]
            );

            if (($accelerate instanceof ResultInterface) and $accelerate['@metadata']['statusCode'] === 200) {
                $this->log(sprintf('Bucket \'%s\' was successfully set to transfer accelerated mode', $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during setting of bucket \'%s\' to transfer accelerated mode', $bucketName), 'warning');

            return false;
        } catch (\Exception $exception) {
            $this->logExceptionOrContinue($exception);
        }
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
