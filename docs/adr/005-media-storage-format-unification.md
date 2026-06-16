# ADR-005: Media Storage Format Unification

**Status:** Proposed  
**Date:** 2026-06-16  
**Author:** Engineering (documented from code audit)  
**Related:** `docs/27-media-library.md`, `app/Models/Media.php`, `app/Support/Sections/SectionFrontendMediaResolver.php`

---

## Context

The platform has a central `media` table and a `Media` model that tracks every uploaded file — its path, disk, dimensions, MIME type, alt text, and uploader. The `media` table is the authoritative source for uploaded files.

However, modules that reference media assets do not do so uniformly. A code audit reveals **three distinct storage patterns** coexisting in the same codebase:

---

### Pattern A — `media.id` Integer FK (True Relational Reference)

The referencing column stores an integer that is the primary key of a `media` row. A database-level foreign key constraint enforces referential integrity. Deleting a media record cascades or nullifies the reference automatically.

**Where used:**

**`feedbacks.image_id`** (migration `2025_06_25_111513_create_feedbacks_table.php`):
```php
$table->foreignId('image_id')
    ->nullable()
    ->constrained('media')
    ->nullOnDelete();   // ← FK enforced at DB level
```
Model relationship:
```php
// Testimonial model
public function image(): BelongsTo
{
    return $this->belongsTo(Media::class, 'image_id');
}
```
Controller validation:
```php
'featured_image_id' => 'nullable|integer|exists:media,id'
```

**`section_definitions.preview_media_id`** (migration `2026_04_18_000002_add_preview_media_id_to_section_definitions_table.php`):
```php
$table->foreignId('preview_media_id')
    ->nullable()
    ->constrained('media')
    ->nullOnDelete();   // ← FK enforced at DB level
```

**`section_translations.content` (JSON fields of type `media`)** — Section Definition fields with `field_type = 'media'` store integer media IDs inside the JSON content blob:
```json
{ "hero_image": 42, "background": 17 }
```
Resolved at render time via:
```php
SectionFrontendMediaResolver::resolve($data['hero_image'] ?? null)
// → fetches Media::find(42) → returns Storage::url($media->file_path)
```
**Note:** These IDs are inside a JSON column — there is NO database-level FK constraint enforcing them. They are application-layer ID references, not true relational FKs.

---

### Pattern B — Raw `file_path` String (No Relational Reference)

The referencing column stores a relative storage path string (e.g., `media/2025/06/image.jpg`) directly. There is no foreign key to the `media` table. The `Media` record may or may not still exist — the path is the only thing stored.

**Where used:**

**`general_settings.logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon`** (migration `2025_06_17_113900_create_general_settings_table.php`):
```php
$table->string('logo')->nullable();           // raw path, no FK
$table->string('dark_logo')->nullable();
$table->string('sticky_logo')->nullable();
$table->string('dark_sticky_logo')->nullable();
$table->string('admin_logo')->nullable();
$table->string('admin_dark_logo')->nullable();
$table->string('favicon')->nullable();
```
These columns store strings like `media/2025/06/logo.png`. Consumed directly:
```php
// HomeController
'logo_url' => $generalSettingModel?->logo ?? '',
```
The read side treats the stored string as a storage-relative path and generates the URL at render time (not via `SectionFrontendMediaResolver` — directly via `asset()` or `Storage::url()`).

---

### Pattern C — Hybrid: ID at Input, Path at Storage

The media picker form component emits a `media.id`. The controller receives the ID, looks up `Media::find($id)`, extracts `$media->file_path`, and stores the **path string** — discarding the ID. The database column stores a path, not an ID.

**Where used:**

**`portfolios.default_image`** and **`portfolios.images`** — migration stores:
```php
$table->string('default_image');     // stores file_path, not media.id
$table->json('images')->nullable();   // stores array of file_path strings
```
Controller (`PortfolioController::resolveMediaIdsToPaths()`):
```php
// Form submits: default_image = "42" (media.id from picker)
// Controller converts before saving:
private function resolveMediaIdsToPaths(mixed $input): ?string
{
    if (is_numeric($input)) {
        $media = Media::find((int) $input);
        return $media?->file_path;     // stores "media/2025/06/img.jpg" — ID lost
    }
    // comma-separated IDs → JSON array of paths
    $paths = Media::whereIn('id', $ids)->pluck('file_path')->toArray();
    return json_encode($paths);        // ["media/2025/06/a.jpg", "media/2025/06/b.jpg"]
}
```
Validation:
```php
'default_image' => 'nullable|integer|exists:media,id',  // validates ID...
// ...but then converts to path before storing
```

