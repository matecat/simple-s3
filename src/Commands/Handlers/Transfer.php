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
use Matecat\SimpleS3\Commands\CommandHandler;
use RuntimeException;

class Transfer extends CommandHandler
{
    /**
     * @param array<string, mixed> $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): bool
    {
        $dest    = $params[ 'dest' ];
        $source  = $params[ 'source' ];
        $options = (isset($params[ 'options' ])) ? $params[ 'options' ] : [];

        return $this->transfer($dest, $source, $options);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return (
                isset($params[ 'dest' ]) and
                isset($params[ 'source' ])
        );
    }

    /**
     * @param string $dest
     * @param string               $source
     * @param array<string, mixed> $options
     *
     * @return bool
     * @throws Exception
     */
    private function transfer(string $dest, string $source, array $options = []): bool
    {
        try {
            $manager = new \Aws\S3\Transfer($this->client->getConn(), $source, $dest, $options);
            $manager->transfer();

            $this->commandHandlerLogger?->log($this, sprintf('Files were successfully transfered from \'%s\' to \'%s\'', $source, $dest));

            return true;
        } catch (RuntimeException $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }
}
