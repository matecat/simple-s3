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

class Transfer extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $dest = $params['dest'];
        $source = $params['source'];
        $options = (isset($params['options'])) ? $params['options'] : [];

        return $this->transfer($dest, $source, $options);
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (
            isset($params['dest']) and
            isset($params['source'])
        );
    }

    /**
     * @param string $dest
     * @param string $source
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    private function transfer($dest, $source, $options = [])
    {
        try {
            $manager = new \Aws\S3\Transfer($this->client->getConn(), $source, $dest, $options);
            $manager->transfer();

            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->log($this, sprintf('Files were successfully transfered from \'%s\' to \'%s\'', $source, $dest));
            }

            return true;
        } catch (\RuntimeException $e) {
            if (null !== $this->commandHandlerLogger) {
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }
}
