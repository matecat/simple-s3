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

use Exception;
use Matecat\SimpleS3\Commands\CommandHandler;

class ClearBucket extends CommandHandler
{
    /**
     * Clear a bucket.
     *
     * @param array{bucket: string} $params
     *
     * @return bool
     * @throws Exception
     */
    public function handle(array $params = ['bucket' => 'no-bucket-provided']): bool
    {
        $bucketName = $params[ 'bucket' ];

        if (false === $this->client->hasBucket(['bucket' => $bucketName])) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' does not exists', $bucketName), 'warning');

            return false;
        }

        // Always use the versioned cleanup path.
        // listObjectVersions + deleteObjects works correctly for both
        // versioned and non-versioned buckets, and avoids relying on
        // isBucketVersioned which can return false under eventual consistency.
        return $this->clearVersionedBucket($bucketName);
    }


    /**
     * Delete all object versions and delete markers from a versioned bucket.
     *
     * Uses the raw S3 client directly to avoid encoder round-trip issues
     * and handles both Versions and DeleteMarkers in paginated results.
     *
     * @param string $bucketName
     *
     * @return bool
     * @throws Exception
     */
    private function clearVersionedBucket(string $bucketName): bool
    {
        $errors        = [];
        $conn          = $this->client->getConn();
        $previousCount = PHP_INT_MAX;

        // Paginate through listObjectVersions, deleting in batches.
        // Break when empty or when no progress is made (to avoid infinite loops).
        while (true) {
            $result  = $conn->listObjectVersions(['Bucket' => $bucketName]);
            $objects = [];

            foreach (['Versions', 'DeleteMarkers'] as $entryType) {
                $entries = $result[ $entryType ] ?? [];

                if (false === is_array($entries)) {
                    continue;
                }

                foreach ($entries as $entry) {
                    if (false === isset($entry[ 'Key' ])) {
                        continue;
                    }

                    $obj = ['Key' => $entry[ 'Key' ]];

                    // Only include VersionId for real versions.
                    // The literal string "null" is returned for non-versioned objects;
                    // passing it would require s3:DeleteObjectVersion permission.
                    if (isset($entry[ 'VersionId' ]) && $entry[ 'VersionId' ] !== 'null') {
                        $obj[ 'VersionId' ] = $entry[ 'VersionId' ];
                    }

                    $objects[] = $obj;
                }
            }

            $currentCount = count($objects);

            if ($currentCount === 0) {
                break;
            }

            // No progress since last iteration — stop to prevent infinite loop
            if ($currentCount >= $previousCount) {
                $this->commandHandlerLogger?->log(
                        $this,
                        sprintf('No progress clearing bucket \'%s\': %d objects remain', $bucketName, $currentCount),
                        'warning'
                );

                return false;
            }

            $previousCount = $currentCount;

            // Batch delete up to 1000 objects at a time (S3 limit)
            foreach (array_chunk($objects, 1000) as $chunk) {
                try {
                    $response = $conn->deleteObjects([
                            'Bucket' => $bucketName,
                            'Delete' => [
                                    'Objects' => $chunk,
                                    'Quiet'   => true,
                            ],
                    ]);

                    // Even with Quiet mode, S3 returns individual errors
                    $deleteErrors = $response[ 'Errors' ] ?? [];
                    if (is_array($deleteErrors) && count($deleteErrors) > 0) {
                        foreach ($deleteErrors as $err) {
                            $errors[] = $err;
                            $this->commandHandlerLogger?->log(
                                    $this,
                                    sprintf('Failed to delete \'%s\' (version: %s) from \'%s\': %s',
                                            $err[ 'Key' ] ?? 'unknown',
                                            $err[ 'VersionId' ] ?? 'unknown',
                                            $bucketName,
                                            $err[ 'Message' ] ?? 'unknown error'
                                    ),
                                    'warning'
                            );
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $this->commandHandlerLogger?->log(
                            $this,
                            sprintf('Error batch-deleting from \'%s\': %s', $bucketName, $e->getMessage()),
                            'warning'
                    );
                }
            }

            // Also clean up the cache entries
            if ($this->client->hasCache()) {
                foreach ($objects as $obj) {
                    $this->client->getCache()->remove($bucketName, $obj[ 'Key' ], $obj[ 'VersionId' ] ?? null);
                }
            }
        }

        if (count($errors) === 0) {
            $this->commandHandlerLogger?->log($this, sprintf('Bucket \'%s\' was successfully cleared', $bucketName));

            return true;
        }

        $this->commandHandlerLogger?->log($this, sprintf('Something went wrong while clearing bucket \'%s\'', $bucketName), 'warning');

        return false;
    }

    /**
     * @param array<string, string> $params
     *
     * @return bool
     */
    public function validateParams(array $params = []): bool
    {
        return isset($params[ 'bucket' ]);
    }
}