**`general_settings` logos (via `AppearanceController::normalizeMediaPath()`):**
```php
protected function normalizeMediaPath($value): ?string
{
    if (ctype_digit($normalized)) {
        $media = Media::find((int) $normalized);  // looks up by ID...
        return ltrim($media->file_path, '/');      // ...but stores path
    }
    // also accepts raw URLs → extracts storage path from URL
}
```
The Appearance settings form uses the media picker (emits ID), but `normalizeMediaPath()` converts to path before saving. The stored result is still Pattern B (path string), even though the input was Pattern A (ID).

---

### Summary of Current State

| Module | DB Column(s) | DB Type | FK Constraint | Stored Value | Pattern |
|--------|-------------|---------|---------------|-------------|---------|
| `feedbacks` | `image_id` | `bigint` | ✅ `constrained('media')` | `42` (media.id) | **A** |
| `section_definitions` | `preview_media_id` | `bigint` | ✅ `constrained('media')` | `17` (media.id) | **A** |
| `section_translations` | `content` (JSON) | `json` | ❌ None (JSON) | `{"image": 42}` | **A*** |
| `portfolios` | `default_image` | `string` | ❌ None | `"media/2025/img.jpg"` | **C** |
| `portfolios` | `images` | `json` | ❌ None | `["media/2025/a.jpg"]` | **C** |
| `general_settings` | `logo`, `dark_logo`, etc. | `string` | ❌ None | `"media/2025/logo.png"` | **B** |

*Pattern A without DB FK — application-layer ID reference inside JSON.

---

## Problem

### 1. No FK Enforcement for Path-Based Columns

Columns storing file paths (`portfolios.default_image`, `portfolios.images`, `general_settings.logo`, etc.) have no referential constraint to the `media` table. Deleting a `media` record does not update or nullify these columns. The path remains in the database pointing to a file that may no longer exist.

**Consequence:** A portfolio item may display a broken image if the underlying media record was deleted. There is no cascading behavior. The application has no way to query "which modules reference media ID 42?" for the path-based columns.

### 2. Orphaned Media Records

When a portfolio is updated with a new image, the controller calls `resolveMediaIdsToPaths()`, stores the new path, and forgets the old path. The old `media` record is not deleted — it is orphaned. The same is true for general settings logos.

**Pattern A columns are immune:** `feedbacks.image_id` uses `nullOnDelete()`, so deleting a media record automatically nullifies the FK. No orphan data on the `feedbacks` side.

**Pattern B/C columns have no cleanup:** No code path in the portfolio or general settings update flow deletes the previously referenced media record when a new image is selected.

### 3. Lost ID at Write Time (Pattern C Specific)

`PortfolioController::resolveMediaIdsToPaths()` and `AppearanceController::normalizeMediaPath()` both accept a `media.id` from the form and immediately discard it after converting to a path. Once written:

- It is impossible to determine which `media` record the path originated from without a full-table `file_path` scan.
- If `media.file_path` is ever renamed or the disk changes, the stored paths in `portfolios` and `general_settings` become stale with no automated way to update them.
- Cleanup tools (media library "find unused") cannot detect portfolio or general-settings references because they would need to scan path strings against `media.file_path`, not join on `media.id`.

### 4. Dual Resolution Code Paths

Two separate resolution utilities exist because of the mixed storage formats:

**`SectionFrontendMediaResolver`** — resolves integer IDs:
```php
SectionFrontendMediaResolver::resolve(42)   // accepts: integer
// → Media::find(42) → Storage::url($media->file_path)
```

**`SectionMediaPreviewBuilder`** — handles both IDs and path strings:
```php
if (is_numeric($value)) { Media::find((int) $value)->url }  // ID branch
if (is_string($value))  { asset($value) }                   // path branch
```

**Portfolio `our_work_showcase.blade.php`** — implements its own resolution:
```php
$resolveImageUrl = static function ($value): ?string {
    if (Str::startsWith($value, ['http://', 'https://', ...]))
        return $value;
    return Str::startsWith($value, 'storage/')
        ? asset($value)
        : asset('storage/' . ltrim($value, '/'));
};
// $portfolio->default_image is a path string — no Media model involved
```

This means **three separate URL resolution implementations** exist for what is conceptually the same operation: "give me the public URL for this media reference." Each has subtly different behavior, introducing inconsistency risk.

