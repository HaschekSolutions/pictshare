<?php
// tests/Integration/HtmlControllerTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class HtmlControllerTest extends PictShareTestCase
{
    protected function tearDown(): void
    {
        unset($_REQUEST['htmluploadcode']);
        parent::tearDown();
    }

    public function testUploadRejectedWithoutCode(): void
    {
        unset($_REQUEST['htmluploadcode']);
        $result = $this->uploadFixture('test.html');
        $this->assertEquals('err', $result['status']);
    }

    public function testUploadRejectedWithWrongCode(): void
    {
        $_REQUEST['htmluploadcode'] = 'wrong';
        $result = $this->uploadFixture('test.html');
        $this->assertEquals('err', $result['status']);
    }

    public function testUploadAcceptedWithCorrectCode(): void
    {
        $_REQUEST['htmluploadcode'] = HTML_UPLOAD_CODE;
        $result = $this->uploadFixture('test.html');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.html', $result['hash']);
    }

    public function testHandleHashServesHtmlAsIs(): void
    {
        $_REQUEST['htmluploadcode'] = HTML_UPLOAD_CODE;
        $upload = $this->uploadFixture('test.html');

        $output = $this->handleHashWithModifiers($upload['hash'], []);

        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('Hello world', $output);
    }

    // Regression: api.class.php's sha1-dedup shortcut runs before any content
    // controller sees the upload, so re-uploading identical bytes without the
    // code must not be able to ride an earlier, correctly-authorized upload's
    // hash back out as a fresh "ok". uploadFixture() calls handleUpload()
    // directly and never exercises that dedup path, so this goes through the
    // real API::upload() flow instead.
    public function testDuplicateContentStillRequiresCodeViaRealApiUpload(): void
    {
        // Unique-per-run content, not the shared test.html fixture: reusing that
        // fixture would collide with stale sha1.csv entries left by every other
        // test in this file (deleteHash() in tearDown never prunes sha1.csv),
        // masking the bug behind the dedup shortcut's file_exists() guard.
        $content = '<!DOCTYPE html><html><body>unique ' . uniqid() . '</body></html>';

        $_REQUEST['htmluploadcode'] = HTML_UPLOAD_CODE;
        $first = $this->apiUploadContent($content);
        $this->assertSame('ok', $first['status'], json_encode($first));
        $this->uploadedHashes[] = $first['hash'];

        unset($_REQUEST['htmluploadcode']);
        $second = $this->apiUploadContent($content);
        $this->assertSame('err', $second['status'],
            'Re-uploading identical content without the code must still be rejected, not served from the sha1-dedup shortcut');
    }

    private function apiUploadContent(string $content): array
    {
        $tmp = ROOT . DS . 'tmp' . DS . 'htmlapi_' . uniqid() . '.html';
        file_put_contents($tmp, $content);
        $_FILES['file'] = ['tmp_name' => $tmp, 'name' => 'test.html', 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
        try {
            return (new API(['upload']))->act();
        } finally {
            unset($_FILES['file']);
        }
    }
}
