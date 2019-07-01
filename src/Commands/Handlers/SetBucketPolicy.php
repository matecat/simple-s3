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
use SimpleS3\Commands\CommandHandler;

class SetBucketPolicy extends CommandHandler
{
    /**
     * Set policy for a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-policy.html?highlight=put%20policy
     *
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $policy = $params['policy'];

        $config = [
            'Bucket' => $bucketName,
            'Policy' => $policy,
        ];

        if(isset($params['access'])){
            $config['ConfirmRemoveSelfBucketAccess'] = $params['access'];
        }

        if(isset($params['md5'])){
            $config['ContentMD5'] = $params['md5'];
        }

        try {
            $policy = $this->client->getConn()->putBucketPolicy($config);

            if (($policy instanceof ResultInterface) and $policy['@metadata']['statusCode'] === 204) {
                if (null !== $this->commandHandlerLogger) {
                    $this->commandHandlerLogger->log($this, sprintf('Policy was successfully set for bucket \'%s\'', $bucketName));
                }

                return true;
            }

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong while setting policy of bucket \'%s\'', $bucketName), 'warning');
            }

            return false;
        } catch (\Exception $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
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
            isset($params['policy'])
        );
    }
}