### 5. Impossible Reverse Lookup

Given a media record, there is no single query to find all places it is referenced:

- `feedbacks.image_id` → `SELECT * FROM feedbacks WHERE image_id = ?` ✅ simple
- `section_definitions.preview_media_id` → `SELECT * FROM section_definitions WHERE preview_media_id = ?` ✅ simple
- `section_translations.content` → `SELECT * FROM section_translations WHERE JSON_CONTAINS(content, '42')` ⚠️ requires JSON search
- `portfolios.default_image` → `SELECT * FROM portfolios WHERE default_image = ?` but you need to know the path ❌ requires joining `media.file_path` first
- `general_settings.*` → seven separate string scans with path matching ❌

A media cleanup tool, or even a simple "is this media record in use?" check, requires bespoke code for each storage pattern.

---

## Decision

**Media records become the canonical representation of uploaded files across the platform. `media.id` is the authoritative reference format for all new code and all migrated existing columns.**

Specifically:

- All columns that reference an uploaded file must store `media.id` as an unsigned integer with a database-level FK constraint to `media`.
- No new column may store a raw `file_path` string as a reference to an uploaded asset.
- Controllers must not convert media IDs to paths before storage. The conversion from ID to URL happens only at render time, via `SectionFrontendMediaResolver` or equivalent.
- `SectionFrontendMediaResolver` is the canonical resolution utility for the frontend. `SectionMediaPreviewBuilder` is the canonical resolution utility for the admin editor preview. No module should implement its own URL resolution from path strings.

---

## Canonical Rules

| Use Case | Canonical Format | DB Column Type | FK Constraint |
|----------|-----------------|----------------|---------------|
| Testimonial avatar | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Section Definition preview | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Section content field (media type) | `media.id` as integer in JSON | `json` | None (JSON; app-layer validation) |
| Portfolio default image | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Portfolio gallery images | `media.id[]` as JSON array | `json` | None (JSON; app-layer validation) |
| General Settings logo variants | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| General Settings favicon | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Appearance footer logo override | `media.id` stored in JSON | `json` → `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Template thumbnail | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |
| Future image/file fields | `media.id` | `unsignedBigInteger` | `constrained('media')->nullOnDelete()` |

---

## Exceptions

The following are NOT subject to this ADR and may legitimately store non-ID values:

**External URLs.** Images served from external CDNs, partner sites, or third-party services that were never uploaded to the local media library. These should be stored as full URL strings in a dedicated column (e.g., `external_image_url`), clearly separate from any media reference column.

**Temporary upload tokens.** Intermediate upload state before a media record is created (e.g., a presigned S3 URL valid for 15 minutes during a multi-step form). These should never reach persistent storage.

**Payment method logos and social icons.** These are typically shipped as static assets in the `public/` directory, referenced by relative paths like `/assets/icons/visa.svg`. They are not user-uploaded and should not be in the media library.

**Flags and CDN images** (e.g., `https://flagcdn.com/w40/sa.png` stored in `languages.flag`). These are external URLs, not uploaded assets, and storing `media.id` would be inappropriate.

---

## Migration Strategy

The migration targets three specific legacy areas: `portfolios`, `general_settings`, and the Appearance/Footer logo overrides stored in `header_variant_settings` and `footer_variant_settings` JSON columns.

> **Critical prerequisite:** All migration steps require that the `media` table contains records corresponding to the path strings currently stored. Before Phase 1, run a reconciliation report: `SELECT COUNT(*) FROM portfolios WHERE default_image IS NOT NULL AND default_image NOT IN (SELECT file_path FROM media)` to determine how many paths have no matching media record.

### Phase 1 — Add New FK Columns (No Breaking Changes)

Add `*_media_id` columns alongside the existing path columns. No application code changes yet. Old and new columns coexist.

```php
// Migration: add_media_ids_to_portfolios_table
Schema::table('portfolios', function (Blueprint $table) {
    $table->foreignId('default_image_media_id')
        ->nullable()
        ->after('default_image')
        ->constrained('media')
        ->nullOnDelete();
    // Note: images (gallery) JSON array remains JSON — media IDs replace path strings
    $table->json('images_media_ids')->nullable()->after('images');
});

// Migration: add_media_ids_to_general_settings_table
Schema::table('general_settings', function (Blueprint $table) {
    foreach (['logo', 'dark_logo', 'sticky_logo', 'dark_sticky_logo',
              'admin_logo', 'admin_dark_logo', 'favicon'] as $field) {
        $table->foreignId("{$field}_media_id")
            ->nullable()
            ->after($field)
            ->constrained('media')
            ->nullOnDelete();
    }
});
```

