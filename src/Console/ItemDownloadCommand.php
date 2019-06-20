<?php

namespace SimpleS3\Console;

use SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ItemDownloadCommand extends Command
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
            ->setName('ss3:item:download')
            ->setDescription('Download an object from a bucket.')
            ->setHelp('This command allows you to download an object from a S3 bucket.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
            ->addArgument('key', InputArgument::REQUIRED, 'The desired keyname')
            ->addArgument('save_as', InputArgument::OPTIONAL, 'How to save the file on your filesystem')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bucket = $input->getArgument('bucket');
        $key = $input->getArgument('key');
        $saveAs = $input->getArgument('save_as');
        $io = new SymfonyStyle($input, $output);

        try {
            if(true === $this->s3Client->downloadItem([
                'bucket' => $bucket,
                'key' => $key,
                'save_as' => $saveAs
            ])){
                $io->success('The item was successfully downloaded into ['.$saveAs.']');
            } else {
                $io->error('There was an error in the download of the item');
            }
        } catch (\Exception $e){
            $io->error($e->getMessage());
        }
    }
}
