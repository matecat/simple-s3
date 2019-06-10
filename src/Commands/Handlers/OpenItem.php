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

use Psr\Http\Message\UriInterface;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Helpers\File;

class OpenItem extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return mixed|UriInterface
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        try {
            $url = $this->client->getPublicItemLink(['bucket' => $bucketName, 'key' => $keyName]);
            $content = File::loadFile($url, $this->client->hasSslVerify());

            if (false === $content) {
                $this->loggerWrapper->log(sprintf('Something went wrong during getting content of \'%s\' item from \'%s\' bucket', $keyName, $bucketName), 'warning');

                return null;
            }

            $this->loggerWrapper->log(sprintf('Content from \'%s\' item was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $content;
        } catch (\Exception $e) {
            $this->loggerWrapper->logExceptionAndContinue($e);
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
