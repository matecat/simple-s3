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

namespace Matecat\SimpleS3\Helpers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class File {
    /**
     * @param string $path
     *
     * @return bool
     */
    public static function checkIfIsADir( string $path ): bool {
        if ( str_contains( $path, DIRECTORY_SEPARATOR ) ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $string
     * @param string $separator
     *
     * @return bool
     */
    public static function endsWith( string $string, string $separator ): bool {
        return substr( $string, -1 ) === $separator;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function getBaseName( string $path ): string {
        if ( !self::checkIfIsADir( $path ) ) {
            return $path;
        }

        return self::getPathInfo( $path )[ 'basename' ];
    }

    /**
     * @param string $filename
     *
     * @return string|null
     */
    public static function getExtension( string $filename ): ?string {
        return self::getPathInfo( $filename )[ 'extension' ];
    }

    /**
     * @param string $filename
     * @param int    $mode
     *
     * @return string
     */
    public static function getMimeType( string $filename, int $mode = 0 ): string {
        // mode 0 = full check
        // mode 1 = extension check only

        $mimetype = '';

        $mime_types = [
                'txt'  => 'text/plain',
                'htm'  => 'text/html',
                'html' => 'text/html',
                'php'  => 'text/html',
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'json' => 'application/json',
                'xml'  => 'application/xml',
                'swf'  => 'application/x-shockwave-flash',
                'flv'  => 'video/x-flv',

            // images
                'png'  => 'image/png',
                'jpe'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg'  => 'image/jpeg',
                'gif'  => 'image/gif',
                'bmp'  => 'image/bmp',
                'ico'  => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif'  => 'image/tiff',
                'svg'  => 'image/svg+xml',
                'svgz' => 'image/svg+xml',

            // archives
                'zip'  => 'application/zip',
                'rar'  => 'application/x-rar-compressed',
                'exe'  => 'application/x-msdownload',
                'msi'  => 'application/x-msdownload',
                'cab'  => 'application/vnd.ms-cab-compressed',

            // audio/video
                'mp3'  => 'audio/mpeg',
                'qt'   => 'video/quicktime',
                'mov'  => 'video/quicktime',

            // adobe
                'pdf'  => 'application/pdf',
                'psd'  => 'image/vnd.adobe.photoshop',
                'ai'   => 'application/postscript',
                'eps'  => 'application/postscript',
                'ps'   => 'application/postscript',

            // ms office
                'doc'  => 'application/msword',
                'rtf'  => 'application/rtf',
                'xls'  => 'application/vnd.ms-excel',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'docx' => 'application/msword',
                'xlsx' => 'application/vnd.ms-excel',
                'pptx' => 'application/vnd.ms-powerpoint',


            // open office
                'odt'  => 'application/vnd.oasis.opendocument.text',
                'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if ( function_exists( 'mime_content_type' ) and $mode === 0 ) {
            return mime_content_type( $filename );
        }

        if ( function_exists( 'finfo_open' ) and $mode === 0 ) {
            $finfo = finfo_open( FILEINFO_MIME );

            if ( false !== $finfo ) {
                $mimetype = finfo_file( $finfo, $filename );
                finfo_close( $finfo );
            }

            return $mimetype;
        }

        $ext = self::getExtension( $filename );

        if ( null !== $ext and array_key_exists( $ext, $mime_types ) ) {
            return $mime_types[ $ext ];
        }

        return 'application/octet-stream';
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public static function getPathInfo( string $path ): array {
        return pathinfo( $path );
    }

    /**
     * @param string $filename
     *
     * @return false|int
     */
    public static function getSize( string $filename ): false|int {
        return filesize( $filename );
    }

    /**
     * @param string $url
     * @param bool   $sslVerify
     *
     * @return bool|string
     */
    public static function loadFile( string $url, bool $sslVerify = true ): bool|string {
        if ( function_exists( 'curl_version' ) ) {
            $ch = curl_init();

            $verifyPeer = $sslVerify ? 1 : 0;
            $verifyHost = $sslVerify ? 2 : 0;

            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, $verifyHost );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer );
            curl_setopt( $ch, CURLOPT_URL, $url );

            $data = curl_exec( $ch );
            curl_close( $ch );

            return $data;
        }

        $context = stream_context_create( [
                'ssl' => [
                        'verify_peer'      => $sslVerify,
                        'verify_peer_name' => $sslVerify,
                ]
        ] );

        return file_get_contents( $url, false, $context );
    }

    /**
     * @param string $filename
     * @param bool   $sslVerify
     *
     * @return bool|resource
     */
    public static function open( string $filename, bool $sslVerify = true ) {
        $context = stream_context_create( [
                'ssl' => [
                        'verify_peer'      => $sslVerify,
                        'verify_peer_name' => $sslVerify,
                ]
        ] );

        return fopen( $filename, 'r', false, $context );
    }

    /**
     * @param string $dir
     * @param bool   $removeItself
     */
    public static function cleanDir( string $dir, bool $removeItself = false ): void {
        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ( $files as $fileInfo ) {
            $todo = ( $fileInfo->isDir() ? 'rmdir' : 'unlink' );
            $todo( $fileInfo->getRealPath() );
        }

        if ( $removeItself ) {
            rmdir( $dir );
        }

    }
}
