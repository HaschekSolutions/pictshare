<?php

require_once __DIR__ . '/../PictShareTestCase.php';

class SvgTest extends PictShareTestCase
{
    public function testSvgUpload(): void
    {
        $result = $this->uploadFixture('test.svg');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.svg', $result['hash']);
    }

    public function testCleanSvgKeepsAnimationsAndStyle(): void
    {
        $upload = $this->uploadFixture('test.svg');
        $hash = $upload['hash'];
        $stored = file_get_contents(TEST_DATA_DIR . DS . $hash . DS . $hash);

        $this->assertStringContainsString('<style>', $stored);
        $this->assertStringContainsString('<animate', $stored);
        $this->assertStringContainsString('<animateTransform', $stored);
        $this->assertStringContainsString('repeatCount="indefinite"', $stored);
        $this->assertStringContainsString('from="10"', $stored);
        $this->assertStringContainsString('to="40"', $stored);
    }

    public function testMaliciousSvgIsSanitized(): void
    {
        $upload = $this->uploadFixture('malicious.svg');
        $this->assertEquals('ok', $upload['status']);
        $hash = $upload['hash'];
        $stored = file_get_contents(TEST_DATA_DIR . DS . $hash . DS . $hash);

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);
        $this->assertStringNotContainsString('foreignObject', $stored);
        $this->assertStringNotContainsString('evil.example.com', $stored);
    }

    public function testHandleHashServesSanitizedSvg(): void
    {
        $upload = $this->uploadFixture('test.svg');
        $hash = $upload['hash'];

        $output = $this->handleHashWithModifiers($hash, []);

        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('<animate', $output);
    }
}
