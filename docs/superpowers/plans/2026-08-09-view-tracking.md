# View/Last-Accessed Tracking Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Record, per stored file, when it was last accessed and how many times, durably in that file's `meta.json`, as groundwork for a future retention policy (issue #145) — no deletion logic in this plan.

**Architecture:** A new Redis key `lastaccessed:<hash>` is set on every real-hash view (alongside the existing `served:<hash>` counter). A daily background loop in the container calls a new CLI command that scans that keyspace, merges `last_accessed` + a `views` snapshot into each hash's `meta.json`, then clears the Redis key.

**Tech Stack:** PHP 8.2+, phpredis extension, PHPUnit 11, bash (`docker/rootfs/start.sh`).

## Global Constraints

- The whole feature is Redis-dependent and must no-op silently (never fatal) when `$GLOBALS['redis']` is unset — matches the existing style for `served:<hash>` in `src/inc/core.php`.
- Use Redis `SCAN`, never `KEYS`, per the approved spec (`docs/superpowers/specs/2026-08-09-view-tracking-design.md`).
- `views` in `meta.json` is a snapshot of `served:<hash>` at flush time, not independently incremented.
- Dynamic content controllers (identicon/placeholder/URL-shortener style — the `$hash === true` branch in `architect()`) are out of scope; they have no `meta.json` to flush into.

---

### Task 1: Extract shared FakeRedis test double

`tests/Unit/StatsCacheTest.php` currently declares `class FakeRedis` and `class FakeRedisPipeline` inline, in the global namespace. Task 2 and Task 3 both need a `FakeRedis` with new methods (`incr`, `scan`) — declaring a second `class FakeRedis` in a new test file would fatal with "Cannot declare class FakeRedis, because the name is already in use" the moment PHPUnit loads both files in the same run. Extract it to a shared file, loaded once from `tests/bootstrap.php`.

**Files:**
- Create: `tests/Support/FakeRedis.php`
- Modify: `tests/bootstrap.php`
- Modify: `tests/Unit/StatsCacheTest.php:1-69` (remove the inline class declarations)

**Interfaces:**
- Produces: `class FakeRedis` with `get(string $key): ?string`, `set(string $key, mixed $value): void`, `incr(string $key): int`, `del(string $key): void`, `hset(string $key, string $field, string $value): void`, `hgetall(string $key): array`, `mget(array $keys): array`, `scan(&$iterator, string $pattern): array|false`, `multi(int $mode = 0): FakeRedisPipeline`. Also `class FakeRedisPipeline` (unchanged from today).

- [ ] **Step 1: Create the shared FakeRedis file**

Move the existing `FakeRedis`/`FakeRedisPipeline` classes out of `tests/Unit/StatsCacheTest.php` (lines 1-69, everything above `class StatsCacheTest extends TestCase`) into a new file, and add `incr()` + `scan()`:

```php
<?php
// tests/Support/FakeRedis.php

/**
 * Minimal in-memory Redis mock covering only the methods exercised by
 * PictShare's Redis-backed code paths (stats cache, view tracking).
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

    public function incr(string $key): int
    {
        $value = ((int)($this->strings[$key] ?? 0)) + 1;
        $this->strings[$key] = (string)$value;
        return $value;
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

    /**
     * Simulate phpredis SCAN closely enough to exercise the standard
     * `while (($batch = $redis->scan($it, $pattern)) !== false)` idiom:
     * returns every matching key on the first call, then false.
     */
    public function scan(&$iterator, string $pattern): array|false
    {
        if ($iterator === 'done') return false;
        $iterator = 'done';
        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
        return array_values(array_filter(array_keys($this->strings), fn($k) => preg_match($regex, $k)));
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
```

- [ ] **Step 2: Remove the inline classes from StatsCacheTest.php**

In `tests/Unit/StatsCacheTest.php`, everything from the top of the file up to (but not including) the `class StatsCacheTest extends TestCase` line is the part being extracted. Replace that whole leading section:

