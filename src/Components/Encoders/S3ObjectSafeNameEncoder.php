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

class S3ObjectSafeNameEncoder implements EncoderInterface
{
    /**
     * @var array
     */
    private static $unsafeChars = [
        '&',
        '$',
        '@',
        '=',
        ';',
        ':',
        '+',
        ' ',
        ',',
        '?',
        '\\',
        '{',
        '^',
        '}',
        '%',
        '`',
        ']',
        '"',
        '>',
        '[',
        '~',
        '<',
        '#',
        '|',
    ];

    /**
     * @var array
     */
    private static $safeChars = [
        'hBC0Cq',
        '4UjykZ',
        'q*WXO*',
        'AB9aLe',
        '7Pq5Ik',
        'SD(wxl',
        'nef6jx',
        'UvPRTI',
        'Mo!Ebv',
        'kT!4Ty',
        'Xr4wsc',
        '3gTZd4',
        'NOYZvh',
        '5*JX(X',
        'ZDfTN9',
        '75\'*H)',
        'OtdJXD',
        'q.XjZO',
        'PLV7Ce',
        'wL5Hoa',
        'nm)WWd',
        'DGiKr*',
        'QPWsEt',
        'QnN5SG',
    ];

    /**
     * @param string $string
     * @return mixed
     */
    public static function decode($string)
    {
        return str_replace(self::$safeChars, self::$unsafeChars, $string);
    }

    /**
     * @param string $string
     * @return mixed
     */
    public static function encode($string)
    {
        return str_replace(self::$unsafeChars, self::$safeChars, $string);
    }
}