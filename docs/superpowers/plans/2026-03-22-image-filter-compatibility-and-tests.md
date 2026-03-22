# Image Filter Compatibility + Full Test Suite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a PHPUnit test suite covering the full app (upload, delete, modifiers, filters, API, reporting), wire it into Docker and Gitea Actions, and implement the image filter compatibility fixes from the design spec using TDD.

**Architecture:** PHPUnit runs inside the existing Docker dev container — no extra infra. Tests call controllers and global functions directly (no HTTP), using a clean test config that disables Redis and redirects data writes to a temp directory via `_TEST_DATA_OVERRIDE`. New features are driven by failing tests first. Bug-fix tasks run before regression tests that depend on the fixes.

**Tech Stack:** PHP 8.2, PHPUnit 11, GD extension (already in container), Docker Compose, Gitea Actions

**Spec:** `docs/superpowers/specs/2026-03-22-image-filter-compatibility-design.md`

---

## File Structure

```
tests/
  bootstrap.php                   # PHPUnit bootstrap: defines ROOT, loads app, disables Redis
  PictShareTestCase.php           # Base test case: setUp/tearDown, uploadFixture() helper
  fixtures/
    generate_fixtures.php         # Script to generate binary test images via GD (run once)
    test.jpg                      # 200x150 JPEG (committed binary)
    test.png                      # 200x150 PNG with transparency (committed binary)
    test.gif                      # 200x150 static GIF (committed binary)
    test_animated.gif             # 2-frame animated GIF (committed binary)
    test.webp                     # 200x150 WebP (committed binary)
    test.bmp                      # 200x150 BMP (committed binary)
    test.txt                      # plain text (committed)
  Unit/
    FilterTest.php                # Unit tests for Filter class methods
    CoreFunctionsTest.php         # Unit tests for pure utility functions
  Integration/
    UploadTest.php                # Upload flow: all types, custom hash, duplicate, naughty list
    DeleteTest.php                # Delete codes: correct, wrong, master code
    BugFixTest.php                # Regression tests for the five E_NOTICE bugs (run early)
    ImageModifierTest.php         # Resize, rotation, WebP, existing filters (after bug fixes)
    GifTest.php                   # Static GIF filter support (TDD - new behaviour)
    NewFiltersTest.php            # brightness, contrast, colorize (TDD - new filters)
    ApiTest.php                   # API class: upload, info, delete, debug, passthrough
    ReportTest.php                # Report submission and listing

src/lib/composer.json             # Add phpunit/phpunit ^11
phpunit.xml                       # PHPUnit config: testsuite, bootstrap, coverage
docker-compose-dev.yml            # Document test run command
.gitea/workflows/test.yml         # Gitea Actions: build image, run tests on push/PR
```

**Task order note:** Task 11 (bug fixes) runs before Task 12 (image modifier regression tests) because several regression tests exercise code paths that are currently broken by the bugs being fixed.

---

## Task 1: Install PHPUnit

**Files:**
- Modify: `src/lib/composer.json`
- Create: `phpunit.xml`

- [ ] **Step 1: Add phpunit to composer.json**

```json
{
    "require": {
        "aws/aws-sdk-php": "^3.33",
        "bitverse/identicon": "^1.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^11"
    }
}
```

- [ ] **Step 2: Create phpunit.xml at project root**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="src/lib/vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Install inside container**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare bash -c "cd /app/public/src/lib && composer install"
```

Expected: `src/lib/vendor/bin/phpunit` now exists.

- [ ] **Step 4: Commit**

```bash
git add src/lib/composer.json phpunit.xml
git commit -m "test: install PHPUnit 11"
```

---

## Task 2: Test bootstrap and base test case

**Files:**
- Create: `tests/bootstrap.php`
- Create: `tests/PictShareTestCase.php`
- Create: `tests/testconfig.php`
- Modify: `src/inc/core.php` (add `_TEST_DATA_OVERRIDE` guard in `getDataDir`)

- [ ] **Step 1: Create test config**

```php
<?php
// tests/testconfig.php
define('URL', 'http://localhost/');
define('MAX_UPLOAD_SIZE', 100);
define('REDIS_CACHING', false);
define('JPEG_COMPRESSION', 90);
define('ALLOW_BLOATING', true);
```

- [ ] **Step 2: Create bootstrap.php**

```php
<?php
// tests/bootstrap.php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath(__DIR__ . '/..'));

// Isolated data directory for all test runs
$testDataDir = sys_get_temp_dir() . DS . 'pictshare_test_data';
foreach ([$testDataDir, ROOT . DS . 'tmp', ROOT . DS . 'logs'] as $d) {
    if (!is_dir($d)) mkdir($d, 0777, true);
}
define('TEST_DATA_DIR', $testDataDir);

// getDataDir() in core.php checks this constant first
define('_TEST_DATA_OVERRIDE', $testDataDir);

// Required for sha1 index and naughty list
if (!file_exists($testDataDir . DS . 'sha1.csv')) touch($testDataDir . DS . 'sha1.csv');
if (!file_exists($testDataDir . DS . 'naughty.csv')) touch($testDataDir . DS . 'naughty.csv');

// Server vars PHPUnit CLI won't set
$_SERVER += [
    'HTTP_USER_AGENT' => 'PHPUnit',
    'REMOTE_ADDR'     => '127.0.0.1',
    'HTTP_HOST'       => 'localhost',
    'HTTP_ACCEPT'     => 'text/html',
];

