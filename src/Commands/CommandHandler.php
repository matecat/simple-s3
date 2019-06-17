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

namespace SimpleS3\Commands;

use SimpleS3\Client;
use SimpleS3\Components\Logger\CommandHandlerLogger;

abstract class CommandHandler implements CommandHandlerInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var CommandHandlerLogger
     */
    protected $commandHandlerLogger;

    /**
     * CommandHandler constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        if($this->client->hasLogger()){
            $this->commandHandlerLogger = new CommandHandlerLogger($client->getLogger());
        }
    }
}
