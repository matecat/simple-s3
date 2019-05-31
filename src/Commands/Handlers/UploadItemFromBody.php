<?php

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Exceptions\InvalidS3NameException;
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

        $this->client->createBucketIfItDoesNotExist(['bucket' => $bucketName]);

        if (false === S3ObjectSafeNameValidator::isValid($keyName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyName)).']', $keyName));
        }

        if ((isset($params['storage']) and false === S3StorageClassNameValidator::isValid($params['storage']))) {
            throw new \InvalidArgumentException(S3StorageClassNameValidator::validate($params['storage'])[0]);
        }

        try {
            $config = [
                'Bucket' => $bucketName,
                'Key'    => $keyName,
                'Body'   => $body
            ];

            if (isset($params['storage'])) {
                $config['StorageClass'] = $params['storage'];
            }

            $result = $this->client->getConn()->putObject($config);

            if (($result instanceof ResultInterface) and $result['@metadata']['statusCode'] === 200) {
                $this->log(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyName, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyName, $bucketName), 'warning');

            return false;
        } catch (\InvalidArgumentException $e) {
            $this->logExceptionOrContinue($e);
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
            isset($params['key']) and
            isset($params['body'])
        );
    }
}
