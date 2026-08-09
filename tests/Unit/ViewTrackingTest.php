<?php
// tests/Unit/ViewTrackingTest.php
use PHPUnit\Framework\TestCase;

class ViewTrackingTest extends TestCase
{
    use HashFixtureTrait;

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
        $this->cleanupTestHashes();
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

    public function testCacheHitIncrementsDynamicControllerServedCounter(): void
    {
        // Same cache-hit setup as above, but this time verify the dynamic-controller
        // branch still counts views somewhere (served:<url>) instead of counting nothing,
        // which would be a regression from the pre-Task-2 behavior (which counted into
        // the junk key served:1, at least counting *something*).
        $url = ['identicon', 'user@example.com', '200x200'];
        $urlKey = 'cache:byurl:' . implode('/', $url);
        $this->redis->set($urlKey, 'IdenticonController;1');

        ob_start();
        architect($url);
        ob_end_clean();

        $this->assertEquals('1', $this->redis->get('served:' . implode('/', $url)));
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
    }

    public function testFlushViewsNoOpsWithoutRedis(): void
    {
        $this->redis->set('lastaccessed:flush004', '1712345678');
        $GLOBALS['redis'] = null;

        $result = flushViews();

        $this->assertEquals(['flushed' => [], 'skipped' => []], $result);
    }

    public function testFlushViewsSurvivesCorruptMetaJsonForOtherHashes(): void
    {
        // flush005 has a corrupted (truncated/invalid) meta.json. Before the fix,
        // json_decode() returning null made array_merge() in updateMetaData() a
        // fatal TypeError, which killed the whole flushViews() run — every hash
        // scanned after the bad one (alphabetically or iteration-order-wise) never
        // got flushed. Verify one corrupt hash doesn't take down the others.
        $this->makeTestHash('flush005');
        file_put_contents(TEST_DATA_DIR . DS . 'flush005' . DS . 'meta.json', '{not valid json');
        $this->redis->set('lastaccessed:flush005', '1712345678');
        $this->redis->set('served:flush005', '3');

        $this->makeTestHash('flush006');
        $this->redis->set('lastaccessed:flush006', '1712345999');
        $this->redis->set('served:flush006', '9');

        $result = flushViews();

        sort($result['flushed']);
        $this->assertEquals(['flush005', 'flush006'], $result['flushed']);
        $this->assertEquals([], $result['skipped']);

        // The corrupt file's stale content is dropped, but the new fields still land
        $meta005 = getMetadataOfHash('flush005');
        $this->assertEquals(1712345678, $meta005['last_accessed']);
        $this->assertEquals(3, $meta005['views']);

        $meta006 = getMetadataOfHash('flush006');
        $this->assertEquals(1712345999, $meta006['last_accessed']);
        $this->assertEquals(9, $meta006['views']);
    }

    public function testFlushViewsSkipsPathTraversalHash(): void
    {
        // isExistingHash() is just is_dir(), so a bare '.' or '..' would otherwise
        // resolve to the data dir itself (or its parent) and pass the existence
        // check, letting updateMetaData() write meta.json outside any real hash dir.
        $this->redis->set('lastaccessed:.', '1712345678');
        $this->redis->set('lastaccessed:..', '1712345678');

        $result = flushViews();

        sort($result['skipped']);
        $this->assertEquals(['.', '..'], $result['skipped']);
        $this->assertEquals([], $result['flushed']);
    }
}
