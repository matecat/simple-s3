<?php

namespace Matecat\SimpleS3\Console;

use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ItemCopyCommand extends Command
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
            ->setName('ss3:item:copy')
            ->setDescription('Copy an object from a bucket to another one.')
            ->setHelp('This command allows you to copy an object from a S3 bucket to another one.')
            ->addArgument('source_bucket', InputArgument::REQUIRED, 'The source bucket')
            ->addArgument('source_key', InputArgument::REQUIRED, 'The source keyname')
            ->addArgument('target_bucket', InputArgument::REQUIRED, 'The target bucket')
            ->addArgument('target_key', InputArgument::REQUIRED, 'The target keyname')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceBucket = $input->getArgument('source_bucket');
        $sourceKey = $input->getArgument('source_key');
        $targetBucket = $input->getArgument('target_bucket');
        $targetKey = $input->getArgument('target_key');
        $io = new SymfonyStyle($input, $output);

        try {
            if (true === $this->s3Client->copyItem([
                'source_bucket' => $sourceBucket,
                'source' => $sourceKey,
                'target_bucket' => $targetBucket,
                'target' => $targetKey,
            ])) {
                $io->success('The item was successfully copied');
            } else {
                $io->error('There was an error during copying the item');
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
