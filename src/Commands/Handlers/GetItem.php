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
use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class GetItem extends CommandHandler
{
    /**
     * Get the details of an item.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/get-object.html
     *
     * @param array<string, mixed> $params
     *
     * @return ResultInterface|mixed
     * @throws Exception
     */
    public function handle(array $params = []): mixed
    {
        $bucketName = $params[ 'bucket' ];
        $keyName    = $params[ 'key' ];
        $version    = (isset($params[ 'version' ])) ? $params[ 'version' ] : null;

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        if ($this->client->hasCache() and $this->client->getCache()->has($bucketName, $keyName, $version)) {
            return $this->returnItemFromCache($bucketName, $keyName, $version);
        }

        return $this->returnItemFromS3($bucketName, $keyName, $version);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return (
                isset($params[ 'bucket' ]) and
                isset($params[ 'key' ])
        );
    }

    /**
     * @param string      $bucketName
     * @param string      $keyName
     * @param string|null $version
     *
     * @return mixed
     * @throws Exception
     */
    private function returnItemFromCache(string $bucketName, string $keyName, ?string $version = null): mixed
    {
        $cache = $this->client->getCache();
        if (null === $cache) {
            // Defensive: this private method is only invoked when hasCache() is true.
            return $this->returnItemFromS3($bucketName, $keyName, $version);
        }

        if ('' === $cache->get($bucketName, $keyName, $version)) {
            $config = [
                    'Bucket' => $bucketName,
                    'Key'    => $keyName
            ];

            if (null != $version) {
                $config[ 'VersionId' ] = $version;
            }

            $file = $this->client->getConn()->getObject($config);
            $cache->set($bucketName, $keyName, $file->toArray(), $version);
        }

        return $cache->get($bucketName, $keyName, $version);
    }

    /**
     * @param string      $bucketName
     * @param string      $keyName
     * @param string|null $version
     *
     * @return array<int|string, mixed>
     * @throws Exception
     * @throws S3Exception
     */
    private function returnItemFromS3(string $bucketName, string $keyName, ?string $version = null): array
    {
        try {
            $config = [
                    'Bucket' => $bucketName,
                    'Key'    => $keyName
            ];

            if (null != $version) {
                $config[ 'VersionId' ] = $version;
            }

            $file = $this->client->getConn()->getObject($config);

            if ($this->client->hasCache()) {
                $this->client->getCache()->set($bucketName, $keyName, $file->toArray(), $version);
            }

            $this->commandHandlerLogger?->log($this, sprintf('File \'%s\' was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $file->toArray();
        } catch (S3Exception $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }
}
