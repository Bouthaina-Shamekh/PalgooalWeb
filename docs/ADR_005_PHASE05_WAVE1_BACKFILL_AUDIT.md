# ADR-005 Phase 0.5 — Wave 1 Backfill Readiness Audit

**Date:** 2026-06-16  
**Status:** COMPLETE  
**Scope:** Read-only audit. No migrations created, no schema changed, no code modified.  
**Database:** `palgoalsnewtest1` (Laragon local, MySQL 8.4.3)

---

## 1. Ghost Relation Audit — `Section::image()`

**Finding: CONFIRMED GHOST ✅**

The `Section` model declares `image(): BelongsTo` (→ `Media`, FK `image_id`).  
SQL check against `information_schema.COLUMNS`:

```sql
SELECT COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='palgoalsnewtest1'
  AND TABLE_NAME='sections'
  AND COLUMN_NAME='image_id';
-- Result: 0
```

The `sections` table has **no `image_id` column in the database**.  
The only migration creating `image_id` is `2025_06_25_111513_create_feedbacks_table.php` (testimonials table, not sections).

**Action required before Wave 1:** Drop the `image()` method from `Section.php` or keep it as a documented dead method. No data migration needed. No risk.

---

## 2. MediaPathNormalizer Audit

Two separate `normalizeMediaPath()` implementations exist with a critical divergence:

| Location | `storage/` prefix stripping | `normalizeMediaPathList()` |
|---|---|---|
| `HomeController.php` (lines 874–899) | ❌ Does NOT strip | ❌ Not present |
| `AppearanceController.php` (lines 676–724) | ✅ Strips `storage/` | ✅ Present |

### HomeController version (simplified):
```php
// Numeric → Media::find() → ltrim(file_path, '/')
// URL     → extractStoragePathFromUrl() or keep as-is
// Other   → ltrim('/')
// NOTE: does NOT strip 'storage/' prefix
```

### AppearanceController version (key difference):
```php
// Same as HomeController PLUS:
if (str_starts_with($normalized, 'storage/')) {
    $normalized = substr($normalized, strlen('storage/'));
}
```

**Implication for Wave 1 migration:** After migration, the write path will store a `media_id` (integer FK). Both normalizers need to be updated: numeric value → `Media::find($id)->file_path` is already handled. The `storage/` stripping divergence will no longer matter once the source values are IDs, not paths.

**However:** During the backfill migration that converts existing paths → IDs, the `AppearanceController` version is the correct one to use (strips `storage/` before lookup), since some stored values may have a `storage/` prefix.

---

## 3. Wave 1 Backfill Audit — Database Counts

### 3.1 `clients.avatar`

| Metric | Value |
|---|---|
| Total rows | 4 |
| Non-null avatar | 2 |
| Matched to `media.file_path` | 0 |
| **Orphaned** | **2** |

**Stored values:**
```
client #1 → images/clients/1.png
client #2 → images/clients/1.png
```

Both records store the same path `images/clients/1.png`. This is an **old direct-upload path** (pre-media-library) that has no corresponding record in the `media` table.

**Root cause:** These clients were created before the media library system was implemented. The avatar was saved directly to `storage/images/clients/` without creating a `media` record.

**Migration impact:** These 2 avatars cannot be auto-backfilled. The migration must either:
- (a) Set `image_id = NULL` for these rows (losing the avatar link), OR
- (b) Manually create `media` records for them before running the migration

**Severity: LOW** — Only 2 records, test/dev data, file is `images/clients/1.png` (likely a placeholder).

---

### 3.2 `services.icon`

| Metric | Value |
|---|---|
| Total rows | 5 |
| Non-null icon | 5 |
| Matched to `media.file_path` | 1 |
| **Orphaned** | **4** |

**Stored values:**
```
service #1 → media/2026/05/69fe157a065ef2.58817454.png   [MEDIA UPLOAD]
service #2 → /assets/tamplate/images/icons/wordpress-hosting.svg  [STATIC ASSET]
service #3 → /assets/tamplate/images/icons/domains.svg            [STATIC ASSET]
service #4 → /assets/tamplate/images/icons/Website-design.svg     [STATIC ASSET]
service #5 → /assets/tamplate/images/icons/Special-programming.svg [STATIC ASSET]
```

**Critical finding — Mixed storage types:**  
`services.icon` currently stores TWO fundamentally different types of values:
1. **Media library paths** (`media/YYYY/MM/...`) — manageable via Pattern A
2. **Static template assets** (`/assets/tamplate/images/icons/...`) — bundled SVG icons from the website theme, NOT user-uploaded media

The 4 static asset paths (`/assets/tamplate/...`) **cannot be migrated to Pattern A (FK → media.id)** because:
- They are not in the media library and never will be
- They are referenced directly from the public filesystem as theme assets
- Creating `media` records for SVG theme assets would misuse the media library

**Severity: HIGH — services.icon is architecturally BLOCKED for Wave 1.**

The migration plan must decide between:
- **(A) Keep `services.icon` as Pattern B permanently** — accept that service icons may reference either media library or static assets (hybrid)
- **(B) Upload theme SVGs to media library first** — creates unnecessary media records for static files
- **(C) Add a fallback `icon_path` column** alongside a new `icon_media_id` FK, resolving media FK first with path as fallback
- **(D) Exclude `services.icon` from ADR-005 scope** — mark it as "intentionally Pattern B"

