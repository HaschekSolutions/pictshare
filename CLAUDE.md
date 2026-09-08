# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

PictShare is a self-hosted image/video/text/URL shortening service written in PHP 8.2+. It is containerized via Docker using FrankenPHP (PHP + Caddy integrated server) and optionally uses Redis for caching.

## Development Commands

### Run locally (development)
```bash
docker compose -f docker-compose-dev.yml up --build
```
This mounts source code as volumes and enables logging. The app is accessible at `http://localhost:8080`.

### Run production image
```bash
docker compose up
```

### Build Docker image manually
```bash
docker build -f docker/Dockerfile -t pictshare .
```

### Install PHP dependencies (Composer)
```bash
cd src/lib && composer install
```

### One-liner quickstart
```bash
docker run -p 8080:80 ghcr.io/hascheksolutions/pictshare
```

## Architecture

### Request Flow

1. **`web/index.php`** — Entry point. Loads config, boots Redis, includes all controllers, calls `architect($url)`.
2. **`src/inc/core.php`** — Central routing (`architect()` function). Parses URL segments, dispatches to content controllers, manages Redis caching, handles deletion logic.
3. **`src/inc/api.class.php`** — REST API handler for `/api/*` routes (upload, delete, info, passthrough, debug).
4. **`src/inc/mcp.class.php`** — MCP server for the `/mcp` route (official `mcp/sdk`, Streamable HTTP). Tools delegate to the `API` class; auth is `Authorization: Bearer <UPLOAD_CODE>` when set. Usage docs: `rtfm/MCP.md`.

### Plugin Architectures

**Content Controllers** (`src/content-controllers/`): Each subdirectory implements the `ContentController` interface (`src/interfaces/contentcontroller.interface.php`). Controllers are auto-discovered. Key methods:
- `getRegisteredExtensions()`: declare handled file types
- `handleHash($hash, $url, $path)`: render/process a file request
- `handleUpload($tmpfile, $hash, $passthrough)`: process an upload

Current controllers: `image/`, `video/`, `text/`, `url/`, `identicon/`, `placeholder/`, `svg/`, `html/` (hosts raw HTML/JS verbatim — disabled unless `HTML_HOSTING_ENABLED` + a separate `HTML_UPLOAD_CODE` are set; see `rtfm/CONFIG.md`)

**Storage Controllers** (`src/storage-controllers/`): Each implements `StorageController` interface. They mirror files to external backends (S3, FTP, local alt folder). Key methods: `isEnabled()`, `hashExists()`, `pullFile()`, `pushFile()`, `deleteFile()`.

### Data Storage

Each uploaded file lives at `data/<hash>/<hash>` with a companion `data/<hash>/meta.json` (MIME, size, SHA1, upload time, IP, user agent, delete code). Once a day, `tools/cron.php flushviews` merges `last_accessed` (unix timestamp) and `views` (int) into `meta.json` from Redis — see Caching below.

### Configuration

Configuration is generated at container startup by `docker/rootfs/start.sh` from environment variables into `src/inc/config.inc.php` as PHP constants. See `src/inc/example.config.inc.php` for all available options. Key env vars: `URL`, `ADMIN_PASSWORD`, `UPLOAD_CODE`, `MAX_UPLOAD_SIZE`, `REDIS_SERVER`, `CONTENTCONTROLLERS`, `S3_*`, `FTP_*`, `ENCRYPTION_KEY`, `HTML_HOSTING_ENABLED`/`HTML_UPLOAD_CODE` (dangerous — off by default, see rtfm/CONFIG.md).

### Caching

Redis stores `cache:byurl:<url>` (URL → controller+hash mapping), `served:<hash>` (view counters), and `lastaccessed:<hash>` (unix timestamp of the most recent view). Redis is optional but enabled by default. `tools/cron.php flushviews` (run daily by `docker/rootfs/start.sh`) merges `lastaccessed:<hash>`/`served:<hash>` into each hash's `meta.json` as `last_accessed`/`views`, then clears the `lastaccessed:` key.

### API Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/upload` | Upload file |
| POST | `/api/geturl` | Download & store remote URL |
| POST | `/api/base64` | Upload base64-encoded file |
| POST | `/api/passthrough/image` | Process image with modifiers, return directly |
| GET | `/api/info` | File metadata |
| GET | `/api/debug` | Server diagnostics |
| POST | `/mcp` | MCP server (JSON-RPC, tools: upload_from_url, upload_base64, upload_html, get_file_info, delete_file, create_album, transform_image) |

### Adding a New Content Type

1. Create `src/content-controllers/<type>/`
2. Implement `ContentController` interface
3. Register extensions in `getRegisteredExtensions()`
4. Implement `handleHash()` and `handleUpload()`

The controller is auto-discovered — no registration needed elsewhere.

## CI/CD

`.github/workflows/build-docker.yml` builds multi-platform images (amd64 + arm64) on version tags (`v*.*.*`) and PRs, pushing to Docker Hub and GHCR.
