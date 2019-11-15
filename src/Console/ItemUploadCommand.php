<?php

namespace Matecat\SimpleS3\Console;

use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ItemUploadCommand extends Command
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
            ->setName('ss3:item:upload')
            ->setDescription('Upload an object into a bucket.')
            ->setHelp('This command allows you to upload an object onto a S3 bucket.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
            ->addArgument('key', InputArgument::REQUIRED, 'The desired keyname')
            ->addArgument('src', InputArgument::REQUIRED, 'The source file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bucket = $input->getArgument('bucket');
        $key = $input->getArgument('key');
        $src = $input->getArgument('src');
        $io = new SymfonyStyle($input, $output);

        try {
            if (true === $this->s3Client->uploadItem(['bucket' => $bucket, 'key' => $key, 'source' => $src])) {
                $io->success('The item was successfully uploaded');
            } else {
                $io->error('There was an error in the upload of the item');
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
