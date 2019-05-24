<?php

namespace SimpleS3;

use Aws\AwsClient;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

/**
 * Class clientFactory
 *
 * User: Mauro Cassani
 * Date: 15/05/19
 * Time: 10:00
 *
 * This class is a simple factory for S3/Client
 *
 * List of options:
 * - api_provider
 * - credentials
 * - debug
 * - stats
 * - endpoint
 * - endpoint_provider
 * - endpoint_discovery
 * - handler
 * - http
 * - http_handler
 * - profile
 * - region
 * - retries
 * - scheme
 * - service
 * - signature_provider
 * - signature_version
 * - ua_append
 * - validate
 * - version
 *
 * Please see the complete config documentation here:
 *
 * https://docs.aws.amazon.com/en_us/sdk-for-php/v3/developer-guide/guide_configuration.html
 *
 * @package SimpleS3
 */
final class ClientFactory
{
    /**
     * @return AwsClient
     */
    public static function create(
        $accessKeyId,
        $secretKey,
        array $config = []
    ) {
        self::validateConfig($config);

        return new S3Client(self::createConfigArray($accessKeyId, $secretKey, $config));
    }

    /**
     * @param       $accessKeyId
     * @param       $secretKey
     * @param array $config
     *
     * @return array
     */
    private static function createConfigArray($accessKeyId, $secretKey, array $config)
    {
        $credentials = new Credentials($accessKeyId, $secretKey);
        $config['credentials'] = $credentials;

        return $config;
    }

    /**
     * @param array $config
     */
    private static function validateConfig(array $config)
    {
        $allowedKeys = [
            'api_provider',
            'debug',
            'stats',
            'endpoint',
            'endpoint_provider',
            'endpoint_discovery',
            'handler',
            'http',
            'http_handler',
            'profile',
            'region',
            'retries',
            'scheme',
            'service',
            'signature_provider',
            'signature_version',
            'ua_append',
            'validate',
            'version',
        ];
        
        foreach (array_keys($config) as $key) {
            if (!in_array($key, $allowedKeys)) {
                throw new \InvalidArgumentException(sprintf('%s is not an allowed key', $key));
            }
        }
    }
}
