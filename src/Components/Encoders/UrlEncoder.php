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

class UrlEncoder extends SafeNameEncoder
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function decode($string)
    {
        $decoded = [];

        foreach (explode(DIRECTORY_SEPARATOR, $string) as $word) {
            if (false === $this->isASafeString($word)) {
                $word = urldecode($word);
            }

            $decoded[] = $word;
        }

        return implode(DIRECTORY_SEPARATOR, $decoded);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function encode($string)
    {
        $encoded = [];

        foreach (explode(DIRECTORY_SEPARATOR, $string) as $word) {
            if (false === $this->isASafeString($word)) {
                $word = urlencode($word);
            }

            $encoded[] = $word;
        }

        return implode(DIRECTORY_SEPARATOR, $encoded);
    }
}
