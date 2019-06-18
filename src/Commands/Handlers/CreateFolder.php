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
use SimpleS3\Helpers\File;

class CreateFolder extends CommandHandler
{
    /**
     * Create a folder.
     * For a complete reference:
     * https://docs.aws.amazon.com/cli/latest/reference/s3api/put-object.html?highlight=put
     *
     * @param mixed $params
     *
     * @return bool
     * @throws \Exception
     */
    public function handle($params = [])
    {
        $bucketName = $params['bucket'];
        $keyName = $params['key'];

        if($this->client->hasEncoder()){
            $keyName = $this->client->getEncoder()->encode($keyName);
        }

        if (false === File::endsWithSlash($keyName)) {
            $keyName .= DIRECTORY_SEPARATOR;
        }

        try {
            $folder = $this->client->getConn()->putObject([
                'Bucket' => $bucketName,
                'Key'    => $keyName,
                'Body'   => '',
                'ACL'    => 'public-read'
            ]);

            if (($folder instanceof ResultInterface) and $folder['@metadata']['statusCode'] === 200) {
                if(null !== $this->commandHandlerLogger){
                    $this->commandHandlerLogger->log($this, sprintf('Folder \'%s\' was successfully created in \'%s\' bucket', $keyName, $bucketName));
                }

                return true;
            }

            if(null !== $this->commandHandlerLogger){
                $this->commandHandlerLogger->log($this, sprintf('Something went wrong during creation of \'%s\' folder inside \'%s\' bucket', $keyName, $bucketName), 'warning');
            }

            return false;
        } catch (S3Exception $e) {
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
