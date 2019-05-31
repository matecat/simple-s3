<?php

namespace SimpleS3\Validators;

/**
 * This class check and create a valid Amazon S3 Storage Class name
 *
 * Complete reference:
 *
 * https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/storage-class-intro.html
 *
 * @package SimpleS3
 */
final class S3StorageClassNameValidator extends S3NameValidator
{
    /**
     * @param string $string
     *
     * @return array
     */
    public static function validate($string)
    {
        $errors = [];

        $allowedStorageClasses = [
            'STANDARD',
            'REDUCED_REDUNDANCY',
            'STANDARD_IA',
            'ONEZONE_IA',
            'INTELLIGENT_TIERING',
            'GLACIER',
            'DEEP_ARCHIVE',
        ];

        if (!in_array($string, $allowedStorageClasses)) {
            $errors[] = 'The string is not a valid StorageClass';
        }

        return $errors;
    }
}
