<?php

namespace Matecat\SimpleS3\Console;

use Exception;
use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
class FolderCopyCommand extends Command
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
                ->setName('ss3:folder:copy')
                ->setDescription('Copy the items from a folder to another one.')
                ->setHelp('This command allows you to copy items from a folder to another one.')
                ->addArgument('source_bucket', InputArgument::REQUIRED, 'The source bucket')
                ->addArgument('source_folder', InputArgument::REQUIRED, 'The source folder')
                ->addArgument('target_bucket', InputArgument::REQUIRED, 'The target bucket')
                ->addArgument('target_folder', InputArgument::REQUIRED, 'The target folder');
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceBucket = $input->getArgument('source_bucket');
        $sourceFolder = $input->getArgument('source_folder');
        $targetBucket = $input->getArgument('target_bucket');
        $targetFolder = $input->getArgument('target_folder');
        $io           = new SymfonyStyle($input, $output);

        try {
            if (true === $this->s3Client->copyFolder([
                            'source_bucket' => $sourceBucket,
                            'source_folder' => $sourceFolder,
                            'target_bucket' => $targetBucket,
                            'target_folder' => $targetFolder,
                    ])) {
                $io->success('The items were successfully copied');

                return 0;
            } else {
                $io->error('There was an error during copying the items');

                return 1;
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }
}
