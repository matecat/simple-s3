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

use Matecat\SimpleS3\Commands\CommandHandler;
use Matecat\SimpleS3\Helpers\File;

class GetCurrentItemVersion extends CommandHandler
{
    /**
     * Get the current version of an item.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-versioning.html?highlight=versioning%20bucket
     *
     * @param array $params
     *
     * @return null|string
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $this->getFilenameTrimmer()->trim($params['key']);

        $fileInfo = File::getPathInfo($keyName);
        $prefix = $fileInfo['dirname'] . $this->client->getPrefixSeparator();

        $results = $this->client->getConn()->listObjectVersions([
            'Bucket' => $bucketName,
            'Prefix' => $prefix
        ]);

        if (false === isset($results['Versions'])) {
            return null;
        }

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        foreach ($results['Versions'] as $result) {
            if (true === $result['IsLatest'] and $keyName === $result['Key']) {
                return $result['VersionId'];
            }
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
            isset($params['key'])
        );
    }
}
