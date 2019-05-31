<?php

namespace SimpleS3\Commands\Handlers;

use Psr\Http\Message\UriInterface;
use SimpleS3\Commands\CommandHandler;

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
            $content = file_get_contents($url, false, stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => $this->client->hasSslVerify(),
                        'verify_peer_name' => $this->client->hasSslVerify()
                    ]
                ]
            ));

            if (false === $content) {
                $this->log(sprintf('Something went wrong during getting content of \'%s\' item from \'%s\' bucket', $keyName, $bucketName), 'warning');

                return null;
            }

            $this->log(sprintf('Content from \'%s\' item was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $content;
        } catch (\Exception $e) {
            $this->logExceptionOrContinue($e);
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
