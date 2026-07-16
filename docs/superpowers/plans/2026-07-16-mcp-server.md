# MCP Server Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Built-in MCP server at `POST /mcp` exposing PictShare upload/info/delete/album/transform functionality to LLM clients.

**Architecture:** New `src/inc/mcp.class.php` builds an MCP server with the official PHP SDK (`mcp/sdk`, Streamable HTTP transport, file-based sessions in `tmp/mcp_sessions`). All six tools are thin wrappers that delegate to the existing `API` class in `src/inc/api.class.php` by populating `$_REQUEST` the same way HTTP callers do — no duplicated business logic. `web/index.php` routes the `mcp` URL segment to this class before `architect()` dispatch, mirroring the `api` route.

**Tech Stack:** PHP 8.2+, composer packages `mcp/sdk` + `nyholm/psr7` + `nyholm/psr7-server`, PHPUnit 11 (existing test harness in `tests/`).

**Spec:** `docs/superpowers/specs/2026-07-16-mcp-server-design.md`

## Global Constraints

- Auth: `Authorization: Bearer <UPLOAD_CODE>` required only when `UPLOAD_CODE` constant is defined and non-empty; otherwise open (matches REST API).
- Tool errors are thrown as `\RuntimeException` with a human-readable message; the SDK surfaces them as tool errors. Never `exit()` inside a tool handler.
- MCP surface grants no privileges beyond the existing REST API.
- Tests run with `src/lib/vendor/bin/phpunit` from repo root (bootstrap `tests/bootstrap.php`, test data in an isolated temp dir, Redis disabled).
- `docs/` is gitignored — commit plan/spec files with `git add -f`.
- `src/lib/vendor/` is not committed; Docker build runs `composer install` (docker/Dockerfile:23). Commit only `composer.json` + `composer.lock`.
- Commit messages end with `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

## Codebase facts the implementer needs

- `API` class (`src/inc/api.class.php`): constructor takes URL-segment array, e.g. `new API(['info', $hash])`, then `->act()` returns an array with `status` = `ok`/`err`. Upload reads `$_REQUEST['url']` (remote fetch) or `$_REQUEST['base64']`; album reads `$_REQUEST['hashes']` (array); upload code is read from `$_REQUEST['uploadcode']` inside `checkUploaderPermissions()`.
- `API::base64ToFile()` does `explode(',', $string)` and uses index `[1]` — a raw base64 string without a `prefix,` part would break, so tool handler must prepend `'base64,'` when the string has no comma.
- Validation helpers: `isExistingHash($hash)` and `isSize($var)` in `src/inc/core.php`; `isRotation($var)` (valid values: `upside`, `left`, `right`) in `src/content-controllers/image/resize.php`; `getFilters()` (returns method list of `Filter` class) in `src/content-controllers/image/filters.php`. The last two files are lazily included by the image controller — `mcp.class.php` must `include_once` them itself.
- `getURL()` returns the instance base URL with trailing slash.
- Test bootstrap (`tests/bootstrap.php`) already loads core, API, content controllers, resize.php, filters.php, and composer autoload; test config defines no `UPLOAD_CODE`, so integration tests run in open mode.
- Test fixtures live in `tests/fixtures/` (`test.png`, `test.jpg`, …). Existing integration tests (see `tests/Integration/AlbumApiTest.php`) call the `API` class directly and clean up via `$this->uploadedHashes[]` (handled by `PictShareTestCase`).

## MCP SDK facts

- Package `mcp/sdk` (official, modelcontextprotocol/php-sdk). Builder: `Mcp\Server::builder()->setServerInfo(...)->setSession(new FileSessionStore($dir))->addTool(handler:, name:, description:)->build()`.
- `addTool` infers the input schema from the closure's parameter names/types.
- Transport: `new Mcp\Server\Transport\StreamableHttpTransport($psr7Request)` (PSR-17 factories auto-discovered from nyholm/psr7); `$server->run($transport)` returns a PSR-7 `ResponseInterface`.
- Sessions: `Mcp\Server\Session\FileSessionStore` — required because each HTTP request is a fresh PHP process. Client flow: `initialize` → response carries `Mcp-Session-Id` header → subsequent requests send that header.
- Responses may be `application/json` or `text/event-stream` (`data: {...}` lines) — the test decoder must handle both.

---

### Task 1: Composer dependencies

**Files:**
- Modify: `src/lib/composer.json`, `src/lib/composer.lock` (via composer, not by hand)

**Interfaces:**
- Produces: autoloadable classes `Mcp\Server`, `Mcp\Server\Transport\StreamableHttpTransport`, `Mcp\Server\Session\FileSessionStore`, `Nyholm\Psr7\Factory\Psr17Factory`, `Nyholm\Psr7Server\ServerRequestCreator`.

- [ ] **Step 1: Install packages**

Run:
```bash
cd /home/chris/git/pictshare/src/lib && composer require mcp/sdk nyholm/psr7 nyholm/psr7-server
```
Expected: resolves and writes lock file without errors. If `mcp/sdk` requires a PHP platform config bump, fix `composer.json` `config.platform` accordingly (local PHP is 8.3).

- [ ] **Step 2: Verify classes autoload**

Run:
```bash
cd /home/chris/git/pictshare && php -r "require 'src/lib/vendor/autoload.php'; var_dump(class_exists('Mcp\\Server'), class_exists('Mcp\\Server\\Transport\\StreamableHttpTransport'), class_exists('Mcp\\Server\\Session\\FileSessionStore'), class_exists('Nyholm\\Psr7\\Factory\\Psr17Factory'), class_exists('Nyholm\\Psr7Server\\ServerRequestCreator'));"
```
Expected: five times `bool(true)`. If a class name differs (SDK version drift), find the real one with `grep -r "class Server" src/lib/vendor/mcp/` etc. and record the corrected names for later tasks.

- [ ] **Step 3: Verify existing tests still pass**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit --testsuite Unit`
Expected: all pass.

