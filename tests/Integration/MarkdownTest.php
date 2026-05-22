<?php

require_once __DIR__ . '/../PictShareTestCase.php';

class MarkdownTest extends PictShareTestCase
{
    public function testMarkdownUpload(): void
    {
        $result = $this->uploadFixture('test.md');
        $this->assertEquals('ok', $result['status']);
        $this->assertStringEndsWith('.md', $result['hash']);
    }

    public function testMarkdownRendering(): void
    {
        $upload = $this->uploadFixture('test.md');
        $hash = $upload['hash'];

        $output = $this->handleHashWithModifiers($hash, []);
        
        $this->assertStringContainsString('<h1>Test Markdown</h1>', $output);
        $this->assertStringContainsString('<strong>test</strong>', $output);
        $this->assertStringContainsString('<li>Item 1</li>', $output);
        $this->assertStringContainsString('<a href="https://example.com">Link</a>', $output);
    }

    public function testMarkdownRawRendering(): void
    {
        $upload = $this->uploadFixture('test.md');
        $hash = $upload['hash'];

        $output = $this->handleHashWithModifiers($hash, ['raw']);
        
        $this->assertStringContainsString('# Test Markdown', $output);
        $this->assertStringNotContainsString('<h1>', $output);
    }
}
