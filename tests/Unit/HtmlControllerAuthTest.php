<?php
// tests/Unit/HtmlControllerAuthTest.php
use PHPUnit\Framework\TestCase;

class HtmlControllerAuthTest extends TestCase
{
    // computeIsEnabled() is the pure form of the enabled check - HTML_HOSTING_ENABLED
    // is fixed 'true' for the whole test suite, so the disabled branch is tested here
    // rather than via a defined constant.
    public function testComputeIsEnabledRequiresBothFlagAndCode(): void
    {
        $this->assertFalse(HtmlController::computeIsEnabled(false, ''));
        $this->assertFalse(HtmlController::computeIsEnabled(false, 'secret'));
        $this->assertFalse(HtmlController::computeIsEnabled(true, ''));
        $this->assertTrue(HtmlController::computeIsEnabled(true, 'secret'));
    }

    public function testCheckUploadCodeRequiresEnabledAndMatchingCode(): void
    {
        $this->assertTrue(HtmlController::checkUploadCode('secret', true, 'secret'));
        $this->assertFalse(HtmlController::checkUploadCode('wrong', true, 'secret'));
        $this->assertFalse(HtmlController::checkUploadCode(null, true, 'secret'));
        $this->assertFalse(HtmlController::checkUploadCode('secret', false, 'secret'));
        $this->assertFalse(HtmlController::checkUploadCode('secret', true, ''));
    }
}