**Recommendation: Option D** — `services.icon` stores UI theme icons that are semantically different from user-uploaded media. Exclude it from ADR-005 and document the intentional exception.

---

### 3.3 `portfolios.default_image`

| Metric | Value |
|---|---|
| Total rows | 5 |
| Non-null default_image | 5 |
| Matched to `media.file_path` | 4 |
| **Orphaned** | **1** |

**Stored values with match status:**
```
portfolio #1 → media/2026/05/69f52777f346d4.42132789.png  → media.id=7  ✅
portfolio #2 → media/2026/05/69f48b71b25757.81629382.png  → media.id=6  ✅
portfolio #3 → media/2026/04/69efa028da4677.81471412.png  → NOT IN media ❌  [ORPHAN]
portfolio #4 → media/2026/05/69f52777f346d4.42132789.png  → media.id=7  ✅
portfolio #5 → media/2026/04/69f3d9e5c58c88.56143449.png  → media.id=4  ✅
```

**Orphan analysis:** Portfolio #3's media record (`69efa028da4677`) was deleted from the `media` table while the path reference remained in `portfolios.default_image`. The physical file may or may not still exist on disk.

**Migration impact:** 4/5 can be auto-backfilled (set `image_id` = the matching media record's ID). Portfolio #3 must be set to `image_id = NULL`.

**Severity: LOW** — 1 record, already broken (media record deleted), NULL is appropriate.

---

### 3.4 `general_settings` (7 logo columns)

| Column | Current Value |
|---|---|
| `logo` | NULL |
| `dark_logo` | NULL |
| `sticky_logo` | NULL |
| `dark_sticky_logo` | NULL |
| `admin_logo` | NULL |
| `admin_dark_logo` | NULL |
| `favicon` | NULL |

**All 7 columns are NULL.** No backfill needed. Adding new `*_media_id` FK columns alongside these (or replacing them) will trivially start as NULL.

**Severity: NONE — trivially ready.**

---

## 4. Summary Table

| Column | Total | Non-null | Matched | Orphaned | Wave 1 Status |
|---|---|---|---|---|---|
| `sections.image_id` (ghost) | N/A | N/A | N/A | N/A | ✅ Ghost confirmed — no DB column exists |
| `clients.avatar` | 4 | 2 | 0 | 2 | ⚠️ 2 orphaned (old direct-upload). NULL on migration acceptable. |
| `services.icon` | 5 | 5 | 1 | 4 | ❌ **BLOCKED** — 4/5 are static template assets, not media library items |
| `portfolios.default_image` | 5 | 5 | 4 | 1 | ⚠️ 1 orphaned (deleted media record). NULL on migration acceptable. |
| `general_settings.logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.dark_logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.sticky_logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.dark_sticky_logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.admin_logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.admin_dark_logo` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |
| `general_settings.favicon` | 1 | 0 | — | 0 | ✅ All NULL — trivially ready |

---

## 5. MediaPathNormalizer Divergence — Required Fix

Before executing Wave 1 backfill (the part that converts existing paths → `media.id`):

The backfill query must use the **AppearanceController** normalization logic (strips `storage/` prefix). The HomeController version does NOT strip `storage/`, so it would fail to find media records for paths stored with that prefix.

**Backfill lookup pseudocode:**
```php
$path = ltrim($rawValue, '/');
if (str_starts_with($path, 'storage/')) {
    $path = substr($path, 8); // strip 'storage/'
}
$media = Media::where('file_path', $path)->first();
$newId = $media?->id; // null if orphaned
```

---

## 6. Readiness Verdict

```
┌─────────────────────────────────────────────────────────────┐
│  READY FOR WAVE 1 — with services.icon EXCLUDED FROM SCOPE  │
└─────────────────────────────────────────────────────────────┘
```

**Columns cleared for Wave 1:**
- `general_settings` ×7 — all NULL, no backfill needed ✅
- `portfolios.default_image` — 4/5 backfillable; 1 orphan → NULL ✅
- `clients.avatar` — 2/2 orphaned (old direct-upload) → NULL; test data only ✅

**Column EXCLUDED from Wave 1:**
- `services.icon` — 4/5 values are static template asset paths (`/assets/tamplate/...`) that are architecturally incompatible with Pattern A (FK → media.id). This column must be addressed separately with an explicit decision (Recommended: Option D — exclude from ADR-005 scope, mark as intentional Pattern B exception).

**Pre-migration checklist for Wave 1 execution:**
- [ ] Decision recorded: `services.icon` excluded from ADR-005 scope
- [ ] ADR_IMPLEMENTATION_PLAN.md updated to remove `services.icon` from Wave 1
- [ ] Backfill logic uses `AppearanceController::normalizeMediaPath()` variant (strips `storage/`)
- [ ] Migration has NULL fallback for unmatched paths (clients.avatar=2, portfolio #3=1)
- [ ] Ghost relation `Section::image()` documented or dropped before Wave 1 execution

---

## 7. Cleanup

The temporary audit script `_audit_wave1.php` should be deleted from the project root after this audit is reviewed. It contains a `DB::` bootstrap that should not remain in the codebase.
