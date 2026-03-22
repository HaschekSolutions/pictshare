<?php
// tests/Integration/GifTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class GifTest extends PictShareTestCase
{
    /**
     * Static GIF should go through the full modifier pipeline.
     * FAILS until the static GIF pipeline is opened in this task.
     */
    public function testStaticGifFilterProducesVariant(): void
    {
        $r = $this->uploadFixture('test.gif');
        $hash = $r['hash'];

        $this->handleHashWithModifiers($hash, [$hash, 'sepia']);
        $path = $this->getModifiedPath($hash, ['filters' => [['filter' => 'sepia']]]);

        $this->assertNotFalse($path, 'Static GIF + sepia should create a cached variant');
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testStaticGifResizeProducesVariant(): void
    {
        $r = $this->uploadFixture('test.gif');
        $hash = $r['hash'];

        $this->handleHashWithModifiers($hash, [$hash, '100x75']);
        $path = $this->getModifiedPath($hash, ['size' => '100x75']);

        $this->assertNotFalse($path);
        [$w, $h] = getimagesize($path);
        $this->assertEquals(100, $w);
        $this->assertEquals(75, $h);
    }

    /**
     * Animated GIF must NOT have filters applied — variant should NOT be created.
     */
    public function testAnimatedGifFiltersAreSkipped(): void
    {
        $r = $this->uploadFixture('test_animated.gif');
        $hash = $r['hash'];

        $this->handleHashWithModifiers($hash, [$hash, 'sepia']);
        $path = $this->getModifiedPath($hash, ['filters' => [['filter' => 'sepia']]]);

        $this->assertFalse($path, 'Animated GIF should NOT produce a modified variant');
    }

}
