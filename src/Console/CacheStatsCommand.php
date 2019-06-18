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
     * @var Client
     */
    private $s3Client;

    /**
     * CacheStatsCommand constructor.
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
            ->setName('ss3:cache:stats')
            ->setDescription('Get the cache statistics.')
            ->setHelp('This command displays the cache statistics.')
            ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->s3Client->hasCache()) {
            throw new \Exception('Cache in not enabled. You have to enable caching to use this command');
        }

        $tableFeed = [];

        if (false === is_string($input->getArgument('bucket'))) {
            throw new \InvalidArgumentException('Provided bucket name was not a string');
        }

        $bucket = $input->getArgument('bucket');

        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $bucket
        ]);

        foreach ($items as $key) {
            $inCache = $this->s3Client->getCache()->search($bucket, $key);
            if (count($inCache) > 0) {
                $index = $this->getDirName($inCache[0]);

                $tableFeed[$index] = [
                    'count' => count($inCache),
                    'files' => [
                        $key => $this->s3Client->getConn()->doesObjectExist($bucket, $key)
                    ],
                    'ttl' => $this->s3Client->getCache()->ttl($bucket, $key),
                ];
            }
        }

        $table = new Table($output);
        $table->setHeaders(['prefix', 'count', 'ttl', 'files', 'align']);

        foreach ($tableFeed as $prefix => $data) {
            $count = (int)$data['count'];



            $files = implode(PHP_EOL, array_keys($data['files']));
            $enabled = implode(PHP_EOL, $data['files']);
            $enabled = str_replace('1', '<fg=green>✓</>', $enabled);
            $enabled = str_replace('0', '<fg=red>✗</>', $enabled);

            $table->addRow([
                $prefix, $count, $data['ttl'], $files, $enabled
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
