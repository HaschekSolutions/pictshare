<?php
// tests/Unit/ViewTrackingTest.php
use PHPUnit\Framework\TestCase;

class ViewTrackingTest extends TestCase
{
    private FakeRedis $redis;
    private mixed $previousRedis;
    private mixed $previousUserAgent;
    private mixed $previousReferer;

    protected function setUp(): void
    {
        $this->previousRedis = $GLOBALS['redis'] ?? null;
        $this->redis = new FakeRedis();
        $GLOBALS['redis'] = $this->redis;

        // Set up minimal HTTP superglobals for architect() testing
        $this->previousUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $this->previousReferer = $_SERVER['HTTP_REFERER'] ?? null;
        $_SERVER['HTTP_USER_AGENT'] = 'test-agent';
        $_SERVER['HTTP_REFERER'] = 'test-referer';
    }

    protected function tearDown(): void
    {
        $GLOBALS['redis'] = $this->previousRedis;
        $_SERVER['HTTP_USER_AGENT'] = $this->previousUserAgent;
        $_SERVER['HTTP_REFERER'] = $this->previousReferer;
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

    public function testCacheHitGuardsAgainstDynamicControllerSentinel(): void
    {
        // Simulate a cached dynamic URL (dynamic controllers use $hash = true, which becomes "1" in Redis)
        // This tests the guard condition: if($hash !== '1') recordView($hash);
        $url = ['identicon', 'user@example.com', '200x200'];
        $urlKey = 'cache:byurl:' . implode('/', $url);
        // Seed the cache with a dynamic controller response (IdenticonController gets serialized as "IdenticonController;1")
        $this->redis->set($urlKey, 'IdenticonController;1');

        // Call architect() with this URL — it should hit the cache and extract $hash = "1"
        // The guard in architect() (if($hash !== '1') recordView($hash)) should prevent recordView('1') from being called
        ob_start();
        architect($url);
        ob_end_clean();

        // Verify that lastaccessed:1 was NOT created (proving the guard worked)
        $this->assertNull($this->redis->get('lastaccessed:1'),
            'Dynamic controller cache hit should not create lastaccessed:1');
    }

    // --- redisScanKeys() ---

    public function testRedisScanKeysReturnsMatchingKeys(): void
    {
        $this->redis->set('lastaccessed:aaa', '1000');
        $this->redis->set('lastaccessed:bbb', '2000');
        $this->redis->set('served:aaa', '5'); // must NOT match the pattern

        $keys = redisScanKeys('lastaccessed:*');
        sort($keys);

        $this->assertEquals(['lastaccessed:aaa', 'lastaccessed:bbb'], $keys);
    }

    public function testRedisScanKeysReturnsEmptyWithoutRedis(): void
    {
        $GLOBALS['redis'] = null;
        $this->assertEquals([], redisScanKeys('lastaccessed:*'));
    }

    // --- flushViews() ---

    private function makeTestHash(string $hash): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir . DS . $hash, 'x');
        file_put_contents($dir . DS . 'meta.json', json_encode(['mime' => 'image/jpeg']));
    }

    private function removeTestHash(string $hash): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . DS . '*'));
            rmdir($dir);
        }
    }

    public function testFlushViewsMergesIntoMetaJsonAndClearsRedisKey(): void
    {
        $this->makeTestHash('flush001');
        $this->redis->set('lastaccessed:flush001', '1712345678');
        $this->redis->set('served:flush001', '7');

        $result = flushViews();

        $this->assertEquals(['flush001'], $result['flushed']);
        $this->assertEquals([], $result['skipped']);

        $meta = getMetadataOfHash('flush001');
        $this->assertEquals(1712345678, $meta['last_accessed']);
        $this->assertEquals(7, $meta['views']);
        $this->assertEquals('image/jpeg', $meta['mime']); // pre-existing field preserved

        $this->assertNull($this->redis->get('lastaccessed:flush001'));

        $this->removeTestHash('flush001');
    }

    public function testFlushViewsSkipsDeletedHash(): void
    {
        // No makeTestHash() call — directory never existed
        $this->redis->set('lastaccessed:ghost002', '1712345678');

        $result = flushViews();

        $this->assertEquals([], $result['flushed']);
        $this->assertEquals(['ghost002'], $result['skipped']);
        // Key is left in place for a future pass, per the design doc
        $this->assertEquals('1712345678', $this->redis->get('lastaccessed:ghost002'));
    }

    public function testFlushViewsDefaultsMissingViewCountToZero(): void
    {
        $this->makeTestHash('flush003');
        $this->redis->set('lastaccessed:flush003', '1712345678');
        // No served:flush003 key set at all

        flushViews();

        $meta = getMetadataOfHash('flush003');
        $this->assertEquals(0, $meta['views']);

        $this->removeTestHash('flush003');
    }

    public function testFlushViewsNoOpsWithoutRedis(): void
    {
        $this->redis->set('lastaccessed:flush004', '1712345678');
        $GLOBALS['redis'] = null;

        $result = flushViews();

        $this->assertEquals(['flushed' => [], 'skipped' => []], $result);
    }
}
