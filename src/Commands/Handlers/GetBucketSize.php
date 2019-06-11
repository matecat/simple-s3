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

        $items = $this->client->getItemsInABucket([
            'bucket' => $bucketName,
            'prefix' => (isset($params['prefix'])) ? $params['prefix'] : null,
            'hydrate' => true
        ]);

        foreach ($items as $item){
            $size += $item['Size'];
        }

//        $resultPaginator = $this->client->getConn()->getPaginator('ListObjects', $config);
//        foreach ($resultPaginator as $result) {
//            for ($i = 0; $i < count($contents = $result->get('Contents')); $i++) {
//                $size += $contents[$i]['Size'];
//            }
//        }

        $this->loggerWrapper->log(sprintf('Size of \'%s\' bucket was successfully obtained', $bucketName));

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
