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
use Aws\S3\Exception\S3Exception;
use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;
use Matecat\SimpleS3\Components\Validators\S3BucketNameValidator;
use Matecat\SimpleS3\Exceptions\InvalidS3NameException;

class CreateBucketIfItDoesNotExist extends CommandHandler
{
    /**
     * Create a bucket if it does not exists.
     * For a complete reference:
     *
     *
     *
     * @param array<string, mixed> $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = []): bool
    {
        $bucketName = $params[ 'bucket' ];

        if (false === S3BucketNameValidator::isValid($bucketName)) {
            throw new InvalidS3NameException(sprintf('%s is not a valid bucket name. [' . implode(', ', S3BucketNameValidator::validate($bucketName)) . ']', $bucketName));
        }

        if (true === $this->client->hasBucket(['bucket' => $bucketName])) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' already exists', $bucketName), 'warning');

            return false;
        }

        try {
            $bucket = $this->client->getConn()->createBucket([
                    'Bucket' => $bucketName
            ]);

            if (isset($params[ 'rules' ]) and count($params[ 'rules' ]) > 0) {
                $this->client->setBucketLifecycleConfiguration(['bucket' => $bucketName, 'rules' => $params[ 'rules' ]]);
            }

            if (isset($params[ 'accelerate' ]) and true === $params[ 'accelerate' ]) {
                $this->client->enableAcceleration(['bucket' => $bucketName]);
            }

            if (($bucket instanceof ResultInterface) and $bucket[ '@metadata' ][ 'statusCode' ] === 200) {
                $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' was successfully created', $bucketName));

                return true;
            }

            $this->commandHandlerLogger?->log($this, sprintf('Something went wrong during creation of bucket \'%s\'', $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->commandHandlerLogger?->logExceptionAndReturnFalse($e);

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return isset($params[ 'bucket' ]);
    }
}
