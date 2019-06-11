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
use SimpleS3\Exceptions\InvalidS3NameException;
use SimpleS3\Helpers\File;
use SimpleS3\Validators\S3ObjectSafeNameValidator;
use SimpleS3\Validators\S3StorageClassNameValidator;

class UploadItemFromBody extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];
        $body = $params['body'];

        if (isset($params['bucket_check']) and true === $params['bucket_check']) {
            $this->client->createBucketIfItDoesNotExist(['bucket' => $bucketName]);
        }

        if (false === S3ObjectSafeNameValidator::isValid($keyName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyName)).']', $keyName));
        }

        if ((isset($params['storage']) and false === S3StorageClassNameValidator::isValid($params['storage']))) {
            throw new \InvalidArgumentException(S3StorageClassNameValidator::validate($params['storage'])[0]);
        }

        return $this->upload($bucketName, $keyName, $body, (isset($params['storage'])) ? $params['storage'] : null);
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
            isset($params['key']) and
            isset($params['body'])
        );
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param string $body
     * @param null $storage
     *
     * @return bool
     * @throws \Exception
     */
    private function upload($bucketName, $keyName, $body, $storage = null)
    {
        try {
            $config = [
                'Bucket' => $bucketName,
                'Key'    => $keyName,
                'Body'   => $body
            ];

            if (null != $storage) {
                $config['StorageClass'] = $storage;
            }

            $result = $this->client->getConn()->putObject($config);

            if (($result instanceof ResultInterface) and $result['@metadata']['statusCode'] === 200) {
                $this->loggerWrapper->log(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyName, $bucketName));

                if (null === $storage and $this->client->hasLogger()) {
                    $this->client->getCache()->set($bucketName, $keyName, $result);
                }

                return true;
            }

            $this->loggerWrapper->log(sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyName, $bucketName), 'warning');

            return false;
        } catch (\InvalidArgumentException $e) {
            $this->loggerWrapper->logExceptionAndContinue($e);
        }
    }
}
