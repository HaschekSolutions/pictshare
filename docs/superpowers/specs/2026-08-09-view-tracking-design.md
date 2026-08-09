# Last-Accessed / View Tracking (retention policy groundwork) — Design

**Date:** 2026-08-09
**Status:** Approved

## Goal

Issue #145 asked for a retention/auto-delete policy, but auto-delete was rejected before (risk of deleting files still being served from a reverse-proxy/CDN cache the server never sees a hit for). This is the deliberately smaller first step: start recording, per stored file, when it was last accessed and how many times — durably, in that file's own `meta.json` — without deleting anything. A retention policy can be designed later once this data exists and has been observed to be trustworthy.

## Architecture

Entirely additive, built on infrastructure that already exists:

- `served:<hash>` is already an existing Redis counter, incremented in `architect()` (`src/inc/core.php`) on every view of a real stored hash. It keeps its current meaning and is not reset by this feature.
- A new Redis key `lastaccessed:<hash>` is set (overwritten) to the current unix timestamp on the same view events.
- Once a day, a background job scans `lastaccessed:*`, merges `last_accessed` and a `views` snapshot into that hash's `meta.json`, then deletes the Redis key.

The whole feature is Redis-dependent (like `served:<hash>` already is) and no-ops entirely when Redis is disabled or unavailable, matching the existing style in `core.php`.

## Components

**`recordView($hash)`** — new function in `src/inc/core.php`. Replaces the two `architect()` call sites that increment `served:<hash>` for a *real* stored hash:

- `core.php:189` — cache-hit path (URL already resolved via `cache:byurl:*`)
- `core.php:311` — first-resolve path, real hash

It does not touch `core.php:313`, which counts views for *dynamic* content controllers (identicon/placeholder/URL-shortener style) — those have no `meta.json` to flush into, so they're out of scope.

```php
function recordView($hash)
{
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return;
    $GLOBALS['redis']->incr("served:$hash");
    $GLOBALS['redis']->set("lastaccessed:$hash", time());
}
```

**`flushviews` command** — new case in `tools/cron.php`, alongside the existing `uploadqueue` command:

- `SCAN` (not `KEYS`, to avoid blocking Redis) over `lastaccessed:*`.
- For each match: extract `<hash>`, read the timestamp, read the current `served:<hash>` counter (missing/falsy → `0`), call `updateMetaData($hash, ['last_accessed' => $ts, 'views' => (int)$views])`.
- Delete the `lastaccessed:<hash>` key after a successful merge.
- Skip if the hash directory no longer exists (file was deleted between the view and the flush) — leave the Redis key in place and record it via the existing `addToLog()`, so it can be cleaned up as dead by a future retention pass rather than silently disappearing.

**Scheduling** — `docker/rootfs/start.sh`, inside the existing `if [[ ${REDIS_CACHING:=true} == true ]]; then ... fi` block, right after `redis-server` is daemonized:

```bash
(while true; do sleep 86400; php /app/public/tools/cron.php flushviews; done) &
```

No Dockerfile changes, no new package — reuses the PHP CLI already in the image.

## Data Model

`meta.json` gains two fields, written only once flushed (absent until the first daily flush after a file's first view):

- `last_accessed` (int, unix timestamp) — overwritten each flush, not accumulated.
- `views` (int) — a snapshot of `served:<hash>` at flush time, not independently incremented.

## Error Handling

- `recordView()` and the flush command both no-op silently if Redis isn't configured/reachable — same posture as the existing `served:<hash>` code.
- A flush that finds a `lastaccessed:<hash>` key for a hash whose directory no longer exists (deleted after being viewed, before the next flush) skips it without deleting the Redis key — see "dead hash" handling above.

## Testing

- Unit: `recordView()` sets both keys correctly when Redis is present; no-ops when absent.
- Integration: upload a file, hit it (via cache-hit and first-resolve paths), run `flushviews`, assert `meta.json` gains correct `last_accessed`/`views` and the Redis key is gone.
- Manual: verify the `start.sh` loop launches alongside `redis-server` in a running container (`docker exec ... ps` or logs), without needing to wait a full day (temporarily shrink the sleep interval for the check, or invoke `tools/cron.php flushviews` directly).

## Out of Scope

- Any actual deletion/retention policy — this only produces the data a future policy would read.
- Tracking views for dynamic (non-`meta.json`) content controllers.
- Admin UI for viewing/editing `last_accessed`/`views` (the existing `/admin/stats` page already surfaces view counts via `stats:index`; wiring `last_accessed` in there is a follow-up, not part of this design).
