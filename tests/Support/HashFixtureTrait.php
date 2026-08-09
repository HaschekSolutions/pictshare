<?php
// tests/Support/HashFixtureTrait.php

/**
 * Shared helpers for creating/removing fake hash directories (a file plus
 * meta.json) inside the isolated TEST_DATA_DIR. Hashes created via
 * makeTestHash() are tracked and removed automatically by cleanupTestHashes()
 * — call that from the consuming TestCase's tearDown() so a failed assertion
 * mid-test can't leak a directory into the shared test data dir.
 */
trait HashFixtureTrait
{
    private array $testHashes = [];

    /** Create a fake hash directory with a file and meta.json in the test data dir. */
    private function makeTestHash(string $hash, array $meta = ['mime' => 'image/jpeg']): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        // Create the "file" (some callers rely on filesize() matching $meta['size'])
        file_put_contents($dir . DS . $hash, str_repeat('x', $meta['size'] ?? 10));
        file_put_contents($dir . DS . 'meta.json', json_encode($meta));
        $this->testHashes[] = $hash;
    }

    /** Remove a fake hash directory. */
    private function removeTestHash(string $hash): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . DS . '*'));
            rmdir($dir);
        }
    }

    /** Remove every hash directory created via makeTestHash() during the current test. Call from tearDown(). */
    private function cleanupTestHashes(): void
    {
        foreach ($this->testHashes as $hash) {
            $this->removeTestHash($hash);
        }
        $this->testHashes = [];
    }
}
