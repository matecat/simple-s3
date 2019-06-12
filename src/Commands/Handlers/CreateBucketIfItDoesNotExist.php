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

class CreateBucketIfItDoesNotExist extends CommandHandler
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

        if (false === S3BucketNameValidator::isValid($bucketName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid bucket name. ['.implode(', ', S3BucketNameValidator::validate($bucketName)).']', $bucketName));
        }

        if (false === $this->client->hasBucket(['bucket' => $bucketName])) {
            try {
                $bucket = $this->client->getConn()->createBucket([
                    'Bucket' => $bucketName
                ]);

                if (isset($params['rules']) and count($params['rules']) > 0) {
                    $this->client->setBucketLifecycleConfiguration(['bucket' => $bucketName, 'rules' => $params['rules']]);
                }

                if (isset($params['accelerate']) and true === $params['accelerate']) {
                    $this->client->enableAcceleration(['bucket' => $bucketName]);
                }

                if (($bucket instanceof ResultInterface) and $bucket['@metadata']['statusCode'] === 200) {
                    $this->loggerWrapper->log(sprintf('Bucket \'%s\' was successfully created', $bucketName));

                    return true;
                }

                $this->loggerWrapper->log(sprintf('Something went wrong during creation of bucket \'%s\'', $bucketName), 'warning');

                return false;
            } catch (S3Exception $e) {
                $this->loggerWrapper->logExceptionAndContinue($e);
            }
        }

        $this->loggerWrapper->log(sprintf('Bucket \'%s\' already exists', $bucketName), 'warning');

        return false;
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
