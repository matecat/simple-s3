<?php

namespace SimpleS3\Commands;

use SimpleS3\Client;
use SimpleS3\Wrappers\Cache;
use SimpleS3\Wrappers\Logger;

abstract class CommandHandler implements CommandHandlerInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $loggerWrapper;

    /**
     * @var Cache
     */
    protected $cacheWrapper;

    /**
     * CommandHandlerAbstract constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->loggerWrapper = new Logger($client);
        $this->cacheWrapper = new Cache($client);
    }
}
