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

use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Helpers\File;

class HasFolder extends CommandHandler
{
    /**
     * Check if a folder already exists.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/head-object.html
     *
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $prefix = $params['prefix'];

        if ($this->client->hasEncoder()) {
            $prefix = $this->client->getEncoder()->encode($prefix);
        }

        if (false === File::endsWith($prefix, $this->client->getPrefixSeparator())) {
            $prefix .= $this->client->getPrefixSeparator();
        }

        if ($this->client->hasCache() and $this->client->getCache()->has($bucketName, $prefix)) {
            return true;
        }

        return $this->returnItemFromS3($bucketName, $prefix);
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
            isset($params['prefix'])
        );
    }

    /**
     * @param string $bucketName
     * @param string $prefix
     *
     * @return bool
     * @throws \Exception
     */
    private function returnItemFromS3($bucketName, $prefix)
    {
        $command = $this->client->getConn()->getCommand(
            'listObjects',
            [
                'Bucket' => $bucketName,
                'Prefix' => $prefix,
                'MaxKeys' => 1,
            ]
        );
        try {
            $result = $this->client->getConn()->execute($command);

            return $result['Contents'] or $result['CommonPrefixes'];
        } catch (S3Exception $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }
}
