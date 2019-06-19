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

class GetItem extends CommandHandler
{
    /**
     * Get the details of an item.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/get-object.html
     *
     * @param array $params
     *
     * @return ResultInterface|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];
        $version = (isset($params['version'])) ? $params['version'] : null;

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        if ($this->client->hasCache() and $this->client->getCache()->has($bucketName, $keyName, $version)) {
            return $this->returnItemFromCache($bucketName, $keyName, $version);
        }

        return $this->returnItemFromS3($bucketName, $keyName, $version);
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
            isset($params['key'])
        );
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param null $version
     *
     * @return mixed
     */
    private function returnItemFromCache($bucketName, $keyName, $version = null)
    {
        if ('' === $this->client->getCache()->get($bucketName, $keyName, $version)) {
            $config = [
                'Bucket' => $bucketName,
                'Key'    => $keyName
            ];

            if(null != $version){
                $config['VersionId'] = $version;
            }

            $file = $this->client->getConn()->getObject($config);
            $this->client->getCache()->set($bucketName, $keyName, $file->toArray(), $version);
        }

        return $this->client->getCache()->get($bucketName, $keyName, $version);
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     * @param null $version
     *
     * @return array
     * @throws \Exception
     */
    private function returnItemFromS3($bucketName, $keyName, $version = null)
    {
        try {
            $config = [
                'Bucket' => $bucketName,
                'Key'    => $keyName
            ];

            if(null != $version){
                $config['VersionId'] = $version;
            }

            $file = $this->client->getConn()->getObject($config);

            if ($this->client->hasCache()) {
                $this->client->getCache()->set($bucketName, $keyName, $file->toArray(), $version);
            }

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyName, $bucketName));
            }

            return $file->toArray();
        } catch (S3Exception $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }
}
