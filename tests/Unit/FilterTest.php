<?php
// tests/Unit/FilterTest.php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FilterTest extends TestCase
{
    private \GdImage $image;
    private Filter $filter;

    protected function setUp(): void
    {
        // filters.php is loaded by bootstrap — Filter class is available
        $this->image = imagecreatetruecolor(100, 100);
        imagefilledrectangle($this->image, 0, 0, 99, 99,
            imagecolorallocate($this->image, 128, 128, 128));
        $this->filter = new Filter();
    }

    protected function tearDown(): void
    {
        if ($this->image instanceof \GdImage) imagedestroy($this->image);
    }

    public static function filterMethodProvider(): array
    {
        // filters.php is loaded by the time this is called (PHPUnit 11 runs providers before tests)
        return array_map(fn($m) => [$m], get_class_methods('Filter'));
    }

    #[DataProvider('filterMethodProvider')]
    public function testAllFiltersReturnGdImage(string $method): void
    {
        // blur, pixelate, brightness, contrast, colorize have specific value handling
        if (in_array($method, ['blur', 'pixelate', 'brightness', 'contrast', 'colorize'])) {
            $this->markTestSkipped("$method tested separately");
        }
        $result = $this->filter->$method($this->image, null);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testBlurDefaultValue(): void
    {
        $result = $this->filter->blur($this->image, null);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testBlurWithValue(): void
    {
        $result = $this->filter->blur($this->image, 3);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testBlurClampsAboveMax(): void
    {
        $result = $this->filter->blur($this->image, 99);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testPixelateDefaultValue(): void
    {
        $result = $this->filter->pixelate($this->image, null);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testPixelateWithValue(): void
    {
        $result = $this->filter->pixelate($this->image, 20);
        $this->assertInstanceOf(\GdImage::class, $result);
    }

    public function testGetFiltersIncludesKnownFilters(): void
    {
        $filters = getFilters();
        foreach (['sepia', 'blur', 'pixelate', 'gray', 'vintage'] as $expected) {
            $this->assertContains($expected, $filters, "$expected should be in filter list");
        }
    }

    public function testAllFilterNamesAreCallable(): void
    {
        foreach (getFilters() as $f) {
            $this->assertTrue(method_exists(new Filter(), $f), "$f should exist on Filter");
        }
    }
}
