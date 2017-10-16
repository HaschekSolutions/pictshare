<?php

namespace App\Support;

/**
 * Class HTML
 * @package App\Support
 */
class HTML
{
    /**
     * @param string $string
     *
     * @return mixed
     */
    public function sanatizeString($string)
    {
        /*
         * Characters that will pass:
         * a-z A-Z 0-9 . _ -
         */
        return preg_replace("/[^a-zA-Z0-9._\-]+/", "", $string);
    }

    /**
     * @param int $byte
     *
     * @return string
     */
    public function renderSize($byte)
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
}
