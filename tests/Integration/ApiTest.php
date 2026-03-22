<?php
// tests/Integration/ApiTest.php
require_once __DIR__ . '/../PictShareTestCase.php';

class ApiTest extends PictShareTestCase
{
    public function testApiDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'delete'),
            'API::delete() method must exist — it is referenced in the match statement');
    }

    public function testApiInfoMethodExists(): void
    {
        $this->assertTrue(method_exists(new API(['']), 'info'),
            'API::info() method must exist — it is referenced in the match statement');
    }
}
