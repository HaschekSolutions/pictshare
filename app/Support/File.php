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
     * @var array
     */
    protected static $subdirs = [];

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
        if (strpos($val, '_') !== false) {
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
        $subdir  = static::getSubDirFromHash($hash);
        $subdir  = $subdir ? $subdir . '/' : '';
        $hashdir = static::uploadDir($subdir . $hash);

        return is_dir($hashdir) && file_exists(static::concatPath($hashdir, $hash));
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
            $hash = Str::getRandomString($length) . '.' . $type;
            if (! static::hashExists($hash)) {
                return $hash;
            }
        }
    }

    /**
     * @param string $hash
     *
     * @return bool|string
     */
    public static function getSubDirFromHash($hash)
    {
        if (isset(static::$subdirs[$hash])) {
            return static::$subdirs[$hash];
        }

        if (config('app.hashes_store') === 'database') {
            $subdir = static::subDirFromHashesDb($hash);
        } else {
            $subdir = static::subDirFromHashesFile($hash);
        }

        if (!$subdir) {
            // if not found return empty string
            return '';
        }

        // found - so "cache" and return
        static::$subdirs[$hash] = $subdir;

        return $subdir;
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

    /**
     * @param int $byte
     *
     * @return string
     */
    public static function renderSize($byte)
    {
        $result = null;

        if ($byte < 1024) {
            $result = round($byte, 2) . ' Byte';
        } elseif ($byte < pow(1024, 2)) {
            $result = round($byte / 1024, 2) . ' KB';
        } elseif ($byte >= pow(1024, 2) and $byte < pow(1024, 3)) {
            $result = round($byte / pow(1024, 2), 2) . ' MB';
        } elseif ($byte >= pow(1024, 3) and $byte < pow(1024, 4)) {
            $result = round($byte / pow(1024, 3), 2) . ' GB';
        } elseif ($byte >= pow(1024, 4) and $byte < pow(1024, 5)) {
            $result = round($byte / pow(1024, 4), 2) . ' TB';
        } elseif ($byte >= pow(1024, 5) and $byte < pow(1024, 6)) {
            $result = round($byte / pow(1024, 5), 2) . ' PB';
        } elseif ($byte >= pow(1024, 6) and $byte < pow(1024, 7)) {
            $result = round($byte / pow(1024, 6), 2) . ' EB';
        }

        return $result;
    }

    /**
     * Get the path to the upload directory.
     *
     * @param string $path
     *
     * @return bool|mixed|string
     */
    public static function uploadDir($path = '')
    {
        // if upload directory is configured then we use it
        if (! ($uploadDir = config('app.upload_dir', false))) {
            // otherwise we fallback to using the default one (inside project)
            $uploadDir = root_path('upload/');
        }

        // if additional path is given as argument we add it to the base directory
        if ($path !== '') {
            // we want to strip directory separator from the start of the additional path string
            $path = Str::stripSlash($path, Str::LEAD_SLASH);
            // and we want to strip directory separator from the end of the base upload directory
            $uploadDir = Str::stripSlash($uploadDir, Str::TAIL_SLASH);
            // so we can concatenate those values with a single directory separator
            $uploadDir .= '/'.$path;
        }

        return $uploadDir;
    }

    /**
     * Takes two paths and concatenates them being careful about directory separator.
     *
     * @param string $path
     * @param string $extra
     *
     * @return string
     */
    public static function concatPath($path, $extra)
    {
        // we want to strip directory separator from the start of the extra string
        $extra = Str::stripSlash($extra, Str::LEAD_SLASH);
        // and we want to strip directory separator from the end of the path string
        $path = Str::stripSlash($path, Str::TAIL_SLASH);
        // so we can concatenate those values with a single directory separator
        return $path . '/' . $extra;
    }

    /**
     * @param string $hash
     *
     * @return bool|string
     */
    protected static function subDirFromHashesFile($hash)
    {
        $hashes = File::uploadDir('hashes.csv');
        if (!file_exists($hashes)) {
            return false;
        }

        $fp = fopen($hashes, 'r');
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (!$line) {
                continue;
            }
            $data = explode(';', $line);
            if ($hash == trim($data[1])) {
                $subdir = trim($data[2]);
                break;
            }
        }

        fclose($fp);

        if (!isset($subdir)) {
            return false;
        }

        return $subdir;
    }

    /**
     * @param string $hash
     *
     * @return bool|string
     */
    protected static function subDirFromHashesDb($hash)
    {
        /** @var Database $db */
        $db = app()->getContainer()->get(Database::class);

        $query = 'SELECT `subdir` FROM `hashes` WHERE `name` = :hash';
        $data  = ['hash' => $hash];

        $stmt = $db->execute($query, $data);

        $sqlResult = false;
        if ($stmt instanceof \PDOStatement) {
            $sqlResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt = null; // free up resources
        }

        if (!$sqlResult || count($sqlResult) != 1) {
            return false;
        }

        $subdir = $sqlResult[0]['subdir'];

        $query = 'UPDATE `hashes` SET `last_access_ts` = now() WHERE `name` = :hash';
        // TODO: error handling?
        $db->execute($query, $data);

        return $subdir;
    }
}
