<?php
namespace Matecat\SimpleS3\Tests;

use Exception;
use Matecat\SimpleS3\Client;
use Matecat\SimpleS3\Components\Cache\RedisCache;
use Matecat\SimpleS3\Components\Encoders\UrlEncoder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class S3ClientWithAnotherSeparatorTest extends BaseTest
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

        $this->s3Client->setPrefixSeparator('---');

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

        $this->bucket      = 'matecat-phpunit-tests-s3-3-empty';
    }

    /**
     * @test
     */
    public function test_the_client_upload_and_retrieve_versionated_items()
    {
        $this->s3Client->createBucketIfItDoesNotExist(['bucket' => $this->bucket]);

        $keyname = 'folder---test.txt';
        $source = __DIR__ . '/support/files/txt/test.txt';
        $upload = $this->s3Client->uploadItem([
            'bucket' => $this->bucket,
            'key' => $keyname,
            'source' => $source
        ]);

        $this->assertTrue($upload);

        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $this->bucket,
            'prefix' => 'folder',
            'hydrate' => true,
            'delimiter' => '---'
        ]);

        $this->assertCount(1, $items);
        $this->assertEquals(114, $this->s3Client->getBucketSize([
            'bucket' => $this->bucket,
            'prefix' => 'folder',
        ]));
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
