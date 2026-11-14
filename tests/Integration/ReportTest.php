<?php
// tests/Integration/ReportTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ReportTest extends PictShareTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure clean state — reports go to TEST_DATA_DIR (via getDataDir())
        $reportFile = TEST_DATA_DIR . DS . 'reports.json';
        file_put_contents($reportFile, '[]');
    }

    // Note: tearDown from PictShareTestCase handles hash cleanup.
    // reports.json in TEST_DATA_DIR is isolated per test run.

    public function testAddAndGetReport(): void
    {
        $r    = $this->uploadFixture('test.jpg');
        $hash = $r['hash'];

        $id = addReport([$hash], 'Test abuse report');
        $this->assertNotEmpty($id);

        $reports = getReports();
        $found   = array_values(array_filter($reports, fn($rep) => $rep['id'] === $id));
        $this->assertNotEmpty($found, 'Report should appear in getReports()');

        $report = $found[0];
        $this->assertContains($hash, $report['hashes']);
        $this->assertEquals('Test abuse report', $report['note']);
        $this->assertEquals('open', $report['status']);
    }

    public function testReportWithMultipleHashes(): void
    {
        $r1 = $this->uploadFixture('test.jpg');
        $r2 = $this->uploadFixture('test.png');

        $id      = addReport([$r1['hash'], $r2['hash']], 'Multi-hash report');
        $reports = getReports();
        $found   = array_values(array_filter($reports, fn($r) => $r['id'] === $id));
        $this->assertCount(2, $found[0]['hashes']);
    }

    public function testEmptyHashListReturnsEarly(): void
    {
        $result = addReport([], 'No hashes provided');
        $this->assertFalse($result);
    }

    public function testMultipleReportsAccumulate(): void
    {
        $r = $this->uploadFixture('test.jpg');

        addReport([$r['hash']], 'First report');
        addReport([$r['hash']], 'Second report');

        $this->assertCount(2, getReports());
    }

    // GHSA-ghw4-6hfp-5pq8: submitted URL lines are echoed back and must be escaped
    public function testReportFormEscapesSkippedForeignUrl(): void
    {
        $_POST = ['urls' => '<script>alert(document.domain)</script>', 'note' => 'x'];
        try {
            $html = architect(['report']);
        } finally {
            $_POST = [];
        }

        $this->assertStringNotContainsString('<script>alert(document.domain)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(document.domain)&lt;/script&gt;', $html);
    }

    public function testReportFormEscapesAlreadyReportedUrl(): void
    {
        $r    = $this->uploadFixture('test.jpg', 'xssreport.jpg');
        $hash = $r['hash'];
        addReport([$hash], 'first');

        $_POST = ['urls' => URL . '<img src=x onerror=alert(1)>/' . $hash, 'note' => 'x'];
        try {
            $html = architect(['report']);
        } finally {
            $_POST = [];
        }

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
