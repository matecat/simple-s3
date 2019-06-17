<?php
/**
 *  This file is part of the Simple S3 package.
 *
 * (c) Mauro Cassani<https://github.com/mauretto78>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Exception;
use SimpleS3\Commands\CommandHandler;

class SetBucketLifecycleConfiguration extends CommandHandler
{
    /**
     * Set bucket lifecycle configuration.
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
                if(null !== $this->commandHandlerLogger){
                    $this->commandHandlerLogger->log($this, sprintf('Lifecycle was successfully set for bucket \'%s\'', $bucketName));
                }

                return true;
            }

            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during setting of lifecycle of \'%s\' bucket', $bucketName), 'warning');
            }

            return false;
        } catch (\Exception $exception) {
            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->logExceptionAndReturnFalse($exception);
            }

            throw $e;
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