### Phase 2 — Dual Write

Update all write paths (controllers) to write both the old path column and the new ID column simultaneously. The old path column remains the read source during this phase.

```php
// PortfolioController::store() and update() — after resolveMediaIdsToPaths():
$portfolio->default_image = $this->resolveMediaIdsToPaths($validated['default_image']);  // old
$portfolio->default_image_media_id = $validated['default_image'] ?? null;               // new
```

**For `AppearanceController`:** The `normalizeMediaPath()` method currently converts IDs to paths. Update the settings save logic to also save the ID:

```php
// AppearanceController::updateLogos() — add parallel save:
$setting->logo = $this->normalizeMediaPath($validated['logo_url']);      // old path
$setting->logo_media_id = $this->extractMediaId($validated['logo_url']); // new ID
```

Deploy and let the system run for at least 2 weeks (covering at least one admin settings update cycle) so both columns are populated by real traffic.

### Phase 3 — Backfill Existing Rows

For rows that existed before Phase 2, backfill `*_media_id` by reverse-looking up `media.file_path`:

```php
// Artisan command or one-time migration:
Portfolio::whereNull('default_image_media_id')
    ->whereNotNull('default_image')
    ->chunkById(200, function ($portfolios) {
        foreach ($portfolios as $portfolio) {
            $media = Media::where('file_path', $portfolio->default_image)->first();
            if ($media) {
                $portfolio->default_image_media_id = $media->id;
                $portfolio->saveQuietly();
            }
            // If no matching media record found: log for manual review
        }
    });
```

After backfill, run reconciliation: count rows where the path exists but no media record was found. These rows require manual remediation (re-upload and re-link, or delete if the files are gone).

### Phase 4 — Switch Reads to IDs

Update all read paths to use the new `*_media_id` columns. Update resolvers to use `SectionFrontendMediaResolver::resolve()` instead of `asset()` / `Storage::url()` with path strings.

```php
// Portfolio Blade view (our_work_showcase.blade.php) — replace custom resolver:
// BEFORE:
$imageUrl = $resolveImageUrl($portfolio->default_image);   // path-based

// AFTER:
$imageUrl = SectionFrontendMediaResolver::resolve($portfolio->default_image_media_id);
```

Update `PortfolioController` validation to stop converting IDs to paths:
```php
// BEFORE:
$portfolioData['default_image'] = $this->resolveMediaIdsToPaths($validated['default_image']);

// AFTER:
$portfolioData['default_image_media_id'] = $validated['default_image'] ?? null;
// drop 'default_image' from save entirely (old path column deprecated)
```

Delete `PortfolioController::resolveMediaIdsToPaths()` — it should no longer be needed.

### Phase 5 — Drop Legacy Path Columns

After Phase 4 is stable across at least one full release cycle:

```php
// Migration: drop_legacy_path_columns_from_portfolios
Schema::table('portfolios', function (Blueprint $table) {
    $table->dropColumn('default_image');
    $table->dropColumn('images');
});

// Migration: drop_legacy_path_columns_from_general_settings
Schema::table('general_settings', function (Blueprint $table) {
    $table->dropColumn(['logo', 'dark_logo', 'sticky_logo',
                        'dark_sticky_logo', 'admin_logo', 'admin_dark_logo', 'favicon']);
});
```

Rename the `*_media_id` columns to the final names (e.g., `default_image_media_id` → `default_image_id`, or simply `default_image` if the old column is dropped — choose one consistent convention).

---

## Consequences

### Positive

**Referential integrity enforced at DB level.** `constrained('media')->nullOnDelete()` ensures that deleting a media record automatically nullifies all references. No orphaned path strings remain after a media deletion.

**Single resolution utility.** `SectionFrontendMediaResolver::resolve($id)` handles all frontend media URL generation. No module needs its own `asset()` or `Storage::url()` call with a stored path. `SectionMediaPreviewBuilder` handles the admin editor case.

**Reverse lookup becomes trivial.** `SELECT * FROM portfolios WHERE default_image_id = 42` finds all users of a media record instantly. A "media in use" check becomes a set of simple FK joins, not path-string scans.

**Disk and path changes are transparent.** If a file is moved on disk, updating `media.file_path` in one row propagates everywhere automatically. Currently, moving a file would require updating every string-storing column independently.

