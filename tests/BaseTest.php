<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 30/05/24
 * Time: 14:33
 *
 */

namespace Matecat\SimpleS3\Tests;

use Matecat\SimpleS3\Client;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase {

    /**
     * @var Client
     */
    protected $s3Client;

    protected $base_bucket_name = 'matecat-phpunit-tests-s3-4';

    /**
     * @return Client
     */
    public function getClient() {

        parent::setUp();

        $config = [];
        if ( file_exists( Constants::TEST_CREDENTIALS_CONFIG ) ) {
            $config = parse_ini_file( Constants::TEST_CREDENTIALS_CONFIG );
        }

        if ( !empty( getenv( 'AWS_ACCESS_KEY_ID' ) ) && !empty( getenv( 'AWS_SECRET_ACCESS_KEY' ) ) ) {

            $this->s3Client = new Client( [
                    'version' => '2006-03-01',
                    'region'  => 'eu-central-1',
            ] );

        } elseif ( !empty( $config[ 'AWS_ACCESS_KEY_ID' ] ) && !empty( $config[ 'AWS_SECRET_ACCESS_KEY' ] ) ) {
            $this->s3Client = new Client(
                    [
                            'version'     => '2006-03-01',
                            'region'      => 'eu-central-1',
                            'credentials' => [
                                    'key'    => $config[ 'AWS_ACCESS_KEY_ID' ],
                                    'secret' => $config[ 'AWS_SECRET_ACCESS_KEY' ]
                            ]
                    ]
            );
        }

        return $this->s3Client;

    }

}