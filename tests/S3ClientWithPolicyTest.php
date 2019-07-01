<?php

use Aws\ResultInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleS3\Client;
use SimpleS3\Components\Cache\RedisCache;
use SimpleS3\Components\Encoders\UrlEncoder;

class S3ClientWithPolicyTest extends PHPUnit_Framework_TestCase
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
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
                'credentials' => [
                    'key' => $config['ACCESS_KEY_ID'],
                    'secret' => $config['SECRET_KEY']
                ]
            ]
        );

        // Inject Encoder
        $encoder = new UrlEncoder();
        $this->s3Client->addEncoder($encoder);

        $this->bucket      = 'mauretto78-bucket-test-policy';
    }

    /**
     * @test
     */
    public function test_the_client_set_and_get_the_bucket_policy()
    {
        $this->s3Client->createBucketIfItDoesNotExist(['bucket' => $this->bucket]);
        $policy = $this->s3Client->setBucketPolicy([
            'bucket' => $this->bucket,
            'policy' => '{
                "Version": "2012-10-17",
                "Id": "Versioning",
                "Statement": [
                    {
                        "Effect": "Deny",
                        "Principal": "*",
                        "Action": "s3:GetBucketVersioning",
                        "Resource": "arn:aws:s3:::mauretto78-bucket-test-policy"
                    }
                ]
            }',
        ]);

        $this->assertTrue($policy);

        $expected = [
            "Version" =>"2012-10-17",
            "Id" => "Versioning",
            "Statement" => [
                [
                    "Effect" => "Deny",
                    "Principal" => "*",
                    "Action" => "s3:GetBucketVersioning",
                    "Resource" => "arn:aws:s3:::mauretto78-bucket-test-policy"
                ]
            ]
        ];

        $this->assertEquals($expected, $this->s3Client->getBucketPolicy([
            'bucket' => $this->bucket,
        ]));
    }

    /**
     * @test
     */
    public function test_the_client_cant_the_bucket_versioning()
    {
        $versioned = $this->s3Client->isBucketVersioned([
            'bucket' => $this->bucket,
        ]);

        $this->assertFalse($versioned);
    }

    /**
     * @test
     */
    public function test_the_client_delete_the_bucket_policy()
    {
        $deleted = $this->s3Client->deleteBucketPolicy([
                'bucket' => $this->bucket,
        ]);

        $this->assertTrue($deleted);
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
