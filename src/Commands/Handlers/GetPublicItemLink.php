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

use Exception;
use InvalidArgumentException;
use Matecat\SimpleS3\Commands\CommandHandler;
use Psr\Http\Message\UriInterface;

class GetPublicItemLink extends CommandHandler
{
    /**
     * Get the temporary public link of an item.
     * It return a presigned URL.
     *
     * @param array<string, mixed> $params
     *
     * @return UriInterface
     * @throws Exception
     */
    public function handle(array $params = []): UriInterface
    {
        $bucketName = $params[ 'bucket' ];
        $keyName    = $params[ 'key' ];
        $expires    = (isset($params[ 'expires' ])) ? $params[ 'expires' ] : '+1 hour';

        if ($this->client->hasEncoder()) {
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        try {
            $cmd = $this->client->getConn()->getCommand('GetObject', [
                    'Bucket' => $bucketName,
                    'Key'    => $keyName,
            ]);

            $link = $this->client->getConn()->createPresignedRequest($cmd, $expires)->getUri();

            $this->commandHandlerLogger?->log($this, sprintf('Public link of \'%s\' file was successfully obtained from \'%s\' bucket', $keyName, $bucketName));

            return $link;
        } catch (InvalidArgumentException $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
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
}
