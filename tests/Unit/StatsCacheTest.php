<?php
// tests/Unit/StatsCacheTest.php
use PHPUnit\Framework\TestCase;

/**
 * Minimal in-memory Redis mock covering only the methods used by stats cache functions.
 */
class FakeRedis
{
    private array $strings = [];
    private array $hashes  = [];

    public function get(string $key): string|null
    {
        return $this->strings[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->strings[$key] = (string)$value;
    }

    public function del(string $key): void
    {
        unset($this->strings[$key], $this->hashes[$key]);
    }

    public function hset(string $key, string $field, string $value): void
    {
        $this->hashes[$key][$field] = $value;
    }

    public function hgetall(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }

    /** Simulate MGET: returns values in same order as keys, null for missing. */
    public function mget(array $keys): array
    {
        return array_map(fn($k) => $this->strings[$k] ?? null, $keys);
    }

    /** Minimal pipeline: collect hset calls and flush on exec(). Mirrors phpredis multi(Redis::PIPELINE). */
    public function multi(int $mode = 0): FakeRedisPipeline
    {
        return new FakeRedisPipeline($this);
    }
}

class FakeRedisPipeline
{
    private FakeRedis $redis;
    private array $ops = [];

    public function __construct(FakeRedis $redis) { $this->redis = $redis; }

    public function hset(string $key, string $field, string $value): void
    {
        $this->ops[] = [$key, $field, $value];
    }

    public function exec(): void
    {
        foreach ($this->ops as [$key, $field, $value]) {
            $this->redis->hset($key, $field, $value);
        }
    }
}

class StatsCacheTest extends TestCase
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

    // --- isCacheStale() ---

    public function testIsCacheStaleReturnsTrueWhenKeyMissing(): void
    {
        // stats:built_at not set → stale
        $this->assertTrue(isCacheStale());
    }

    public function testIsCacheStaleReturnsTrueWhenOlderThan5Minutes(): void
    {
        $this->redis->set('stats:built_at', (string)(time() - 301));
        $this->assertTrue(isCacheStale());
    }

    public function testIsCacheStaleReturnsFalseWhenFresh(): void
    {
        $this->redis->set('stats:built_at', (string)(time() - 60));
        $this->assertFalse(isCacheStale());
    }

    // --- rebuildStatsCache() ---

