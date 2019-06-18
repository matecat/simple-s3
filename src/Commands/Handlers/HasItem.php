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
use SimpleS3\Components\Encoders\UrlEncoder;

class HasItem extends CommandHandler
{
    /**
     * Check if a item already exists.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/head-object.html
     *
     * @param array $params
     *
     * @return bool
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        if ($this->client->hasCache() and $this->client->getCache()->has($bucketName, $keyName)) {
            return true;
        }

        return $this->client->getConn()->doesObjectExist($bucketName, $keyName);
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
