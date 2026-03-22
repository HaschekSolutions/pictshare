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
