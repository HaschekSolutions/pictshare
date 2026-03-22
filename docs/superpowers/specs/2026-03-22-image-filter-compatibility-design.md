# Image Filter Compatibility — Design Spec

**Date:** 2026-03-22
**Scope:** `src/content-controllers/image/` (image.controller.php, filters.php, resize.php)
**Goal:** Fix correctness bugs, extend format support, and add parameterizable filters — with full backward compatibility for all existing URLs.

---

## Background

The image content controller supports URL-based modifiers (resize, rotate, filters). Several correctness issues exist — undefined variables masked by error suppression, missing format cases in save logic, and almost all filter methods accepting a `$val` parameter they never use. Static GIFs are entirely excluded from the filter pipeline even though GD handles them fine.

Backward compatibility is critical: existing URLs are embedded in thousands of external sites.

---

## Section 1: Bug Fixes

### `$modifiers` uninitialized
`$modifiers` is never declared before `if($modifiers)`. Works only due to `E_NOTICE` suppression.
**Fix:** Add `$modifiers = [];` before the URL-segment loop.

### `$a[1]` missing index guard
`$value = $a[1]` when a filter has no `_N` suffix (e.g. `sepia`) triggers E_NOTICE because index 1 doesn't exist.
**Fix:** Change to `$value = $a[1] ?? null;`. Behavior is identical — `null` is not numeric, so the value-less branch is taken.

### `$modifiers['forcesize']` unguarded access
Line: `if(in_array('forcesize',$url) && $modifiers['size'])` accesses `$modifiers['size']` without `isset`.
**Fix:** Flip to `isset($modifiers['size']) && in_array('forcesize', $url)` — short-circuits before accessing the array key.

### `$_SERVER['HTTP_ACCEPT']` without isset
`shouldAlwaysBeWebp()` reads `$_SERVER['HTTP_ACCEPT']` directly.
**Fix:** Wrap with `isset($_SERVER['HTTP_ACCEPT'])`.

### `saveObjOfImage` missing format cases
The method has no `case` for `gif`, `bmp`, or `ico`, so saving a modified image of those types silently does nothing (tmp file never created, rename never happens).
**Fix:**
- `gif` → `imagegif($im, $tmppath)`
- `bmp` → `imagebmp($im, $tmppath)` (available in PHP 7.2+; present in this codebase's PHP 8.2 requirement)
- `ico` → no native GD support; return `false` gracefully so the caller falls through to serving the original unmodified file

---

## Section 2: Static GIF Support

### Problem
The entire filter/resize/rotation pipeline is skipped for all GIFs with the comment "PHP can't handle animated gifs." This is overly broad — static (single-frame) GIFs work fine through GD.

### Detection
Detect animated GIFs by counting Graphic Control Extension blocks (`\x21\xF9\x04`) in the raw file bytes. One such block = static; more than one = animated.

### Behavior
- **Static GIF:** run the full filter/resize/rotation pipeline (same as JPG/PNG). `saveObjOfImage` now handles `gif` write-back via `imagegif`.
- **Animated GIF:** keep existing behavior unchanged — skip all modifiers except MP4 conversion.
- **Detection failure** (unreadable file): treat as animated, skip filters. Safe fallback, no regression.

### Backward compatibility
Existing URLs for animated GIFs are unaffected. Static GIF URLs with no modifiers are unaffected (no `$modifiers` set → existing serve path). Static GIF URLs with modifiers didn't work before (silently served unmodified), so any change is an improvement, not a regression.

---

## Section 3: Filter Value Support

### Existing parameterizable filters
`blur_N` (N = 1–6) and `pixelate_N` already work. The E_NOTICE fix from Section 1 (`?? null`) is the only change needed for them.

### Existing preset filters
The 28 named aesthetic presets (sepia, dream, forest, etc.) combine multiple hardcoded operations. There is no meaningful single value to expose for them. They keep their existing signatures (`$im, $val` where `$val` is ignored). No changes.

### New parameterizable filters (new URL segments — backward compatible)
Three new filter methods added to the `Filter` class:

| Filter | URL example | PHP mapping | Value range |
|---|---|---|---|
| `brightness` | `brightness_50` | `IMG_FILTER_BRIGHTNESS` | -255 to 255 |
| `contrast` | `contrast_-30` | `IMG_FILTER_CONTRAST` | -100 to 100 |
| `colorize` | `colorize_80_20_0` | `IMG_FILTER_COLORIZE` | R/G/B each -255 to 255 |

Out-of-range values are clamped silently to valid range.

### Parser change for `colorize`
`colorize` requires three underscore-separated values (R, G, B). The existing filter parser splits on `_` and reads `$a[1]` as a single value. For `colorize`, the parser reads `$a[1]`, `$a[2]`, `$a[3]` and passes them to the filter method.

This requires a small extension to the modifier parsing block: after matching a filter name, check if the filter is `colorize` and extract three values instead of one. All other filters use the existing single-value path.

---

## Section 4: Data Flow

```
Request URL segments
  → $modifiers = []  (initialized)
  → foreach URL segment:
      isSize()      → $modifiers['size']
      isRotation()  → $modifiers['rotation']
      isFilter()    → $modifiers['filters'][]  (with value(s))
      'webp'        → $modifiers['webp']
      'forcesize'   → $modifiers['forcesize'] (only if size also set)
  → animated GIF check (if type == gif):
      animated → MP4-only branch (unchanged)
      static   → continue to modifier pipeline
  → if $modifiers not empty:
      compute modhash (unchanged — cache still valid)
      if cached variant exists: serve it
      else: apply modifiers, saveObjOfImage, serve
```

Cache behavior is unchanged. The modhash covers all modifier combinations, so new filters get their own cached variant automatically.

---

## Out of Scope

- Animated GIF filter support (technically complex, not worth the risk)
- `ico` write-back (no GD support)
- Extracting `parseModifiers()` to a standalone function (deferred to avoid refactor scope creep)
- Automated tests (planned for a later stage)
- Changes to any other content controller

---

## Files Changed

| File | Changes |
|---|---|
| `src/content-controllers/image/image.controller.php` | Initialize `$modifiers`, fix `forcesize` guard, fix `shouldAlwaysBeWebp`, static GIF detection, `colorize` parser extension, `saveObjOfImage` gif/bmp/ico cases |
| `src/content-controllers/image/filters.php` | Add `brightness`, `contrast`, `colorize` methods to `Filter` class |
| `src/content-controllers/image/resize.php` | No changes expected |
