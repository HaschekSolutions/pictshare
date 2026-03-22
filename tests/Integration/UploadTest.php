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

    /**
     * uploadFixture() calls the content controller directly, bypassing the API layer.
     * The API layer (api.class.php handleFile()) is what writes meta.json with mime,
     * delete_code, sha1, etc. Direct controller uploads produce no meta.json.
     * So getMetadataOfHash returns an empty array for direct uploads.
     */
    public function testUploadedFileHasMetadata(): void
    {
        $this->markTestIncomplete(
            'meta.json is written by API::handleFile(), not by direct controller upload. ' .
            'This test will be implemented as part of Task 14 (API endpoint tests).'
        );
    }

    /**
     * delete_code is written to meta.json only via the API layer.
     * Direct controller uploads produce no meta.json, so getDeleteCodeOfHash returns false.
     */
    public function testUploadedFileHasDeleteCode(): void
    {
        $this->markTestIncomplete(
            'delete_code is stored in meta.json via API::handleFile(), not by direct controller upload. ' .
            'This test will be implemented as part of Task 14 (API endpoint tests).'
        );
    }

    /**
     * Deduplication only happens at the API layer (sha1Exists check in API::handleFile).
     * Direct controller uploads always create a new hash even for the same binary content.
     * storeFile() calls addSha1() which writes to sha1.csv — verify that index is populated.
     */
    public function testUploadPopulatesSha1Index(): void
    {
        $result = $this->uploadFixture('test.jpg');
        // sha1 is stored in sha1.csv by addSha1(), not in meta.json
        $filePath = TEST_DATA_DIR . DS . $result['hash'] . DS . $result['hash'];
        $sha1 = sha1_file($filePath);
        $this->assertNotEmpty($sha1);
        $this->assertEquals(40, strlen($sha1));
        // sha1Exists() looks up sha1.csv and returns the associated hash
        $this->assertEquals($result['hash'], sha1Exists($sha1));
    }
}
