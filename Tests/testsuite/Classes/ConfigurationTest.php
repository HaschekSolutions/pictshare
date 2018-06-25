<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;
use PictShare\Classes\Configuration;
use PictShare\Classes\Exceptions\ConfigDefaultMissingException;

class ConfigurationTest extends TestCase
{
    const TEST_CONFIG_VALUE_1 = 'TEST_CONFIG_VALUE_1';
    const TEST_CONFIG_VALUE_2 = 'TEST_CONFIG_VALUE_2';


    public function testDefinedValue()
    {
        \define(self::TEST_CONFIG_VALUE_1, true);

        $configuration = new Configuration();

        self::assertTrue($configuration::getValue(self::TEST_CONFIG_VALUE_1));
    }

    public function testUndefinedValue()
    {
        $configuration = new Configuration();

        $this->expectException(ConfigDefaultMissingException::class);
        $this->expectExceptionMessage('Missing default value for config option ' . self::TEST_CONFIG_VALUE_2);

        $configuration::getValue(self::TEST_CONFIG_VALUE_2);
    }

    public function testDefaultValue()
    {
        $configuration = new Configuration();

        self::assertFalse($configuration::getValue(Configuration::BACKBLAZE));
    }

    public function testBackblazeDefaultOff()
    {
        $configuration = new Configuration();

        self::assertFalse($configuration::isBackblazeEnabled());
        self::assertFalse($configuration::isBackblazeAutoDownloadEnabled());
        self::assertFalse($configuration::isBackblazeAutoUploadEnabled());
        self::assertFalse($configuration::isBackblazeAutoDeleteEnabled());
    }
}
