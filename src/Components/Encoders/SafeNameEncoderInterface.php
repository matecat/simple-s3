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

namespace Matecat\SimpleS3\Components\Encoders;

/**
 * Amazon S3 object safe naming Requirements
 * -------------------------------------------------------------------------
 * You can use any UTF-8 character in an object key name. However, using certain characters in key names may cause problems with some applications and protocols.
 * The following guidelines help you maximize compliance with DNS, web-safe characters, XML parsers, and other APIs.
 *
 * Allowed characters:
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
 * Directory separator / is also allowed.
 *
 * Complete reference:
 *
 * https://docs.aws.amazon.com/en_us/AmazonS3/latest/dev/UsingMetadata.html
 *
 * @package SimpleS3
 */
interface SafeNameEncoderInterface
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function decode($string);

    /**
     * @param string $string
     *
     * @return string
     */
    public function encode($string);
}
