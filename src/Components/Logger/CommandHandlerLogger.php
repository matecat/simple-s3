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

namespace Matecat\SimpleS3\Components\Logger;

use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;
use Psr\Log\LoggerInterface;

class CommandHandlerLogger
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Logger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param CommandHandler $commandHandler
     * @param string         $message
     * @param string         $level
     */
    public function log(CommandHandler $commandHandler, string $message, string $level = 'info'): void
    {
        $msg = '[' . get_class($commandHandler) . '] ' . $message;
        $this->logger->{$level}($msg);
    }

    /**
     * @param Exception $exception
     *
     * @return bool
     */
    public function logExceptionAndReturnFalse(Exception $exception): bool
    {
        $this->logger->error($exception->getMessage());

        return false;
    }
}
