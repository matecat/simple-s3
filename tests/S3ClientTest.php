<?php

use Aws\Result;
use SimpleS3\Client;
use SimpleS3\Exceptions\InvalidS3BucketNameException;
use SimpleS3\Helper\S3BucketNameValidator;

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
     * @throws InvalidS3BucketNameException
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
        $this->bucket      = S3BucketNameValidator::generateFromString('mauretto78-bucket-test');
        $this->keyname     = 'test.txt';
    }

    /**
     * @test
     */
    public function test_the_client_uploads_a_file()
    {
        $source = __DIR__ . '/support/files/txt/test.txt';

        /** @var Result $upload */
        $upload = $this->s3Client->uploadFile($this->bucket, $this->keyname, $source);

        $this->assertInstanceOf(Result::class, $upload);
        $this->assertEquals($upload[ 'Bucket' ], $this->bucket);
        $this->assertEquals($upload[ 'Key' ], $this->keyname);
        $this->assertEquals($upload[ '@metadata' ][ 'statusCode' ], 200);
    }

    /**
     * @test
     */
    public function test_the_client_gets_a_file()
    {
        $file = $this->s3Client->getFile($this->bucket, $this->keyname);

        $this->assertInstanceOf(Result::class, $file);
        $this->assertEquals($file[ 'ContentType' ], 'text/plain');
        $this->assertEquals($file[ '@metadata' ][ 'statusCode' ], 200);
    }

    /**
     * @test
     */
    public function test_the_client_gets_the_download_link_for_a_file()
    {
        $link = $this->s3Client->getPublicFileLink($this->bucket, $this->keyname);

        $this->assertContains('This is nothing but a simple text for test the php Client for S3.', file_get_contents($link));
    }

    /**
     * @test
     */
    public function test_the_client_gets_files_in_a_bucket()
    {
        $files = $this->s3Client->getFilesFromABucket($this->bucket);

        $this->assertTrue(is_array($files));
        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->assertInstanceOf(Result::class, $file);
            $this->assertEquals($file[ 'ContentType' ], 'text/plain');
            $this->assertEquals($file[ '@metadata' ][ 'statusCode' ], 200);
        }
    }

    /**
     * @test
     */
    public function test_the_client_deletes_all_the_files()
    {
        $files = $this->s3Client->clearBucket($this->bucket);

        $this->assertCount(1, $files);

        foreach ($files as $file) {
            $this->assertEquals($file[ 'DeleteMarker' ], false);
            $this->assertEquals($file[ '@metadata' ][ 'statusCode' ], 204);
        }
    }
}
