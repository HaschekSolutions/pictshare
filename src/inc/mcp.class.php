<?php
/**
 * MCP (Model Context Protocol) endpoint for PictShare.
 * Serves POST /mcp via the official PHP SDK (mcp/sdk), streamable HTTP transport.
 * All tools delegate to the existing API class — no duplicated business logic.
 */

use Mcp\Exception\ToolCallException;
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
            ->addTool(
                handler: function (string $html, string $html_upload_code): array {
                    return PictShareMcp::callApi(['upload'], [
                        'base64' => 'base64,'.base64_encode($html),
                        'htmluploadcode' => $html_upload_code,
                    ]);
                },
                name: 'upload_html',
                description: 'Publish a self-contained HTML page (inline CSS/JS allowed) verbatim on this '
                    .'domain - for one-pager demos. Disabled on most instances: only works if the admin has '
                    .'enabled HTML_HOSTING_ENABLED and given you the separate html_upload_code, which is not '
                    .'the same as the normal upload code. This is a high-risk feature (it runs arbitrary script '
                    .'on the host domain) - only use it when a user explicitly asks you to publish an HTML page '
                    .'and has given you that code. Returns hash, public url and delete_code.'
            )
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
            ->addTool(
                handler: function (string $hash, ?string $size = null, ?string $filter = null,
                                   ?string $rotate = null, bool $forcesize = false, bool $webp = false): array {
                    $hash = sanatizeString(trim($hash));
                    if (!isExistingHash($hash))
                        throw new ToolCallException("Hash not found: $hash");

                    $mods = [];
                    if ($size !== null) {
                        if (!isSize($size))
                            throw new ToolCallException("Invalid size '$size'. Use WIDTHxHEIGHT (e.g. 300x200) or a single number for proportional scaling");
                        $mods[] = $size;
                    }
                    if ($filter !== null) {
                        $base = explode('_', $filter)[0];
                        if (!in_array($base, getFilters()))
                            throw new ToolCallException("Unknown filter '$filter'. Available: ".implode(', ', getFilters()));
                        $mods[] = $filter;
                    }
                    if ($rotate !== null) {
                        if (!isRotation($rotate))
                            throw new ToolCallException("Invalid rotation '$rotate'. Allowed: left, right, upside");
                        $mods[] = $rotate;
                    }
                    if ($forcesize) {
                        if ($size === null)
                            throw new ToolCallException('forcesize requires a size');
                        $mods[] = 'forcesize';
                    }
                    if ($webp)
                        $mods[] = 'webp';
                    if (!$mods)
                        throw new ToolCallException('No modifiers given. Provide at least one of: size, filter, rotate, webp');

                    return ['url' => getURL().implode('/', $mods).'/'.$hash];
                },
                name: 'transform_image',
                description: 'Build a URL that serves a transformed version of an uploaded image. '
                    .'Modifiers: size ("300x200" or "300"), filter (e.g. sepia, blur_5, pixelate_10), '
                    .'rotate (left/right/upside), forcesize (crop to exact size), webp (convert format). '
                    .'PictShare renders the derived image lazily on first request of the URL.'
            )
            ->build();
    }

    /**
     * Run an existing API action with the given request vars.
     * MCP auth already validated the upload code, so it is forwarded automatically.
     * @throws ToolCallException on API-level errors (surfaced as MCP tool errors)
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
            throw new ToolCallException($result['reason'] ?? 'Unknown API error');
        return $result;
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
