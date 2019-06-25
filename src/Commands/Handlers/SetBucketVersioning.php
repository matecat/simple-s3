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

class SetBucketVersioning extends CommandHandler
{
    /**
     * Enable versioning for a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-versioning.html?highlight=versioning%20bucket
     *
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $config = [
            'Bucket' => $bucketName,
            'VersioningConfiguration' => [
                'MFADelete' => 'Disabled',
                'Status' => 'Enabled',
            ],
        ];

        if (isset($params['md5'])) {
            $config['ContentMD5'] = $params['md5'];
        }

        if (isset($params['mfa'])) {
            $config['MFA'] = $params['mfa'];
        }

        if (isset($params['mfa_delete'])) {
            $config['VersioningConfiguration']['MFADelete'] = $params['mfa_delete'];
        }

        if (isset($params['status'])) {
            $config['VersioningConfiguration']['Status'] = $params['status'];
        }

        try {
            $versioning = $this->client->getConn()->putBucketVersioning($config);

            if (($versioning instanceof ResultInterface) and $versioning['@metadata']['statusCode'] === 200) {
                if (null !== $this->commandHandlerLogger) {
                    $this->commandHandlerLogger->log($this, sprintf('Versioning was successfully enabled for \'%s\' bucket', $bucketName));
                }

                return true;
            }

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during versioning of bucket \'%s\'', $bucketName), 'warning');
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
        return isset($params['bucket']);
    }
}
