<?php

namespace Matecat\SimpleS3\Console;

use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheFlushCommand extends Command
{
    /**
     * @var Client
     */
    private $s3Client;

    /**
     * CacheFlushCommand constructor.
     *
     * @param Client $s3Client
     * @param null   $name
     */
    public function __construct(Client $s3Client, $name = null)
    {
        parent::__construct($name);

        $this->s3Client = $s3Client;
    }

    protected function configure()
    {
        $this
            ->setName('ss3:cache:flush')
            ->setDescription('Flush all data stored in cache.')
            ->setHelp('This command flushes all data stored in cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->s3Client->hasCache()) {
            throw new \Exception('Cache in not enabled. You have to enable caching to use this command');
        }

        if (true === $this->s3Client->getCache()->flushAll()) {
            $output->writeln('<fg=green>Cache was successful flushed.</>');
        } else {
            $output->writeln('<fg=red>Error during cache flushing.</>');
        }
    }
}
