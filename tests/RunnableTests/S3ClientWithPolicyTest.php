<?php

namespace Matecat\SimpleS3\Tests\RunnableTests;

use Exception;
use Matecat\SimpleS3\Client;
use Matecat\SimpleS3\Components\Encoders\UrlEncoder;
use Matecat\SimpleS3\Tests\BaseTest;

class S3ClientWithPolicyTest extends BaseTest {
    /**
     * @var Client
     */
    protected Client $s3Client;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @throws Exception
     */
    protected function setUp(): void {
        parent::setUp();

        $this->getClient();

        // Inject Encoder
        $encoder = new UrlEncoder();
        $this->s3Client->addEncoder( $encoder );

        $this->bucket = $this->base_bucket_name . "-policy";
    }

    /**
     * @test
     */
    public function test_the_client_set_and_get_the_bucket_policy() {
        $this->s3Client->createBucketIfItDoesNotExist( [ 'bucket' => $this->bucket ] );
        $policy = $this->s3Client->setBucketPolicy( [
                'bucket' => $this->bucket,
                'policy' => '{
                "Version": "2012-10-17",
                "Id": "Versioning",
                "Statement": [
                    {
                        "Effect": "Deny",
                        "Principal": "*",
                        "Action": "s3:GetBucketVersioning",
                        "Resource": "arn:aws:s3:::' . $this->bucket . '"
                    }
                ]
            }',
        ] );

        $this->assertTrue( $policy );

        $expected = [
                "Version"   => "2012-10-17",
                "Id"        => "Versioning",
                "Statement" => [
                        [
                                "Effect"    => "Deny",
                                "Principal" => "*",
                                "Action"    => "s3:GetBucketVersioning",
                                "Resource"  => "arn:aws:s3:::" . $this->bucket
                        ]
                ]
        ];

        $this->assertEquals( $expected, $this->s3Client->getBucketPolicy( [
                'bucket' => $this->bucket,
        ] ) );
    }

    /**
     * @test
     */
    public function test_the_client_cant_the_bucket_versioning() {
        $versioned = $this->s3Client->isBucketVersioned( [
                'bucket' => $this->bucket,
        ] );

        $this->assertFalse( $versioned );
    }

    /**
     * @test
     */
    public function test_the_client_delete_the_bucket_policy() {
        $deleted = $this->s3Client->deleteBucketPolicy( [
                'bucket' => $this->bucket,
        ] );

        $this->assertTrue( $deleted );
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_deletes_the_bucket() {
        $this->assertTrue( $this->s3Client->deleteBucket( [ 'bucket' => $this->bucket ] ) );
        $this->assertFalse( $this->s3Client->hasBucket( [ 'bucket' => $this->bucket ] ) );
    }
}
