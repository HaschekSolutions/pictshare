<?php

namespace App\Support;

/**
 * Class Utils
 * @package App\Support
 */
class Utils
{
    /**
     * @return string
     */
    public static function getUserIP()
    {
        $client  = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '';
        $forward = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $remote  = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        if (strpos($forward, ',')) {
            $a       = explode(',', $forward);
            $forward = trim($a[0]);
        }
        if (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } elseif (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else {
            $ip = $remote;
        }
        return $ip;
    }

    /**
     * @param string $ip
     *
     * @return mixed
     */
    public static function isIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * @param string $ip
     * @param string $range
     *
     * @return bool
     */
    public static function cidrMatch($ip, $range)
    {
        list($subnet, $bits) = explode('/', $range);
        $ip     = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask   = -1 << (32 - $bits);
        $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        return ($ip & $mask) == $subnet;
    }

    /**
     * @param string|string[] $value
     *
     * @return array|string
     */
    public static function stripSlashesDeep($value)
    {
        $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
        return $value;
    }

    /**
     * @return void
     */
    public static function removeMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            $_GET    = self::stripSlashesDeep($_GET);
            $_POST   = self::stripSlashesDeep($_POST);
            $_COOKIE = self::stripSlashesDeep($_COOKIE);
        }
    }

    /**
     * @param array $array
     * @param string $key
     *
     * @return void
     */
    public static function aasort(&$array, $key)
    {
        $sorter = [];
        $ret    = [];
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }
}