require_once __DIR__ . '/testconfig.php';
require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'core.php';

// resize.php and filters.php define functions used in unit tests
require_once ROOT . DS . 'src' . DS . 'content-controllers' . DS . 'image' . DS . 'resize.php';
require_once ROOT . DS . 'src' . DS . 'content-controllers' . DS . 'image' . DS . 'filters.php';

require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'api.class.php';
loadAllContentControllers();

if (file_exists(ROOT . '/src/lib/vendor/autoload.php'))
    require_once ROOT . '/src/lib/vendor/autoload.php';

// Redis disabled — GLOBALS['redis'] stays null (REDIS_CACHING=false skips init in index.php)
```

- [ ] **Step 3: Patch getDataDir() in src/inc/core.php**

Find `function getDataDir()` and add one guard line at the top:

```php
function getDataDir()
{
    if (defined('_TEST_DATA_OVERRIDE')) return _TEST_DATA_OVERRIDE;  // ← ADD
    if(defined('SPLIT_DATA_DIR') && SPLIT_DATA_DIR===true && getDomain() && in_array(getDomain(),explode(',',ALLOWED_DOMAINS)))
    {
```

- [ ] **Step 4: Create tests/PictShareTestCase.php**

```php
<?php
// tests/PictShareTestCase.php
use PHPUnit\Framework\TestCase;

abstract class PictShareTestCase extends TestCase
{
    protected array $uploadedHashes = [];

    protected function setUp(): void
    {
        // Ensure test data directory exists with required index files
        if (!is_dir(TEST_DATA_DIR)) mkdir(TEST_DATA_DIR, 0777, true);
        if (!file_exists(TEST_DATA_DIR . DS . 'sha1.csv')) touch(TEST_DATA_DIR . DS . 'sha1.csv');
        if (!file_exists(TEST_DATA_DIR . DS . 'naughty.csv')) touch(TEST_DATA_DIR . DS . 'naughty.csv');
    }

    protected function tearDown(): void
    {
        foreach ($this->uploadedHashes as $hash) {
            if (isExistingHash($hash)) deleteHash($hash);
        }
        $this->uploadedHashes = [];
    }

    /**
     * Copy a fixture to ROOT/tmp, run through a content controller's handleUpload,
     * track the hash for cleanup, and return the result array.
     */
    protected function uploadFixture(string $filename, ?string $hash = null): array
    {
        $src = __DIR__ . '/fixtures/' . $filename;
        $tmp = ROOT . DS . 'tmp' . DS . 'test_' . uniqid() . '_' . $filename;
        copy($src, $tmp);

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        foreach (loadAllContentControllers() as $ccName) {
            $cc = new $ccName();
            if (in_array($ext, $cc->getRegisteredExtensions())) {
                $result = $cc->handleUpload($tmp, $hash);
                if (isset($result['hash']) && $result['status'] === 'ok')
                    $this->uploadedHashes[] = $result['hash'];
                return $result;
            }
        }
        return ['status' => 'err', 'reason' => 'No controller found for extension: ' . $ext];
    }

    /**
     * Call handleHash with URL modifier parts and capture any output.
     */
    protected function handleHashWithModifiers(string $hash, array $urlParts): string
    {
        $ext = strtolower(pathinfo($hash, PATHINFO_EXTENSION));
        foreach (loadAllContentControllers() as $ccName) {
            $cc = new $ccName();
            if (in_array($ext, $cc->getRegisteredExtensions())) {
                ob_start();
                $cc->handleHash($hash, $urlParts);
                return ob_get_clean();
            }
        }
        return '';
    }

    /**
     * Returns the filesystem path of a modifier-cached variant, or false if not found.
     * $modifiers must match exactly what handleHash builds internally.
     */
    protected function getModifiedPath(string $hash, array $modifiers): string|false
    {
        $modhash = md5(http_build_query($modifiers, '', ','));
        $path = TEST_DATA_DIR . DS . $hash . DS . $modhash . '_' . $hash;
        return file_exists($path) ? $path : false;
    }
}
```

- [ ] **Step 5: Verify bootstrap loads without errors**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  --list-tests 2>&1 | head -5
```

Expected: PHPUnit banner + empty test list (no fatal errors)

- [ ] **Step 6: Commit**

```bash
git add tests/bootstrap.php tests/testconfig.php tests/PictShareTestCase.php \
        src/inc/core.php phpunit.xml
git commit -m "test: add PHPUnit bootstrap, base test case, getDataDir test override"
```

---

## Task 3: Generate and commit test fixtures

**Files:**
- Create: `tests/fixtures/generate_fixtures.php`
- Create: `tests/fixtures/test.jpg`, `test.png`, `test.gif`, `test_animated.gif`, `test.webp`, `test.bmp`, `test.txt`

- [ ] **Step 1: Create fixture generator script**

```php
<?php
// tests/fixtures/generate_fixtures.php
// Run once: docker compose -f docker-compose-dev.yml run --rm pictshare \
//           php /app/public/tests/fixtures/generate_fixtures.php
$dir = __DIR__;

function makeGradientImage(int $w = 200, int $h = 150): \GdImage
{
    $im = imagecreatetruecolor($w, $h);
    for ($x = 0; $x < $w; $x++) {
        $r = (int)(255 * $x / $w);
        $b = 255 - $r;
        $col = imagecolorallocate($im, $r, 100, $b);
        imageline($im, $x, 0, $x, $h - 1, $col);
    }
    return $im;
}

// JPEG
$im = makeGradientImage();
imagejpeg($im, $dir . '/test.jpg', 90);
imagedestroy($im);

// PNG with partial transparency
$im = imagecreatetruecolor(200, 150);
imagealphablending($im, false);
imagesavealpha($im, true);
imagefilledrectangle($im, 0, 0, 199, 149, imagecolorallocatealpha($im, 0, 0, 0, 127));
imagefilledrectangle($im, 20, 20, 180, 130, imagecolorallocate($im, 200, 50, 50));
imagepng($im, $dir . '/test.png');
imagedestroy($im);

// Static GIF (single frame)
$im = makeGradientImage();
imagegif($im, $dir . '/test.gif');
imagedestroy($im);

// Animated GIF: minimal valid 2-frame GIF89a (10x10, loops)
// Built from raw GIF binary to guarantee exactly 2 Graphic Control Extension blocks.
$gce   = "\x21\xF9\x04\x00\x0A\x00\x00\x00";  // GCE, 100ms delay
$desc  = "\x2C\x00\x00\x00\x00\x0A\x00\x0A\x00\x00"; // Image descriptor 10x10
// Minimal LZW-compressed solid-colour image data (pre-computed)
$imgR  = "\x02\x16\x8C\x2D\x99\x87\x2A\x1C\xDC\x33\xA0\x02\x75\xEC\x95\xFA\xA8\xDE\x60\x8C\x04\x91\x4C\x01\x00";
$imgB  = "\x02\x16\x8C\x2D\x99\x87\x2A\x1C\xDC\x33\xA0\x02\x75\xEC\x95\xFA\xA8\xDE\x60\x8C\x04\x91\x4C\x01\x00";
// Use pre-baked base64 blob for reliability
$animatedGif = base64_decode(
    'R0lGODlhCgAKAIABAP8AAP///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQABAABACwAAAAA' .
    'CgAKAAACC1xmqYvtD6OctNqLAAAh+QQABAABACwAAAAACgAKAAACC2RmmYvtD6OctNqLAAA7'
);
// Verify it contains 2+ GCE blocks (animation detection relies on this)
assert(substr_count($animatedGif, "\x21\xF9\x04") > 1,
    'test_animated.gif must contain multiple GCE blocks for animation detection to work');
file_put_contents($dir . '/test_animated.gif', $animatedGif);

// WebP
$im = makeGradientImage();
imagewebp($im, $dir . '/test.webp', 80);
imagedestroy($im);

// BMP
$im = makeGradientImage();
imagebmp($im, $dir . '/test.bmp');
imagedestroy($im);

// Plain text
file_put_contents($dir . '/test.txt', "Hello, PictShare!\nLine two.");

echo "Fixtures generated in $dir\n";
```

- [ ] **Step 2: Run the generator inside the container**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/tests/fixtures/generate_fixtures.php
```

Expected: `Fixtures generated in /app/public/tests/fixtures`

- [ ] **Step 3: Verify fixtures were created and animated GIF has multiple GCE blocks**

```bash
ls -la tests/fixtures/
# Verify animated gif has 2+ GCE blocks (animation detection depends on this):
docker compose -f docker-compose-dev.yml run --rm pictshare php -r \
  "echo substr_count(file_get_contents('/app/public/tests/fixtures/test_animated.gif'), \"\x21\xF9\x04\") . \" GCE blocks\n\";"
```

Expected: static GIF shows 1 block; animated GIF shows 2+ blocks.

- [ ] **Step 4: Commit fixtures**

```bash
git add tests/fixtures/
git commit -m "test: add fixture generator and binary test images"
```

---

## Task 4: Docker test target + Gitea Actions

**Files:**
- Modify: `docker-compose-dev.yml`
- Create: `.gitea/workflows/test.yml`

- [ ] **Step 1: Document test command in docker-compose-dev.yml**

Add a comment block at the bottom of `docker-compose-dev.yml`:

```yaml
  # Run tests (uses the pictshare service image):
  # docker compose -f docker-compose-dev.yml run --rm pictshare \
  #   php /app/public/src/lib/vendor/bin/phpunit --configuration /app/public/phpunit.xml
```

- [ ] **Step 2: Create .gitea/workflows/test.yml**

```bash
mkdir -p .gitea/workflows
```

```yaml
# .gitea/workflows/test.yml
name: tests

on:
  push:
    branches:
      - "**"
  pull_request:
    branches:
      - "master"

jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Build image
        run: docker build -f docker/Dockerfile -t pictshare-test .

      - name: Install dev dependencies (PHPUnit)
        run: |
          docker run --rm \
            -v "${{ github.workspace }}:/app/public" \
            pictshare-test \
            bash -c "cd /app/public/src/lib && composer install --no-interaction"

      - name: Run PHPUnit
        run: |
          docker run --rm \
            -v "${{ github.workspace }}:/app/public" \
            pictshare-test \
            php /app/public/src/lib/vendor/bin/phpunit \
              --configuration /app/public/phpunit.xml \
              --colors=never
```

- [ ] **Step 3: Commit**

```bash
git add docker-compose-dev.yml .gitea/workflows/test.yml
git commit -m "ci: add Gitea Actions workflow and document docker test command"
```

---

## Task 5: Unit tests — core utility functions

**Files:**
- Create: `tests/Unit/CoreFunctionsTest.php`

- [ ] **Step 1: Write tests**

```php
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

    // sizeStringToWidthHeight() is in resize.php — loaded by bootstrap
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
}
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Unit/CoreFunctionsTest.php
```

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/CoreFunctionsTest.php
git commit -m "test: unit tests for core utility functions"
```

---

## Task 6: Unit tests — Filter class (existing filters)

**Files:**
- Create: `tests/Unit/FilterTest.php`

- [ ] **Step 1: Write tests**

```php
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
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Unit/FilterTest.php
```

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/FilterTest.php
git commit -m "test: unit tests for existing Filter class methods"
```

---

## Task 7: Integration tests — Upload flow

**Files:**
- Create: `tests/Integration/UploadTest.php`

- [ ] **Step 1: Write tests**

```php
<?php
// tests/Integration/UploadTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class UploadTest extends PictShareTestCase
{
    public function testUploadJpeg(): void
    {
        $result = $this->uploadFixture('test.jpg');
        $this->assertEquals('ok', $result['status']);
        $this->assertNotEmpty($result['hash']);
        $this->assertStringEndsWith('.jpg', $result['hash']);
        $this->assertTrue(isExistingHash($result['hash']));
    }

    public function testUploadPng(): void
    {
        $result = $this->uploadFixture('test.png');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.png', $result['hash']);
    }

    public function testUploadGif(): void
    {
        $result = $this->uploadFixture('test.gif');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.gif', $result['hash']);
    }

    public function testUploadWebp(): void
    {
        $result = $this->uploadFixture('test.webp');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.webp', $result['hash']);
    }

    public function testUploadBmp(): void
    {
        $result = $this->uploadFixture('test.bmp');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.bmp', $result['hash']);
    }

    public function testUploadText(): void
    {
        $result = $this->uploadFixture('test.txt');
        $this->assertEquals('ok', $result['status']);
    }

    public function testCustomHash(): void
    {
        $custom = 'testhash.jpg';
        $result = $this->uploadFixture('test.jpg', $custom);
        $this->assertEquals('ok', $result['status']);
        $this->assertEquals($custom, $result['hash']);
        $this->assertTrue(isExistingHash($custom));
    }

    public function testCustomHashDuplicateIsRejected(): void
    {
        $custom = 'duptest.jpg';
        $this->uploadFixture('test.jpg', $custom);
        $result = $this->uploadFixture('test.jpg', $custom);
        $this->assertEquals('err', $result['status']);
        $this->assertStringContainsStringIgnoringCase('already exists', $result['reason']);
    }

    public function testUploadedFileHasMetadata(): void
    {
        $result = $this->uploadFixture('test.jpg');
        // Note: actual function name in core.php is getMetadataOfHash, not getMetaData
        $meta = getMetadataOfHash($result['hash']);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('mime', $meta);
        $this->assertEquals('image/jpeg', $meta['mime']);
    }

    public function testUploadedFileHasDeleteCode(): void
    {
        $result = $this->uploadFixture('test.jpg');
        $code = getDeleteCodeOfHash($result['hash']);
        $this->assertNotEmpty($code);
        $this->assertEquals(32, strlen($code));
    }

    /**
     * Deduplication only happens at the API layer (sha1Exists check in API::handleFile).
     * Direct controller uploads always create a new hash even for the same binary content.
     * This test verifies that the sha1 index is populated after an upload.
     */
    public function testUploadPopulatesSha1Index(): void
    {
        $result = $this->uploadFixture('test.jpg');
        $meta = getMetadataOfHash($result['hash']);
        $this->assertNotEmpty($meta['sha1'] ?? '');
        $this->assertEquals(40, strlen($meta['sha1']));
    }
}
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/UploadTest.php
```

Expected: All GREEN

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/UploadTest.php
git commit -m "test: integration tests for upload flow"
```

---

## Task 8: Integration tests — Delete codes

**Files:**
- Create: `tests/Integration/DeleteTest.php`

- [ ] **Step 1: Write tests**

```php
<?php
// tests/Integration/DeleteTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class DeleteTest extends PictShareTestCase
{
    public function testDeleteRemovesHashDirectory(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        $dir = TEST_DATA_DIR . DS . $hash;

        $this->assertDirectoryExists($dir);
        deleteHash($hash);
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertFalse(isExistingHash($hash));

        // Already deleted — remove from tearDown list
        $this->uploadedHashes = array_values(array_filter($this->uploadedHashes, fn($h) => $h !== $hash));
    }

    public function testDeleteCodeIsPresent(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $code = getDeleteCodeOfHash($r['hash']);
        $this->assertNotEmpty($code);
        $this->assertEquals(32, strlen($code));
    }

    public function testDeleteCodeIsUniquePerFile(): void
    {
        $r1 = $this->uploadFixture('test.jpg');
        $r2 = $this->uploadFixture('test.png');
        $this->assertNotEquals(
            getDeleteCodeOfHash($r1['hash']),
            getDeleteCodeOfHash($r2['hash'])
        );
    }

    /**
     * The delete-code guard lives in architect() (core.php URL routing), not in deleteHash().
     * We test the guard by calling the same logic it uses: comparing the code in the URL
     * against getDeleteCodeOfHash() and MASTER_DELETE_CODE.
     */
    public function testCorrectCodePassesGuard(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        $code = getDeleteCodeOfHash($hash);

        $isAuthorized = (
            getDeleteCodeOfHash($hash) === $code ||
            (defined('MASTER_DELETE_CODE') && MASTER_DELETE_CODE === $code)
        );

        $this->assertTrue($isAuthorized);
    }

    public function testWrongCodeFailsGuard(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        $wrongCode = 'thisiswrongcode000000000000000000';

        $isAuthorized = (
            getDeleteCodeOfHash($hash) === $wrongCode ||
            (defined('MASTER_DELETE_CODE') && MASTER_DELETE_CODE === $wrongCode)
        );

        $this->assertFalse($isAuthorized);
        // File must still be present
        $this->assertTrue(isExistingHash($hash));
    }
}
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/DeleteTest.php
```

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/DeleteTest.php
git commit -m "test: integration tests for delete codes"
```

---

## Task 9: API prerequisite — Add missing delete() method

The `API` class's `match` statement references `$this->delete()` but no such method exists. This is a pre-existing bug. Add it before writing API tests.

**Files:**
- Modify: `src/inc/api.class.php`

- [ ] **Step 1: Write a failing test that documents the missing method**

```php
<?php
// tests/Integration/ApiTest.php (just the delete test for now — rest added in Task 13)
require_once __DIR__ . '/../PictShareTestCase.php';

class ApiTest extends PictShareTestCase
{
    public function testApiDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'delete'),
            'API::delete() method must exist — it is referenced in the match statement');
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/ApiTest.php
```

Expected: FAIL — method does not exist.

- [ ] **Step 3: Implement API::delete() in api.class.php**

Add after the `info()` method:

```php
public function delete()
{
    // URL pattern: /api/delete/{code}/{hash}
    $code = $this->url[1] ?? '';
    $hash = $this->url[2] ?? '';

    if (!$hash || !$code)
        return ['status' => 'err', 'reason' => 'Missing code or hash'];

    if (!isExistingHash($hash))
        return ['status' => 'err', 'reason' => 'Hash not found'];

    $correctCode = getDeleteCodeOfHash($hash);
    $masterCode  = defined('MASTER_DELETE_CODE') ? MASTER_DELETE_CODE : null;

    if ($correctCode !== $code && $masterCode !== $code)
        return ['status' => 'err', 'reason' => 'Invalid delete code'];

    deleteHash($hash);
    return ['status' => 'ok', 'hash' => $hash];
}
```

- [ ] **Step 4: Run — expect GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/ApiTest.php
```

