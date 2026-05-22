<?php
require_once __DIR__ . '/../PictShareTestCase.php';

class AlbumApiTest extends PictShareTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(TEST_DATA_DIR . DS . 'sha1.csv', '');
    }

    private function apiUpload(string $fixture): array
    {
        $src = __DIR__ . '/../fixtures/' . $fixture;
        $tmp = ROOT . DS . 'tmp' . DS . 'album_' . uniqid() . '_' . $fixture;
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

    public function testAlbumControllerLoads(): void
    {
        $this->assertTrue(class_exists('AlbumController'));
        $cc = new AlbumController();
        $this->assertContains('album', $cc->getRegisteredExtensions());
    }

    public function testCreateAlbumReturnsMissingHashes(): void
    {
        $api    = new API(['album']);
        $result = $api->act();
        $this->assertEquals('err', $result['status']);
        $this->assertStringContainsString('hashes', $result['reason']);
    }

    public function testCreateAlbumWithInvalidHashReturnsErr(): void
    {
        $_REQUEST['hashes'] = ['doesnotexist.jpg'];
        $api    = new API(['album']);
        $result = $api->act();
        unset($_REQUEST['hashes']);
        $this->assertEquals('err', $result['status']);
    }

    public function testCreateAlbumWithValidHashesReturnsOk(): void
    {
        $r1 = $this->apiUpload('test.jpg');
        $r2 = $this->apiUpload('test.png');

        $_REQUEST['hashes'] = [$r1['hash'], $r2['hash']];
        $api    = new API(['album']);
        $result = $api->act();
        unset($_REQUEST['hashes']);

        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.album', $result['hash']);
        $this->assertArrayHasKey('url', $result);

        $this->uploadedHashes[] = $result['hash'];
    }

    public function testCreateAlbumMetaJsonContainsHashes(): void
    {
        $r1 = $this->apiUpload('test.jpg');

        $_REQUEST['hashes'] = [$r1['hash']];
        $api    = new API(['album']);
        $result = $api->act();
        unset($_REQUEST['hashes']);

        $this->assertEquals('ok', $result['status']);
        $albumHash = $result['hash'];
        $this->uploadedHashes[] = $albumHash;

        $meta = getMetadataOfHash($albumHash);
        $this->assertContains($r1['hash'], $meta['hashes']);
    }
}
