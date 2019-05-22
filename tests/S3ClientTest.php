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
     * @throws Exception
     */
    public function test_the_client_creates_a_bucket()
    {
        $this->s3Client->createBucketIfItDoesNotExist($this->bucket, 5);

        $this->assertInstanceOf(DateTimeResult::class, $this->s3Client->getBucketLifeCycle($this->bucket));
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
        $this->assertEquals(249, $size);
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
        $this->assertCount(4, $items);

        foreach ($items as $item) {
            $this->assertInstanceOf(ResultInterface::class, $item);
            $this->assertEquals($item['@metadata']['statusCode'], 200);
        }
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

        $this->assertTrue($delete);
        $this->assertTrue($deleteCopied);
    }
}
