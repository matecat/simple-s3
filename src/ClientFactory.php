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

namespace SimpleS3;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;

/**
 * Class ClientFactory
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
     * @param array $config
     *
     * @return S3Client
     */
    public static function create(array $config = [])
    {
        self::validateConfig($config);

        return new S3Client(self::createConfigArray($config));
    }

    /**
     * @param string $accessKeyId
     * @param string $secretKey
     * @param array $config
     *
     * @return array
     */
    private static function createConfigArray(array $config)
    {
        $credentials = self::getCredentials($config);
        if (!empty($credentials)) {
            $config['credentials'] = new Credentials(
                $credentials['key'],
                $credentials['secret'],
                $credentials['token']
            );
        }

        return $config;
    }

    /**
     * @param array $config
     */
    private static function validateConfig(array $config)
    {
        $allowedKeys = [
            'api_provider',
            'credentials',
            'debug',
            'endpoint',
            'endpoint_provider',
            'endpoint_discovery',
            'handler',
            'http',
            'http_handler',
            'iam',
            'profile',
            'region',
            'retries',
            'scheme',
            'service',
            'signature_provider',
            'signature_version',
            'stats',
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

    /**
     * @param array $config
     *
     * @return array
     */
    private static function getCredentials(array $config)
    {
        // 1. credentials
        if (isset($config['credentials']['key']) and isset($config['credentials']['secret'])) {
            return [
                    'key'    => $config['credentials']['key'],
                    'secret' => $config['credentials']['secret'],
                    'token'  => isset($config['credentials']['token']) ? $config['credentials']['token'] : null
            ];
        }

        // 2. IAM
        if (isset($config['iam'])) {
            $stsClient = new StsClient([
                'profile' => (isset($config['profile'])) ? $config['profile'] : 'default',
                'region' => $config['region'],
                'version' => $config['version']
            ]);

            $result = $stsClient->AssumeRole([
                'RoleArn' => $config['iam']['arn'],
                'RoleSessionName' => $config['iam']['session'],
            ]);

            return [
                'key'    => $result['Credentials']['AccessKeyId'],
                'secret' => $result['Credentials']['SecretAccessKey'],
                'token'  => isset($result['Credentials']['SessionToken']) ? $result['Credentials']['SessionToken'] : null
            ];
        }

        // 3. env
        if (false !== getenv('AWS_ACCESS_KEY_ID') and false !== getenv('AWS_SECRET_ACCESS_KEY')) {
            return [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                'token'  => (false !== getenv('AWS_SESSION_TOKEN')) ? getenv('AWS_SESSION_TOKEN') : null
            ];
        }

        return [];
    }
}
