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

namespace SimpleS3\Components\Encoders;

interface EncoderInterface
{
    /**
     * @param string $string
     * @return mixed
     */
    public static function decode($string);

    /**
     * @param string $string
     * @return mixed
     */
    public static function encode($string);
}