**Orphaned media detection is reliable.** A media cleanup job can `LEFT JOIN` all FK columns and find records with no references. Currently, this would require scanning path strings across multiple tables.

**Media metadata is always accessible.** Via the `media` relationship, any code can access the file's `alt`, `title`, `caption`, `width`, `height` — not just its URL. Path-based references provide none of this.

### Negative

**Migration risk for existing data.** The backfill (Phase 3) relies on matching `media.file_path` to the stored path strings. If any paths were stored with different formatting (trailing slash differences, `storage/` prefix present or absent), the match will fail and the row must be remediated manually.

**General settings and Appearance settings contain complex JSON columns.** `header_variant_settings` and `footer_variant_settings` store logo overrides deep inside JSON blobs. Migrating these is more complex than migrating flat string columns — the JSON structure must be traversed and the IDs extracted or inserted.

**`PortfolioController::resolveMediaIdsToPaths()` must be deleted, not just deprecated.** The method embeds conversion logic that would silently undo Phase 4 if left in place. Its removal must be coordinated with Phase 4 completion.

**AppearanceController needs significant refactoring.** `normalizeMediaPath()` currently handles three input formats (numeric ID, full URL, relative path). After migration, it should only accept numeric IDs. Input validation must be hardened accordingly.

**Any external integrations that read path strings from the DB** (direct DB access, data exports, CLI scripts) will see different column names and integer values after Phase 5.

---

## Alternatives Considered

### Path Strings Everywhere (Normalize to Pattern B)

Standardize on storing file paths in all columns. Remove `feedbacks.image_id` and `section_definitions.preview_media_id` FKs, store paths instead.

**Rejected.** Path strings provide no referential integrity. The `media` table would become a shadow catalog with no enforced connections to any module. Media deletion, cleanup, and reverse lookup all become impossible to implement correctly. The existing Pattern A implementations in `feedbacks` and `section_definitions` are architecturally correct — the rest of the codebase should move toward them, not away.

### Hybrid Forever (Accept Both Patterns Permanently)

Formalize both patterns: some columns store IDs, some store paths. Document which modules use which convention and never migrate.

**Rejected.** The current state IS the hybrid-forever state, and it has produced three different URL resolution code paths, broken orphan detection, and impossible reverse lookup. Formalizing the inconsistency makes it worse by removing the motivation to fix it. The `SectionMediaPreviewBuilder` already demonstrates the tax: it contains explicit branches for `is_numeric($value)` vs `is_string($value)` because it cannot assume which format it will receive. Every utility that touches media must carry this dual-path complexity forever under the hybrid approach.

### Polymorphic Media Relationships

Use a `media_attachments` pivot table (`mediable_id`, `mediable_type`, `media_id`, `collection_name`) to associate media with any model without touching the model's own columns. This is the approach used by Spatie Media Library.

**Not adopted — scope too large.** This would require replacing the existing `image_id`, `preview_media_id`, and all proposed new FK columns with a pivot table lookup. It is architecturally superior to FK columns for polymorphic use cases, but it represents a significantly larger refactor than the current migration. It should be considered as a future improvement after ID-based storage is standardized.

---

## Impacted Systems

### Media Library (`docs/27-media-library.md`)

`SectionFrontendMediaResolver` is the correct post-migration resolution utility. Its `resolve()` and `resolveMany()` methods are designed for scalar and collection ID resolution respectively. No changes to the resolver itself are required.

`SectionMediaPreviewBuilder::build()` currently has both an ID branch and a string branch. After migration, the string branch should be removed or converted to a no-op (accepting only numeric IDs). This simplifies the admin editor preview pipeline.

### Portfolio

`portfolios.default_image` and `portfolios.images` — both must migrate from path strings to media IDs (Phases 1–5). `PortfolioController::resolveMediaIdsToPaths()` must be deleted after Phase 4.

The `our_work_showcase.blade.php` Blade component has an inline `$resolveImageUrl` closure that handles path strings. After Phase 4, replace it with `SectionFrontendMediaResolver::resolve($portfolio->default_image_media_id)`.

### Testimonials

`feedbacks.image_id` is already Pattern A with a DB FK. No migration required. The `TestimonialsController` correctly validates `featured_image_id` against `media,id`. The `Testimonial::image()` relationship and `getImageUrlAttribute()` accessor are correct and can serve as the reference implementation for other modules.

### General Settings

