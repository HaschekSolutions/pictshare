<?php

declare(strict_types=1);

namespace PictShare\Classes;

class FileSizeFormatter
{
    const UNITS = [
        'B',
        'KB',
        'MB',
        'GB',
        'TB',
        'PB',
        'EB',
    ];

    const ONE_KILOBYTE = 1024;


    /**
     * @param int $sizeInBytes
     * @param int $precision
     *
     * @return string
     */
    public static function format(int $sizeInBytes, int $precision = 2): string
    {
        $sizeInBytes = $sizeInBytes < 0 ? 0 : $sizeInBytes;
        $logBytes    = $sizeInBytes ? log($sizeInBytes) : 0;
        $pow         = floor($logBytes / log(static::ONE_KILOBYTE));
        $pow         = min($pow, count(static::UNITS) - 1);

        $sizeInBytes /= (static::ONE_KILOBYTE ** $pow);

        return round($sizeInBytes, $precision) . ' ' . static::UNITS[$pow];
    }
}
