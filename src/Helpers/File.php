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

namespace SimpleS3\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class File
{
    /**
     * @param string $path
     *
     * @return array
     */
    public static function getBaseName($path)
    {
        return self::getPathInfo($path)['basename'];
    }

    /**
     * @param string $filename
     * @param int $mode
     *
     * @return mixed|string
     */
    public static function getMimeType($filename, $mode = 0)
    {
        // mode 0 = full check
        // mode 1 = extension check only

        $mimetype = '';

        $mime_types = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',


            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if (function_exists('mime_content_type') and $mode === 0) {
            $mimetype = mime_content_type($filename);

            return $mimetype;
        }

        if (function_exists('finfo_open') and $mode === 0) {
            $finfo = finfo_open(FILEINFO_MIME);

            if (false !== $finfo) {
                $mimetype = finfo_file($finfo, $filename);
                finfo_close($finfo);
            }

            return $mimetype;
        }

        $ext = self::getExtension($filename);

        if (null !== $ext and array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }

        return 'application/octet-stream';
    }

    /**
     * @param string $filename
     *
     * @return string|null
     */
    public static function getExtension($filename)
    {
        $filenameArray = explode('.', $filename);
        $filenameArray = array_pop($filenameArray);

        if (null !== $filenameArray) {
            return strtolower($filenameArray);
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public static function getPathInfo($path)
    {
        return pathinfo($path);
    }

    /**
     * @param string $filename
     *
     * @return false|int
     */
    public static function getSize($filename)
    {
        return filesize($filename);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function endsWithSlash($string)
    {
        return substr($string, -1) === DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    public static function loadFile($url, $sslVerify = true)
    {
        if (function_exists('curl_version')) {
            $ch = curl_init();

            $verifyPeer = (true == $sslVerify) ? 1 : 0;
            $verifyHost = (true == $sslVerify) ? 2 : 0;

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifyHost);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);
            curl_setopt($ch, CURLOPT_URL, $url);

            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => (($sslVerify)) ? $sslVerify : true,
                'verify_peer_name' => (($sslVerify)) ? $sslVerify : true,
            ]
        ]);

        return file_get_contents($url, false, $context);
    }

    /**
     * @param string $filename
     * @param bool $sslVerify
     *
     * @return bool|resource
     */
    public static function open($filename, $sslVerify = true)
    {
        $context = stream_context_create([
           'ssl' => [
                'verify_peer' => (($sslVerify)) ? $sslVerify : true,
                'verify_peer_name' => (($sslVerify)) ? $sslVerify : true,
           ]
        ]);

        return fopen($filename, 'r', false, $context);
    }

    /**
     * @param string $dir
     */
    public static function removeDir($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($dir);
    }



    /**
     * @param string $string
     *
     * @return string
     */
    public static function strToHex($string){
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++){
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0'.$hexCode, -2);
        }

        return strToUpper($hex);
    }

    /**
     * @param string $hex
     *
     * @return string
     */
    public static function hexToStr($hex){
        $string = '';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }

        return $string;
    }
}
