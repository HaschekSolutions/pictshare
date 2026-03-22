<?php
// tests/Integration/ApiTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ApiTest extends PictShareTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset sha1.csv so duplicate detection works correctly within each test,
        // without stale entries from prior tests whose files were deleted in tearDown.
        file_put_contents(TEST_DATA_DIR . DS . 'sha1.csv', '');
    }

    public function testApiDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'delete'));
    }

    public function testApiInfoMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'info'));
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

        $api    = new API(['info', $r['hash']]);
        $result = $api->act();

        // info() returns raw metadata (no 'status' envelope)
        $this->assertArrayHasKey('mime', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertEquals($r['hash'], $result['hash']);
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
