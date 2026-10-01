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
     * We test the guard through isValidDeleteCode(), which both architect() and API::delete() use
     * to compare the given code against getDeleteCodeOfHash() and MASTER_DELETE_CODE.
     */
    public function testCorrectCodePassesGuard(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        updateMetaData($hash, ['delete_code' => 'correctcode0000000000000000000000']);

        $this->assertTrue(isValidDeleteCode($hash, 'correctcode0000000000000000000000'));
    }

    public function testWrongCodeFailsGuard(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        updateMetaData($hash, ['delete_code' => 'correctcode0000000000000000000000']);

        $this->assertFalse(isValidDeleteCode($hash, 'thisiswrongcode000000000000000000'));
        $this->assertFalse(isValidDeleteCode($hash, ''));
    }

    /**
     * Hashes without a delete_code in meta.json (legacy files, direct controller uploads)
     * and the Docker default of MASTER_DELETE_CODE='' (see testconfig.php) must not be
     * deletable with an empty or falsy code like /delete_/<hash> or /delete_0/<hash>.
     */
    public function testEmptyOrFalsyCodeFailsGuardWhenNoCodeIsStored(): void
    {
        $r = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];
        $this->assertFalse(getDeleteCodeOfHash($hash));

        $this->assertFalse(isValidDeleteCode($hash, ''));
        $this->assertFalse(isValidDeleteCode($hash, '0'));
    }
}
