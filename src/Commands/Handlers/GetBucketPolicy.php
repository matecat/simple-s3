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

class GetBucketPolicy extends CommandHandler
{
    /**
     * Get the policy of a bucket.
     *
     * @param array $params
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        $result = $this->client->getConn()->getBucketPolicy([
            'Bucket' => $bucketName,
        ]);

        if (null !== $this->commandHandlerLogger) {
            $this->commandHandlerLogger->log($this, sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));
        }

        return (isset($result['Policy'])) ? json_decode($result['Policy']->getContents(), true) : '';
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