Seven string columns (`logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon`) must migrate to FK integer columns. The `HomeController::updateGeneralSettings()` path currently validates these as `nullable|string|max:255`. Post-migration, validation must become `nullable|integer|exists:media,id`.

The `GeneralSetting` model needs seven new relationships:
```php
public function logoMedia(): BelongsTo { return $this->belongsTo(Media::class, 'logo_media_id'); }
// ...repeat for all 7 fields
```

### Appearance (Header/Footer Variant Settings)

`header_variant_settings` and `footer_variant_settings` are JSON columns that contain `logo_override` keys storing path strings (via `normalizeMediaPath()`). These are the most complex to migrate because the path values are nested inside JSON.

After migration, `logo_override` inside these JSON columns should store a media ID (integer) or a dedicated `logo_override_media_id` column should be added alongside the JSON.

`AppearanceController::normalizeMediaPath()` and `normalizeMediaPathList()` must be deleted or refactored to no longer convert IDs to paths.

### Section Definitions

`section_definitions.preview_media_id` is already Pattern A with a DB FK. No migration required.

Section content JSON (inside `section_translations.content`) stores integer IDs for media-type fields. This is already correct application-layer behavior. No migration required here; `SectionFrontendMediaResolver` already handles this correctly.

### Future Modules

All new models with image/file references must:
1. Use `unsignedBigInteger` or `foreignId` with `constrained('media')->nullOnDelete()`
2. Validate with `nullable|integer|exists:media,id`
3. Resolve via `SectionFrontendMediaResolver::resolve()` (frontend) or `$model->image?->url` (admin)
4. Never call `resolveMediaIdsToPaths()` or `normalizeMediaPath()` — these are migration-era utilities to be deleted

---

## Technical Debt Closed

This ADR directly addresses Technical Debt documented in `docs/27-media-library.md`:

**TD-3 (Portfolio path storage):** `portfolios.default_image` and `portfolios.images` store file paths instead of `media.id`. No FK constraint. Orphaned file paths accumulate as portfolios are updated. Closed by: completing Phases 1–5 for the `portfolios` table.

**TD-2 (Missing FK relations for General Settings):** `general_settings.logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon` store path strings with no FK to `media`. Closed by: completing Phases 1–5 for the `general_settings` table.

**TD-4 (Dual resolution code paths):** `SectionMediaPreviewBuilder` contains explicit `is_numeric` vs `is_string` branching because it cannot assume which format it will receive. `our_work_showcase.blade.php` implements its own URL resolution for path strings. Closed by: eliminating path-string storage, leaving only ID-based storage which has a single resolution path via `SectionFrontendMediaResolver`.

**TD-5 (Orphaned media from portfolio updates):** `PortfolioController` updates replace path strings without deleting the previously stored media records. No cleanup mechanism exists. Closed by: switching to FK columns — `nullOnDelete()` provides cascading behavior, and a reverse lookup via `SELECT * FROM portfolios WHERE default_image_id = ?` enables a "find unused media" tool.

---

## References

- `app/Models/Media.php` — Media model, `getUrlAttribute()` via `Storage::disk($disk)->url($file_path)`
- `app/Support/Sections/SectionFrontendMediaResolver.php` — canonical ID-to-URL resolver
- `app/Support/Sections/SectionMediaPreviewBuilder.php` — dual-branch resolver (to be simplified post-migration)
- `app/Models/Portfolio.php` — `default_image` and `images` as path-storing fields
- `app/Models/Testimonial.php` — `image_id` as FK reference (reference implementation)
- `app/Models/GeneralSetting.php` — seven path-storing logo/favicon columns
- `app/Http/Controllers/Admin/PortfolioController.php` — `resolveMediaIdsToPaths()` (to be deleted)
- `app/Http/Controllers/Admin/AppearanceController.php` — `normalizeMediaPath()`, `normalizeMediaPathList()` (to be deleted/refactored)
- `database/migrations/2025_06_11_203841_create_media_table.php` — media table schema
- `database/migrations/2025_06_25_111513_create_feedbacks_table.php` — Pattern A reference
- `database/migrations/2026_04_18_000002_add_preview_media_id_to_section_definitions_table.php` — Pattern A reference
- `database/migrations/2025_06_28_111512_create_portfolios_table.php` — Pattern C source
- `database/migrations/2025_06_17_113900_create_general_settings_table.php` — Pattern B source
- `docs/27-media-library.md` — full media library documentation, TD-2 through TD-5
- `resources/views/components/template/sections/our_work_showcase.blade.php` — inline path resolver (to be replaced)