- [ ] **Step 4: Commit**

```bash
git add src/lib/composer.json src/lib/composer.lock
git commit -m "build(deps): add official MCP PHP SDK and PSR-7 implementation

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: MCP server skeleton — auth + initialize handshake

**Files:**
- Create: `src/inc/mcp.class.php`
- Test: `tests/Integration/McpServerTest.php`

**Interfaces:**
- Produces:
  - `PictShareMcp::checkAuth(?string $authHeader, ?string $requiredCode): bool`
  - `PictShareMcp::buildServer(?string $sessionDir = null): \Mcp\Server` — later tasks add `->addTool(...)` calls inside this method.
  - `PictShareMcp::handle(\Psr\Http\Message\ServerRequestInterface $request, ?string $sessionDir = null): \Psr\Http\Message\ResponseInterface`
  - `PictShareMcp::serve(): void` — full HTTP entry point (auth, globals → PSR-7, emit).
- Consumes: composer classes from Task 1.

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/McpServerTest.php`:

```php
<?php
require_once __DIR__ . '/../PictShareTestCase.php';
require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'mcp.class.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class McpServerTest extends PictShareTestCase
{
    private static string $sessionDir;

    public static function setUpBeforeClass(): void
    {
        self::$sessionDir = sys_get_temp_dir() . DS . 'pictshare_mcp_sessions_' . uniqid();
        mkdir(self::$sessionDir, 0777, true);
    }

    // ---------- helpers ----------

    private function mcpRequest(array $payload, ?string $sessionId = null): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createServerRequest('POST', 'http://localhost/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json, text/event-stream')
            ->withBody($psr17->createStream(json_encode($payload)));
        if ($sessionId)
            $request = $request->withHeader('Mcp-Session-Id', $sessionId);
        return PictShareMcp::handle($request, self::$sessionDir);
    }

    private function decodeResponse(ResponseInterface $r): ?array
    {
        $body = (string)$r->getBody();
        if (str_contains($r->getHeaderLine('Content-Type'), 'text/event-stream')) {
            foreach (explode("\n", $body) as $line)
                if (str_starts_with($line, 'data: '))
                    return json_decode(substr($line, 6), true);
            return null;
        }
        return json_decode($body, true);
    }

    private function initSession(): string
    {
        $resp = $this->mcpRequest([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
            ],
        ]);
        $this->assertSame(200, $resp->getStatusCode());
        $sid = $resp->getHeaderLine('Mcp-Session-Id');
        $this->mcpRequest(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $sid);
        return $sid;
    }

    /** Full handshake + tools/call, returns decoded JSON-RPC response */
    private function callTool(string $name, array $args): array
    {
        $sid = $this->initSession();
        $resp = $this->mcpRequest([
            'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $args ?: (object)[]],
        ], $sid);
        $decoded = $this->decodeResponse($resp);
        $this->assertNotNull($decoded, 'tools/call returned undecodable body: ' . (string)$resp->getBody());
        return $decoded;
    }

    /** Extract ['isError' => bool, 'data' => array|string] from a tools/call result */
    private function toolResult(array $rpc): array
    {
        $this->assertArrayHasKey('result', $rpc, 'JSON-RPC error: ' . json_encode($rpc['error'] ?? null));
        $result = $rpc['result'];
        $text = $result['content'][0]['text'] ?? '';
        $json = json_decode($text, true);
        return ['isError' => (bool)($result['isError'] ?? false), 'data' => $json ?? $text];
    }

    // ---------- tests ----------

    public function testCheckAuth(): void
    {
        // no code configured -> always allowed
        $this->assertTrue(PictShareMcp::checkAuth(null, null));
        $this->assertTrue(PictShareMcp::checkAuth('Bearer whatever', null));
        // code configured -> bearer must match
        $this->assertTrue(PictShareMcp::checkAuth('Bearer sekrit', 'sekrit'));
        $this->assertTrue(PictShareMcp::checkAuth('bearer sekrit', 'sekrit'));
        $this->assertFalse(PictShareMcp::checkAuth('Bearer wrong', 'sekrit'));
        $this->assertFalse(PictShareMcp::checkAuth('sekrit', 'sekrit'));
        $this->assertFalse(PictShareMcp::checkAuth(null, 'sekrit'));
        $this->assertFalse(PictShareMcp::checkAuth('', 'sekrit'));
    }

    public function testInitializeHandshake(): void
    {
        $resp = $this->mcpRequest([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
            ],
        ]);
        $this->assertSame(200, $resp->getStatusCode());
        $decoded = $this->decodeResponse($resp);
        $this->assertSame('PictShare', $decoded['result']['serverInfo']['name'] ?? null);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: FAIL — `mcp.class.php` does not exist / class `PictShareMcp` not found.

- [ ] **Step 3: Write implementation**

Create `src/inc/mcp.class.php`:

```php
<?php
/**
 * MCP (Model Context Protocol) endpoint for PictShare.
 * Serves POST /mcp via the official PHP SDK (mcp/sdk), streamable HTTP transport.
 * All tools delegate to the existing API class — no duplicated business logic.
 */

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// validation helpers normally lazy-loaded by the image controller
include_once(ROOT.DS.'src'.DS.'content-controllers'.DS.'image'.DS.'resize.php');
include_once(ROOT.DS.'src'.DS.'content-controllers'.DS.'image'.DS.'filters.php');

