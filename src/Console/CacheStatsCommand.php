<?php

namespace SimpleS3\Console;

use SimpleS3\Client;
use SimpleS3\Helpers\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheStatsCommand extends Command
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
            ->setName('ss3:cache:stats')
            ->setDescription('Get the cache statistics.')
            ->setHelp('This command displays the cache statistics.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableFeed = [];
        $bucket = $input->getArgument('bucket');

        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $bucket
        ]);

        foreach ($items as $key){
            $inCache = $this->s3Client->getCache()->search($bucket, $key);
            if(count($inCache) > 0){
                $tableFeed[$this->getDirName($inCache[0])]['ttl'] = $this->s3Client->getCache()->ttl($bucket, $key);
                $tableFeed[$this->getDirName($inCache[0])]['count' ] = count($inCache);
                $tableFeed[$this->getDirName($inCache[0])]['files'][$key] = $this->s3Client->getConn()->doesObjectExist($bucket, $key);
            }
        }

        $table = new Table($output);
        $table->setHeaders(['prefix', 'count', 'ttl', 'files', 'align']);

        foreach ($tableFeed as $prefix => $data){
            $files = implode(PHP_EOL, array_keys($data['files']));
            $enabled = implode(PHP_EOL, $data['files']);
            $enabled = str_replace(1, '<fg=green>✓</>', $enabled);
            $enabled = str_replace(0, '<fg=red>✗</>', $enabled);

            $table->addRow([
                $prefix, $data['count'], $data['ttl'], $files, $enabled
            ]);
        }
        $table->render();
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