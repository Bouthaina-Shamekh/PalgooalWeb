# ADR-005 Wave 3 — Implementation Report

**Date:** 2026-06-16  
**Status:** Complete  
**Scope:** JSON media fields in `portfolios.images`, `header_variant_settings.*.logo_override`, `footer_variant_settings.*.logo_override`, `footer_variant_settings.*.payment_logos`

---

## Executive Summary

Wave 3 converts three JSON-stored media fields from Pattern C (paths resolved at write time, stored as strings/arrays) to Pattern A (IDs stored, paths preserved at write time for high-traffic renders).

| Field | Old Format | New Format | Strategy |
|-------|-----------|------------|----------|
| `portfolios.images` | `["media/img1.jpg", "media/img2.jpg"]` | `[7, 12]` | Pure IDs — resolve at render |
| `*.logo_override` | `"media/logo.png"` | `{"id": 5, "path": "media/logo.png"}` | Dual-write (ID + path for zero render overhead) |
| `*.payment_logos` | `["media/visa.png", "media/mc.png"]` | `{"ids": [3, 8], "paths": ["media/...", "..."]}` | Dual-write (IDs + paths for zero render overhead) |

**No migrations were created** — Wave 3 targets JSON columns in existing rows. The schema is unchanged.

---

## Why Different Strategies for Different Fields

**`portfolios.images`** uses pure ID storage because:
- It's only read on the individual portfolio detail page (low traffic)
- A single `Media::whereIn('id', $ids)` query is acceptable
- Preserving paths alongside adds no value for this access pattern

**`logo_override` and `payment_logos`** use dual-write (ID + path) because:
- They live inside `header_variant_settings` and `footer_variant_settings`
- These JSON blobs are read on *every page request* (header/footer render)
- A DB lookup per-request would be catastrophically expensive
- Preserving the path at write time enables zero-cost reads

---

## Files Modified

### Models
| File | Change |
|------|--------|
| `app/Models/Portfolio.php` | Added `resolvedGalleryImages(): array` — handles both ID arrays (new) and path arrays (old) |

### Controllers
| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/PortfolioController.php` | Added `resolveImagesToIds()` method; replaced `resolveMediaIdsToPaths()` calls for `images` field in `store()` and `update()` |
| `app/Http/Controllers/Admin/AppearanceController.php` | Added `normalizeMediaPathAsObject()` and `normalizeMediaPathListAsObject()`; updated `updateHeaderSettings()` and `updateFooterSettings()` to use new methods |

### Front-end Views
| File | Change |
|------|--------|
| `resources/views/front/pages/portfolio.blade.php` | Replaced 20-line manual images normalization with `$portfolio->resolvedGalleryImages()` call |
| `resources/views/front/layouts/headers/purple_topbar.blade.php` | Added compatibility reader: extracts `path` from `logo_override` whether it's a string (old) or `{id, path}` object (new) |
| `resources/views/front/layouts/footers/palgoals_marketing.blade.php` | Added compatibility readers for `logo_override` (string → object) and `payment_logos` (flat array → `{ids, paths}` object) |

### Admin Views
| File | Change |
|------|--------|
| `resources/views/dashboard/appearance/header.blade.php` | Updated `$purpleLogoPath` pre-fill to extract `path` from the new object format |
| `resources/views/dashboard/appearance/footer.blade.php` | Updated `$palgoalsLogoPath` pre-fill (logo_override) and `$palgoalsPaymentLogoPaths` pre-fill (payment_logos) for new object formats |
| `resources/views/dashboard/portfolios/_form.blade.php` | No change needed — existing code already detects numeric first element and loads from DB |

### Artisan
| File | Change |
|------|--------|
| `routes/console.php` | Added `adr005:backfill-wave3` command with `--dry-run` support, covering all three targets |

### Validation
| File | Note |
|------|------|
| `public/__adr005_wave3_validate.php` | Standalone browser-runnable validation script — **delete after use** |

---

## `resolvedGalleryImages()` — Format Detection Logic

```php
// In Portfolio model
public function resolvedGalleryImages(): array
{
    $raw   = $this->images; // cast to array by Eloquent
    $first = reset($raw);

    // New format: integer IDs → resolve via whereIn
    if (is_int($first) || ctype_digit((string) $first)) {
        $ids          = array_map('intval', $raw);
        $mediaRecords = Media::whereIn('id', $ids)->get()->keyBy('id');
        // returns full asset() URLs in original order
    }

    // Old format: path strings → return asset() URLs directly
    foreach ($raw as $path) {
        $urls[] = asset('storage/' . ltrim($path, '/'));
    }
}
```

---

## `normalizeMediaPathAsObject()` — Dual-write Logic

```php
// In AppearanceController
protected function normalizeMediaPathAsObject($value): ?array
{
    $path    = $this->normalizeMediaPath($value);   // existing path normalizer
    $mediaId = ctype_digit(trim($value))
        ? (int) $value
        : Media::where('file_path', $path)->first()?->id;

    return $path ? ['id' => $mediaId, 'path' => $path] : null;
}
```

---

## Compatibility Layer

All reads use **format-agnostic readers** that handle both old and new formats:

```php
// Front-end and admin Blade views:
$logoOverrideRaw = $variantSettings['logo_override'] ?? null;
$logoPath = is_array($logoOverrideRaw)
    ? ($logoOverrideRaw['path'] ?? null)   // new: extract path from object
    : $logoOverrideRaw;                    // old: use string directly

