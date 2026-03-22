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

    public function testAnimatedGifMp4IsNotAffected(): void
    {
        // Confirm animated GIFs still enter the MP4 branch
        // (We just test that calling with 'mp4' does not crash)
        $r = $this->uploadFixture('test_animated.gif');
        $hash = $r['hash'];

        if (!@shell_exec('which ffmpeg')) {
            $this->markTestSkipped('ffmpeg not available in this environment');
        }

        $threw = false;
        try {
            ob_start();
            (new ImageController())->handleHash($hash, [$hash, 'mp4', 'raw']);
            ob_get_clean();
        } catch (\Throwable $e) {
            ob_get_clean();
            $threw = true;
        }
        $this->assertFalse($threw, 'MP4 conversion of animated GIF should not throw');
    }
}
