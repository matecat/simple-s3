<?php

namespace SimpleS3\Console;

use SimpleS3\Client;
use SimpleS3\Helpers\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheAlignCommand extends Command
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Client
     */
    private $s3Client;

    public function __construct( Client $s3Client , $name = null  )
    {
        parent::__construct( $name );

        $this->name = $name;
        $this->s3Client = $s3Client;
    }

    protected function configure()
    {
        $this
            ->setName('ss3:cache:align')
            ->setDescription('Align the cache items with S3.')
            ->setHelp('This command allows you to align the elements stored in cache to get aligned with S3.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bucket = $input->getArgument('bucket');
        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $bucket
        ]);

        foreach ($items as $key){
            $inCache = $this->s3Client->getCache()->search($bucket, $key);
            if(count($inCache) > 0){
                if(false === $this->s3Client->getConn()->doesObjectExist($bucket, $key)){
                    $this->s3Client->getCache()->remove($bucket, $key);
                    $output->writeln('<fg=yellow>Item ['.$bucket.']'.$key.' was deleted from cache.</>');
                } else {
                    $output->writeln('<fg=green>Item ['.$bucket.']'.$key.' is aligned with S3.</>');
                }
            } else {
                $output->writeln('<fg=red>No elements in cache.</>');
            }
        }
    }

    /**
     * @param string $item
     *
     * @return string
     */
    private function getDirName($item)
    {
        if (File::endsWithSlash($item)) {
            return $item;
        }

        $fileInfo = File::getPathInfo($item);

        return $fileInfo['dirname'] . DIRECTORY_SEPARATOR;
    }
}