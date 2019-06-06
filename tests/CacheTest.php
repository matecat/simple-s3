<?php

use Aws\PsrCacheAdapter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleS3\Client;
use SimpleS3\Commands\Handlers\UploadItem;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheTest extends PHPUnit_Framework_TestCase
{
    const BUCKET_NAME = 'bucket';

    /**
     * @var Client
     */
    private $s3Client;

    /**
     * @throws Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $config = parse_ini_file(__DIR__.'/../config/credentials.ini');
        $this->s3Client    = new Client(
            $config['ACCESS_KEY_ID'],
            $config['SECRET_KEY'],
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
            ]
        );

        // Inject Logger
        $logger = new Logger('channel-test');
        $logger->pushHandler(new StreamHandler(__DIR__.'/../log/test.log', Logger::DEBUG));
        $this->s3Client->addLogger($logger);

        // Inject Cache
        $redis = new Predis\Client();
        $cacheAdapter = new RedisAdapter($redis);
        $this->s3Client->addCache(new PsrCacheAdapter($cacheAdapter));
    }

    /**
     * @test
     */
    public function upload_and_retrieve_from_cache()
    {
        $command = new UploadItem($this->s3Client);

        $command->setInCache(self::BUCKET_NAME, 'file.txt');
        $command->setInCache(self::BUCKET_NAME, 'folder/file.txt');
        $command->setInCache(self::BUCKET_NAME, 'folder/to/file.txt');
        $command->setInCache(self::BUCKET_NAME, 'folder/to/file(2).txt');
        $command->setInCache(self::BUCKET_NAME, 'file(2).txt');
        $command->setInCache(self::BUCKET_NAME, 'another-folder/file.txt');
        $command->setInCache(self::BUCKET_NAME, 'another-folder/to/file.txt');

        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, '.')[0], 'file.txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'folder')[0], 'folder/file.txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'folder/to')[0], 'folder/to/file.txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'folder/to/')[1], 'folder/to/file(2).txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, '.')[1], 'file(2).txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'another-folder')[0], 'another-folder/file.txt');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'another-folder/to')[0], 'another-folder/to/file.txt');
    }

    /**
     * @test
     */
    public function upload_and_retrieve_folders_from_cache()
    {
        $command = new UploadItem($this->s3Client);

        $command->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/');
        $command->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/d595afa5f0a4282342506bc7f1106e6acebff53b!!it-it');
        $command->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/lorem.txt');

        $this->assertCount(3, $command->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/'));
        $this->assertCount(3, $command->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b'));

        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/')[0], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/')[1], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/d595afa5f0a4282342506bc7f1106e6acebff53b!!it-it');
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b')[2], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/lorem.txt');
    }

    /**
     * @test
     */
    public function test_with_files_with_and_without_extension()
    {
        $command = new UploadItem($this->s3Client);

        $command->setInCache(self::BUCKET_NAME, 'sub_folder/no-extension');
        $command->setInCache(self::BUCKET_NAME, 'sub_folder/with-extension.ext');

        $withPrefix = $command->getFromCache(self::BUCKET_NAME, 'sub_folder/');
        $noPrefix = $command->getFromCache(self::BUCKET_NAME, 'sub_folder');

        $this->assertEquals($withPrefix, $noPrefix);

        $this->assertEquals($withPrefix[0], 'sub_folder/no-extension');
        $this->assertEquals($noPrefix[1], 'sub_folder/with-extension.ext');
    }

    /**
     * @test
     */
    public function delete_from_cache()
    {
        $command = new UploadItem($this->s3Client);

        $command->removeFromCache(self::BUCKET_NAME, 'another-folder/to/file.txt');

        $this->assertCount(11, $command->getFromCache(self::BUCKET_NAME));

        $command->removeFromCache(self::BUCKET_NAME);

        $this->assertCount(0, $command->getFromCache(self::BUCKET_NAME));
    }
}
