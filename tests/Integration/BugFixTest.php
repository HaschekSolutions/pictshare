<?php
// tests/Integration/BugFixTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class BugFixTest extends PictShareTestCase
{
    /**
     * Bug: $a[1] undefined index when filter has no _value suffix.
     * Fix: $value = $a[1] ?? null
     * Note: core.php suppresses E_NOTICE globally. We override here to catch it.
     */
    public function testValuelessFilterDoesNotTriggerNotice(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];

        $oldLevel = error_reporting(E_ALL);
        set_error_handler(function(int $errno, string $errstr) {
            if ($errno === E_NOTICE || $errno === E_WARNING)
                $this->fail("Unexpected error/notice: $errstr");
            return false;
        });

        try {
            $this->handleHashWithModifiers($hash, [$hash, 'sepia']);
        } finally {
            restore_error_handler();
            error_reporting($oldLevel);
        }

        $this->addToAssertionCount(1);
    }

    /**
     * Bug: $fd['value'] undefined for preset filters stored without 'value' key.
     * Fix: $value = $fd['value'] ?? null in dispatch loop.
     */
    public function testPresetFilterDispatchDoesNotTriggerNotice(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];

        $oldLevel = error_reporting(E_ALL);
        set_error_handler(function(int $errno, string $errstr) {
            if ($errno === E_NOTICE || $errno === E_WARNING)
                $this->fail("Unexpected error/notice in filter dispatch: $errstr");
            return false;
        });

        try {
            $this->handleHashWithModifiers($hash, [$hash, 'gray']);
        } finally {
            restore_error_handler();
            error_reporting($oldLevel);
        }

        $this->addToAssertionCount(1);
    }

    /**
     * Bug: saveObjOfImage has no 'gif' case — modified GIF silently produces no file.
     * Fix: add imagegif() case.
     */
    public function testSaveObjOfImageHandlesGif(): void
    {
        $ctrl = new ImageController();
        $im = imagecreatefromgif(__DIR__ . '/../fixtures/test.gif');
        $tmpPath = ROOT . DS . 'tmp' . DS . 'test_save_gif_' . uniqid() . '.gif';

        $result = $ctrl->saveObjOfImage($im, $tmpPath, 'gif');

        $this->assertNotFalse($result, 'saveObjOfImage must return a GdImage for gif type');
        $this->assertFileExists($tmpPath);
        $this->assertGreaterThan(0, filesize($tmpPath));

        @unlink($tmpPath);
    }

    /**
     * Bug: saveObjOfImage has no 'bmp' case.
     * Fix: add imagebmp() case.
     */
    public function testSaveObjOfImageHandlesBmp(): void
    {
        $ctrl = new ImageController();
        $im = imagecreatefrombmp(__DIR__ . '/../fixtures/test.bmp');
        $tmpPath = ROOT . DS . 'tmp' . DS . 'test_save_bmp_' . uniqid() . '.bmp';

        $result = $ctrl->saveObjOfImage($im, $tmpPath, 'bmp');

        $this->assertNotFalse($result);
        $this->assertFileExists($tmpPath);

        @unlink($tmpPath);
    }

    /**
     * Bug: saveObjOfImage returns false for 'ico' but caller still sets $path = $newpath.
     * Fix: saveObjOfImage returns false for ico; caller guards with !== false.
     */
    public function testSaveObjOfImageReturnsFalseForIco(): void
    {
        $ctrl = new ImageController();
        $im = imagecreatetruecolor(10, 10);
        $result = $ctrl->saveObjOfImage($im, ROOT . DS . 'tmp' . DS . 'fake.ico', 'ico');
        $this->assertFalse($result);
    }
}
