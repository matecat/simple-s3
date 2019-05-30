<?php

namespace SimpleS3\Validators\Contracts;

interface S3NameValidatorInterface
{
    /**
     * @param string $string
     *
     * @return array
     */
    public static function validate($string);
}
