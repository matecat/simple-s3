<?php

use Aws\Api\DateTimeResult;
use Aws\Result;
use Aws\ResultInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleS3\Client;

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

        $logger = new Logger('channel-test');
        $logger->pushHandler(new StreamHandler(__DIR__.'/../log/test.log', Logger::DEBUG));
        $this->s3Client->addLogger($logger);

        $this->bucket      = 'mauretto78-bucket-test';
        $this->keyname     = 'test.txt';
    }

    /**
     * @test
     * @expectedException \SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_creates_a_bucket_with_an_invalid_name()
    {
        $this->s3Client->createBucketIfItDoesNotExist('172.0.0.1');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function test_the_client_creates_a_bucket_with_an_invalid_lifecycle_configuration()
    {
        $this->s3Client->createBucketIfItDoesNotExist($this->bucket.'2', 10, 100);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function test_the_client_creates_a_bucket_with_an_invalid_storage_configuration()
    {
        $this->s3Client->createBucketIfItDoesNotExist($this->bucket.'3', 2000, 5, 'NOT_EXSISTING_STORAGE');
    }

    /**
     * @test
     */
    public function test_the_client_creates_a_bucket_without_lifecycle_configuration()
    {
        $created = $this->s3Client->createBucketIfItDoesNotExist($this->bucket.'4');

        $this->assertTrue($created);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_creates_a_bucket()
    {
        $this->s3Client->createBucketIfItDoesNotExist($this->bucket, 5, 2, 'GLACIER');

        $configuration = $this->s3Client->getBucketLifeCycleConfiguration($this->bucket);

        $this->assertInstanceOf(ResultInterface::class, $configuration);
        $this->assertEquals(5, $configuration['Rules'][0]['Expiration']['Days']);
        $this->assertEquals(2, $configuration['Rules'][0]['Transition']['Days']);
        $this->assertEquals('GLACIER', $configuration['Rules'][0]['Transition']['StorageClass']);
        $this->assertTrue($this->s3Client->createFolder($this->bucket, 'folder'));
    }

    /**
     * @test
     * @expectedException \SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_uploads_a_item_with_an_invalid_name()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $this->s3Client->uploadItem($this->bucket, '{invalid name}', $source);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_and_then_copy_a_item()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadItem($this->bucket, $this->keyname, $source);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem($this->bucket, $this->keyname));

        $copied = $this->s3Client->copyItem($this->bucket, $this->keyname, $this->bucket, $this->keyname.'(1)');
        $this->assertTrue($copied);

        $this->s3Client->uploadItem($this->bucket, 'folder/'.$this->keyname, $source);
        $this->assertCount(2, $this->s3Client->getItemsInABucket($this->bucket, 'folder/'));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_an_item_from_content()
    {
        $itemKey = 'item-from-body.txt';
        $itemContent = 'This is a simple text';

        $upload = $this->s3Client->uploadItemFromBody($this->bucket, $itemKey, $itemContent);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem($this->bucket, $itemKey));
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
        $size = $this->s3Client->getBucketSize($this->bucket);
        $this->assertEquals(363, $size);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_a_item()
    {
        $item = $this->s3Client->getItem($this->bucket, $this->keyname);

        $this->assertInstanceOf(Result::class, $item);
        $this->assertEquals($item['ContentType'], 'text/plain');
        $this->assertEquals($item['@metadata']['statusCode'], 200);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_download_link_for_a_item()
    {
        $link = $this->s3Client->getPublicItemLink($this->bucket, $this->keyname);

        $this->assertContains('This is nothing but a simple text for test the php Client for S3.', file_get_contents($link));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_items_in_a_bucket()
    {
        $items = $this->s3Client->getItemsInABucket($this->bucket);

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
    public function test_the_client_put_item_in_glacier_and_then_restore_it()
    {
        $keyname = 'file-to-be-restored-from-glacier';
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadItem($this->bucket, $keyname, $source, 'GLACIER');
        $this->assertTrue($upload);

        $restore = $this->s3Client->restoreItem($this->bucket, $keyname);
        $this->assertTrue($restore);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_all_the_items()
    {
        $items = $this->s3Client->clearBucket($this->bucket);
        $itemsCopied = $this->s3Client->clearBucket($this->bucket.'-copied');

        $this->assertTrue($items);
        $this->assertTrue($itemsCopied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket()
    {
        $delete = $this->s3Client->deleteBucket($this->bucket);
        $deleteCopied = $this->s3Client->deleteBucket($this->bucket.'-copied');
        $deleteCopied2 = $this->s3Client->deleteBucket($this->bucket.'2');
        $deleteCopied3 = $this->s3Client->deleteBucket($this->bucket.'3');
        $deleteCopied4 = $this->s3Client->deleteBucket($this->bucket.'4');

        $this->assertTrue($delete);
        $this->assertTrue($deleteCopied);
        $this->assertTrue($deleteCopied2);
        $this->assertTrue($deleteCopied3);
        $this->assertTrue($deleteCopied4);
    }
}
