<?php

namespace SimpleS3\Commands\Handlers;

use Aws\CommandInterface;
use Aws\Exception\MultipartUploadException;
use Aws\ResultInterface;
use Aws\S3\MultipartUploader;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Exceptions\InvalidS3NameException;
use SimpleS3\Helpers\File;
use SimpleS3\Validators\S3ObjectSafeNameValidator;
use SimpleS3\Validators\S3StorageClassNameValidator;

class UploadItem extends CommandHandler
{
    const MAX_FILESIZE = 6291456; // 6 Mb

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
        $source = $params['source'];

        if(isset($params['bucket_check']) and true === $params['bucket_check']){
            $this->client->createBucketIfItDoesNotExist(['bucket' => $bucketName]);
        }

        if (false === S3ObjectSafeNameValidator::isValid($keyName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyName)).']', $keyName));
        }

        if ((isset($params['storage']) and false === S3StorageClassNameValidator::isValid($params['storage']))) {
            throw new \InvalidArgumentException(S3StorageClassNameValidator::validate($params['storage'])[0]);
        }

        if(File::getSize($source) > self::MAX_FILESIZE){
            return $this->multipartUpload($bucketName, $keyName, $source, $params);
        }

        return (new UploadItemFromBody($this->client))->handle([
            'bucket' => $bucketName,
            'key' =>$keyName,
            'body' => File::open($source),
            'storage' => (isset($params['storage'])) ? $params['storage'] : null
        ]);
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
            isset($params['source'])
        );
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param string $source
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    private function multipartUpload($bucketName, $keyName, $source, $params = [])
    {
        $uploader = new MultipartUploader(
            $this->client->getConn(),
            $source,
            [
                'bucket' => $bucketName,
                'key'    => $keyName,
                'before_initiate' => function (CommandInterface $command) use ($source, $params) {
                    if (extension_loaded('fileinfo')) {
                        $command['ContentType'] = File::getMimeType($source);
                    }

                    if ((isset($params['storage']))) {
                        $command['StorageClass'] = $params['storage'];
                    }
                }
            ]
        );

        try {
            $upload = $uploader->upload();

            if (($upload instanceof ResultInterface) and $upload['@metadata']['statusCode'] === 200) {
                $this->log(sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyName, $bucketName));

                return true;
            }

            $this->log(sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyName, $bucketName), 'warning');

            return false;
        } catch (MultipartUploadException $e) {
            $this->logExceptionOrContinue($e);
        }
    }
}
