# MCP Server for PictShare — Design

**Date:** 2026-07-16
**Status:** Approved

## Goal

Expose PictShare's core functionality (upload, info, delete, albums, image/video transforms) to LLM clients via the Model Context Protocol, so agents like Claude Code and Claude Desktop can use a PictShare instance directly as a tool.

## Architecture

The MCP server is **built into PictShare itself** — no separate process or package.

- **Transport:** MCP Streamable HTTP, stateless mode, served at `POST /mcp`.
- **Routing:** `web/index.php` intercepts the `mcp` URL segment before `architect()` dispatch, mirroring the existing `/api` pattern, and hands the request to a new `src/inc/mcp.class.php`.
- **SDK:** The official [`modelcontextprotocol/php-sdk`](https://github.com/modelcontextprotocol/php-sdk) (composer package `mcp/sdk`), installed via the existing composer setup in `src/lib`. The Docker build already runs composer install, so the dependency ships with the image automatically.
- **Business logic:** All tools are thin wrappers around existing code in `src/inc/api.class.php` and `src/inc/core.php`. No upload, validation, or storage logic is duplicated.

## Authentication

- Requests to `/mcp` must present `Authorization: Bearer <UPLOAD_CODE>` when the `UPLOAD_CODE` config constant is set.
- The check runs **before** the SDK processes the request. On mismatch: HTTP 401 with a JSON-RPC error body.
- If `UPLOAD_CODE` is not set, the MCP endpoint is open — consistent with the existing REST API behavior.
- No new configuration is introduced.

## Tools

| Tool | Wraps | Parameters | Returns |
|------|-------|------------|---------|
| `upload_from_url` | `/api/geturl` logic | `url` (string, required) | hash, public URL, delete code |
| `upload_base64` | `/api/base64` logic | `data` (base64 string, required) — no filename parameter; the file type is detected from content | hash, public URL, delete code |
| `get_file_info` | `/api/info` logic | `hash` (string, required) | mime type, size, sha1, view count, upload timestamp |
| `delete_file` | `/api/delete` logic | `hash` (string, required), `delete_code` (string, required) | success or error message |
| `create_album` | `Api::createAlbum` | `hashes` (string array, required) | album hash, album URL |
| `transform_image` | URL modifier builder | see below | validated transformed URL (no rendering at call time) |

### `transform_image`

Parameters:

- `hash` (string, required)
- `size` (string, optional) — `WxH` format, e.g. `300x200`
- `filter` (string, optional) — one of the filters supported by the image controller (`negative`, `grayscale`, `sepia`, etc.)
- `rotate` (string, optional) — `left`, `right`, or `upside`
- `forcesize` (bool, optional) — requires `size`
- `webp` (bool, optional) — convert output to WebP

(Implementation delta: no video `format` parameter — the video controller has no WebM conversion, and GIF→MP4 remains a plain URL modifier outside this tool's scope.)

Behavior: validates each modifier against the known modifier list of the relevant content controller. Unknown or malformed modifiers are rejected with a descriptive error. On success, returns the composed URL (e.g. `https://host/300x200/negative/abc.jpg`). PictShare renders the derived file lazily on first fetch, as it already does today — the tool never triggers rendering itself.

## Error Handling

- Tool-level failures return an MCP tool result with `isError: true` and a human-readable message.
- Error messages reuse existing strings: `Api::uploadErrorMessage()`, naughty-list rejection, permission failures.
- Existing checks (`checkPermissions`, `checkUploaderPermissions`, `isFileInNaughtyList`) are invoked exactly as the REST API does — the MCP surface grants no additional privileges.
- Protocol-level errors (malformed JSON-RPC, unknown method) are handled by the SDK.

## Testing

- PHPUnit-style tests posting JSON-RPC payloads to `/mcp`:
  - `initialize` handshake
  - `tools/list` returns all six tools with correct schemas
  - Happy path per tool
  - Auth: request without/with wrong bearer token → 401
  - `transform_image` rejects unknown modifiers
- Manual verification: register against a running dev container with `claude mcp add --transport http pictshare http://localhost:8080/mcp` and exercise tools from Claude Code.

## Documentation

- New README section: endpoint URL, authentication, example client configuration (Claude Code / Claude Desktop), tool overview.

## Out of Scope

- MCP resources and prompts (tools only for now)
- Session-based/stateful transport, SSE streaming
- A standalone stdio server package
- Returning image content blocks from `transform_image` (URL only)
