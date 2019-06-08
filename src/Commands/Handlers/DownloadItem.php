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
use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class DownloadItem extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        try {
            $download = $this->client->getConn()->getObject([
                'Bucket'  => $bucketName,
                'Key'     => $keyName,
                'SaveAs'  => (isset($params['save_as'])) ? $params['save_as'] : $keyName,
            ]);

            if (($download instanceof ResultInterface) and $download['@metadata']['statusCode'] === 200) {
                $this->loggerWrapper->log(sprintf('\'%s\' was successfully downloaded from bucket \'%s\'', $keyName, $bucketName));

                return true;
            }

            $this->loggerWrapper->log(sprintf('Something went wrong during downloading \'%s\' from bucket \'%s\'', $keyName, $bucketName), 'warning');

            return false;
        } catch (S3Exception $e) {
            $this->loggerWrapper->logExceptionOrContinue($e);
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
