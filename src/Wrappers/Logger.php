<?php

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
     * Log the exception or continue with default behaviour
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function logExceptionOrContinue(\Exception $exception)
    {
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->error($exception->getMessage());
        } else {
            throw $exception; // continue with the default behaviour
        }
    }
}
