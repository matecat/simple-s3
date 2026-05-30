<?php

namespace Matecat\SimpleS3\Console;

use Exception;
use InvalidArgumentException;
use Matecat\SimpleS3\Client;
use Matecat\SimpleS3\Helpers\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheStatsCommand extends Command
{
    /**
     * @var Client
     */
    private Client $s3Client;

    /**
     * CacheStatsCommand constructor.
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
     * @throws ConsoleInvalidArgumentException
     */
    protected function configure(): void
    {
        $this
                ->setName('ss3:cache:stats')
                ->setDescription('Get the cache statistics.')
                ->setHelp('This command displays the cache statistics.')
                ->addArgument('bucket', InputArgument::REQUIRED, 'The name of the bucket')
                ->addArgument('prefix', InputArgument::REQUIRED, 'The prefix in the bucket');
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (false === $this->s3Client->hasCache()) {
            throw new Exception('Cache in not enabled. You have to enable caching to use this command');
        }


        if (false === is_string($input->getArgument('bucket')) or false === is_string($input->getArgument('prefix'))) {
            throw new InvalidArgumentException('Provided bucket or prefix name were not strings');
        }

        $bucket = $input->getArgument('bucket');
        $prefix = $input->getArgument('prefix');

        try {
            $items = $this->s3Client->getItemsInABucket([
                    'bucket' => $bucket,
                    'prefix' => $prefix,
            ]);

            $tableFeed = [];
            foreach ($items as $key) {
                $inCache = $this->s3Client->getCache()->search($bucket, $key);
                if (count($inCache) > 0) {
                    $index = $this->getDirName($inCache[ 0 ]);

                    $files = [];
                    foreach ($inCache as $item) {
                        $files[ $item ] = $this->s3Client->getConn()->doesObjectExist($bucket, $item);
                    }

                    $tableFeed[ $index ] = [
                            'count' => count($inCache),
                            'files' => $files,
                            'ttl'   => $this->s3Client->getCache()->ttl($bucket, $key),
                    ];
                }
            }

            $table = new Table($output);
            $table->setHeaders(['prefix', 'count', 'ttl', 'files', 'align']);

            foreach ($tableFeed as $prefix => $data) {
                $count = (int)$data[ 'count' ];

                $files   = implode(PHP_EOL, array_keys($data[ 'files' ]));
                $enabled = implode(PHP_EOL, $data[ 'files' ]);
                $enabled = str_replace('1', '<fg=green>✓</>', $enabled);
                $enabled = str_replace('0', '<fg=red>✗</>', $enabled);

                $table->addRow([
                        $prefix,
                        $count,
                        $data[ 'ttl' ],
                        $files,
                        $enabled
                ]);
            }
            $table->render();

            return 0;
        } catch (Exception) {
            $io = new SymfonyStyle($input, $output);
            $io->error('No results were found');

            return 1;
        }
    }

    /**
     * @param string $item
     *
     * @return string
     */
    private function getDirName(string $item): string
    {
        if (File::endsWith($item, $this->s3Client->getPrefixSeparator())) {
            return $item;
        }

        $fileInfo = File::getPathInfo($item);

        return $fileInfo[ 'dirname' ] . $this->s3Client->getPrefixSeparator();
    }
}