class PictShareMcp
{
    public static function checkAuth(?string $authHeader, ?string $requiredCode): bool
    {
        if (!$requiredCode) return true;
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $m)) return false;
        return hash_equals($requiredCode, $m[1]);
    }

    public static function buildServer(?string $sessionDir = null): Server
    {
        if (!$sessionDir) $sessionDir = ROOT.DS.'tmp'.DS.'mcp_sessions';
        if (!is_dir($sessionDir)) mkdir($sessionDir, 0755, true);

        return Server::builder()
            ->setServerInfo('PictShare', '1.0.0')
            ->setInstructions('Self-hosted media hosting. Upload files by URL or base64, '
                .'then use the returned hash with the info/transform/album/delete tools.')
            ->setSession(new FileSessionStore($sessionDir))
            ->build();
    }

    public static function handle(ServerRequestInterface $request, ?string $sessionDir = null): ResponseInterface
    {
        $server = self::buildServer($sessionDir);
        $transport = new StreamableHttpTransport($request);
        return $server->run($transport);
    }

    public static function serve(): void
    {
        header_remove('X-Powered-By');

        if (!class_exists('Mcp\\Server')) {
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => [
                'code' => -32603,
                'message' => 'MCP dependencies not installed. Run: cd src/lib && composer install',
            ]]);
            return;
        }

        $required = (defined('UPLOAD_CODE') && UPLOAD_CODE != '') ? UPLOAD_CODE : null;
        if (!self::checkAuth($_SERVER['HTTP_AUTHORIZATION'] ?? null, $required)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => [
                'code' => -32001,
                'message' => 'Unauthorized. Send "Authorization: Bearer <upload code>"',
            ]]);
            return;
        }

        $psr17 = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
        $response = self::handle($creator->fromGlobals());

        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values)
            foreach ($values as $value)
                header($name.': '.$value, false);
        echo (string)$response->getBody();
    }
}
```

Note: `class_exists('Mcp\Server')` guard matters in production (fresh checkout without composer install renders a clear error instead of a fatal). The `use` statements at file top do not trigger autoload by themselves, so the file parses fine without vendor.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: PASS (2 tests). If `initialize` fails, debug by dumping `(string)$resp->getBody()` — likely causes: different transport constructor signature or session-store namespace; check actual names under `src/lib/vendor/mcp/sdk/src/`.

- [ ] **Step 5: Commit**

```bash
git add src/inc/mcp.class.php tests/Integration/McpServerTest.php
git commit -m "feat(mcp): add MCP server skeleton with bearer auth and handshake

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Upload tools (upload_from_url, upload_base64)

