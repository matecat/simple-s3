<?php

namespace SimpleS3\Helper;

use SimpleS3\Exceptions\InvalidS3BucketNameException;

/**
 * Class S3BucketNameHelper
 *
 * User: Mauro Cassani
 * Date: 15/05/19
 * Time: 10:00
 *
 * This class check and create a valid Amazon S3 Bucket Name
 *
 * Amazon S3 Bucket Naming Requirements
 * -------------------------------------------------------------------------
 * The Amazon S3 bucket that you use to store CloudTrail log files must have a name that conforms with naming requirements for non-US Standard regions. Amazon S3 defines a bucket name as a series of one or more labels, separated by periods, that adhere to the following rules:
 *
 * - The bucket name can be between 3 and 63 characters long, and can contain only lower-case characters, numbers, periods, and dashes.
 * - Each label in the bucket name must start with a lowercase letter or number.
 * - The bucket name cannot contain underscores, end with a dash, have consecutive periods, or use dashes adjacent to periods.
 * - The bucket name cannot be formatted as an IP address (198.51.100.24).
 *
 * https://docs.aws.amazon.com/en_us/awscloudtrail/latest/userguide/cloudtrail-s3-bucket-naming-requirements.html
 *
 * @package SimpleS3
 */
final class S3BucketNameValidator
{
    const SEPARATOR = '-';

    /**
     * @param $string
     *
     * @return string
     * @throws InvalidS3BucketNameException
     */
    public static function generateFromString($string)
    {
        if (count($errors = self::validate($string)) > 0) {
            throw new InvalidS3BucketNameException(self::renderErrors($string, $errors));
        }

        return $string;
    }

    /**
     * @param $string
     *
     * @return array
     */
    private static function validate($string)
    {
        $errors = [];

        if (strlen($string) < 3) {
            $errors[] = 'The string is too short';
        }

        if (strlen($string) > 64) {
            $errors[] = 'The string is too long';
        }

        if (filter_var($string, FILTER_VALIDATE_IP)) {
            $errors[] = 'The string is an IP address';
        }

        if (strtolower($string) !== $string) {
            $errors[] = 'The string contains capital letters';
        }

        if (preg_match('/[^a-z.\-0-9]/i', $string)) {
            $errors[] = 'The string contains a not allowed character';
        }

        if (substr($string, -1) === '-') {
            $errors[] = 'The string ends with a -';
        }

        $notAllowedCharacterCombinations = [
                '..',
                '-.',
                '.-'
        ];

        foreach ($notAllowedCharacterCombinations as $allowedCharacterCombination) {
            if (strpos($string, $allowedCharacterCombination)) {
                $errors[] = 'The string contains a not allowed character combination';
            }
        }

        return $errors;
    }

    /**
     * @param $string
     * @param $errors
     *
     * @return string
     */
    private static function renderErrors($string, $errors)
    {
        $message = sprintf('%s is not a valid S3 bucket name', $string);
        $message .= ' [';
        $message .= implode(',', $errors);
        $message .= ']';

        return $message;
    }
}
