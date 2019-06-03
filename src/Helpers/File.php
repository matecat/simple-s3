<?php

namespace SimpleS3\Helpers;

class File
{
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
     * @param string $filename
     *
     * @return false|int
     */
    public static function getSize($filename)
    {
        return filesize($filename);
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
}