**Files:**
- Modify: `src/inc/mcp.class.php` (inside `buildServer()`, before `->build()`)
- Test: `tests/Integration/McpServerTest.php`

**Interfaces:**
- Consumes: `API` class; `callTool()` / `toolResult()` helpers from Task 2.
- Produces: MCP tools `upload_from_url(url)` and `upload_base64(data)`, both returning the API result array (`hash`, `url`, `delete_code`, …).

- [ ] **Step 1: Write the failing tests**

Add to `McpServerTest`:

```php
    public function testToolsListContainsUploadTools(): void
    {
        $sid = $this->initSession();
        $resp = $this->mcpRequest(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => (object)[]], $sid);
        $decoded = $this->decodeResponse($resp);
        $names = array_column($decoded['result']['tools'] ?? [], 'name');
        $this->assertContains('upload_from_url', $names);
        $this->assertContains('upload_base64', $names);
    }

    public function testUploadBase64(): void
    {
        $data = base64_encode(file_get_contents(__DIR__ . '/../fixtures/test.png'));
        $res = $this->toolResult($this->callTool('upload_base64', ['data' => $data]));
        $this->assertFalse($res['isError'], 'tool errored: ' . json_encode($res['data']));
        $this->assertSame('ok', $res['data']['status']);
        $this->assertNotEmpty($res['data']['hash']);
        $this->assertNotEmpty($res['data']['url']);
        $this->uploadedHashes[] = $res['data']['hash'];
    }

    public function testUploadFromUrlRejectsInvalidUrl(): void
    {
        $res = $this->toolResult($this->callTool('upload_from_url', ['url' => 'ftp://example.com/x.png']));
        $this->assertTrue($res['isError']);
        $this->assertStringContainsStringIgnoringCase('invalid url', json_encode($res['data']));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: 3 new tests FAIL (unknown tool / not in list).

- [ ] **Step 3: Implement the tools**

In `buildServer()`, insert between `->setSession(...)` and `->build()`:

```php
            ->addTool(
                handler: function (string $url): array {
                    return PictShareMcp::callApi(['upload'], ['url' => trim($url)]);
                },
                name: 'upload_from_url',
                description: 'Download a file from a public http(s) URL and store it on this '
                    .'PictShare instance (max 20 MB). Returns hash, public url and delete_code.'
            )
            ->addTool(
                handler: function (string $data): array {
                    if (!str_contains($data, ','))
                        $data = 'base64,'.$data; // API::base64ToFile expects a "prefix," part
                    return PictShareMcp::callApi(['upload'], ['base64' => $data]);
                },
                name: 'upload_base64',
                description: 'Upload a base64-encoded file (raw base64 or data-URI). The file type '
                    .'is detected from content. Returns hash, public url and delete_code.'
            )
