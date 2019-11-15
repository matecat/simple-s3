<?php

namespace Matecat\SimpleS3\Console;

use Aws\CommandInterface;
use Aws\S3\Transfer;
use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BatchTransferCommand extends Command
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
            ->setName('ss3:batch:transfer')
            ->setDescription('Transfer files from/to a bucket.')
            ->setHelp('This command transfer files from/to a bucket on S3. Remember: IT\'S PERMITTED ONLY the transfer from local to remote or vice versa.')
            ->addArgument('src', InputArgument::REQUIRED, 'The source')
            ->addArgument('dest', InputArgument::REQUIRED, 'The destination')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $io = new SymfonyStyle($input, $output);

        $io->title('Starting the file transfer...(may take a while)');

        $from = 'local filesystem';
        $to = 'S3';

        if (strpos($src, 's3://') !== false) {
            $from = 'S3';
            $to = 'local filesystem';
        }

        try {
            $manager = new Transfer($this->s3Client->getConn(), $src, $dest, [
                'before' => function (CommandInterface $command) use ($output, $from, $to) {
                    $output->writeln('Transferring <fg=green>['.$command['Key'].']</> from '. $from .' to ' . $to);
                }
            ]);
            $manager->transfer();

            $output->writeln('');
            $io->success('The files were successfully transfered');
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
