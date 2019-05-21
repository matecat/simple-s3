<?php

namespace SimpleS3\Validators;

use SimpleS3\Validators\Contracts\S3NameValidatorInterface;

abstract class AbstractS3NameValidator implements S3NameValidatorInterface
{
    /**
     * @param $string
     *
     * @return bool
     */
    public static function isValid($string)
    {
        return (count(static::validate($string)) === 0);
    }
}
