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

class HasBucket extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        return $this->client->getConn()->doesBucketExist($bucketName);
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return isset($params['bucket']);
    }
}
