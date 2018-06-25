<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;
use PictShare\Classes\Configuration;
use PictShare\Classes\Exceptions\ClassNotFoundException;

class AutoloaderTest extends TestCase
{
    const BAD_NAMESPACE = 'RandomNamespace\Classes\MissingClass';
    const BAD_CLASS_NAME = 'PictShare\Classes\MissingClass';
    const GOOD_CLASS_NAME = Configuration::class;


    public function testRootClassLoad()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        self::assertInstanceOf(\PictShare\Classes\Autoloader::class, $autoloader);
    }

    public function testInit()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        $expected = [
            \PictShare\Classes\Autoloader::class,
            'namespaceAutoloader',
        ];

        $autoloader::init();

        self::assertContains($expected, spl_autoload_functions());
    }

    public function testGoodLoad()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        $result = $autoloader::namespaceAutoloader(self::GOOD_CLASS_NAME);

        self::assertTrue($result);
    }

    public function testBadClassLoad()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('Class ' . self::BAD_CLASS_NAME . ' not found.');

        $autoloader::namespaceAutoloader(self::BAD_CLASS_NAME);
    }

    public function testBadNamespace()
    {
        $autoloader = new \PictShare\Classes\Autoloader();

        self::assertFalse($autoloader::namespaceAutoloader(self::BAD_NAMESPACE));
    }
}