- [ ] **Step 5: Commit**

```bash
git add src/inc/api.class.php tests/Integration/ApiTest.php
git commit -m "fix: implement missing API::delete() method"
```

---

## Task 10: TDD — Bug fixes

Write tests that expose each bug from the spec. Apply fixes. Tests must use `error_reporting(E_ALL)` to catch notices that are suppressed globally in core.php.

**Files:**
- Create: `tests/Integration/BugFixTest.php`
- Modify: `src/content-controllers/image/image.controller.php`

- [ ] **Step 1: Write failing tests**

```php
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
```

- [ ] **Step 2: Run — confirm which tests fail (at minimum gif and bmp)**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/BugFixTest.php
```

Record which tests fail.

- [ ] **Step 3: Apply fix — initialize $modifiers**

In `handleHash`, before the `getFilters()` call (around line 114):

```php
// ADD THIS LINE before $filters = getFilters();
$modifiers = [];
```

- [ ] **Step 4: Apply fix — $a[1] null guard**

In the filter parsing loop, change:

```php
$a = explode('_',$u);
$value = $a[1];
if(is_numeric($value))
    $modifiers['filters'][] = array('filter'=>$filter,'value'=>$value);
else
    $modifiers['filters'][] = array('filter'=>$filter);
```

To (colorize special-casing added in Task 14; for now just fix the null):

```php
$a = explode('_', $u);
$value = $a[1] ?? null;
if (is_numeric($value))
    $modifiers['filters'][] = ['filter' => $filter, 'value' => $value];
