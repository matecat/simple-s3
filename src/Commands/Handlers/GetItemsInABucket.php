<?php

namespace SimpleS3\Commands\Handlers;

use Aws\S3\Exception\S3Exception;
use SimpleS3\Commands\CommandHandler;

class GetItemsInABucket extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];

        try {
            $config = [
                'Bucket' => $bucketName,
            ];

            if (isset($params['prefix'])) {
                $config['Delimiter'] = DIRECTORY_SEPARATOR;
                $config['Prefix'] = $params['prefix'];
            }

            $results = $this->client->getConn()->getIterator('ListObjects', $config);

            $filesArray = [];

            foreach ($results as $result) {
                if (isset($params['hydrate']) and true ===$params['hydrate']) {
                    $filesArray[$result['Key']] = $this->client->getItem(['bucket' => $bucketName, 'key' => $result[ 'Key' ]]);
                } else {
                    $filesArray[] = $result['Key'];
                }
            }

            $this->log(sprintf('Files were successfully obtained from \'%s\' bucket', $bucketName));

            return $filesArray;
        } catch (S3Exception $e) {
            $this->logExceptionOrContinue($e);
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return (isset($params['bucket']));
    }
}
