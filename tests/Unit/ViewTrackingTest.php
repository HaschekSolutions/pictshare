<?php
// tests/Unit/ViewTrackingTest.php
use PHPUnit\Framework\TestCase;

class ViewTrackingTest extends TestCase
{
    private FakeRedis $redis;
    private mixed $previousRedis;

    protected function setUp(): void
    {
        $this->previousRedis = $GLOBALS['redis'] ?? null;
        $this->redis = new FakeRedis();
        $GLOBALS['redis'] = $this->redis;
    }

    protected function tearDown(): void
    {
        $GLOBALS['redis'] = $this->previousRedis;
    }

    public function testRecordViewIncrementsServedCounter(): void
    {
        recordView('abc123.jpg');
        recordView('abc123.jpg');

        $this->assertEquals('2', $this->redis->get('served:abc123.jpg'));
    }

    public function testRecordViewSetsLastAccessedTimestamp(): void
    {
        $before = time();
        recordView('abc123.jpg');
        $after = time();

        $ts = (int)$this->redis->get('lastaccessed:abc123.jpg');
        $this->assertGreaterThanOrEqual($before, $ts);
        $this->assertLessThanOrEqual($after, $ts);
    }

    public function testRecordViewNoOpsWithoutRedis(): void
    {
        $GLOBALS['redis'] = null;
        // Must not throw/fatal when Redis is unavailable
        recordView('abc123.jpg');
        $this->assertNull($this->redis->get('served:abc123.jpg'));
    }
}
