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

namespace SimpleS3\Components\Validators;

use SimpleS3\Components\Validators\Contracts\S3NameValidatorInterface;

abstract class S3NameValidator implements S3NameValidatorInterface
{
    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isValid($string)
    {
        return (count(static::validate($string)) === 0);
    }
}