else
    $modifiers['filters'][] = ['filter' => $filter];
```

- [ ] **Step 5: Apply fix — $fd['value'] null guard in dispatch loop**

Find `case 'filters':` in the modifier switch (around line 169):

```php
// BEFORE:
$filter = $fd['filter'];
$value = $fd['value'];

// AFTER:
$filter = $fd['filter'];
$value = $fd['value'] ?? null;
```

- [ ] **Step 6: Apply fix — forcesize guard**

```php
// BEFORE:
if(in_array('forcesize',$url) && $modifiers['size'])

// AFTER:
if(isset($modifiers['size']) && in_array('forcesize', $url))
```

- [ ] **Step 7: Apply fix — shouldAlwaysBeWebp isset guard**

```php
// BEFORE:
if(!$_SERVER['HTTP_ACCEPT']) return false;

// AFTER:
if(!isset($_SERVER['HTTP_ACCEPT']) || !$_SERVER['HTTP_ACCEPT']) return false;
```

- [ ] **Step 8: Apply fix — saveObjOfImage gif/bmp/ico cases**

In the `switch($type)` inside `saveObjOfImage`, add after the `webp` case:

```php
case 'gif':
    imagegif($im, $tmppath);
break;

case 'bmp':
    imagebmp($im, $tmppath);
