<?php
/**
 *  This file is part of the Simple S3 package.
 *
 * (c) Mauro Cassani<https://github.com/mauretto78>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Matecat\SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class SetBucketPolicy extends CommandHandler {
    /**
     * Set policy for a bucket.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-bucket-policy.html?highlight=put%20policy
     *
     * @param array $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle( array $params = [] ): bool {
        $bucketName = $params[ 'bucket' ];
        $policy     = $params[ 'policy' ];

        $config = [
                'Bucket' => $bucketName,
                'Policy' => $policy,
        ];

        if ( isset( $params[ 'access' ] ) ) {
            $config[ 'ConfirmRemoveSelfBucketAccess' ] = $params[ 'access' ];
        }

        if ( isset( $params[ 'md5' ] ) ) {
            $config[ 'ContentMD5' ] = $params[ 'md5' ];
        }

        try {
            $policy = $this->client->getConn()->putBucketPolicy( $config );

            if ( ( $policy instanceof ResultInterface ) and $policy[ '@metadata' ][ 'statusCode' ] === 204 ) {
                $this->commandHandlerLogger?->log( $this, sprintf( 'Policy was successfully set for bucket \'%s\'', $bucketName ) );

                return true;
            }

            $this->commandHandlerLogger?->log( $this, sprintf( 'Something went wrong while setting policy of bucket \'%s\'', $bucketName ), 'warning' );

            return false;
        } catch ( Exception $e ) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse( $e );

            throw $e;
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams( array $params = [] ): bool {
        return (
                isset( $params[ 'bucket' ] ) and
                isset( $params[ 'policy' ] )
        );
    }
}
