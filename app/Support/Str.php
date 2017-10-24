<?php

namespace App\Support;

/**
 * Class Str
 * @package App\Support
 */
class Str
{
    /**
     * @param int    $length
     * @param string $keyspace
     *
     * @return string
     */
    public static function getRandomString($length = 32, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[rand(0, $max)];
        }
        return $str;
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        $strlen  = strlen($haystack);
        $testlen = strlen($needle);
        if ($testlen > $strlen) {
            return false;
        }
        return substr_compare($haystack, $needle, $strlen - $testlen, $testlen) === 0;
    }

    /**
     * @param string $string
     *
     * @return mixed
     */
    public static function sanitize($string)
    {
        /*
         * Characters that will pass:
         * a-z A-Z 0-9 . _ -
         */
        return preg_replace("/[^a-zA-Z0-9._\-]+/", "", $string);
    }
}
