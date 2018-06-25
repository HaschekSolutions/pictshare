<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;
use PictShare\Classes\Exceptions\ClassNotFoundException;
use PictShare\Classes\FilterFactory;
use PictShare\Classes\Filters\AquaFilter;

class FilterFactoryTest extends TestCase
{
    const TEST_FILTER = 'TEST_FILTER';
    const AQUA_FILTER = 'aqua';

    public function testInvalidFilter()
    {
        $filterFactory = new FilterFactory();

        self::assertFalse($filterFactory::isValidFilter(self::TEST_FILTER));

        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('Filter for URL name ' . self::TEST_FILTER . ' not found.');

        $filterFactory::getFilter(self::TEST_FILTER);
    }

    public function testValidFilter()
    {
        $filterFactory = new FilterFactory();

        self::assertTrue($filterFactory::isValidFilter(self::AQUA_FILTER));

        $aquaFilter = $filterFactory::getFilter(self::AQUA_FILTER);

        self::assertSame(AquaFilter::class, \get_class($aquaFilter));
    }
}