```php
<?php
// tests/Unit/StatsCacheTest.php
use PHPUnit\Framework\TestCase;

/**
 * Minimal in-memory Redis mock covering only the methods used by stats cache functions.
 */
class FakeRedis
{
    // ... (the full class body, as it exists today)
}

class FakeRedisPipeline
{
    // ... (the full class body, as it exists today)
}

class StatsCacheTest extends TestCase
```

with just:

```php
<?php
// tests/Unit/StatsCacheTest.php
use PHPUnit\Framework\TestCase;

class StatsCacheTest extends TestCase
```

Do not touch anything from `class StatsCacheTest extends TestCase` onward (including its opening `{` and every test method below it) — only the `FakeRedis`/`FakeRedisPipeline` declarations above it are removed, since they now live in `tests/Support/FakeRedis.php`.

- [ ] **Step 3: Load the shared file from bootstrap**

In `tests/bootstrap.php`, add this line after the `require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'core.php';` line:

```php
require_once __DIR__ . '/Support/FakeRedis.php';
```

- [ ] **Step 4: Run the full suite to verify nothing broke**

Run: `./src/lib/vendor/bin/phpunit`
Expected: all existing tests still pass (same pass/skip/incomplete counts as before this change — `StatsCacheTest` in particular must still fully pass, now using the relocated class).

- [ ] **Step 5: Commit**

```bash
git add tests/Support/FakeRedis.php tests/bootstrap.php tests/Unit/StatsCacheTest.php
git commit -m "test: extract shared FakeRedis test double"
```

---

### Task 2: `recordView()` — stamp last-accessed alongside the view counter

