<?php
// tests/Integration/NewFiltersTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class NewFiltersTest extends PictShareTestCase
{
    private string $jpgHash;

    protected function setUp(): void
    {
        parent::setUp();
        $r = $this->uploadFixture('test.jpg');
        $this->jpgHash = $r['hash'];
    }

    // --- Method existence and return type (unit-level) ---

    public function testBrightnessMethodExists(): void
    {
        $this->assertTrue(method_exists(new Filter(), 'brightness'));
    }

    public function testContrastMethodExists(): void
    {
        $this->assertTrue(method_exists(new Filter(), 'contrast'));
    }

    public function testColorizeMethodExists(): void
    {
        $this->assertTrue(method_exists(new Filter(), 'colorize'));
    }

    public function testBrightnessReturnsGdImage(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $result = (new Filter())->brightness($im, 80);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testContrastReturnsGdImage(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $result = (new Filter())->contrast($im, -30);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testColorizeReturnsGdImage(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $result = (new Filter())->colorize($im, [80, 20, 0]);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testBrightnessValueClamped(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $result = (new Filter())->brightness($im, 999);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testContrastValueClamped(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $result = (new Filter())->contrast($im, 999);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    // --- Integration: URL → cached variant ---

    public function testBrightnessUrlProducesVariant(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'brightness_80']);
        $path = $this->getModifiedPath($this->jpgHash, [
            'filters' => [['filter' => 'brightness', 'value' => '80']]
        ]);
        $this->assertNotFalse($path, 'brightness_80 should produce a cached variant');
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testContrastUrlProducesVariant(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'contrast_-30']);
        $path = $this->getModifiedPath($this->jpgHash, [
            'filters' => [['filter' => 'contrast', 'value' => '-30']]
        ]);
        $this->assertNotFalse($path);
    }

    public function testColorizeUrlProducesVariant(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'colorize_80_20_0']);
        $path = $this->getModifiedPath($this->jpgHash, [
            'filters' => [['filter' => 'colorize', 'value' => [80, 20, 0]]]
        ]);
        $this->assertNotFalse($path, 'colorize_80_20_0 should produce a cached variant');
    }

    public function testColorizeWithMissingChannelsDefaultsToZero(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'colorize_80']);
        $path = $this->getModifiedPath($this->jpgHash, [
            'filters' => [['filter' => 'colorize', 'value' => [80, 0, 0]]]
        ]);
        $this->assertNotFalse($path);
    }

    public function testNewFiltersAppearInFilterList(): void
    {
        foreach (['brightness', 'contrast', 'colorize'] as $filter) {
            $this->assertContains($filter, getFilters(),
                "$filter should appear in getFilters() once method is added to Filter class");
        }
    }
}
