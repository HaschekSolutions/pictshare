<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;
use PictShare\Classes\FileSizeFormatter;

class FileSizeFormatterTest extends TestCase
{
    const ONE_KILOBYTE = 1024;

    public function testBytes()
    {
        self::assertEquals('0 B',   FileSizeFormatter::format(0));
        self::assertEquals('1 B',   FileSizeFormatter::format(1));
        self::assertEquals('100 B', FileSizeFormatter::format(100));
        self::assertEquals('999 B', FileSizeFormatter::format(999));
    }

    public function testKilobytes()
    {
        self::assertEquals('1 KB',   FileSizeFormatter::format(self::ONE_KILOBYTE));
        self::assertEquals('10 KB',  FileSizeFormatter::format(self::ONE_KILOBYTE * 10));
        self::assertEquals('999 KB', FileSizeFormatter::format(self::ONE_KILOBYTE * 999));
    }
}
