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

abstract class SafeNameEncoder implements SafeNameEncoderInterface
{
    /**
     * @param string $string
     *
     * @return bool
     */
    protected function isASafeString($string)
    {
        if (!preg_match('/^[a-zA-Z 0-9\/\!\-\_\.\'\*\(\)]*$/', $string)) {
            return false;
        }

        return true;
    }
}