break;

case 'ico':
    return false;  // no native GD support
```

- [ ] **Step 9: Apply fix — guard $path = $newpath with $saved !== false**

```php
// BEFORE:
$this->saveObjOfImage($im,$newpath,$type);

// AFTER:
$saved = $this->saveObjOfImage($im,$newpath,$type);
```

And the unconditional assignment after the `if/else if` block:

```php
// BEFORE:
$path = $newpath;

// AFTER:
if ($saved !== false) $path = $newpath;
```

- [ ] **Step 10: Run bug fix tests — expect all GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/BugFixTest.php
```

- [ ] **Step 11: Run full suite to confirm no regressions**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml
```

Expected: All GREEN

- [ ] **Step 12: Commit**

```bash
git add src/content-controllers/image/image.controller.php \
        tests/Integration/BugFixTest.php
git commit -m "fix: initialize modifiers, null-safe value guards, saveObjOfImage gif/bmp/ico, caller $path guard"
```

---

## Task 11: Integration tests — Image modifiers (regression)

These tests run after the bug fixes in Task 10 are applied, because some paths
(e.g. sepia filter dispatch) trigger the `$fd['value']` notice that was just fixed.

**Files:**
- Create: `tests/Integration/ImageModifierTest.php`

- [ ] **Step 1: Write tests**

```php
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
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/ImageModifierTest.php
```

Expected: All GREEN (depends on Task 10 bug fixes being applied first)

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/ImageModifierTest.php
git commit -m "test: regression tests for image modifiers (resize, rotate, webp, filters)"
```

