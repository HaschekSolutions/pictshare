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
}
