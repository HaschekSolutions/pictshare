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