// payment_logos:
$paymentLogosRaw = $variantSettings['payment_logos'] ?? [];
if (is_array($paymentLogosRaw) && isset($paymentLogosRaw['paths'])) {
    $paths = $paymentLogosRaw['paths'];   // new: dual-write object
} elseif (is_array($paymentLogosRaw)) {
    $paths = $paymentLogosRaw;            // old: flat array
}
```

This ensures **zero downtime** — existing data continues to render correctly before backfill.

---

## User Actions Required

After deploying this code:

```bash
# 1. Preview what would be changed
php artisan adr005:backfill-wave3 --dry-run

# 2. Apply the backfill
php artisan adr005:backfill-wave3

# 3. Clear cache (general_settings is cached)
php artisan cache:clear

# 4. Open the validation URL in your browser
# https://palgoals.wpgoals.com/public/__adr005_wave3_validate.php

# 5. Delete the validation script
# rm public/__adr005_wave3_validate.php
```

---

## Backfill Command Summary

`php artisan adr005:backfill-wave3` handles 3 targets:

**1. `portfolios.images`**  
- Detects rows where `images` is a JSON path array  
- Resolves each path to a Media ID via `MediaPathNormalizer::resolveToMediaId()`  
- Orphaned paths (no media record) are dropped from the array  
- Writes `json_encode([7, 12, 15])` back to the column  

**2. `header_variant_settings.purple_topbar.logo_override`**  
- Reads the JSON blob from `general_settings`  
- If `logo_override` is a string → resolves to `{id, path}` object  
- Writes the updated JSON back to `general_settings`  

**3. `footer_variant_settings.palgoals_marketing`**  
- `logo_override`: same as above  
- `payment_logos`: flat path array → `{ids: [...], paths: [...]}` object  
- Both sub-targets written in a single update to avoid partial updates  

---

## Scope Boundaries — What Was NOT Changed

- `services.icon` — excluded per ADR-005 decision (static asset paths, not media library references)
- `portfolios.default_image` — already handled by Wave 1
- All other JSON columns not named in the Wave 3 spec
- No schema changes (no `ALTER TABLE`, no new columns, no dropped columns)
- No Livewire, no `__()`, no `with('success')`

---

## ADR-005 Progress

| Wave | Scope | Status |
|------|-------|--------|
| Wave 1 | `clients.avatar`, `portfolios.default_image`, `general_settings` ×7 logos | ✅ Complete |
| Wave 2 | `templates.image` | ✅ Complete |
| Wave 3 | `portfolios.images`, `*.logo_override`, `*.payment_logos` | ✅ Complete |
| Future | ADR-003 money columns (separate ADR) | Deferred |
