<?php

use Aws\PsrCacheAdapter;
use SimpleS3\Client;
use SimpleS3\Wrappers\Cache;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheTest extends PHPUnit_Framework_TestCase
{
    const BUCKET_NAME = 'bucket';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @throws Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $config = parse_ini_file(__DIR__.'/../config/credentials.ini');
        $s3Client    = new Client(
            $config['ACCESS_KEY_ID'],
            $config['SECRET_KEY'],
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
            ]
        );

        // Inject Cache
        $redis = new Predis\Client();
        $cacheAdapter = new RedisAdapter($redis);
        $s3Client->addCache(new PsrCacheAdapter($cacheAdapter));

        $this->cache = new Cache($s3Client);
    }

    /**
     * @test
     */
    public function upload_and_retrieve_from_cache()
    {
        $this->cache->setInCache(self::BUCKET_NAME, 'file.txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'folder/file.txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'folder/to/file.txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'folder/to/file(2).txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'file(2).txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'another-folder/file.txt');
        $this->cache->setInCache(self::BUCKET_NAME, 'another-folder/to/file.txt');

        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, './')[0], 'file.txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'folder')[0], 'folder/file.txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'folder/to')[0], 'folder/to/file.txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'folder/to/')[1], 'folder/to/file(2).txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, './')[1], 'file(2).txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'another-folder')[0], 'another-folder/file.txt');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'another-folder/to')[0], 'another-folder/to/file.txt');
    }

    /**
     * @test
     */
    public function upload_and_retrieve_folders_from_cache()
    {
        $this->cache->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/');
        $this->cache->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/d595afa5f0a4282342506bc7f1106e6acebff53b!!it-it');
        $this->cache->setInCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/lorem.txt');

        $this->assertCount(3, $this->cache->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/'));
        $this->assertCount(3, $this->cache->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b'));

        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/')[0], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/')[1], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/d595afa5f0a4282342506bc7f1106e6acebff53b!!it-it');
        $this->assertEquals($this->cache->getFromCache(self::BUCKET_NAME, 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b')[2], 'queue-projects/dfd4d08c-4966-88f5-c7ff-010cf66cae9b/lorem.txt');
    }

    /**
     * @test
     */
    public function test_with_files_with_and_without_extension()
    {
        $this->cache->setInCache(self::BUCKET_NAME, 'sub_folder/no-extension');
        $this->cache->setInCache(self::BUCKET_NAME, 'sub_folder/with-extension.ext');

        $withPrefix = $this->cache->getFromCache(self::BUCKET_NAME, 'sub_folder/');
        $noPrefix = $this->cache->getFromCache(self::BUCKET_NAME, 'sub_folder');

        $this->assertEquals($withPrefix, $noPrefix);

        $this->assertEquals($withPrefix[0], 'sub_folder/no-extension');
        $this->assertEquals($noPrefix[1], 'sub_folder/with-extension.ext');
    }

    /**
     * @test
     */
    public function test_with_a_wrong_prefix()
    {
        $this->assertEquals([], $this->cache->getFromCache(self::BUCKET_NAME, 'fsdfsdfsdfsdfsd'));
    }

    /**
     * @test
     */
    public function delete_from_cache()
    {
        $this->cache->removeFromCache(self::BUCKET_NAME, 'folder/to');

        $this->assertCount(10, $this->cache->getFromCache(self::BUCKET_NAME));

        $this->cache->removeFromCache(self::BUCKET_NAME);

        $this->assertCount(0, $this->cache->getFromCache(self::BUCKET_NAME));
    }
}
