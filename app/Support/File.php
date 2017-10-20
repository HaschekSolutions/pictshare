<?php

namespace App\Support;

/**
 * Class File
 * @package App\Support
 *
 * TODO: Find a better name...
 */
class File
{
    /**
     * @param string $hash
     *
     * @return bool
     */
    public static function isFile($hash)
    {
        if (!$hash) {
            return false;
        }
        return static::hashExists($hash);
    }

    /**
     * @param mixed $var
     *
     * @return bool
     */
    public static function isSize($var)
    {
        if (is_numeric($var)) {
            return true;
        }
        $a = explode('x', $var);
        if (count($a) != 2 || !is_numeric($a[0]) || !is_numeric($a[1])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    public static function isRotation($var)
    {
        switch ($var) {
            case 'upside':
            case 'left':
            case 'right':
                return true;

            default:
                return false;
        }
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    public static function isFilter($var)
    {
        if (strpos($var, '_')) {
            $a   = explode('_', $var);
            $var = $a[0];
            $val = $a[1];
            if (!is_numeric($val)) {
                return false;
            }
        }

        switch ($var) {
            case 'negative':
            case 'grayscale':
            case 'brightness':
            case 'edgedetect':
            case 'smooth':
            case 'contrast':
            case 'blur':
            case 'sepia':
            case 'sharpen':
            case 'emboss':
            case 'cool':
            case 'light':
            case 'aqua':
            case 'fuzzy':
            case 'boost':
            case 'gray':
            case 'pixelate':
                return true;

            default:
                return false;
        }
    }

    /**
     * @param string $val
     *
     * @return array|bool
     */
    public static function isLegacyThumbnail($val)
    {
        if (strpos($val, '_')) {
            $a    = explode('_', $val);
            $size = $a[0];
            $hash = $a[1];
            if (! static::isSize($size) || ! static::isFile($hash)) {
                return false;
            }

            return ['hash' => $hash, 'size' => $size];
        } else {
            return false;
        }
    }

    /**
     * @param static string $hash
     *
     * @return bool
     */
    public static function hashExists($hash)
    {
        return is_dir(root_path('upload/' . $hash));
    }

    /**
     * @param string $type
     * @param int    $length
     *
     * @return string
     */
    public static function getNewHash($type, $length = 10)
    {
        while (1) {
            $hash = String::getRandomString($length) . '.' . $type;
            if (! static::hashExists($hash)) {
                return $hash;
            }
        }
    }

    /**
     * @param $hash
     *
     * @return string
     */
    public static function getType($hash)
    {
        if ($hash && static::hashExists($hash)) {
            return mime_content_type($hash);
        }

        return null;
    }

    /**
     * @param string $filename
     *
     * @return bool|string
     */
    public static function getExtension($filename)
    {
        return substr($filename, strrpos($filename, '.'));
    }
}
