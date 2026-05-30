<?php

namespace Matecat\SimpleS3\Console;

use Exception;
use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class CacheFlushCommand extends Command
{
    /**
     * @var Client
     */
    private Client $s3Client;

    /**
     * CacheFlushCommand constructor.
     *
     * @param Client      $s3Client
     * @param string|null $name
     */
    public function __construct(Client $s3Client, ?string $name = null)
    {
        parent::__construct($name);

        $this->s3Client = $s3Client;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this
                ->setName('ss3:cache:flush')
                ->setDescription('Flush all data stored in cache.')
                ->setHelp('This command flushes all data stored in cache.');
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (false === $this->s3Client->hasCache()) {
            throw new \Exception('Cache in not enabled. You have to enable caching to use this command');
        }

        if (true === $this->s3Client->getCache()->flushAll()) {
            $output->writeln('<fg=green>Cache was successful flushed.</>');

            return 0;
        } else {
            $output->writeln('<fg=red>Error during cache flushing.</>');

            return 1;
        }
    }
}
