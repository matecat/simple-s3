<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Exception;
use SimpleS3\Commands\CommandHandler;

class SetBucketLifecycleConfiguration extends CommandHandler
{
    /**
     * Set bucket lifecycle configuration
     *
     * For a complete reference of bucket lifecycle rules:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-lifecycle-configuration.html
     *
     * @param array $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $rules = $params['rules'];

        try {
            $settings = [
                'Bucket' => $bucketName,
                'LifecycleConfiguration' => [
                    'Rules' => $rules
                ]
            ];

            $config = $this->client->getConn()->putBucketLifecycleConfiguration($settings);

            if (($config instanceof ResultInterface) and $config['@metadata']['statusCode'] === 200) {
                $this->loggerWrapper->log(sprintf('Lifecycle was successfully set for bucket \'%s\'', $bucketName));

                return true;
            }

            $this->loggerWrapper->log(sprintf('Something went wrong during setting of lifecycle of \'%s\' bucket', $bucketName), 'warning');

            return false;
        } catch (\Exception $exception) {
            $this->loggerWrapper->logExceptionOrContinue($exception);
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
            isset($params['rules'])
        );
    }
}
