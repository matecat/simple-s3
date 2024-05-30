<?php
namespace Matecat\SimpleS3\Tests;

use Exception;
use Matecat\SimpleS3\Client;
use Matecat\SimpleS3\Components\Cache\RedisCache;
use Matecat\SimpleS3\Components\Encoders\UrlEncoder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class S3ClientWithVersioningTest extends BaseTest
{
    /**
     * @var Client
     */
    protected $s3Client;

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

        $this->getClient();

        // Inject Encoder
        $encoder = new UrlEncoder();
        $this->s3Client->addEncoder($encoder);

        // Inject Logger
        $logger = new Logger('channel-test');
        $logger->pushHandler(new StreamHandler(__DIR__.'/../log/test.log', Logger::DEBUG));
        $this->s3Client->addLogger($logger);

        // Inject Cache
        $redis = new \Predis\Client();
        $cacheAdapter = new RedisCache($redis);
        $this->s3Client->addCache($cacheAdapter);

        $this->bucket      = $this->base_bucket_name;
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
        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => $keyname]));

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
    public function test_the_client_gets_the_current_version_of_an_item()
    {
        $keyname = 'folder/仿宋人笔意.txt';
        $version = $this->s3Client->getCurrentItemVersion(['bucket' => $this->bucket, 'key' => $keyname]);

        $this->assertNotNull($version);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_download_link_for_an_item()
    {
        $keyname = 'folder/仿宋人笔意.txt';
        $link = $this->s3Client->getPublicItemLink(['bucket' => $this->bucket, 'key' => $keyname]);

        $this->assertContains('Here you can find activities to practise your reading skills.', file_get_contents($link));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_content_of_an_item()
    {
        $keyname = 'folder/仿宋人笔意.txt';
        $open = $this->s3Client->openItem(['bucket' => $this->bucket, 'key' => $keyname]);

        $this->assertContains('Here you can find activities to practise your reading skills.', $open);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_copy_an_item()
    {
        $keyname = 'folder/test.txt';
        $source = __DIR__ . '/support/files/txt/test.txt';
        $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $copied = $this->s3Client->copyItem([
            'source_bucket' => $this->bucket,
            'source' => 'folder/test.txt',
            'target_bucket' => $this->bucket.'-copied',
            'target' => 'copied-file.txt'
        ]);
        $this->assertTrue($copied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket()
    {
        $this->assertTrue($this->s3Client->deleteBucket(['bucket' => $this->bucket]));
        $this->assertTrue($this->s3Client->deleteBucket(['bucket' => $this->bucket.'-copied']));
        $this->assertFalse($this->s3Client->hasBucket(['bucket' => $this->bucket]));
        $this->assertFalse($this->s3Client->hasBucket(['bucket' => $this->bucket.'-copied']));
    }
}
