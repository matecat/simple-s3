<?php

use Aws\S3\S3Client;
use SimpleS3\ClientFactory;

class S3ClientFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function test_it_throw_an_exception_if_wrong_configuration_is_passed()
    {
        $config = parse_ini_file(__DIR__.'/../config/credentials.ini');
        ClientFactory::create(
            [
                'not-allowed-key' => 'xxxx',
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
                'credentials' => [
                    'key' => $config['ACCESS_KEY_ID'],
                    'secret' => $config['SECRET_KEY']
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function test_it_initialize_S3Client()
    {
        $config = parse_ini_file(__DIR__.'/../config/credentials.ini');
        $client = ClientFactory::create(
            [
                'version' => $config['VERSION'],
                'region' => $config['REGION'],
                'credentials' => [
                    'key' => $config['ACCESS_KEY_ID'],
                    'secret' => $config['SECRET_KEY']
                ]
            ]
        );

        $this->assertInstanceOf(S3Client::class, $client);
    }
}
