<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
    public function testRootClassLoad()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        self::assertInstanceOf(\PictShare\Classes\Autoloader::class, $autoloader);
    }
}
