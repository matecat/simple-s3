<?php

namespace Matecat\SimpleS3\Components\Cache;

use Matecat\SimpleS3\Helpers\File;
use Predis\Client as Redis;

class RedisCache implements CacheInterface {
    /**
     * @var string
     */
    private string $prefixSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var Redis
     */
    private Redis $redisClient;

    /**
     * RedisCache constructor.
     *
     * @param Redis $redisClient
     */
    public function __construct( Redis $redisClient ) {
        $this->redisClient = $redisClient;
    }

    /**
     * @return bool
     */
    public function flushAll(): bool {
        $flush = $this->redisClient->flushall();

        if ( $flush->getPayload() === 'OK' ) {
            return true;
        }

        return false;
    }

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return mixed
     */
    public function get( string $bucket, string $keyname, ?string $version = null ): mixed {
        if ( null != $version ) {
            $keyname .= '<VERSION_ID:' . $version . '>';
        }

        return unserialize( $this->redisClient->hget( $this->getHashPrefix( $bucket, $keyname ), $keyname ) );
    }

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return bool
     */
    public function has( string $bucket, string $keyname, ?string $version = null ): bool {
        if ( null != $version ) {
            $keyname .= '<VERSION_ID:' . $version . '>';
        }

        return 1 === $this->redisClient->hexists( $this->getHashPrefix( $bucket, $keyname ), $keyname );
    }

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return bool
     */
    public function remove( string $bucket, string $keyname, ?string $version = null ): bool {
        if ( null != $version ) {
            $keyname .= '<VERSION_ID:' . $version . '>';
        }

        return $this->redisClient->hdel( $this->getHashPrefix( $bucket, $keyname ), [ $keyname ] ) === 1;
    }

    /**
     * @param string $bucket
     * @param string $keyname
     *
     * @return array
     */
    public function search( string $bucket, string $keyname ): array {
        return $this->redisClient->hkeys( $this->getHashPrefix( $bucket, $keyname ) );
    }

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param mixed       $content
     * @param string|null $version
     * @param int         $ttl
     *
     * @return int
     */
    public function set( string $bucket, string $keyname, mixed $content, ?string $version = null, int $ttl = 0 ): int {
        if ( null != $version ) {
            $keyname .= '<VERSION_ID:' . $version . '>';
        }

        $res = $this->redisClient->hset( $this->getHashPrefix( $bucket, $keyname ), $keyname, serialize( $content ) );

        if ( $this->ttl( $bucket, $keyname ) === -1 ) {
            return $this->redisClient->expire( $this->getHashPrefix( $bucket, $keyname ), ( null != $ttl ) ? $ttl * 60 : self::TTL_STANDARD );
        }

        return $res;
    }

    /**
     * @param string $separator
     */
    public function setPrefixSeparator( string $separator ): void {
        $this->prefixSeparator = $separator;
    }

    /**
     * @param string      $bucket
     * @param string      $keyname
     * @param string|null $version
     *
     * @return int
     */
    public function ttl( string $bucket, string $keyname, ?string $version = null ): int {
        if ( null != $version ) {
            $keyname .= '<VERSION_ID:' . $version . '>';
        }

        return $this->redisClient->ttl( $this->getHashPrefix( $bucket, $keyname ) );
    }

    /**
     * @param string $bucketName
     * @param string $keyName
     *
     * @return string
     */
    private function getHashPrefix( string $bucketName, string $keyName ): string {
        return hash( self::HASH_ALGORITHM, $bucketName . self::HASH_SAFE_SEPARATOR . $this->getDirName( $keyName ) );
    }

    /**
     * @param string $item
     *
     * @return string
     */
    private function getDirName( string $item ): string {
        if ( File::endsWith( $item, $this->prefixSeparator ) ) {
            return $item;
        }

        $fileInfo = File::getPathInfo( $item );

        return $fileInfo[ 'dirname' ] . $this->prefixSeparator;
    }
}