**Files:**
- Modify: `src/inc/core.php:189` (cache-hit path in `architect()`)
- Modify: `src/inc/core.php:311` (first-resolve path in `architect()`, real-hash branch only — not line 313's dynamic-controller branch)
- Modify: `src/inc/core.php` — add `recordView()` near `updateMetaData()` (around line 1364)
- Test: `tests/Unit/ViewTrackingTest.php`

**Interfaces:**
- Consumes: `$GLOBALS['redis']` (phpredis instance or `FakeRedis`, or unset).
- Produces: `function recordView(string $hash): void` — used by Task 3 indirectly only insofar as it's what populates the `lastaccessed:*` keyspace Task 3 reads. No other task calls it directly.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ViewTrackingTest.php`:

```php
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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./src/lib/vendor/bin/phpunit tests/Unit/ViewTrackingTest.php`
Expected: FAIL — `recordView()` does not exist (`Error: Call to undefined function recordView()`).

- [ ] **Step 3: Implement `recordView()`**

In `src/inc/core.php`, add this immediately before `function updateMetaData($hash, $meta)` (currently at line 1364):

```php
function recordView($hash)
{
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return;
    $GLOBALS['redis']->incr("served:$hash");
    $GLOBALS['redis']->set("lastaccessed:$hash", time());
}
```

- [ ] **Step 4: Wire it into the two real-hash view sites**

In `architect()`, replace the cache-hit increment (currently `src/inc/core.php:189`):

```php
            $GLOBALS['redis']->incr("served:$hash");
```

with:

```php
            recordView($hash);
```

And replace the first-resolve real-hash increment (currently `src/inc/core.php:311`, inside the `if($hash!==true) ... else ...` block):

```php
                    if($hash!==true)
                        $GLOBALS['redis']->incr("served:$hash");
                    else //if it's a dynamic image, we count how many times this url was served
                        $GLOBALS['redis']->incr("served:".implode('/',$u));
```

with:

```php
                    if($hash!==true)
                        recordView($hash);
                    else //if it's a dynamic image, we count how many times this url was served
                        $GLOBALS['redis']->incr("served:".implode('/',$u));
```

(The dynamic-controller `else` branch is untouched — no `meta.json` exists for those.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `./src/lib/vendor/bin/phpunit tests/Unit/ViewTrackingTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Run the full suite**

Run: `./src/lib/vendor/bin/phpunit`
Expected: all tests pass, same counts as Task 1's baseline plus the 3 new `ViewTrackingTest` tests.

- [ ] **Step 7: Commit**

```bash
git add src/inc/core.php tests/Unit/ViewTrackingTest.php
git commit -m "feat(core): record last-accessed timestamp alongside view counter"
```

---

### Task 3: `flushViews()` — daily merge into meta.json

**Files:**
- Modify: `src/inc/core.php` — add `redisScanKeys()` and `flushViews()` near `recordView()`
- Modify: `tools/cron.php` — add Redis boot + `flushviews` CLI command
- Test: `tests/Unit/ViewTrackingTest.php` (append to the file from Task 2)

**Interfaces:**
- Consumes: `recordView()`'s output — the `lastaccessed:<hash>` keys it creates — plus `isExistingHash($hash)`, `updateMetaData($hash, array $meta)`, `addToLog($data)` (all pre-existing in `core.php`).
- Produces: `function redisScanKeys(string $pattern): array` (flat list of matching keys, empty array if Redis unset). `function flushViews(): array` returning `['flushed' => string[], 'skipped' => string[]]` (hashes, without the `lastaccessed:` prefix).

- [ ] **Step 1: Write the failing tests**

Append to `tests/Unit/ViewTrackingTest.php` (inside the `ViewTrackingTest` class, after the existing tests):

```php
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
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `./src/lib/vendor/bin/phpunit tests/Unit/ViewTrackingTest.php`
Expected: FAIL — `redisScanKeys()` and `flushViews()` do not exist.

- [ ] **Step 3: Implement `redisScanKeys()` and `flushViews()`**

In `src/inc/core.php`, add both immediately after `recordView()` (added in Task 2):

```php
function redisScanKeys(string $pattern): array
{
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return [];
    $keys = [];
    $it = null;
    while (($batch = $GLOBALS['redis']->scan($it, $pattern)) !== false) {
        $keys = array_merge($keys, $batch);
    }
    return $keys;
}

function flushViews(): array
{
    $result = ['flushed' => [], 'skipped' => []];
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return $result;

    foreach (redisScanKeys('lastaccessed:*') as $key) {
        $hash = substr($key, strlen('lastaccessed:'));

        if (!isExistingHash($hash)) {
            addToLog("flushViews: skipping $hash, hash directory no longer exists");
            $result['skipped'][] = $hash;
            continue;
        }

        $ts = (int)$GLOBALS['redis']->get($key);
        $views = (int)$GLOBALS['redis']->get("served:$hash");
        updateMetaData($hash, ['last_accessed' => $ts, 'views' => $views]);
        $GLOBALS['redis']->del($key);
        $result['flushed'][] = $hash;
    }

    return $result;
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `./src/lib/vendor/bin/phpunit tests/Unit/ViewTrackingTest.php`
Expected: PASS (9 tests total in this file).

- [ ] **Step 5: Wire up the CLI command**

In `tools/cron.php`, add a Redis boot step matching `web/index.php:24-28`. Change:

```php
include_once(ROOT.DS.'inc/core.php');

switch($argv[1])
{
    case 'uploadqueue':
        uploadqueue();
    break;
    default:
        exit("[ERR] Command not found. Available commands are: uploadqueue");
}
```

to:

```php
include_once(ROOT.DS.'inc/core.php');

if(!defined('REDIS_CACHING') || REDIS_CACHING == true)
{
    $GLOBALS['redis'] = new Redis();
    $GLOBALS['redis']->connect((!defined('REDIS_SERVER'))?'localhost':REDIS_SERVER, (!defined('REDIS_PORT'))?6379:REDIS_PORT);
}

switch($argv[1])
{
    case 'uploadqueue':
        uploadqueue();
    break;
    case 'flushviews':
        $result = flushViews();
        echo "[i] Flushed " . count($result['flushed']) . " hash(es), skipped " . count($result['skipped']) . "\n";
    break;
    default:
        exit("[ERR] Command not found. Available commands are: uploadqueue, flushviews");
}
```

- [ ] **Step 6: Run the full suite**

Run: `./src/lib/vendor/bin/phpunit`
Expected: all tests pass.

- [ ] **Step 7: Manually verify the CLI command against a real container**

```bash
docker build -f docker/Dockerfile -t pictshare-viewtracking-test .
docker run -d --rm --name viewtracking-test -p 18095:80 \
  -e URL=http://localhost:18095/ pictshare-viewtracking-test
sleep 3
# upload a fixture and view it twice to populate served:/lastaccessed: in Redis
# (the upload response itself does not go through architect(), so it does not count as a view)
RESP=$(curl -s -F "file=@tests/fixtures/test.jpg" http://localhost:18095/api/upload)
echo "$RESP"
HASH=$(echo "$RESP" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo $d["hash"];')
curl -s -o /dev/null http://localhost:18095/$HASH
curl -s -o /dev/null http://localhost:18095/$HASH
# run the flush command directly
docker exec viewtracking-test php /app/public/tools/cron.php flushviews
# confirm meta.json now has last_accessed and views
docker exec viewtracking-test cat /app/public/data/$HASH/meta.json
docker stop viewtracking-test
docker rmi pictshare-viewtracking-test
```

Expected: the `flushviews` output reports 1 flushed hash, and the printed `meta.json` contains `"last_accessed"` (a recent unix timestamp) and `"views":2` (exactly the two `curl` GETs above — the upload step doesn't count).

- [ ] **Step 8: Commit**

```bash
git add src/inc/core.php tools/cron.php tests/Unit/ViewTrackingTest.php
git commit -m "feat(core): add flushViews() and wire up the flushviews CLI command"
```

---

### Task 4: Schedule the daily flush in the container

**Files:**
- Modify: `docker/rootfs/start.sh:79` (inside the existing `if [[ ${REDIS_CACHING:=true} == true ]]; then` block)

**Interfaces:**
- Consumes: the `flushviews` command from Task 3 (`php /app/public/tools/cron.php flushviews`).
- Produces: nothing consumed by other tasks — this is the final task.

- [ ] **Step 1: Add the background loop**

In `docker/rootfs/start.sh`, change:

```bash
    # Trap SIGTERM and SIGINT signals to save Redis data before shutdown
    trap "echo 'Stopping Redis'; redis-cli save; redis-cli shutdown; exit" TERM INT
    redis-server /etc/redis.conf --daemonize yes
fi
```

to:

```bash
    # Trap SIGTERM and SIGINT signals to save Redis data before shutdown
    trap "echo 'Stopping Redis'; redis-cli save; redis-cli shutdown; exit" TERM INT
    redis-server /etc/redis.conf --daemonize yes

    # Daily flush of Redis-tracked last-accessed/view data into each file's meta.json
    (while true; do sleep 86400; php /app/public/tools/cron.php flushviews; done) &
fi
```

- [ ] **Step 2: Verify the loop starts in a real container**

```bash
docker build -f docker/Dockerfile -t pictshare-viewtracking-test .
docker run -d --rm --name viewtracking-test -p 18096:80 \
  -e URL=http://localhost:18096/ pictshare-viewtracking-test
sleep 3
docker exec viewtracking-test ps aux | grep -i "sleep 86400"
docker stop viewtracking-test
docker rmi pictshare-viewtracking-test
```

Expected: the `ps aux` output shows the `sleep 86400` process running in the background (confirms the loop launched and is waiting for its first tick — no need to wait 24 hours to verify this).

- [ ] **Step 3: Commit**

```bash
git add docker/rootfs/start.sh
git commit -m "feat(docker): schedule daily view-tracking flush alongside redis-server"
```

---

## Post-Plan

Once all 4 tasks are done, consider commenting on GitHub issue #145 with a summary and asking whether to proceed with an actual retention/auto-delete policy now that this data exists — that policy itself is explicitly out of scope for this plan.
