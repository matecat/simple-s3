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

use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class CopyItem extends CommandHandler
{
    /**
     * Copy an item from a bucket to another one.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/copy-object.html?highlight=copy
     *
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $targetBucketName = $params['target_bucket'];
        $targetKeyname = $params['target'];
        $sourceBucket = $params['source_bucket'];
        $sourceKeyname = $params['source'];

        $this->client->createBucketIfItDoesNotExist(['bucket' => $targetBucketName]);

        if ($this->client->hasEncoder()) {
            $targetKeyname = $this->client->getEncoder()->encode($targetKeyname);
            $sourceKeyname = $this->client->getEncoder()->encode($sourceKeyname);
        }

        $copySource = trim($this->getCopySource($sourceBucket, $sourceKeyname));

        try {
            $config = [
                'Bucket' => $targetBucketName,
                'Key'    => $targetKeyname,
                'CopySource' => $copySource,
            ];

            if ($this->client->isBucketVersioned(['bucket' => $sourceBucket])) {
                $version = $this->client->getCurrentItemVersion(['bucket' => $sourceBucket, 'key' => $params['source']]);
                $config['CopySource'] = $copySource . '?versionId='.$version;
            }

            $copied = $this->client->getConn()->copyObject($config);

            if (($copied instanceof ResultInterface) and $copied['@metadata']['statusCode'] === 200) {
                if (null !== $this->commandHandlerLogger) {
                    $this->commandHandlerLogger->log($this, sprintf('File \'%s/%s\' was successfully copied to \'%s/%s\'', $sourceBucket, $sourceKeyname, $targetBucketName, $targetKeyname));
                }

                if ($this->client->hasCache()) {
                    $this->client->getCache()->set($targetBucketName, $targetKeyname, '');
                }

                return true;
            }

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong in copying file \'%s/%s\'', $sourceBucket, $sourceKeyname), 'warning');
            }

            return false;
        } catch (S3Exception $exception) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($exception);
            }

            throw $exception;
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
            isset($params['target_bucket']) and
            isset($params['target']) and
            isset($params['source_bucket']) and
            isset($params['source'])
        );
    }

    /**
     * Returns an urlencoded string for PUT requests
     *
     * @param string $sourceBucket
     * @param string $sourceKeyname
     *
     * @return string
     */
    protected function getCopySource($sourceBucket, $sourceKeyname)
    {
        if ($this->client->hasEncoder()) {
            return $sourceBucket . $this->client->getPrefixSeparator() . $sourceKeyname;
        }

        $encoded = [];

        foreach (explode($this->client->getPrefixSeparator(), $sourceKeyname) as $word) {
            $encoded[] = urlencode($word);
        }

        return $sourceBucket . $this->client->getPrefixSeparator() . implode($this->client->getPrefixSeparator(), $encoded);
    }
}
