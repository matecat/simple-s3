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

namespace Matecat\SimpleS3\Commands\Handlers;

use Aws\CommandInterface;
use Aws\Exception\MultipartUploadException;
use Aws\ResultInterface;
use Aws\S3\MultipartUploader;
use Matecat\SimpleS3\Commands\CommandHandler;
use Matecat\SimpleS3\Components\Validators\S3ObjectSafeNameValidator;
use Matecat\SimpleS3\Components\Validators\S3StorageClassNameValidator;
use Matecat\SimpleS3\Exceptions\InvalidS3NameException;
use Matecat\SimpleS3\Helpers\File;

class UploadItem extends CommandHandler
{
    const MAX_FILESIZE = 6291456; // 6 Mb

    /**
     * Upload a file to S3.
     * Il filesize is > 6Mb a multipart upload is performed.
     * For a complete reference of put object see:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-object.html?highlight=put
     *
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $this->getFilenameTrimmer()->trim($params['key']);
        $source = $params['source'];

        if (isset($params['bucket_check']) and true === $params['bucket_check']) {
            $this->client->createBucketIfItDoesNotExist(['bucket' => $bucketName]);
        }

        if (false === S3ObjectSafeNameValidator::isValid($keyName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid S3 object name. ['.implode(', ', S3ObjectSafeNameValidator::validate($keyName)).']', $keyName));
        }

        if ((isset($params['storage']) and false === S3StorageClassNameValidator::isValid($params['storage']))) {
            throw new \InvalidArgumentException(S3StorageClassNameValidator::validate($params['storage'])[0]);
        }

        if (File::getSize($source) > self::MAX_FILESIZE) {
            return $this->multipartUpload($bucketName, $keyName, $source, $params);
        }

        return (new UploadItemFromBody($this->client))->handle([
            'bucket' => $bucketName,
            'key' => $keyName,
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
        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        $uploader = new MultipartUploader(
            $this->client->getConn(),
            $source,
            [
                'bucket' => $bucketName,
                'key'    => $keyName,
                'before_initiate' => function (CommandInterface $command) use ($source, $params, $keyName) {
                    if (extension_loaded('fileinfo')) {
                        $command['ContentType'] = File::getMimeType($source);
                    }

                    if ((isset($params['storage']))) {
                        $command['StorageClass'] = $params['storage'];
                    }

                    if ((isset($params['Metadata']))) {
                        $command['Metadata'] = $params['meta'];
                    }

                    $command['Metadata'][ 'original_name'] = File::getBaseName($keyName);
                    $command['MetadataDirective'] =  'REPLACE';
                }
            ]
        );

        try {
            $upload = $uploader->upload();

            if (($upload instanceof ResultInterface) and $upload['@metadata']['statusCode'] === 200) {
                if (null !== $this->commandHandlerLogger) {
                    $this->commandHandlerLogger->log($this, sprintf('File \'%s\' was successfully uploaded in \'%s\' bucket', $keyName, $bucketName));
                }

                return true;
            }

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during upload of file \'%s\' in \'%s\' bucket', $keyName, $bucketName), 'warning');
            }

            // update cache
            if ((!isset($params['storage'])) and $this->client->hasCache()) {
                $version = null;
                if (isset($upload['@metadata']['headers']['x-amz-version-id'])) {
                    $version = $upload['@metadata']['headers']['x-amz-version-id'];
                }

                $this->client->getCache()->set($bucketName, $keyName, '', $version);
            }

            return false;
        } catch (MultipartUploadException $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }
}