    /** Create a fake hash directory with a meta.json file in the test data dir. */
    private function makeTestHash(string $hash, array $meta): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        // Create the "file" (required for filesize())
        file_put_contents($dir . DS . $hash, str_repeat('x', $meta['size'] ?? 10));
        file_put_contents($dir . DS . 'meta.json', json_encode($meta));
    }

    /** Remove a fake hash directory. */
    private function removeTestHash(string $hash): void
    {
        $dir = TEST_DATA_DIR . DS . $hash;
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . DS . '*'));
            rmdir($dir);
        }
    }

    public function testRebuildStatsCacheWritesEntriesToRedis(): void
    {
        $this->makeTestHash('aaa111', ['mime' => 'image/jpeg', 'ip' => '1.2.3.4', 'uploaded' => 1700000000, 'original_filename' => 'cat.jpg', 'size' => 1024]);
        $this->makeTestHash('bbb222', ['mime' => 'image/png',  'ip' => '5.6.7.8', 'uploaded' => 1700001000, 'original_filename' => 'dog.png', 'size' => 2048]);

        rebuildStatsCache();

        $index = $this->redis->hgetall('stats:index');
        $this->assertArrayHasKey('aaa111', $index);
        $this->assertArrayHasKey('bbb222', $index);

        $entry = json_decode($index['aaa111'], true);
        $this->assertEquals('image/jpeg', $entry['mime']);
        $this->assertEquals('cat.jpg',    $entry['original_filename']);
        $this->assertEquals('1.2.3.4',   $entry['ip']);
        $this->assertEquals(1024,         $entry['size']);

        $this->removeTestHash('aaa111');
        $this->removeTestHash('bbb222');
    }

    public function testRebuildStatsCacheSetsBuiltAt(): void
    {
        $before = time();
        rebuildStatsCache();
        $after  = time();

        $builtAt = (int)$this->redis->get('stats:built_at');
        $this->assertGreaterThanOrEqual($before, $builtAt);
        $this->assertLessThanOrEqual($after, $builtAt);
    }

    public function testRebuildStatsCacheClearsStaleEntries(): void
    {
        // Pre-populate with a hash that doesn't exist on disk
        $this->redis->hset('stats:index', 'stale999', '{"mime":"image/gif"}');

        rebuildStatsCache(); // no dirs on disk → should wipe stale entries

        $index = $this->redis->hgetall('stats:index');
        $this->assertArrayNotHasKey('stale999', $index);
    }

    // --- getStatsPage() ---

    /** Seed stats:index with N fake entries. */
    private function seedIndex(array $entries): void
    {
        foreach ($entries as $hash => $data) {
            $this->redis->hset('stats:index', $hash, json_encode($data));
        }
        $this->redis->set('stats:built_at', (string)time());
    }

    public function testGetStatsPageReturnsCorrectPageSize(): void
    {
        $entries = [];
        for ($i = 1; $i <= 60; $i++) {
            $entries[str_pad((string)$i, 6, '0', STR_PAD_LEFT)] = [
                'mime' => 'image/jpeg', 'ip' => '1.1.1.1',
                'uploaded' => 1700000000 + $i, 'original_filename' => "file$i.jpg",
                'size' => 100, 'views' => $i,
            ];
        }
        $this->seedIndex($entries);

        $result = getStatsPage(1, 'uploaded', 'desc', '');
        $this->assertCount(50, $result['rows']);
        $this->assertEquals(60, $result['total']);
        $this->assertEquals(2,  $result['total_pages']);
    }

    public function testGetStatsPageFiltersOnSearchQuery(): void
    {
        $this->seedIndex([
            'abc123' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 1000, 'original_filename' => 'cat.jpg',  'size' => 100, 'views' => 5],
            'def456' => ['mime' => 'image/png',  'ip' => '2.2.2.2', 'uploaded' => 2000, 'original_filename' => 'dog.png',  'size' => 200, 'views' => 3],
            'xyz789' => ['mime' => 'video/mp4',  'ip' => '3.3.3.3', 'uploaded' => 3000, 'original_filename' => 'clip.mp4', 'size' => 300, 'views' => 1],
        ]);

        $result = getStatsPage(1, 'uploaded', 'desc', 'image');
        $this->assertEquals(2, $result['total']); // jpeg and png
        $hashes = array_column($result['rows'], 'hash');
        $this->assertContains('abc123', $hashes);
        $this->assertContains('def456', $hashes);
    }

    public function testGetStatsPageSortsByViewsDesc(): void
    {
        $this->seedIndex([
            'low111' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 1000, 'original_filename' => 'low.jpg',  'size' => 100, 'views' => 1],
            'mid222' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 2000, 'original_filename' => 'mid.jpg',  'size' => 100, 'views' => 50],
            'top333' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 3000, 'original_filename' => 'top.jpg',  'size' => 100, 'views' => 999],
        ]);

        $result = getStatsPage(1, 'views', 'desc', '');
        $this->assertEquals('top333', $result['rows'][0]['hash']);
        $this->assertEquals('low111', $result['rows'][2]['hash']);
    }

    public function testGetStatsPageSortsByViewsAsc(): void
    {
        $this->seedIndex([
            'low111' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 1000, 'original_filename' => 'low.jpg', 'size' => 100, 'views' => 1],
            'top333' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 3000, 'original_filename' => 'top.jpg', 'size' => 100, 'views' => 999],
        ]);

        $result = getStatsPage(1, 'views', 'asc', '');
        $this->assertEquals('low111', $result['rows'][0]['hash']);
    }

    public function testGetStatsPageReturnsMetaInResult(): void
    {
        $this->seedIndex([
            'abc123' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 1000, 'original_filename' => 'cat.jpg', 'size' => 100, 'views' => 5],
        ]);

        $result = getStatsPage(1, 'uploaded', 'desc', '');
        $this->assertEquals(1,         $result['page']);
        $this->assertEquals(1,         $result['total_pages']);
        $this->assertEquals('uploaded',$result['sort']);
        $this->assertEquals('desc',    $result['dir']);
        $this->assertEquals('',        $result['q']);
    }

    public function testGetStatsPageEmptyIndex(): void
    {
        $result = getStatsPage(1, 'uploaded', 'desc', '');
        $this->assertCount(0, $result['rows']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['total_pages']);
        $this->assertEquals(1, $result['page']); // clamped to 1 even when empty
    }

    public function testGetStatsPageSanitizesInvalidParams(): void
    {
        $this->seedIndex([
            'abc123' => ['mime' => 'image/jpeg', 'ip' => '1.1.1.1', 'uploaded' => 1000, 'original_filename' => 'cat.jpg', 'size' => 100, 'views' => 5],
        ]);

        // Invalid sort/dir fall back to defaults
        $result = getStatsPage(0, 'invalid_column', 'sideways', '');
        $this->assertEquals(1,         $result['page']);   // page minimum is 1
        $this->assertEquals('uploaded',$result['sort']);   // invalid → default
        $this->assertEquals('desc',    $result['dir']);    // invalid → default
    }
}
