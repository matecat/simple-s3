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
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = 'info')
    {
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->{$level}($message);
        }
    }

    /**
     * Log the exception and continue with default behaviour
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function logExceptionAndContinue( \Exception $exception)
    {
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->error($exception->getMessage());
        }

        throw $exception; // continue with the default behaviour
    }
}
