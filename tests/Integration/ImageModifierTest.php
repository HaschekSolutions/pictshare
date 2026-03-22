<?php
// tests/Integration/ImageModifierTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ImageModifierTest extends PictShareTestCase
{
    private string $jpgHash;
    private string $pngHash;

    protected function setUp(): void
    {
        parent::setUp();
        $r1 = $this->uploadFixture('test.jpg');
        $r2 = $this->uploadFixture('test.png');
        $this->jpgHash = $r1['hash'];
        $this->pngHash = $r2['hash'];
    }

    // --- Resize ---

    public function testResizeProducesCorrectDimensions(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, '100x75']);
        $path = $this->getModifiedPath($this->jpgHash, ['size' => '100x75']);
        $this->assertNotFalse($path, 'Resized variant should exist on disk');
        [$w, $h] = getimagesize($path);
        $this->assertEquals(100, $w);
        $this->assertEquals(75, $h);
    }

    public function testResizeSquare(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, '50']);
        $path = $this->getModifiedPath($this->jpgHash, ['size' => '50']);
        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        $this->assertLessThanOrEqual(50, max($w, $h));
    }

    public function testForceResizeFillsExactDimensions(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, '100x75', 'forcesize']);
        $path = $this->getModifiedPath($this->jpgHash, ['size' => '100x75', 'forcesize' => true]);
        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        $this->assertEquals(100, $w);
        $this->assertEquals(75, $h);
    }

    // --- Rotation ---

    public function testRotateLeft(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'left']);
        $path = $this->getModifiedPath($this->jpgHash, ['rotation' => 'left']);
        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        // Original 200x150 rotated 90° → 150x200
        $this->assertEquals(150, $w);
        $this->assertEquals(200, $h);
    }

    public function testRotateRight(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'right']);
        $path = $this->getModifiedPath($this->jpgHash, ['rotation' => 'right']);
        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        $this->assertEquals(150, $w);
        $this->assertEquals(200, $h);
    }

    public function testRotateUpside(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'upside']);
        $path = $this->getModifiedPath($this->jpgHash, ['rotation' => 'upside']);
        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        // 180° preserves dimensions
        $this->assertEquals(200, $w);
        $this->assertEquals(150, $h);
    }

    // --- WebP ---

    public function testWebpConversionOutputsWebpFile(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'webp']);
        $path = $this->getModifiedPath($this->jpgHash, ['webp' => true]);
        $this->assertNotFalse($path);
        $this->assertEquals(IMAGETYPE_WEBP, exif_imagetype($path));
    }

    // --- Existing preset filters ---

    public function testSepiaFilterProducesValidImage(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'sepia']);
        $path = $this->getModifiedPath($this->jpgHash, ['filters' => [['filter' => 'sepia']]]);
        $this->assertNotFalse($path, 'sepia filter should create a cached variant');
        $this->assertGreaterThan(0, filesize($path));
        $this->assertNotFalse(exif_imagetype($path));
    }

    public function testBlurWithValueProducesValidImage(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'blur_3']);
        $path = $this->getModifiedPath($this->jpgHash, ['filters' => [['filter' => 'blur', 'value' => '3']]]);
        $this->assertNotFalse($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testPixelateWithValueProducesValidImage(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'pixelate_15']);
        $path = $this->getModifiedPath($this->jpgHash, ['filters' => [['filter' => 'pixelate', 'value' => '15']]]);
        $this->assertNotFalse($path);
    }

    public function testCombinedResizeAndFilter(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, '100x75', 'sepia']);
        $path = $this->getModifiedPath($this->jpgHash, [
            'size' => '100x75',
            'filters' => [['filter' => 'sepia']],
        ]);
        $this->assertNotFalse($path);
        [$w] = getimagesize($path);
        $this->assertEquals(100, $w);
    }

    // --- Caching ---

    public function testSameModifiersAreServedFromCache(): void
    {
        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'sepia']);
        $path = $this->getModifiedPath($this->jpgHash, ['filters' => [['filter' => 'sepia']]]);
        $mtime1 = filemtime($path);

        $this->handleHashWithModifiers($this->jpgHash, [$this->jpgHash, 'sepia']);
        clearstatcache();
        $mtime2 = filemtime($path);

        $this->assertEquals($mtime1, $mtime2, 'Second call should serve cached variant');
    }

    // --- PNG transparency ---

    public function testPngTransparencyPreservedAfterResize(): void
    {
        $this->handleHashWithModifiers($this->pngHash, [$this->pngHash, '100x75']);
        $path = $this->getModifiedPath($this->pngHash, ['size' => '100x75']);
        $this->assertNotFalse($path);
        $this->assertEquals(IMAGETYPE_PNG, exif_imagetype($path));
    }
}
