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
use SimpleS3\Components\Encoders\S3ObjectSafeNameEncoder;
use SimpleS3\Helpers\File;

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

        if ($this->client->hasCache() and $this->client->getCache()->has($bucketName, $keyName)) {
            return $this->returnItemFromCache($bucketName, $keyName);
        }

        return $this->returnItemFromS3($bucketName, $keyName);
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
     *
     * @return mixed
     */
    private function returnItemFromCache($bucketName, $keyName)
    {
        if ('' === $this->client->getCache()->get($bucketName, $keyName)) {
            $file = $this->client->getConn()->getObject([
                'Bucket' => $bucketName,
                'Key'    => S3ObjectSafeNameEncoder::encode($keyName)
            ]);

            $this->client->getCache()->set($bucketName, $keyName, $file->toArray());
        }

        return $this->client->getCache()->get($bucketName, $keyName);
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     *
     * @return array
     * @throws \Exception
     */
    private function returnItemFromS3($bucketName, $keyName)
    {
        try {
            $file = $this->client->getConn()->getObject([
                'Bucket' => $bucketName,
                'Key'    => $keyName
            ]);

            if ($this->client->hasCache()) {
                $this->client->getCache()->set($bucketName, $keyName, $file->toArray());
            }

            $this->commandHandlerLogger->log($this, sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $file->toArray();
        } catch (S3Exception $e) {
            $this->commandHandlerLogger->logExceptionAndContinue($e);
        }
    }
}
