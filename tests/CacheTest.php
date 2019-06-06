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
        $this->assertEquals($command->getFromCache(self::BUCKET_NAME, 'folder/to')[1], 'folder/to/file(2).txt');
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

        $command->setInCache(self::BUCKET_NAME, 'new_folder/to');
        $command->setInCache(self::BUCKET_NAME, 'new_folder/to/file.txt');

        $this->assertCount(2, $command->getFromCache(self::BUCKET_NAME, 'new_folder/to'));
    }

    /**
     * @test
     */
    public function delete_from_cache()
    {
        $command = new UploadItem($this->s3Client);

        $command->removeFromCache(self::BUCKET_NAME, 'another-folder/to/file.txt');

        $this->assertCount(8, $command->getFromCache(self::BUCKET_NAME));

        $command->removeFromCache(self::BUCKET_NAME);

        $this->assertCount(0, $command->getFromCache(self::BUCKET_NAME));
    }
}