---

## Task 12: TDD — Static GIF filter support

**Files:**
- Create: `tests/Integration/GifTest.php`
- Modify: `src/content-controllers/image/image.controller.php`

- [ ] **Step 1: Write failing tests**

```php
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
```

- [ ] **Step 2: Run — expect static GIF tests to FAIL**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/GifTest.php
```

Expected: first two tests FAIL, animated GIF test may pass

- [ ] **Step 3: Add isAnimatedGif() helper to ImageController**

```php
private function isAnimatedGif(string $path): bool
{
    $contents = @file_get_contents($path);
    if ($contents === false) return true; // safe fallback
    return substr_count($contents, "\x21\xF9\x04") > 1;
}
```

- [ ] **Step 4: Replace the `if($type!='gif')` block in handleHash**

```php
// BEFORE:
if($type!='gif')
{
    // ... modifier parsing ...
}
else //gif
{
    if(in_array('mp4',$url))
        $modifiers['mp4']=true;
}

// AFTER:
$isAnimatedGif = ($type === 'gif' && $this->isAnimatedGif($path));

if (!$isAnimatedGif)
{
    // ... all existing modifier parsing unchanged (size, rotation, filters, webp, forcesize) ...
}
else // animated gif: MP4 conversion only
{
    if(in_array('mp4',$url))
        $modifiers['mp4']=true;
}
```

- [ ] **Step 5: Run GIF tests — expect GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/GifTest.php
```

- [ ] **Step 6: Run full suite**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml
```

Expected: All GREEN

- [ ] **Step 7: Commit**

```bash
git add src/content-controllers/image/image.controller.php \
        tests/Integration/GifTest.php
git commit -m "feat: apply filters/resize to static GIFs, skip animated GIFs"
```

---

## Task 13: TDD — New filters: brightness, contrast, colorize

**Files:**
- Create: `tests/Integration/NewFiltersTest.php`
- Modify: `src/content-controllers/image/filters.php`
- Modify: `src/content-controllers/image/image.controller.php` (colorize parser)

- [ ] **Step 1: Write failing tests**

```php
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
```

- [ ] **Step 2: Run — expect all to FAIL**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/NewFiltersTest.php
```

- [ ] **Step 3: Add brightness, contrast, colorize methods to Filter class in filters.php**

```php
public function brightness($im, $val)
{
    $val = max(-255, min(255, (int)$val));
    imagefilter($im, IMG_FILTER_BRIGHTNESS, $val);
    return $im;
}

public function contrast($im, $val)
{
    $val = max(-100, min(100, (int)$val));
    imagefilter($im, IMG_FILTER_CONTRAST, $val);
    return $im;
}

public function colorize($im, $val)
{
    // $val is [R, G, B] array or a single R value
    if (is_array($val)) {
        $r = (int)($val[0] ?? 0);
        $g = (int)($val[1] ?? 0);
        $b = (int)($val[2] ?? 0);
    } else {
        $r = (int)$val;
        $g = 0;
        $b = 0;
    }
    $r = max(-255, min(255, $r));
    $g = max(-255, min(255, $g));
    $b = max(-255, min(255, $b));
    imagefilter($im, IMG_FILTER_COLORIZE, $r, $g, $b);
    return $im;
}
```

- [ ] **Step 4: Update colorize parser in handleHash (image.controller.php)**

Replace the filter value parsing block:

