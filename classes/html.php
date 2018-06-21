<?php

class HTML
{
    /**
     * @param $byte
     *
     * @return string
     */
    public function renderSize($byte)
    {
        $result = '';

        if ($byte < 1024) {
            $result = round($byte, 2) . ' Byte';
        } elseif ($byte < (1024 ** 2)) {
            $result = round($byte / 1024, 2) . ' KB';
        } elseif ($byte >= (1024 ** 2) && $byte < (1024 ** 3)) {
            $result = round($byte / (1024 ** 2), 2) . ' MB';
        } elseif ($byte >= (1024 ** 3) && $byte < (1024 ** 4)) {
            $result = round($byte / (1024 ** 3), 2) . ' GB';
        } elseif ($byte >= (1024 ** 4) && $byte < (1024 ** 5)) {
            $result = round($byte / (1024 ** 4), 2) . ' TB';
        } elseif ($byte >= (1024 ** 5) && $byte < (1024 ** 6)) {
            $result = round($byte / (1024 ** 5), 2) . ' PB';
        } elseif ($byte >= (1024 ** 6) && $byte < (1024 ** 7)) {
            $result = round($byte / (1024 ** 6), 2) . ' EB';
        }

        return $result;
    }
}
