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

### `$fd['value']` missing key guard
In the `case 'filters':` dispatch loop, `$value = $fd['value']` triggers E_NOTICE for preset filters stored as `['filter' => 'sepia']` with no `'value'` key.
**Fix:** Change to `$value = $fd['value'] ?? null;`. Behavior is identical — preset filter methods ignore `$val` anyway.

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
- `ico` → no native GD support; return `false`

**Caller fix:** `saveObjOfImage` returns `$im` (a GD resource) on success or `false` on failure. The call site has two places where `$path = $newpath` is assigned unconditionally — once inside the `if(!file_exists($newpath))` block and once in the `else if($modifiers['webp'])` branch below it. Both must be guarded: only assign `$path = $newpath` if `saveObjOfImage !== false`. If it returns `false`, keep the original `$path` and serve the unmodified file. Use `!== false` explicitly (not a truthiness check) to be clear about intent, even though a GD resource is always truthy.

---

## Section 2: Static GIF Support

### Problem
The entire filter/resize/rotation pipeline is skipped for all GIFs with the comment "PHP can't handle animated gifs." This is overly broad — static (single-frame) GIFs work fine through GD.

### Detection
Detect animated GIFs by counting Graphic Control Extension blocks (`\x21\xF9\x04`) in the raw file bytes. One such block = static; more than one = animated.

**Known limitation:** these three bytes can theoretically appear in pixel data or comment blocks of a single-frame GIF, producing a false positive (static GIF treated as animated). The consequence is that filters are silently skipped — not a crash or regression. This heuristic is accepted as good enough for this scope.

### Behavior
- **Static GIF:** run the full filter/resize/rotation pipeline (same as JPG/PNG), **including WebP conversion**. `saveObjOfImage` now handles `gif` write-back via `imagegif`.
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
`colorize` requires three underscore-separated values (R, G, B). The existing dispatch call is `$f->$filter($im, $value)` which passes a single value.

**Approach:** store R/G/B as a single array in the `$modifiers['filters']` entry:
```php
// stored as:
['filter' => 'colorize', 'value' => [80, 20, 0]]

// dispatched as:
$im = $f->colorize($im, $value);  // $value is an array [R, G, B]
```
The `Filter::colorize` method signature stays `($im, $val)` — it receives the array as `$val` and unpacks it internally. The dispatch call in `handleHash` is unchanged.

For parsing: after matching `colorize` as the filter name, extract `$a[1]`, `$a[2]`, `$a[3]` (defaulting missing values to 0) and store as array. All other filters continue to use the single scalar value path.

---

## Section 4: Data Flow

```
Request URL segments
  → $modifiers = []  (initialized)
  → foreach URL segment:
      isSize()      → $modifiers['size']
      isRotation()  → $modifiers['rotation']
      isFilter()    → $modifiers['filters'][]  (with value(s))
  → after loop (existing placement, unchanged):
      'webp' in url → $modifiers['webp']
      'forcesize' in url AND isset($modifiers['size']) → $modifiers['forcesize']
  → animated GIF check (if type == gif):
      animated → MP4-only branch (unchanged)
      static   → continue to modifier pipeline
  → if $modifiers not empty:
      compute modhash (unchanged — cache still valid)
      if cached variant exists: serve it
      else: apply modifiers, saveObjOfImage !== false → $path = $newpath, serve
      if saveObjOfImage === false: serve original $path
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
