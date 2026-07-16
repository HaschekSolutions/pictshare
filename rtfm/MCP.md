# MCP server

PictShare has a built-in [Model Context Protocol](https://modelcontextprotocol.io) server, so LLM agents (Claude Code, Claude Desktop, and any other MCP-capable client) can upload and manage files on your instance directly.

- Endpoint: `POST https://your.pictshare.host/mcp`
- Transport: Streamable HTTP
- Auth: only if your instance sets `UPLOAD_CODE` (see below)

No extra setup needed — the endpoint ships with the Docker image.

## Connecting a client

### Claude Code

```bash
claude mcp add --transport http pictshare https://your.pictshare.host/mcp
```

With an upload code:

```bash
claude mcp add --transport http pictshare https://your.pictshare.host/mcp \
  --header "Authorization: Bearer YOUR_UPLOAD_CODE"
```

### Claude Desktop / generic JSON config

```json
{
  "mcpServers": {
    "pictshare": {
      "type": "http",
      "url": "https://your.pictshare.host/mcp",
      "headers": {
        "Authorization": "Bearer YOUR_UPLOAD_CODE"
      }
    }
  }
}
```

Omit the `headers` block if your instance has no `UPLOAD_CODE`.

## Authentication

If the `UPLOAD_CODE` [config option](/rtfm/CONFIG.md) is set, every MCP request must carry it as a bearer token:

```
Authorization: Bearer <your upload code>
```

Without the header (or with a wrong code) the server answers `401`. If `UPLOAD_CODE` is not set, the MCP endpoint is open — same behavior as the REST API.

## Tools

### upload_from_url

Downloads a file from a public http(s) URL and stores it (max 20 MB).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `url` | string | yes | Public http(s) URL of the file |

Returns:

```json
{
    "status": "ok",
    "hash": "y1b6hr.jpg",
    "filetype": "image/jpeg",
    "url": "https://your.pictshare.host/y1b6hr.jpg",
    "delete_code": "abc...xyz",
    "delete_url": "https://your.pictshare.host/delete_abc...xyz/y1b6hr.jpg"
}
```

Keep the `delete_code` if you want to delete the file later.

### upload_base64

Uploads base64-encoded content. Accepts raw base64 or a data-URI (`data:image/png;base64,...`). The file type is detected from the content itself.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `data` | string | yes | Base64-encoded file content |

Returns the same structure as `upload_from_url`.

### get_file_info

Fetches metadata of an uploaded file.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `hash` | string | yes | File hash, e.g. `y1b6hr.jpg` |

Returns mime type, size, sha1, upload time and more — same data as [`/api/info`](/rtfm/API.md).

### delete_file

Permanently deletes a file. Needs the delete code returned at upload time (or the instance's `MASTER_DELETE_CODE`).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `hash` | string | yes | File hash |
| `delete_code` | string | yes | Delete code from the upload response |

### create_album

Groups existing files into an immutable album (max 200 hashes).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `hashes` | string[] | yes | Hashes of already-uploaded files |

Returns the album hash, a shareable album URL and a delete code.

### transform_image

Builds a URL that serves a transformed version of an uploaded image. Nothing is rendered at call time — PictShare renders the derived image lazily on first request of the URL (see [MODIFIERS](/rtfm/MODIFIERS.md)).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `hash` | string | yes | Hash of an uploaded image |
| `size` | string | no | `WIDTHxHEIGHT` (e.g. `300x200`) or a single number for proportional scaling |
| `filter` | string | no | One of the [image filters](/rtfm/IMAGEFILTERS.md), e.g. `sepia`, `blur_5`, `pixelate_10` |
| `rotate` | string | no | `left`, `right` or `upside` |
| `forcesize` | bool | no | Crop to the exact size instead of fitting (requires `size`) |
| `webp` | bool | no | Convert output to WebP |

Example call with `{"hash": "y1b6hr.jpg", "size": "100x50", "filter": "sepia"}` returns:

```json
{
    "url": "https://your.pictshare.host/100x50/sepia/y1b6hr.jpg"
}
```

## Error handling

Tool failures come back as MCP tool errors (`isError: true`) with the same human-readable reasons the REST API uses, e.g. `Invalid delete code`, `Unsupported mime type: application/zip` or `Unknown filter 'unicorn'`.

## Testing with curl

The endpoint speaks plain JSON-RPC 2.0, so you can poke it without an MCP client:

```bash
# 1. initialize — note the Mcp-Session-Id response header
curl -i -X POST https://your.pictshare.host/mcp \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl","version":"1.0"}}}'

# 2. list tools (replace SESSION_ID with the header value from step 1)
curl -X POST https://your.pictshare.host/mcp \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'Mcp-Session-Id: SESSION_ID' \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
```

Add `-H "Authorization: Bearer YOUR_UPLOAD_CODE"` to both calls if your instance requires it.

## Troubleshooting

| Symptom | Cause / fix |
|---------|-------------|
| `401 Unauthorized` | Instance has `UPLOAD_CODE` set — send it as bearer token |
| `503` with "MCP dependencies not installed" | Manual (non-Docker) install without composer deps — run `cd src/lib && composer install` |
| "Session not found" errors | Sessions live in `tmp/mcp_sessions` — make sure `tmp/` is writable |
