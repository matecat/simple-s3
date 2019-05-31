<?php

namespace SimpleS3\Commands\Handlers;

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
     * @return mixed|void
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

            $this->client->getConn()->putBucketLifecycleConfiguration($settings);
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
        return (
            isset($params['bucket']) and
            isset($params['rules'])
        );
    }
}