```

And add this method to the class:

```php
    /**
     * Run an existing API action with the given request vars.
     * MCP auth already validated the upload code, so it is forwarded automatically.
     * @throws RuntimeException on API-level errors (surfaced as MCP tool errors)
     */
    public static function callApi(array $urlSegments, array $requestVars = []): array
    {
        if (defined('UPLOAD_CODE') && UPLOAD_CODE != '')
            $requestVars['uploadcode'] = UPLOAD_CODE;

        foreach ($requestVars as $k => $v)
            $_REQUEST[$k] = $v;
        try {
            $result = (new API($urlSegments))->act();
        } finally {
            foreach (array_keys($requestVars) as $k)
                unset($_REQUEST[$k]);
        }

        if (($result['status'] ?? 'ok') === 'err')
            throw new RuntimeException($result['reason'] ?? 'Unknown API error');
        return $result;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: PASS. If the thrown `RuntimeException` surfaces as a JSON-RPC error instead of a tool result with `isError: true`, adapt `toolResult()` to treat `$rpc['error']['message']` as `['isError' => true, 'data' => message]` — both are acceptable MCP error surfacing; the assertion on the message text stays.

- [ ] **Step 5: Commit**

```bash
git add src/inc/mcp.class.php tests/Integration/McpServerTest.php
git commit -m "feat(mcp): add upload_from_url and upload_base64 tools

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Metadata tools (get_file_info, delete_file, create_album)

**Files:**
- Modify: `src/inc/mcp.class.php`
- Test: `tests/Integration/McpServerTest.php`

**Interfaces:**
- Consumes: `PictShareMcp::callApi()` from Task 3; upload helper from Task 3's tests.
- Produces: MCP tools `get_file_info(hash)`, `delete_file(hash, delete_code)`, `create_album(hashes)`.

- [ ] **Step 1: Write the failing tests**

Add to `McpServerTest`:

```php
    /** Upload a fixture via the API directly; returns [hash, delete_code] */
    private function uploadFixture(string $fixture = 'test.png'): array
    {
        $tmp = ROOT . DS . 'tmp' . DS . 'mcp_' . uniqid() . '_' . $fixture;
        copy(__DIR__ . '/../fixtures/' . $fixture, $tmp);
        $_FILES['file'] = ['tmp_name' => $tmp, 'name' => $fixture, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
        try {
            $result = (new API(['upload']))->act();
        } finally {
            unset($_FILES['file']);
        }
        $this->assertSame('ok', $result['status'] ?? null, json_encode($result));
        $this->uploadedHashes[] = $result['hash'];
        return [$result['hash'], $result['delete_code'] ?? ''];
    }

    public function testGetFileInfo(): void
    {
        [$hash] = $this->uploadFixture();
        $res = $this->toolResult($this->callTool('get_file_info', ['hash' => $hash]));
        $this->assertFalse($res['isError'], json_encode($res['data']));
        $this->assertSame($hash, $res['data']['hash']);
        $this->assertNotEmpty($res['data']['mime']);
    }

    public function testGetFileInfoUnknownHash(): void
    {
        $res = $this->toolResult($this->callTool('get_file_info', ['hash' => 'nope123.png']));
        $this->assertTrue($res['isError']);
    }

    public function testDeleteFile(): void
    {
        [$hash, $code] = $this->uploadFixture('test.jpg');
        $res = $this->toolResult($this->callTool('delete_file', ['hash' => $hash, 'delete_code' => $code]));
        $this->assertFalse($res['isError'], json_encode($res['data']));
        $this->assertFalse(isExistingHash($hash));
    }

    public function testDeleteFileWrongCode(): void
    {
        [$hash] = $this->uploadFixture('test.webp');
        $res = $this->toolResult($this->callTool('delete_file', ['hash' => $hash, 'delete_code' => 'wrong']));
        $this->assertTrue($res['isError']);
        $this->assertTrue(isExistingHash($hash));
    }

    public function testCreateAlbum(): void
    {
        [$h1] = $this->uploadFixture('test.png');
        [$h2] = $this->uploadFixture('test.gif');
        $res = $this->toolResult($this->callTool('create_album', ['hashes' => [$h1, $h2]]));
        $this->assertFalse($res['isError'], json_encode($res['data']));
        $this->assertSame(2, $res['data']['count']);
        $this->assertNotEmpty($res['data']['hash']);
        $this->uploadedHashes[] = $res['data']['hash'];
    }
```

Note: `testDeleteFile` uses `test.jpg` and `testDeleteFileWrongCode` uses `test.webp` so sha1-deduplication against `test.png` uploads in other tests can't cross-contaminate, and deleting puts that sha1 on the naughty list — using distinct fixtures per delete test avoids breaking the upload tests.

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: 5 new tests FAIL (unknown tool).

- [ ] **Step 3: Implement the tools**

Add to the builder chain in `buildServer()`:

```php
            ->addTool(
                handler: function (string $hash): array {
                    return PictShareMcp::callApi(['info', sanatizeString(trim($hash))]);
                },
                name: 'get_file_info',
                description: 'Get metadata of an uploaded file by hash: mime type, size, sha1, upload time.'
            )
            ->addTool(
                handler: function (string $hash, string $delete_code): array {
                    return PictShareMcp::callApi(['delete', sanatizeString(trim($delete_code)), sanatizeString(trim($hash))]);
                },
                name: 'delete_file',
                description: 'Permanently delete an uploaded file. Requires the delete_code returned at upload time.'
            )
            ->addTool(
                handler: function (array $hashes): array {
                    return PictShareMcp::callApi(['album'], ['hashes' => $hashes]);
                },
                name: 'create_album',
                description: 'Create an album from existing file hashes (max 200). Returns the album hash and url.'
            )
```

Note: `API::info()` returns the raw metadata array which has no `status` key — `callApi`'s `?? 'ok'` default handles that. It also contains `delete_code`/`ip`/`useragent`; that matches what the public REST `/api/info` endpoint already exposes (no new privilege).

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/inc/mcp.class.php tests/Integration/McpServerTest.php
git commit -m "feat(mcp): add get_file_info, delete_file and create_album tools

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: transform_image tool

**Files:**
- Modify: `src/inc/mcp.class.php`
- Test: `tests/Integration/McpServerTest.php`

**Interfaces:**
- Consumes: `isExistingHash()`, `isSize()` (core.php), `isRotation()` (resize.php), `getFilters()` (filters.php) — the latter two are include_once'd at the top of mcp.class.php since Task 2.
- Produces: MCP tool `transform_image(hash, size?, filter?, rotate?, forcesize?, webp?)` returning `['url' => <transformed url>]`. No rendering happens at call time.

- [ ] **Step 1: Write the failing tests**

Add to `McpServerTest`:

```php
    public function testTransformImage(): void
    {
        [$hash] = $this->uploadFixture();
        $res = $this->toolResult($this->callTool('transform_image', [
            'hash' => $hash, 'size' => '100x50', 'filter' => 'sepia', 'rotate' => 'left', 'forcesize' => true,
        ]));
        $this->assertFalse($res['isError'], json_encode($res['data']));
        $this->assertSame(getURL() . "100x50/sepia/left/forcesize/$hash", $res['data']['url']);
    }

    public function testTransformImageRejectsUnknownFilter(): void
    {
        [$hash] = $this->uploadFixture();
        $res = $this->toolResult($this->callTool('transform_image', ['hash' => $hash, 'filter' => 'unicornify']));
        $this->assertTrue($res['isError']);
        $this->assertStringContainsStringIgnoringCase('filter', json_encode($res['data']));
    }

    public function testTransformImageRejectsBadSizeAndUnknownHash(): void
    {
        [$hash] = $this->uploadFixture();
        $res = $this->toolResult($this->callTool('transform_image', ['hash' => $hash, 'size' => 'bogus']));
        $this->assertTrue($res['isError']);

        $res = $this->toolResult($this->callTool('transform_image', ['hash' => 'nope123.png', 'size' => '100']));
        $this->assertTrue($res['isError']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: 3 new tests FAIL (unknown tool).

- [ ] **Step 3: Implement the tool**

Add to the builder chain:

```php
            ->addTool(
                handler: function (string $hash, ?string $size = null, ?string $filter = null,
                                   ?string $rotate = null, bool $forcesize = false, bool $webp = false): array {
                    $hash = sanatizeString(trim($hash));
                    if (!isExistingHash($hash))
                        throw new RuntimeException("Hash not found: $hash");

                    $mods = [];
                    if ($size !== null) {
                        if (!isSize($size))
                            throw new RuntimeException("Invalid size '$size'. Use WIDTHxHEIGHT (e.g. 300x200) or a single number for proportional scaling");
                        $mods[] = $size;
                    }
                    if ($filter !== null) {
                        $base = explode('_', $filter)[0];
                        if (!in_array($base, getFilters()))
                            throw new RuntimeException("Unknown filter '$filter'. Available: ".implode(', ', getFilters()));
                        $mods[] = $filter;
                    }
                    if ($rotate !== null) {
                        if (!isRotation($rotate))
                            throw new RuntimeException("Invalid rotation '$rotate'. Allowed: left, right, upside");
                        $mods[] = $rotate;
                    }
                    if ($forcesize) {
                        if ($size === null)
                            throw new RuntimeException('forcesize requires a size');
                        $mods[] = 'forcesize';
                    }
                    if ($webp)
                        $mods[] = 'webp';
                    if (!$mods)
                        throw new RuntimeException('No modifiers given. Provide at least one of: size, filter, rotate, webp');

                    return ['url' => getURL().implode('/', $mods).'/'.$hash];
                },
                name: 'transform_image',
                description: 'Build a URL that serves a transformed version of an uploaded image. '
                    .'Modifiers: size ("300x200" or "300"), filter (e.g. sepia, blur_5, pixelate_10), '
                    .'rotate (left/right/upside), forcesize (crop to exact size), webp (convert format). '
                    .'PictShare renders the derived image lazily on first request of the URL.'
            )
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit tests/Integration/McpServerTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full suite**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit`
Expected: all tests pass (no regressions from the shared `$_REQUEST` handling).

- [ ] **Step 6: Commit**

```bash
git add src/inc/mcp.class.php tests/Integration/McpServerTest.php
git commit -m "feat(mcp): add transform_image tool with modifier validation

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: Route wiring, end-to-end verify, docs

**Files:**
- Modify: `web/index.php:32` (insert before the `api` block)
- Modify: `README.md` (new "MCP server" section)
- Modify: `docs/superpowers/specs/2026-07-16-mcp-server-design.md` (record scope deltas)

**Interfaces:**
- Consumes: `PictShareMcp::serve()` from Task 2.

- [ ] **Step 1: Wire the route**

In `web/index.php`, directly above `if($url[0] == 'api')`:

```php
if($url[0] == 'mcp')
{
	require_once(ROOT.DS.'src'.DS.'inc'.DS.'mcp.class.php');
	PictShareMcp::serve();
	exit();
}
```

(Keep the file's tab indentation.)

- [ ] **Step 2: End-to-end verify against the dev container**

```bash
cd /home/chris/git/pictshare && docker compose -f docker-compose-dev.yml up --build -d
sleep 5
curl -s -X POST http://localhost:8080/mcp \
  -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}' -i
```
Expected: HTTP 200, response contains `"serverInfo"` with `"name":"PictShare"` and an `Mcp-Session-Id` header. Then list tools using that session id:
```bash
curl -s -X POST http://localhost:8080/mcp \
  -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' \
  -H "Mcp-Session-Id: <id from above>" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
```
Expected: six tools. Optionally register in Claude Code: `claude mcp add --transport http pictshare http://localhost:8080/mcp`. Shut down with `docker compose -f docker-compose-dev.yml down`.

If the dev container mounts don't include `src/lib/vendor` with the new packages, run `cd src/lib && composer install` first (dev compose mounts source as volumes).

- [ ] **Step 3: Write README section**

Add after the API documentation section of `README.md`:

```markdown
## MCP server

PictShare has a built-in [Model Context Protocol](https://modelcontextprotocol.io) endpoint at `POST /mcp` (Streamable HTTP), so LLM agents can upload and manage files directly.

Register in Claude Code:

​```bash
claude mcp add --transport http pictshare https://your.pictshare.host/mcp
​```

If your instance sets `UPLOAD_CODE`, clients must send it as a bearer token: `Authorization: Bearer <upload code>`.

Available tools:

| Tool | Purpose |
|------|---------|
| `upload_from_url` | Download a remote URL and store it |
| `upload_base64` | Upload base64-encoded content |
| `get_file_info` | File metadata (mime, size, sha1, upload time) |
| `delete_file` | Delete a file using its delete code |
| `create_album` | Combine hashes into an album |
| `transform_image` | Build a resized/filtered/rotated image URL |
```
(Remove the zero-width markers around the inner code fence.)

- [ ] **Step 4: Update spec with scope deltas**

In `docs/superpowers/specs/2026-07-16-mcp-server-design.md`: replace the `format` parameter row/paragraph of `transform_image` with `webp` bool (the video controller has no webm conversion; GIF→MP4 stays a plain URL modifier), note that `upload_base64` has no `filename` parameter (mime sniffing covers type detection), and note rotation values are `left`/`right`/`upside`.

- [ ] **Step 5: Run full test suite one last time**

Run: `cd /home/chris/git/pictshare && src/lib/vendor/bin/phpunit`
Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add web/index.php README.md
git add -f docs/superpowers/specs/2026-07-16-mcp-server-design.md
git commit -m "feat(mcp): wire /mcp route and document MCP server

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```