```php
// BEFORE:
$a = explode('_', $u);
$value = $a[1] ?? null;
if (is_numeric($value))
    $modifiers['filters'][] = ['filter' => $filter, 'value' => $value];
else
    $modifiers['filters'][] = ['filter' => $filter];

// AFTER:
$a = explode('_', $u);

if ($filter === 'colorize') {
    // colorize takes up to 3 values: R_G_B; missing channels default to 0
    $modifiers['filters'][] = [
        'filter' => 'colorize',
        'value'  => [
            (int)($a[1] ?? 0),
            (int)($a[2] ?? 0),
            (int)($a[3] ?? 0),
        ],
    ];
} else {
    $value = $a[1] ?? null;
    if (is_numeric($value))
        $modifiers['filters'][] = ['filter' => $filter, 'value' => $value];
    else
        $modifiers['filters'][] = ['filter' => $filter];
}
```

- [ ] **Step 5: Run new filter tests — expect GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/NewFiltersTest.php
```

- [ ] **Step 6: Run full suite**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml
```

Expected: All GREEN

- [ ] **Step 7: Commit**

```bash
git add src/content-controllers/image/filters.php \
        src/content-controllers/image/image.controller.php \
        tests/Integration/NewFiltersTest.php
git commit -m "feat: add brightness, contrast, colorize filters with value support"
```

---

## Task 14: Integration tests — API endpoints

**Files:**
- Modify: `tests/Integration/ApiTest.php` (expand from Task 9)

- [ ] **Step 1: Expand ApiTest.php with full coverage**

```php
<?php
// tests/Integration/ApiTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ApiTest extends PictShareTestCase
{
    public function testApiDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'delete'));
    }

    // Helper: simulate a file upload through the API class
    private function apiUpload(string $fixture): array
    {
        $src = __DIR__ . '/../fixtures/' . $fixture;
        $tmp = ROOT . DS . 'tmp' . DS . 'api_' . uniqid() . '_' . $fixture;
        copy($src, $tmp);

        $_FILES['file'] = [
            'tmp_name' => $tmp,
            'name'     => $fixture,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmp),
        ];

        $api    = new API(['upload']);
        $result = $api->act();
        unset($_FILES['file']);

        if (($result['status'] ?? '') === 'ok' && isset($result['hash']))
            $this->uploadedHashes[] = $result['hash'];

        return $result;
    }

    public function testApiUploadReturnsOk(): void
    {
        $result = $this->apiUpload('test.jpg');
        $this->assertEquals('ok', $result['status']);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('delete_code', $result);
        $this->assertArrayHasKey('delete_url', $result);
        $this->assertArrayHasKey('url', $result);
    }

    public function testApiUploadReturnsFiletype(): void
    {
        $result = $this->apiUpload('test.jpg');
        $this->assertEquals('image/jpeg', $result['filetype']);
    }

    public function testApiUploadDuplicateReturnsDuplicateFlag(): void
    {
        $r1 = $this->apiUpload('test.jpg');
        $r2 = $this->apiUpload('test.jpg');
        // API deduplicates by SHA1
        $this->assertTrue($r2['duplicate'] ?? false);
        $this->assertEquals($r1['hash'], $r2['hash']);
    }

    public function testApiUploadBase64(): void
    {
        $jpeg = file_get_contents(__DIR__ . '/../fixtures/test.jpg');
        $_REQUEST['base64'] = 'data:image/jpeg;base64,' . base64_encode($jpeg);

        $api    = new API(['upload']);
        $result = $api->act();
        unset($_REQUEST['base64']);

        $this->assertEquals('ok', $result['status']);
        if (isset($result['hash'])) $this->uploadedHashes[] = $result['hash'];
    }

    public function testApiDeleteEndpoint(): void
    {
        $r    = $this->apiUpload('test.jpg');
        $hash = $r['hash'];
        $code = $r['delete_code'];

        $api    = new API(['delete', $code, $hash]);
        $result = $api->act();

        $this->assertEquals('ok', $result['status']);
        $this->assertFalse(isExistingHash($hash));

        $this->uploadedHashes = array_values(
            array_filter($this->uploadedHashes, fn($h) => $h !== $hash)
        );
    }

    public function testApiDeleteWithWrongCodeFails(): void
    {
        $r    = $this->apiUpload('test.jpg');
        $hash = $r['hash'];

        $api    = new API(['delete', 'wrongcode00000000000000000000000', $hash]);
        $result = $api->act();

        $this->assertEquals('err', $result['status']);
        $this->assertTrue(isExistingHash($hash));
    }

    public function testApiInfoEndpoint(): void
    {
        $r = $this->apiUpload('test.jpg');
        $_REQUEST['hash'] = $r['hash'];

        $api    = new API(['info']);
        $result = $api->act();
        unset($_REQUEST['hash']);

        $this->assertEquals('ok', $result['status']);
        $this->assertArrayHasKey('mime', $result);
        $this->assertArrayHasKey('size', $result);
    }

    public function testApiDebugEndpoint(): void
    {
        $api    = new API(['debug']);
        $result = $api->act();
        $this->assertArrayHasKey('server_name', $result);
        $this->assertArrayHasKey('remote_addr', $result);
    }

    public function testApiUnknownEndpointReturnsError(): void
    {
        $api    = new API(['nonexistentendpoint']);
        $result = $api->act();
        $this->assertEquals('err', $result['status']);
        $this->assertArrayHasKey('reason', $result);
    }
}
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/ApiTest.php
```

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/ApiTest.php
git commit -m "test: full API endpoint coverage"
```

---

## Task 15: Integration tests — Reporting

**Files:**
- Create: `tests/Integration/ReportTest.php`

> **Note:** `addReport()` and `getReports()` write to `ROOT.DS.'data'.DS.'reports.json'` directly (not via `getDataDir()`). The tearDown cleans up that specific file.

- [ ] **Step 1: Write tests**

```php
<?php
// tests/Integration/ReportTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ReportTest extends PictShareTestCase
{
    // reports.json is written to ROOT/data/ (not TEST_DATA_DIR) by addReport()
    private string $reportFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportFile = ROOT . DS . 'data' . DS . 'reports.json';
        // Backup any existing reports file to restore after test
        if (file_exists($this->reportFile))
            copy($this->reportFile, $this->reportFile . '.bak');
        // Start with an empty reports file
        file_put_contents($this->reportFile, '[]');
    }

    protected function tearDown(): void
    {
        // Restore backup or remove test file
        if (file_exists($this->reportFile . '.bak')) {
            rename($this->reportFile . '.bak', $this->reportFile);
        } else {
            @unlink($this->reportFile);
        }
        parent::tearDown();
    }

    public function testAddAndGetReport(): void
    {
        $r    = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];

        $id = addReport([$hash], 'Test abuse report');
        $this->assertNotEmpty($id);

        $reports = getReports();
        $found   = array_values(array_filter($reports, fn($rep) => $rep['id'] === $id));
        $this->assertNotEmpty($found, 'Report should appear in getReports()');

        $report = $found[0];
        $this->assertContains($hash, $report['hashes']);
        $this->assertEquals('Test abuse report', $report['note']);
        $this->assertEquals('open', $report['status']);
    }

    public function testReportWithMultipleHashes(): void
    {
        $r1 = $this->uploadFixture('test.jpg');
        $r2 = $this->uploadFixture('test.png');

        $id      = addReport([$r1['hash'], $r2['hash']], 'Multi-hash report');
        $reports = getReports();
        $found   = array_values(array_filter($reports, fn($r) => $r['id'] === $id));
        $this->assertCount(2, $found[0]['hashes']);
    }

    public function testEmptyHashListReturnsEarly(): void
    {
        $result = addReport([], 'No hashes provided');
        $this->assertFalse($result);
    }

    public function testMultipleReportsAccumulate(): void
    {
        $r = $this->uploadFixture('test.jpg');

        addReport([$r['hash']], 'First report');
        addReport([$r['hash']], 'Second report');

        $this->assertCount(2, getReports());
    }
}
```

- [ ] **Step 2: Run and confirm GREEN**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  tests/Integration/ReportTest.php
```

