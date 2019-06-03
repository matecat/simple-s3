<?php

namespace SimpleS3\Commands\Handlers;

use SimpleS3\Commands\CommandHandler;

class GetBucketSize extends CommandHandler
{
    /**
     * @param array $params
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $size = 0;

        $config = [
            'Bucket' => $bucketName,
        ];

        if (isset($params['prefix'])) {
            $config['Delimiter'] = DIRECTORY_SEPARATOR;
            $config['Prefix'] = $params['prefix'];
        }

        $objectIterator = $this->client->getConn()->getIterator('ListObjects', $config);
        foreach ($objectIterator as $object) {
            $size += $object['Size'];
        }

        $this->log(sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));

        return $size;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function validateParams($params = [])
    {
        return isset($params['bucket']);
    }
}
