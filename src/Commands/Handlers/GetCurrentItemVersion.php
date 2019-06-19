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

use SimpleS3\Commands\CommandHandler;

class GetCurrentItemVersion extends CommandHandler
{
    /**
     * Get the current version of an item.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-versioning.html?highlight=versioning%20bucket
     *
     * @param array $params
     *
     * @return string
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        $results = $this->client->getConn()->listObjectVersions([
            'Bucket' => $bucketName,
            'Key' => $keyName
        ]);

        if(false === isset($results['Versions'])){
            return null;
        }

        foreach ($results['Versions'] as $result) {
            if(true === $result['IsLatest']){
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
