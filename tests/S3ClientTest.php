<?php
namespace Matecat\SimpleS3\Tests;

use Aws\ResultInterface;
use Exception;
use Matecat\SimpleS3\Client;
use Matecat\SimpleS3\Components\Cache\RedisCache;
use Matecat\SimpleS3\Components\Encoders\UrlEncoder;
use Matecat\SimpleS3\Helpers\File;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Matecat\SimpleS3\Exceptions\InvalidS3NameException;

class S3ClientTest extends BaseTest
{
    /**
     * @var Client
     */
    protected $s3Client;

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
        $this->keyname     = 'test.txt';
    }

    /**
     * @test
     * @expectException Matecat\SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_creates_a_bucket_with_an_invalid_name()
    {
        $this->expectException( InvalidS3NameException::class );
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

        $this->s3Client->createBucketIfItDoesNotExist([
            'bucket' => $this->bucket,
            'rules' => $rules,
            'accelerate' => true
        ]);

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
        $this->assertTrue($this->s3Client->hasFolder(['bucket' => $this->bucket, 'prefix' => 'folder']));
        $this->assertFalse($this->s3Client->hasFolder(['bucket' => $this->bucket, 'prefix' => 'not_existing_folder']));
    }

    /**
     * @test
     */
    public function test_the_client_throws_exception_if_filename_is_too_long()
    {
        $source = __DIR__ . '/support/files/txt/Образование_Зависимость_от_мобильных_телефонов_Статья_24122019.docx';
        $key = 'Образование_Зависимость_от_мобильных_телефонов_Статья_24122019.docx';

        try {
            $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $key, 'source' => $source]);
        } catch (InvalidS3NameException $e){
            $this->assertEquals($e->getMessage(), $key . ' is not a valid S3 object name. [The string is too long (max length of urlencoded string is 221 bytes)]');
        }
    }

    /**
     * @test
     */
    public function test_the_client_uploads_an_item_with_an_invalid_name()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => '[not][safe]key.txt', 'source' => $source]);

        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => '[not][safe]key.txt']));
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

        $this->s3Client->uploadItem([
            'bucket' => $this->bucket,
            'key' => 'folder/'.$this->keyname,
            'source' => $source,
            'check_bucket' => true
        ]);

        $this->assertCount(1, $this->s3Client->getItemsInABucket([
                'bucket' => $this->bucket,
                'prefix' => 'folder',
                'exclude-cache' => true
        ]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_an_item_from_content()
    {
        $itemKey = 'item-from-body.txt';
        $itemContent = 'This is a simple text';

        $upload = $this->s3Client->uploadItemFromBody([
            'bucket' => $this->bucket,
            'key' => $itemKey,
            'body' =>  $itemContent,
            'check_bucket' => true
        ]);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => $this->keyname]));
    }

    /**
     * @test
     * @throws Exception
     */
    public function copyInBatch_throws_an_exception_if_an_emtpy_soruce_array_is_provided()
    {
        $input = [
                'source_bucket' => $this->bucket,
                'target_bucket' => $this->bucket.'-copied',
                'files' => [
                        'source' => [],
                        'target' => [
                                $this->keyname,
                                $this->keyname.'(1)',
                        ],
                ],
        ];

        try {
            $this->s3Client->copyInBatch($input);
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'source files array cannot be empty.');
        }
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
        $this->assertEquals(477, $size);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_an_item()
    {
        $item = $this->s3Client->getItem([
            'bucket' => $this->bucket,
            'key' => $this->keyname
        ]);

        $this->assertTrue(is_array($item));
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
    public function test_the_client_downloads_an_item()
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
    public function test_the_client_gets_items_in_a_bucket_with_hydratation()
    {
        $items = $this->s3Client->getItemsInABucket([
            'bucket' => $this->bucket,
            'hydrate' => true,
        ]);

        $this->assertTrue(is_array($items));
        $this->assertCount(5, $items);

        foreach ($items as $key => $item) {
            $this->assertTrue(is_array($item));
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

        File::removeDir($dest);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_items_in_a_bucket_from_cache()
    {
        $items = $this->s3Client->getItemsInABucket(
            [
                'bucket' => $this->bucket,
                'prefix' => 'folder'
            ]
        );

        $this->assertTrue(is_array($items));
        $this->assertCount(1, $items);

        $this->assertContains('folder/test.txt', $items);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_items_in_a_bucket_from_s3()
    {

        $items = $this->s3Client->getItemsInABucket(
            [
                'bucket' => $this->bucket,
                'prefix' => 'folder'
            ]
        );

        $this->assertTrue(is_array($items));
        $this->assertCount(1, $items);
        $this->assertContains('folder/test.txt', $items);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_put_item_in_glacier_and_then_restore_it()
    {
        $keyname = 'file-to-be-restored-from-glacier.txt';
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source, 'storage' => 'GLACIER', 'check_bucket' => true]);
        $this->assertTrue($upload);

        $restore = $this->s3Client->restoreItem(['bucket' => $this->bucket, 'key' => $keyname]);
        $this->assertTrue($restore);

        sleep(10); // wait the file is restored

        $deleted = $this->s3Client->deleteItem(['bucket' => $this->bucket, 'key' => $keyname]);
        $this->assertTrue($deleted);
    }

    /**
     * @test
     */
    public function test_the_client_upload_and_retrieve_items_with_non_standard_keynames()
    {
        $keyname = 'folder/仿宋人笔意.txt';
        $source = __DIR__ . '/support/files/txt/仿宋人笔意.txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);

        $keyname = 'folder/هناك سبعة .txt';
        $source = __DIR__ . '/support/files/txt/هناك سبعة .txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);

        $items = $this->s3Client->getItemsInABucket(
            [
                'bucket' => $this->bucket,
                'prefix' => 'folder'
            ]
        );

        $input = [
            'source_bucket' => $this->bucket,
            'target_bucket' => $this->bucket.'-copied',
            'files' => [
                'source' => $items
            ],
        ];

        $copied = $this->s3Client->copyInBatch($input);

        $this->assertTrue($copied);
    }

    /**
     * @test
     */
    public function test_the_client_can_copy_a_folder()
    {
        $copyFolder = $this->s3Client->copyFolder([
                'source_bucket' => $this->bucket,
                'source_folder' => 'folder',
                'target_folder' => 'target/copied'
        ]);

        $items = $this->s3Client->getItemsInABucket(
            [
                        'bucket' => $this->bucket,
                        'prefix' => 'target/copied'
                ]
        );

        $this->assertCount(3, $items);
        $this->assertTrue($copyFolder);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_a_folder()
    {
        $delete = $this->s3Client->deleteFolder(['bucket' => $this->bucket, 'prefix' => 'folder']);
        $delete2 = $this->s3Client->deleteFolder(['bucket' => $this->bucket, 'prefix' => 'target/copied']);

        $this->assertTrue($delete);
        $this->assertTrue($delete2);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_all_the_items()
    {
        $buckets = [
            $this->bucket.'-copied',
            $this->bucket.'2',
            $this->bucket,
        ];

        foreach ($buckets as $bucket) {
            $this->assertTrue($this->s3Client->clearBucket(['bucket' => $bucket]));
            $this->assertEquals(0, $this->s3Client->getBucketSize(['bucket' => $bucket]));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket()
    {
        $buckets = [
            $this->bucket.'-copied',
            $this->bucket.'2',
            $this->bucket,
        ];

        foreach ($buckets as $bucket) {
            $this->assertTrue($this->s3Client->deleteBucket(['bucket' => $bucket]));
            $this->assertFalse($this->s3Client->hasBucket(['bucket' => $bucket]));
        }
    }
}
