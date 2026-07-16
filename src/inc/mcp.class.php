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
