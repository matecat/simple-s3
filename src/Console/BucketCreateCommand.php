<?php

namespace Matecat\SimpleS3\Console;

use Exception;
use Matecat\SimpleS3\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BucketCreateCommand extends Command {
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
    public function __construct( Client $s3Client, ?string $name = null ) {
        parent::__construct( $name );

        $this->s3Client = $s3Client;
    }

    protected function configure(): void {
        $this
                ->setName( 'ss3:bucket:create' )
                ->setDescription( 'Creates a bucket.' )
                ->setHelp( 'This command creates a bucket on S3.' )
                ->addArgument( 'bucket', InputArgument::REQUIRED, 'The name of the bucket' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ): int {
        $bucket = $input->getArgument( 'bucket' );
        $io     = new SymfonyStyle( $input, $output );

        try {
            if ( true === $this->s3Client->createBucketIfItDoesNotExist( [ 'bucket' => $bucket ] ) ) {
                $io->success( 'The bucket was successfully created' );

                return 1;
            } else {
                $io->error( 'There was an error in creating bucket' );

                return 0;
            }
        } catch ( Exception $e ) {
            $io->error( $e->getMessage() );

            return 1;
        }
    }
}
