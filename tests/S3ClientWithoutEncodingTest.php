<?php
namespace Matecat\SimpleS3\Tests;

use Exception;
use Matecat\SimpleS3\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class S3ClientWithoutEncodingTest extends BaseTest
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

        // Inject Logger
        $logger = new Logger('channel-test');
        $logger->pushHandler(new StreamHandler(__DIR__.'/../log/test.log', Logger::DEBUG));
        $this->s3Client->addLogger($logger);

        $this->bucket      = $this->base_bucket_name;
        $this->keyname     = 'test.txt';

        $this->s3Client->createBucketIfItDoesNotExist([
                'bucket' => $this->bucket,
        ]);
    }

    /**
     * @test
     */
    public function test_the_client_upload_items_with_non_standard_keynames()
    {
        $keyname = 'folder/仿宋人笔意.txt';
        $source = __DIR__ . '/support/files/txt/仿宋人笔意.txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);

        $keyname = 'folder/هناك سبعة .txt';
        $source = __DIR__ . '/support/files/txt/هناك سبعة .txt';
        $upload = $this->s3Client->uploadItem(['bucket' => $this->bucket, 'key' => $keyname, 'source' => $source]);

        $this->assertTrue($upload);
    }

    /**
     * @test
     */
    public function test_the_client_copy_a_batch_of_items()
    {
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
    public function test_the_client_copy_a_single_item()
    {
        $copied = $this->s3Client->copyItem([
            'source_bucket' => $this->bucket,
            'source' => 'folder/仿宋人笔意.txt',
            'target_bucket' => $this->bucket.'-copied',
            'target' => 'copied-file.txt'
        ]);

        $this->assertTrue($copied);
    }

    /**
     * @test
     * @throws Exception
     */
    public function test_the_client_uploads_and_then_copy_an_item_with_cyrillic_name()
    {
        $source = __DIR__ . '/support/files/txt/Образование.docx';
        $key = 'Образование.docx';

        $upload = $this->s3Client->uploadItem([
                'bucket' => $this->bucket,
                'key' => $key,
                'source' => $source
        ]);

        $this->assertTrue($upload);
        $this->assertTrue($this->s3Client->hasItem(['bucket' => $this->bucket, 'key' => $key]));

        $copied = $this->s3Client->copyItem([
                'source_bucket' => $this->bucket,
                'source' => $key,
                'target_bucket' => $this->bucket,
                'target' => $key.'(1)'
        ]);
        $this->assertTrue($copied);

        $this->s3Client->uploadItem([
                'bucket' => $this->bucket,
                'key' => 'folder/'.$key,
                'source' => $source,
                'check_bucket' => true
        ]);

        $this->assertCount(3, $this->s3Client->getItemsInABucket([
                'bucket' => $this->bucket,
                'prefix' => 'folder',
                'exclude-cache' => true
        ]));

        $copied = $this->s3Client->copyInBatch([
                'source_bucket' => $this->bucket,
                'files' => [
                        'source' => [
                                $key,
                        ],
                        'target' => [
                                $key . '(222)',
                        ],
                ],
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
