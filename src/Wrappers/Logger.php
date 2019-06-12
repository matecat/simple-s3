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

namespace SimpleS3\Wrappers;

use SimpleS3\Client;
use SimpleS3\Commands\CommandHandler;

class Logger
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Logger constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param CommandHandler $commandHandler
     * @param string $message
     * @param string $level
     */
    public function log(CommandHandler $commandHandler, $message, $level = 'info')
    {
        if ($this->client->hasLogger()) {
            $msg = '['.get_class($commandHandler).'] ' . $message;

            $this->client->getLogger()->{$level}($msg);
        }
    }

    /**
     * If the client has a logger set, this method log the exception and return false
     *
     * @param \Exception $exception
     *
     * @return bool
     * @throws \Exception
     */
    public function logExceptionAndContinue( \Exception $exception)
    {
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->error($exception->getMessage());

            return false;
        }

        throw $exception; // continue with the default behaviour
    }
}
