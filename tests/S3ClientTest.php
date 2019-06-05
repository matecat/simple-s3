<?php

use Aws\PsrCacheAdapter;
use Aws\Result;
use Aws\ResultInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleS3\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class S3ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $s3Client;

    /**
     * @var string
     */
    private $keyname;

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

        $this->bucket      = 'mauretto78-bucket-test';
        $this->keyname     = 'test.txt';
    }

    /**
     * @test
     * @expectedException \SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_creates_a_bucket_with_an_invalid_name()
    {
        $this->s3Client->createBucketIfItDoesNotExist(['bucket' => '172.0.0.1']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_creates_a_bucket_without_lifecycle_configuration()
    {
        $created = $this->s3Client->createBucketIfItDoesNotExist(['bucket' => $this->bucket.'2']);

        $this->assertTrue($created);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_creates_a_bucket()
    {
        $rules = [
            [
                "ID"=>  "Move rotated logs to Glacier",
                "Prefix"=>  "rotated/",
                "Status"=>  "Enabled",
                "Transitions"=>  [
                    [
                        "Date"=>  "2015-11-10T00:00:00.000Z",
                        "StorageClass"=>  "GLACIER"
                    ]
                ]
            ],
            [
                "Status" => "Enabled",
                "Prefix" => "",
                "NoncurrentVersionTransitions"=>  [
                    [
                        "NoncurrentDays"=>  2,
                        "StorageClass"=>  "GLACIER"
                    ]
                ],
                "ID"=>  "Move old versions to Glacier"
            ]
        ];

        $this->s3Client->createBucketIfItDoesNotExist(['bucket' => $this->bucket, 'rules' => $rules, 'accelerate' => true]);

        $configuration = $this->s3Client->getBucketLifeCycleConfiguration(['bucket' => $this->bucket]);

        /** @var Aws\Api\DateTimeResult $transitionsDate */
        $transitionsDate = $configuration['Rules'][0]['Transition']['Date'];

        $this->assertInstanceOf(ResultInterface::class, $configuration);
        $this->assertEquals("2015-11-10 00:00:00", $transitionsDate->format('Y-m-d H:i:s'));
        $this->assertEquals("Z", $transitionsDate->getTimezone()->getName());
        $this->assertEquals('GLACIER', $configuration['Rules'][0]['Transition']['StorageClass']);
        $this->assertTrue($this->s3Client->createFolder([
            'bucket' => $this->bucket,
            'key' => 'folder'
        ]));
    }

    /**
     * @test
     * @expectedException \SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_uploads_an_item_with_an_invalid_name()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => '{invalid name}', 'source' => $source]);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_and_then_copy_an_item()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadItem([
            'bucket' => $this->bucket,
            'key' => $this->keyname,
            'source' => $source
        ]);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => $this->keyname]));

        $copied = $this->s3Client->copyItem([
            'source_bucket' => $this->bucket,
            'source' => $this->keyname,
            'target_bucket' => $this->bucket,
            'target' => $this->keyname.'(1)'
        ]);
        $this->assertTrue($copied);

        $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => 'folder/'.$this->keyname, 'source' => $source, 'check_bucket' => true]);

        $this->assertCount(2, $this->s3Client->getItemsInABucket(['bucket' => $this->bucket, 'prefix' => 'folder']));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_an_item_from_content()
    {
        $itemKey = 'item-from-body.txt';
        $itemContent = 'This is a simple text';

        $upload = $this->s3Client->uploadItemFromBody(['bucket' => $this->bucket, 'key' => $itemKey, 'body' =>  $itemContent, 'check_bucket' => true]);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => $this->keyname]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_copy_items_in_batch()
    {
        $input = [
            'source_bucket' => $this->bucket,
            'target_bucket' => $this->bucket.'-copied',
            'files' => [
                'source' => [
                    $this->keyname,
                    $this->keyname.'(1)',
                ],
                'target' => [
                    $this->keyname,
                    $this->keyname.'(1)',
                ],
            ],
        ];

        $copied = $this->s3Client->copyInBatch($input);

        $this->assertTrue($copied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_bucket_size()
    {
        $size = $this->s3Client->getBucketSize(['bucket' => $this->bucket]);
        $this->assertEquals(363, $size);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_an_item()
    {
        $item = $this->s3Client->getItem(['bucket' => $this->bucket, 'key' => $this->keyname]);

        $this->assertInstanceOf(Result::class, $item);
        $this->assertEquals($item['ContentType'], 'text/plain');
        $this->assertEquals($item['@metadata']['statusCode'], 200);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_download_link_for_an_item()
    {
        $link = $this->s3Client->getPublicItemLink(['bucket' => $this->bucket, 'key' => $this->keyname]);

        $this->assertContains('This is nothing but a simple text for test the php Client for S3.', file_get_contents($link));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_downloads__an_item()
    {
        $saveAs = __DIR__ . '/support/files/txt/test(download).txt';
        $download = $this->s3Client->downloadItem([
            'bucket' => $this->bucket,
            'key' => $this->keyname,
            'save_as' => $saveAs
        ]);

        $this->assertTrue($download);
        $this->assertTrue(file_exists($saveAs));

        unlink($saveAs);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_content_of_an_item()
    {
        $open = $this->s3Client->openItem(['bucket' => $this->bucket, 'key' => $this->keyname]);

        $this->assertContains('This is nothing but a simple text for test the php Client for S3.', $open);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_items_in_a_bucket_with_callback()
    {
        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $this->bucket,
            'hydrate' => true,
        ]);

        $this->assertTrue(is_array($items));
        $this->assertCount(5, $items);

        foreach ($items as $item) {
            $this->assertInstanceOf(ResultInterface::class, $item);
            $this->assertEquals($item['@metadata']['statusCode'], 200);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_transfer_the_content_from_a_bucket_to_filesystem()
    {
        $source = 's3://'.$this->bucket;
        $dest = __DIR__ . '/support/files/transfer';

        $this->s3Client->transfer(['dest' => $dest, 'source' => $source]);

        $this->assertTrue(is_dir($dest));

        SimpleS3\Helpers\File::removeDir($dest);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_items_in_a_bucket()
    {
        $items = $this->s3Client->getItemsInABucket(['bucket' => $this->bucket]);

        $this->assertTrue(is_array($items));
        $this->assertCount(5, $items);
        $this->assertContains('folder', $items);
        $this->assertContains('folder/test.txt', $items);
        $this->assertContains('item-from-body.txt', $items);
        $this->assertContains('test.txt', $items);
        $this->assertContains('test.txt(1)', $items);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_put_item_in_glacier_and_then_restore_it()
    {
        $keyname = 'file-to-be-restored-from-glacier';
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source, 'storage' => 'GLACIER', 'check_bucket' => true]);
        $this->assertTrue($upload);

        $restore = $this->s3Client->restoreItem(['bucket' => $this->bucket, 'key' => $keyname]);
        $this->assertTrue($restore);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_all_the_items()
    {
        $items = $this->s3Client->clearBucket(['bucket' => $this->bucket]);
        $itemsCopied = $this->s3Client->clearBucket(['bucket' => $this->bucket.'-copied']);

        $this->assertTrue($items);
        $this->assertTrue($itemsCopied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket()
    {
        $delete = $this->s3Client->deleteBucket(['bucket' => $this->bucket]);
        $deleteCopied = $this->s3Client->deleteBucket(['bucket' => $this->bucket.'-copied']);
        $deleteCopied2 = $this->s3Client->deleteBucket(['bucket' => $this->bucket.'2']);

        $this->assertTrue($delete);
        $this->assertTrue($deleteCopied);
        $this->assertTrue($deleteCopied2);
    }
}
