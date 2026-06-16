# ADR-005 Wave 1 — Implementation Report

**Date:** 2026-06-16  
**Branch scope:** clients · portfolios · general_settings  
**Status:** ✅ All 9 steps completed  

---

## Summary

Wave 1 adds nullable `*_media_id` FK columns that reference `media.id` alongside every
existing path-string column in scope. Old columns are intentionally kept (no-drop policy)
until a stability window passes (Wave 2/3). All writes now populate both the old path
column AND the new FK column simultaneously (dual-write).

---

## Step-by-step Completion

### Step 1.1 — Ghost Relation Removed ✅

`app/Models/Section.php` — removed `image()` BelongsTo relation.  
The `sections.image_id` column does not exist in the database; the relation was dead code
that would throw an exception if ever called.

---

### Step 1.2 — MediaPathNormalizer Created ✅

**File:** `app/Support/Media/MediaPathNormalizer.php`

Unified static utility replacing two divergent private `normalizeMediaPath()` methods
(HomeController vs AppearanceController). Provides:

| Method | Purpose |
|--------|---------|
| `normalize($value)` | Strip leading `/`, `storage/` prefix; null for empty/external URLs |
| `resolveToMediaId($value)` | Path or numeric ID → `media.id` (null if orphaned) |
| `resolveMany(array)` | Batch version of `resolveToMediaId` |

**Key divergence fixed:** HomeController's private version did NOT strip `storage/` prefix;
AppearanceController's private version DID. `MediaPathNormalizer::normalize()` always strips.

---

### Step 2 — Migration Created ✅

**File:** `database/migrations/2026_06_16_200001_add_media_id_columns_wave1.php`

Adds 9 nullable FK columns across 3 tables:

| Table | New Column | References |
|-------|-----------|-----------|
| `clients` | `avatar_media_id` | `media.id` nullOnDelete |
| `portfolios` | `default_image_media_id` | `media.id` nullOnDelete |
| `general_settings` | `logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `dark_logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `sticky_logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `dark_sticky_logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `admin_logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `admin_dark_logo_media_id` | `media.id` nullOnDelete |
| `general_settings` | `favicon_media_id` | `media.id` nullOnDelete |

Old path columns (`avatar`, `default_image`, `logo`, …) are **NOT** dropped.

**Excluded from Wave 1 (intentional):**
- `services.icon` — stores static theme asset paths (`/assets/tamplate/images/icons/*.svg`),
  incompatible with Pattern A. Documented in `docs/ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md`.
- `templates.image`, `portfolios.images`, `payment_logos`, `logo_override` — deferred to
  Wave 2/3.

---

### Step 3 — Models Updated ✅

#### `app/Models/Client.php`
- Added `avatar_media_id` to `$fillable`
- Added `avatarMedia(): BelongsTo`

#### `app/Models/Portfolio.php`
- Added `default_image_media_id` to `$fillable`
- Added `defaultImageMedia(): BelongsTo`
- Added `use Illuminate\Database\Eloquent\Relations\BelongsTo`

#### `app/Models/GeneralSetting.php`
- Added 7 `*_media_id` fields to `$fillable`
- Added 7 BelongsTo relations: `logoMedia()`, `darkLogoMedia()`, `stickyLogoMedia()`,
  `darkStickyLogoMedia()`, `adminLogoMedia()`, `adminDarkLogoMedia()`, `faviconMedia()`
- Added `use Illuminate\Database\Eloquent\Relations\BelongsTo`

---

### Step 4 — Dual Write in Controllers ✅

Every controller that writes the old path columns now also writes the corresponding
`*_media_id` column by calling `MediaPathNormalizer::resolveToMediaId($rawValue)`.

#### `app/Http/Controllers/Admin/ClientController.php`
- Added `use App\Support\Media\MediaPathNormalizer`
- `buildClientPayload()`: dual-write `avatar_media_id` when avatar is set

#### `app/Http/Controllers/Admin/PortfolioController.php`
- `store()` + `update()`: `default_image_media_id` written from already-validated integer ID
  (`nullable|integer|exists:media,id`) — no path normalization needed; written directly

#### `app/Http/Controllers/Admin/HomeController.php`
- Added `use App\Support\Media\MediaPathNormalizer`
- `importGeneralSettings()` loop: captures raw value before `normalizeMediaPath()`, writes
  `$validated[$field . '_media_id']`
- `updateGeneralSettings()`: 14 assignment lines (7 path + 7 FK) for all logo/favicon fields
- `autoSaveGeneralSettings()` `$assetMap` loop: adds `*_media_id` assignment alongside path

---

### Step 5 — Backfill Command Created ✅

**File:** `routes/console.php` — `adr005:backfill-wave1`

```bash
# Dry run — preview without writing
php artisan adr005:backfill-wave1 --dry-run

# Apply
php artisan adr005:backfill-wave1
```

**Expected output (based on Phase 0.5 audit):**

| Table | Rows with path | Resolvable | Orphaned → NULL |
|-------|---------------|-----------|-----------------|
| `clients` | 2 | 0 | 2 (images/clients/1.png — no media record) |
| `portfolios` | varies | varies | 1 (portfolio #3 — deleted media record) |
| `general_settings` | 0 | 0 | 0 (all NULL — nothing to backfill) |

---

### Step 6 — Read Switch ✅

#### Model helpers added (non-breaking, prefer FK then fallback to old column)

**`GeneralSetting`** — 7 methods:  
`resolvedLogoPath()`, `resolvedDarkLogoPath()`, `resolvedStickyLogoPath()`,
`resolvedDarkStickyLogoPath()`, `resolvedAdminLogoPath()`, `resolvedAdminDarkLogoPath()`,
`resolvedFaviconPath()`

**`Portfolio`** — `resolvedDefaultImagePath()`

**`Client`** — `resolvedAvatarPath()`

Each method returns `$this->xyzMedia?->file_path ?? $this->getRawOriginal('xyz') ?? null`.

#### AppServiceProvider updated
`GeneralSetting::first()` now eager-loads all 7 media relations so `resolved*Path()` calls
in views trigger no extra queries:

```php
GeneralSetting::with([
    'logoMedia', 'darkLogoMedia', 'stickyLogoMedia', 'darkStickyLogoMedia',
    'adminLogoMedia', 'adminDarkLogoMedia', 'faviconMedia',
])->first()
```

#### Views updated (Wave 1 scope — dashboard views only)

| View | Change |
|------|--------|
| `dashboard/portfolios/index.blade.php` | `$portfolio->resolvedDefaultImagePath()` with fallback |
| `dashboard/clients/show.blade.php` | `$client->resolvedAvatarPath()` with fallback |

**Front-end public views** (`front/layouts/footers/`, `front/layouts/partials/`) still read
`$settings->logo` etc. directly — their path values are identical post-backfill. Full view
migration is deferred to Wave 2 when old columns are dropped.

---

### Step 7 — No Drop ✅

Old path columns (`clients.avatar`, `portfolios.default_image`, `general_settings.logo`, …)
are untouched in this migration. They will be dropped in a separate Wave 2 migration after a
stability window (recommend: ≥ 2 weeks after backfill with zero regressions).

---

## User Action Required

Run the following commands on the production server:

```bash
# 1. Apply migration (adds the 9 FK columns)
php artisan migrate

# 2. Clear all caches
php artisan optimize:clear

# 3. Dry-run backfill to preview
php artisan adr005:backfill-wave1 --dry-run

# 4. Apply backfill
php artisan adr005:backfill-wave1
```

### Validation SQL (run in phpMyAdmin or mysql CLI after migration)

```sql
-- 1. Confirm 9 new columns exist
SELECT COLUMN_NAME, TABLE_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'palgoalsnewtest1'
  AND COLUMN_NAME IN (
    'avatar_media_id',
    'default_image_media_id',
    'logo_media_id', 'dark_logo_media_id',
    'sticky_logo_media_id', 'dark_sticky_logo_media_id',
    'admin_logo_media_id', 'admin_dark_logo_media_id',
    'favicon_media_id'
  )
ORDER BY TABLE_NAME, COLUMN_NAME;
-- Expected: 9 rows

-- 2. Confirm 9 FK constraints exist
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'palgoalsnewtest1'
  AND REFERENCED_TABLE_NAME = 'media'
ORDER BY TABLE_NAME;
-- Expected: includes the 9 new FKs (+ any pre-existing media FKs)

-- 3. After backfill: count rows still needing resolution
SELECT 'clients orphaned' AS label,
       COUNT(*) AS cnt
FROM clients
WHERE avatar IS NOT NULL
  AND avatar_media_id IS NULL
UNION ALL
SELECT 'portfolios orphaned',
       COUNT(*)
FROM portfolios
WHERE default_image IS NOT NULL
  AND default_image_media_id IS NULL;
-- Expected: 0+0 (all path rows resolved or confirmed orphaned)
```

---

## Files Changed

| File | Change Type |
|------|------------|
| `app/Models/Section.php` | Remove ghost relation |
| `app/Support/Media/MediaPathNormalizer.php` | **New file** |
| `database/migrations/2026_06_16_200001_add_media_id_columns_wave1.php` | **New file** |
| `app/Models/Client.php` | fillable + relation + read helper |
| `app/Models/Portfolio.php` | fillable + relation + read helper |
| `app/Models/GeneralSetting.php` | fillable + 7 relations + 7 read helpers |
| `app/Http/Controllers/Admin/ClientController.php` | dual-write in buildClientPayload() |
| `app/Http/Controllers/Admin/PortfolioController.php` | dual-write in store() + update() |
| `app/Http/Controllers/Admin/HomeController.php` | dual-write in 3 methods |
| `routes/console.php` | Backfill command added |
| `app/Providers/AppServiceProvider.php` | Eager-load media relations on GeneralSetting |
| `resources/views/dashboard/portfolios/index.blade.php` | resolvedDefaultImagePath() |
| `resources/views/dashboard/clients/show.blade.php` | resolvedAvatarPath() |
| `docs/ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md` | **New file** (Phase 0.5 findings) |

---

## What Wave 2 Should Do

1. Drop old path columns (`clients.avatar`, `portfolios.default_image`, `general_settings.logo/dark_logo/…`)
2. Update all front-end views (`front/layouts/`) to use `$settings->resolved*Path()`
3. Remove fallback branches from the read helper methods
4. Update `services.icon` if/when theme assets are migrated to the media table
