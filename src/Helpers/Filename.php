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

namespace Matecat\SimpleS3\Helpers;

/**
 * Class Filename
 * @package Matecat\SimpleS3\Helper
 *
 * This class check for string byte length and eventually cut it to a safe value of 221
 *
 * For file name limits, please see:
 *
 * https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingMetadata.html
 */
class Filename
{
    const FILENAME_BYTES_LIMIT = 255;

    /**
     * @param string $string
     *
     * @return string
     */
    public static function getSafe($string)
    {
        if (self::isValid($string)){
            return $string;
        }

        $pathInfo =  File::getPathInfo($string);
        $ext = File::getExtension($string);
        $tmp = '/tmp/5ddfab5e8a6aa9.45666312_.out.';

        $limit = self::FILENAME_BYTES_LIMIT - strlen($ext) - 1 - strlen($tmp);

        $filename = ($pathInfo['dirname']) ? $pathInfo['dirname'] . DIRECTORY_SEPARATOR : '';
        $filename .= $pathInfo['filename'];

        return substr($filename, 0, $limit) . '.'.$ext;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private static function isValid($string)
    {
        return strlen($string) <= self::FILENAME_BYTES_LIMIT;
    }
}