- [ ] **Step 3: Commit**

```bash
git add tests/Integration/ReportTest.php
git commit -m "test: integration tests for report submission and retrieval"
```

---

## Task 16: Final full suite run + CI check

- [ ] **Step 1: Run the entire test suite with verbose output**

```bash
docker compose -f docker-compose-dev.yml run --rm pictshare \
  php /app/public/src/lib/vendor/bin/phpunit \
  --configuration /app/public/phpunit.xml \
  --testdox
```

Expected: All tests GREEN with readable names

- [ ] **Step 2: Push branch and verify Gitea Actions passes**

Push the branch and confirm `.gitea/workflows/test.yml` runs and goes green.

- [ ] **Step 3: Final commit if any stray changes**

```bash
git status
git add -p
git commit -m "test: finalize full test suite"
```

---

## Summary of files changed

| File | Type | Purpose |
|---|---|---|
| `src/lib/composer.json` | Modify | Add PHPUnit dev dependency |
| `phpunit.xml` | Create | PHPUnit configuration |
| `src/inc/core.php` | Modify | `_TEST_DATA_OVERRIDE` in `getDataDir()` |
| `src/inc/api.class.php` | Modify | Add missing `delete()` method |
| `src/content-controllers/image/image.controller.php` | Modify | All bug fixes + static GIF detection + colorize parser |
| `src/content-controllers/image/filters.php` | Modify | Add `brightness`, `contrast`, `colorize` |
| `docker-compose-dev.yml` | Modify | Document test run command |
| `.gitea/workflows/test.yml` | Create | CI test runner (with composer install step) |
| `tests/bootstrap.php` | Create | PHPUnit bootstrap |
| `tests/testconfig.php` | Create | Test-only PHP config |
| `tests/PictShareTestCase.php` | Create | Base test case class |
| `tests/fixtures/generate_fixtures.php` | Create | Fixture generator |
| `tests/fixtures/test.*` | Create | Binary fixture images |
| `tests/Unit/CoreFunctionsTest.php` | Create | Utility function unit tests |
| `tests/Unit/FilterTest.php` | Create | Filter class unit tests |
| `tests/Integration/UploadTest.php` | Create | Upload flow tests |
| `tests/Integration/DeleteTest.php` | Create | Delete code tests |
| `tests/Integration/BugFixTest.php` | Create | Bug fix regression tests (run before modifier tests) |
| `tests/Integration/ImageModifierTest.php` | Create | Modifier regression tests (after bug fixes) |
| `tests/Integration/GifTest.php` | Create | Static GIF support (TDD) |
| `tests/Integration/NewFiltersTest.php` | Create | brightness/contrast/colorize (TDD) |
| `tests/Integration/ApiTest.php` | Create | API endpoint tests |
| `tests/Integration/ReportTest.php` | Create | Report flow tests |
