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

namespace SimpleS3\Commands\Handlers;

use Aws\ResultInterface;
use Psr\Http\Message\UriInterface;
use SimpleS3\Commands\CommandHandler;
use SimpleS3\Components\Encoders\S3ObjectSafeNameEncoder;

class RestoreItem extends CommandHandler
{
    /**
     * Send a basic restore request for an archived copy of an object back into Amazon S3.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/restore-object.html
     *
     * @param array $params
     *
     * @return mixed|UriInterface
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];
        $days =(isset($params['days'])) ? $params['days'] : 5;
        $tier = (isset($params['tier'])) ? $params['tier'] : 'Expedited';

        $allowedTiers = [
            'Bulk',
            'Expedited',
            'Standard',
        ];

        if ($tier and !in_array($tier, $allowedTiers)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid tier value. Allowed values are: ['.implode(',', $allowedTiers).']', $tier));
        }

        try {
            $request = $this->client->getConn()->restoreObject([
                'Bucket' => $bucketName,
                'Key' => S3ObjectSafeNameEncoder::encode($keyName),
                'RestoreRequest' => [
                    'Days'       => $days,
                    'GlacierJobParameters' => [
                        'Tier'  => $tier,
                    ],
                ],
            ]);

            if (($request instanceof ResultInterface) and $request['@metadata']['statusCode'] === 202) {
                $this->commandHandlerLogger->log($this, sprintf('A request for restore \'%s\' item in \'%s\' bucket was successfully sended', $keyName, $bucketName));

                if ($this->client->hasCache()) {
                    $this->client->getCache()->set($bucketName, $keyName, '');
                }

                return true;
            }

            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during sending restore questo for \'%s\' item in \'%s\' bucket', $keyName, $bucketName), 'warning');
            }

            return false;
        } catch (\Exception $e) {
            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->logExceptionAndReturnFalse($e);
            }

            throw $e;
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (
            isset($params['bucket']) and
            isset($params['key'])
        );
    }
}
