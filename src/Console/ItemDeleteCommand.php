<?php

namespace Matecat\SimpleS3\Console;

use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ItemDeleteCommand extends Command
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
            ->setName('ss3:item:delete')
            ->setDescription('Deletes an object from a bucket.')
            ->setHelp('This command allows you to delete an object from a S3 bucket.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
            ->addArgument('key', InputArgument::REQUIRED, 'The desired keyname')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bucket = $input->getArgument('bucket');
        $key = $input->getArgument('key');
        $io = new SymfonyStyle($input, $output);

        try {
            if (true === $this->s3Client->deleteItem(['bucket' => $bucket, 'key' => $key])) {
                $io->success('The item was successfully cleared');
            } else {
                $io->error('There was an error in deleting the item');
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
