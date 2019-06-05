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

            if($this->client->hasCache()){
                var_dump(
                    $this->getFromCache($bucketName, (isset($config['Prefix'])) ? $config['Prefix']: null)
                );
            }

            $resultPaginator = $this->client->getConn()->getPaginator('ListObjects', $config);

            $filesArray = [];
            foreach ($resultPaginator as $result) {
                for ($i = 0; $i < count($contents = $result->get('Contents')); $i++){
                    $key = $contents[$i]['Key'];

                    if (isset($params['hydrate']) and true === $params['hydrate']) {
                        $filesArray[$key] = $this->client->getItem(['bucket' => $bucketName, 'key' => $key]);
                    } else {
                        $filesArray[] = $key;
                    }
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
