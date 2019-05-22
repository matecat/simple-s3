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
    }

    /**
     * @test
     * @expectedException \SimpleS3\Exceptions\InvalidS3NameException
     */
    public function test_the_client_uploads_a_file_with_an_invalid_name()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $this->s3Client->uploadFile($this->bucket, '{invalid name}', $source);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_and_then_copy_a_file()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        $upload = $this->s3Client->uploadFile($this->bucket, $this->keyname, $source);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasFile($this->bucket, $this->keyname));

        $copied = $this->s3Client->copyFile($this->bucket, $this->keyname, $this->bucket, $this->keyname.'(1)');
        $this->assertTrue($copied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_copy_files_in_batch()
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
        $this->assertEquals(228, $size);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_a_file()
    {
        $file = $this->s3Client->getFile($this->bucket, $this->keyname);

        $this->assertInstanceOf(Result::class, $file);
        $this->assertEquals($file['ContentType'], 'text/plain');
        $this->assertEquals($file['@metadata']['statusCode'], 200);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_the_download_link_for_a_file()
    {
        $link = $this->s3Client->getPublicFileLink($this->bucket, $this->keyname);

        $this->assertContains('This is nothing but a simple text for test the php Client for S3.', file_get_contents($link));
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_gets_files_in_a_bucket()
    {
        $files = $this->s3Client->getFilesInABucket($this->bucket);

        $this->assertTrue(is_array($files));
        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(ResultInterface::class, $file);
            $this->assertEquals($file[ 'ContentType' ], 'text/plain');
            $this->assertEquals($file[ '@metadata' ][ 'statusCode' ], 200);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_all_the_files()
    {
        $files = $this->s3Client->clearBucket($this->bucket);
        $filesCopied = $this->s3Client->clearBucket($this->bucket.'-copied');

        $this->assertTrue($files);
        $this->assertTrue($filesCopied);
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
