<?php

namespace SimpleS3\Validators;

/**
 * Class S3BucketNameHelper
 *
 * This class check and create a valid Amazon S3 object Name
 *
 * Amazon S3 object safe naming Requirements
 * -------------------------------------------------------------------------
 * You can use any UTF-8 character in an object key name. However, using certain characters in key names may cause problems with some applications and protocols.
 * The following guidelines help you maximize compliance with DNS, web-safe characters, XML parsers, and other APIs.
 *
 * *** Alphanumeric characters ***
 * - 0-9
 * - a-z
 * - A-Z
 *
 * *** Special characters ***
 * - !
 * - -
 * - _
 * - .
 * - *
 * - '
 * - (
 * - )
 *
 * Directory separator / is allowed.
 *
 * Complete reference:
 *
 * https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html
 *
 * @package SimpleS3
 */
final class S3ObjectSafeNameValidator extends AbstractS3NameValidator
{
    /**
     * @param $string
     *
     * @return array
     */
    public static function validate($string)
    {
        $errors = [];

        $pattern = '/^[a-zA-Z 0-9\/\!\-\_\.\'\*\(\)]*$/';

        if (!preg_match($pattern, $string)) {
            $errors[] = 'The string contains a not allowed character';
        }

        return $errors;
    }
}
