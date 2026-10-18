<?php
// tests/Unit/CoreFunctionsTest.php
use PHPUnit\Framework\TestCase;

class CoreFunctionsTest extends TestCase
{
    // isSize() is in core.php — loaded by bootstrap
    public function testIsSizeAcceptsNumericSquare(): void
    {
        $this->assertTrue(isSize('800'));
    }

    public function testIsSizeAcceptsWidthXHeight(): void
    {
        $this->assertTrue(isSize('800x600'));
    }

    public function testIsSizeRejectsText(): void
    {
        $this->assertFalse(isSize('large'));
    }

    public function testIsSizeRejectsMalformed(): void
    {
        $this->assertFalse(isSize('800x'));
        $this->assertFalse(isSize('x600'));
    }

    // isRotation() is in resize.php — loaded by bootstrap
    public function testIsRotationAcceptsValidDirections(): void
    {
        $this->assertTrue(isRotation('left'));
        $this->assertTrue(isRotation('right'));
        $this->assertTrue(isRotation('upside'));
    }

    public function testIsRotationRejectsInvalid(): void
    {
        $this->assertFalse(isRotation('flip'));
        $this->assertFalse(isRotation(''));
        $this->assertFalse(isRotation('180'));
    }

    // sizeStringToWidthHeight() is in core.php — loaded by bootstrap
    public function testSizeStringToWidthHeightSquare(): void
    {
        $result = sizeStringToWidthHeight('400');
        $this->assertEquals(['width' => '400', 'height' => '400'], $result);
    }

    public function testSizeStringToWidthHeightRectangle(): void
    {
        $result = sizeStringToWidthHeight('800x600');
        $this->assertEquals(['width' => '800', 'height' => '600'], $result);
    }

    public function testMightBeAHashAcceptsValidFormat(): void
    {
        $this->assertTrue(mightBeAHash('abc123.jpg'));
        $this->assertTrue(mightBeAHash('xF3q2.png'));
    }

    public function testMightBeAHashRejectsInvalid(): void
    {
        $this->assertFalse(mightBeAHash('nodot'));
        $this->assertFalse(mightBeAHash('two.dots.here'));
        $this->assertFalse(mightBeAHash('.jpg'));
    }

    public function testStartsWith(): void
    {
        $this->assertTrue(startsWith('sepia_10', 'sepia'));
        $this->assertFalse(startsWith('grayscale', 'sepia'));
    }

    public function testGetRandomStringLength(): void
    {
        $s = getRandomString(8);
        $this->assertEquals(8, strlen($s));
        $this->assertMatchesRegularExpression('/^[0-9a-z]+$/', $s);
    }

    // delete codes come from getRandomString(), so it must not be reproducible from the seedable mt_rand state
    public function testGetRandomStringIsNotDerivedFromSeedablePrng(): void
    {
        mt_srand(1234);
        $a = getRandomString(32);
        mt_srand(1234);
        $b = getRandomString(32);
        mt_srand();

        $this->assertNotSame($a, $b);
    }

    public function testGetTempFilePathIsInsideTmpDir(): void
    {
        $path = getTempFilePath();
        $this->assertSame(ROOT . DS . 'tmp', dirname($path));
        $this->assertFileDoesNotExist($path);
    }

    public function testGetTempFilePathIsUnpredictable(): void
    {
        mt_srand(1234);
        $a = getTempFilePath('url');
        mt_srand(1234);
        $b = getTempFilePath('url');
        mt_srand();

        $this->assertNotSame($a, $b);
        $this->assertStringStartsWith('url_', basename($a));
    }
}
