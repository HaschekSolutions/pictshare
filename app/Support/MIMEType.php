<?php

namespace App\Support;

/**
 * Class MIMEType
 * @package App\Support
 */
class MIMEType
{
    const DEFAULT_MIME_TYPE = 'application/octet-stream';

    /**
     * @var array List of extensions and corresponding MIME types
     */
    public static $mimeTypesByExtension = [
        '.aac'   => 'audio/aac',
        '.abw'   => 'application/x-abiword',
        '.arc'   => 'application/octet-stream',
        '.avi'   => 'video/x-msvideo',
        '.azw'   => 'application/vnd.amazon.ebook',
        '.bin'   => 'application/octet-stream',
        '.bz'    => 'application/x-bzip',
        '.bz2'   => 'application/x-bzip2',
        '.csh'   => 'application/x-csh',
        '.css'   => 'text/css',
        '.csv'   => 'text/csv',
        '.doc'   => 'application/msword',
        '.dot'   => 'application/msword',
        '.docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        '.dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        '.docm'  => 'application/vnd.ms-word.document.macroEnabled.12',
        '.dotm'  => 'application/vnd.ms-word.template.macroEnabled.12',
        '.eot'   => 'application/vnd.ms-fontobject',
        '.epub'  => 'application/epub+zip',
        '.gif'   => 'image/gif',
        '.htm'   => 'text/html',
        '.html'  => 'text/html',
        '.ico'   => 'image/x-icon',
        '.ics'   => 'text/calendar',
        '.jar'   => 'application/java-archive',
        '.jpeg'  => 'image/jpeg',
        '.jpg'   => 'image/jpeg',
        '.js'    => 'application/javascript',
        '.json'  => 'application/json',
        '.mdb'   => 'application/vnd.ms-access',
        '.mid'   => 'audio/midi',
        '.midi'  => 'audio/midi',
        '.mpeg'  => 'video/mpeg',
        '.mpkg'  => 'application/vnd.apple.installer+xml',
        '.odp'   => 'application/vnd.oasis.opendocument.presentation',
        '.ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        '.odt'   => 'application/vnd.oasis.opendocument.text',
        '.oga'   => 'audio/ogg',
        '.ogv'   => 'video/ogg',
        '.ogx'   => 'application/ogg',
        '.otf'   => 'font/otf',
        '.png'   => 'image/png',
        '.pdf'   => 'application/pdf',
        '.ppt'   => 'application/vnd.ms-powerpoint',
        '.pot'   => 'application/vnd.ms-powerpoint',
        '.pps'   => 'application/vnd.ms-powerpoint',
        '.ppa'   => 'application/vnd.ms-powerpoint',
        '.pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        '.potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        '.ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        '.ppam'  => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        '.pptm'  => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        '.potm'  => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        '.ppsm'  => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        '.rar'   => 'application/x-rar-compressed',
        '.rtf'   => 'application/rtf',
        '.sh'    => 'application/x-sh',
        '.svg'   => 'image/svg+xml',
        '.swf'   => 'application/x-shockwave-flash',
        '.tar'   => 'application/x-tar',
        '.tif'   => 'image/tiff',
        '.tiff'  => 'image/tiff',
        '.ts'    => 'application/typescript',
        '.ttf'   => 'font/ttf',
        '.vsd'   => 'application/vnd.visio',
        '.wav'   => 'audio/x-wav',
        '.weba'  => 'audio/webm',
        '.webm'  => 'video/webm',
        '.webp'  => 'image/webp',
        '.woff'  => 'font/woff',
        '.woff2' => 'font/woff2',
        '.xhtml' => 'application/xhtml+xml',
        '.xls'   => 'application/vnd.ms-excel',
        '.xlt'   => 'application/vnd.ms-excel',
        '.xla'   => 'application/vnd.ms-excel',
        '.xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        '.xlsm'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        '.xltm'  => 'application/vnd.ms-excel.template.macroEnabled.12',
        '.xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
        '.xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        '.xml'   => 'application/xml',
        '.xul'   => 'application/vnd.mozilla.xul+xml',
        '.zip'   => 'application/zip',
        //'.3gp'   => ['video/3gpp', 'audio/3gpp'],
        //'.3g2'   => ['video/3gpp2', 'audio/3gpp2'],
        '.7z'    => 'application/x-7z-compressed'
    ];

    /**
     * @param string $extension
     *
     * @return string
     */
    public static function getMimeTypeFromExtension($extension)
    {
        if ($extension[0] !== '.') {
            $extension = '.' . $extension;
        }

        if (isset(static::$mimeTypesByExtension[$extension])) {
            return static::$mimeTypesByExtension[$extension];
        }

        // return default
        return static::DEFAULT_MIME_TYPE;
    }

    /**
     * @param string $mimeType
     * @param bool   $withDot
     *
     * @return string
     */
    public static function getExtensionFromMimeType($mimeType, $withDot = false)
    {
        $extensionsByMimeTypes = array_flip(static::$mimeTypesByExtension);

        if (isset($extensionsByMimeTypes[$mimeType])) {
            if ($withDot) {
                return $extensionsByMimeTypes[$mimeType];
            }

            return substr($extensionsByMimeTypes[$mimeType], 1);
        }

        return null;
    }

    /**
     * @param string$mimeType
     *
     * @return bool
     */
    public static function isValidMimeType($mimeType)
    {
        return in_array($mimeType, static::$mimeTypesByExtension, false);
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public static function isValidExtension($extension)
    {
        if ($extension[0] !== '.') {
            $extension = '.' . $extension;
        }

        return in_array($extension, array_keys(static::$mimeTypesByExtension), false);
    }
}
