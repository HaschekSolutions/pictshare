<?php
// tests/Unit/FileTypeDetectionTest.php
use PHPUnit\Framework\TestCase;

class FileTypeDetectionTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../fixtures/';

    public static function fixtureTypes(): array
    {
        return [
            'jpg'  => ['test.jpg',  'jpeg',    'image/jpeg'],
            'png'  => ['test.png',  'png',     'image/png'],
            'gif'  => ['test.gif',  'gif',     'image/gif'],
            'bmp'  => ['test.bmp',  'bmp',     'image/bmp'],
            'webp' => ['test.webp', 'webp',    'image/webp'],
            'svg'  => ['test.svg',  'svg+xml', 'image/svg+xml'],
            'txt'  => ['test.txt',  'text',    'text/plain'],
            'md'   => ['test.md',   'text',    'text/plain'],
            'html' => ['test.html', 'text',    'text/html'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fixtureTypes')]
    public function testGetTypeOfFile(string $file, string $type, string $mime): void
    {
        $this->assertSame($type, getTypeOfFile(self::FIXTURES . $file));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fixtureTypes')]
    public function testGetFileMimeType(string $file, string $type, string $mime): void
    {
        $this->assertSame($mime, getFileMimeType(self::FIXTURES . $file));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fixtureTypes')]
    public function testDetectMimeWithCharsetStartsWithMimeType(string $file, string $type, string $mime): void
    {
        $this->assertStringStartsWith($mime . '; charset=', detectMimeWithCharset(self::FIXTURES . $file));
    }

    public function testDetectMimeWithCharsetOfMissingFileIsFalse(): void
    {
        $this->assertFalse(detectMimeWithCharset(self::FIXTURES . 'does-not-exist.bin'));
    }

    public function testGetTypeOfMissingFileIsFalse(): void
    {
        $this->assertFalse(getTypeOfFile(self::FIXTURES . 'does-not-exist.bin'));
    }

    public function testStringInFileFindsExactLine(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sif');
        file_put_contents($file, "abc\ndef \nghi\n");
        try {
            $this->assertTrue(stringInFile('def', $file));
            $this->assertFalse(stringInFile('de', $file));
        } finally {
            unlink($file);
        }
    }

    public function testStringInFileOnMissingFileIsFalse(): void
    {
        $this->assertFalse(stringInFile('abc', sys_get_temp_dir() . '/pictshare-no-such-file-' . bin2hex(random_bytes(4))));
    }

    public function testOpenRedisConnectionReturnsNullWhenServerIsUnreachable(): void
    {
        if (!class_exists('Redis')) $this->markTestSkipped('phpredis not installed');
        // nothing listens on port 1
        $this->assertNull(openRedisConnection('127.0.0.1', 1, 0.5));
    }
}
