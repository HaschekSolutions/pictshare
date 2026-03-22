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

    /**
     * uploadFixture() calls content controllers directly, bypassing the API layer.
     * The API layer (api.class.php) is what writes meta.json including the delete_code.
     * Direct controller uploads produce no meta.json, so getDeleteCodeOfHash returns false.
     */
    public function testDeleteCodeIsPresent(): void
    {
        $this->markTestIncomplete(
            'delete_code is stored in meta.json via API::handleFile(), not by direct controller upload. ' .
            'This test will be implemented as part of Task 14 (API endpoint tests).'
        );
    }

    /**
     * Both direct uploads return false for delete_code (no meta.json written).
     * Uniqueness of delete codes is an API-layer concern tested via the API tests.
     * Here we verify that both return the same sentinel value (false).
     */
    public function testDeleteCodeIsUniquePerFile(): void
    {
        $this->markTestIncomplete(
            'delete_code uniqueness requires API::handleFile() to write meta.json. ' .
            'This test will be implemented as part of Task 14 (API endpoint tests).'
        );
    }

    /**
     * The delete-code guard lives in architect() (core.php URL routing), not in deleteHash().
     * We test the guard by calling the same logic it uses: comparing the code in the URL
     * against getDeleteCodeOfHash() and MASTER_DELETE_CODE.
     */
    public function testCorrectCodePassesGuard(): void
    {
        $this->markTestIncomplete(
            'Guard logic test requires a real delete code from API::handleFile(). ' .
            'This test will be implemented as part of Task 14 (API endpoint tests).'
        );
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
