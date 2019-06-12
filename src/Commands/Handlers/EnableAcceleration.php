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
                $this->loggerWrapper->log($this, sprintf('Bucket \'%s\' was successfully set to transfer accelerated mode', $bucketName));

                return true;
            }

            $this->loggerWrapper->log($this, sprintf('Something went wrong during setting of bucket \'%s\' to transfer accelerated mode', $bucketName), 'warning');

            return false;
        } catch (\Exception $exception) {
            $this->loggerWrapper->logExceptionAndContinue($exception);
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
