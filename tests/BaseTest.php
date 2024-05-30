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
use PHPUnit_Framework_TestCase;

abstract class BaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Client
     */
    protected $s3Client;

    protected $base_bucket_name = 'matecat-phpunit-tests-s3-3';

    /**
     * @return Client
     */
    public function getClient() {

        parent::setUp();

        $config = [];
        if ( file_exists( __DIR__ . '/../config/credentials.ini' ) ) {
            $config = parse_ini_file( __DIR__ . '/../config/credentials.ini' );
        }

        if ( !empty( getenv( 'AWS_ACCESS_KEY_ID' ) ) && !empty( getenv( 'AWS_SECRET_ACCESS_KEY' ) ) ) {

            $this->s3Client = new Client( [] );

        } elseif ( !empty( $config[ 'AWS_ACCESS_KEY_ID' ] ) && !empty( $config[ 'AWS_SECRET_ACCESS_KEY' ] ) ) {
            $this->s3Client = new Client(
                    [
                            'version'     => $config[ 'VERSION' ],
                            'region'      => $config[ 'REGION' ],
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