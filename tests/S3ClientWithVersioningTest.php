<?php

use Aws\ResultInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleS3\Client;
use SimpleS3\Components\Cache\RedisCache;
use SimpleS3\Components\Encoders\UrlEncoder;

class S3ClientWithVersioningTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $s3Client;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @throws Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $config = parse_ini_file(__DIR__.'/../config/credentials.ini');
        $this->s3Client = new Client(
            $config['ACCESS_KEY_ID'],
            $config['SECRET_KEY'],
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
            ]
        );

        // Inject Encoder
        $encoder = new UrlEncoder();
        $this->s3Client->addEncoder($encoder);

        // Inject Logger
        $logger = new Logger('channel-test');
        $logger->pushHandler(new StreamHandler(__DIR__.'/../log/test.log', Logger::DEBUG));
        $this->s3Client->addLogger($logger);

        // Inject Cache
        $redis = new Predis\Client();
        $cacheAdapter = new RedisCache($redis);
        $this->s3Client->addCache($cacheAdapter);

        $this->bucket      = 'mauretto78-bucket-test-versionated';
    }

    /**
     * @test
     */
    public function test_the_client_upload_and_retrieve_versionated_items()
    {
        $this->s3Client->createBucketIfItDoesNotExist(['bucket' => $this->bucket]);
        $this->s3Client->setBucketVersioning(['bucket' => $this->bucket]);
        $versioning = $this->s3Client->isBucketVersioned(['bucket' => $this->bucket]);

        $this->assertTrue($versioning);

        $keyname = 'folder/仿宋人笔意.txt';
        $source = __DIR__ . '/support/files/txt/仿宋人笔意.txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);

        $keyname = 'folder/仿宋人笔意.txt';
        $source = __DIR__ . '/support/files/txt/仿宋人笔意.txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);

        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $this->bucket,
            'prefix' => 'folder/',
            'hydrate' => true
        ]);

        $this->assertCount(2, $items);
        $this->assertEquals(1496, $this->s3Client->getBucketSize([
                'bucket' => $this->bucket,
                'prefix' => 'folder/',
        ]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_all_the_items()
    {
        $this->assertTrue($this->s3Client->clearBucket(['bucket' => $this->bucket]));
        $this->assertEquals(0, $this->s3Client->getBucketSize(['bucket' => $this->bucket]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket()
    {
        $this->assertTrue($this->s3Client->deleteBucket(['bucket' => $this->bucket]));
        $this->assertFalse($this->s3Client->hasBucket(['bucket' => $this->bucket]));
    }
}
