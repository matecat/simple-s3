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
class FilenameTrimmer
{
    /**
     * @var int
     */
    private $max_size;

    /**
     * @var int
     */
    private $safe_size;

    /**
     * FilenameTrimmer constructor.
     *
     * @param null $max_size
     */
    public function __construct($max_size = null)
    {
        $this->max_size = ($max_size and $max_size > 34) ? $max_size : 255;
        $this->safe_size = $this->max_size - 34;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function trim($string)
    {
        if (false === self::hasToBeReduced($string)) {
            return $string;
        }

        $pathInfo =  File::getPathInfo($string);
        $ext = File::getExtension($string);
        $limit = $this->max_size - strlen($ext) - 35;

        $filename = ($pathInfo['dirname']) ? $pathInfo['dirname'] . DIRECTORY_SEPARATOR : '';
        $filename .= $pathInfo['filename'];

        return substr($filename, 0, $limit) . '.'.$ext;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function hasToBeReduced($string)
    {
        return strlen($string) > $this->safe_size;
    }
}
