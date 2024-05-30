<?php
namespace Matecat\SimpleS3\Tests;

use Aws\S3\S3Client;
use InvalidArgumentException;
use Matecat\SimpleS3\ClientFactory;

class S3ClientFactoryTest extends BaseTest
{
    /**
     *
     * @test
     */
    public function test_it_throw_an_exception_if_wrong_configuration_is_passed()
    {
        $config = parse_ini_file(__DIR__.'/../config/credentials.sample.ini');
        $this->expectException( InvalidArgumentException::class );
        ClientFactory::create(
                [
                        'not-allowed-key' => 'xxxx',
                        'version' => $config['VERSION'],
                        'region' => $config['REGION'],
                ]
        );
    }

    /**
     * @test
     */
    public function test_it_initialize_from_file_S3Client()
    {
        $config = parse_ini_file(__DIR__.'/../config/credentials.sample.ini');

        if( !empty( getenv('AWS_SECRET_ACCESS_KEY') ) ){
            $config['AWS_ACCESS_KEY_ID'] = getenv('AWS_ACCESS_KEY_ID');
            $config['AWS_SECRET_ACCESS_KEY'] = getenv('AWS_SECRET_ACCESS_KEY');
        }

        $client = ClientFactory::create(
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
                'credentials' => [
                    'key' => $config['AWS_ACCESS_KEY_ID'],
                    'secret' => $config['AWS_SECRET_ACCESS_KEY']
                ]
            ]
        );

        $this->assertInstanceOf(S3Client::class, $client);
    }

    /**
     * @test
     */
    public function test_it_initialize_without_credentials_S3Client()
    {
        $config = parse_ini_file(__DIR__.'/../config/credentials.sample.ini');
        $client = ClientFactory::create(
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
            ]
        );

        $this->assertInstanceOf(S3Client::class, $client);
    }
}
