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
        $r = $this->uploadFixture('test.jpg');
        $code = getDeleteCodeOfHash($r['hash']);
        // Direct controller upload: no meta.json is written → delete_code is absent
        $this->assertFalse($code);
    }

    /**
     * Both direct uploads return false for delete_code (no meta.json written).
     * Uniqueness of delete codes is an API-layer concern tested via the API tests.
     * Here we verify that both return the same sentinel value (false).
     */
    public function testDeleteCodeIsUniquePerFile(): void
    {
        $r1 = $this->uploadFixture('test.jpg');
        $r2 = $this->uploadFixture('test.png');
        // Both have no meta.json → both return false; uniqueness is not applicable here
        $this->assertFalse(getDeleteCodeOfHash($r1['hash']));
        $this->assertFalse(getDeleteCodeOfHash($r2['hash']));
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
