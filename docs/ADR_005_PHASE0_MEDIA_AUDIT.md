# ADR-005 Phase 0 — Media Storage Audit

> **Date:** 2026-06-16  
> **Status:** Audit Complete — Read-Only, No Code Changed  
> **ADR:** [005-media-storage-format-unification.md](adr/005-media-storage-format-unification.md)  
> **Auditor:** Code-first search across `app/`, `database/migrations/`, `resources/views/`

---

## Executive Summary

| Pattern | Description | Locations (columns) |
|---------|-------------|---------------------|
| **Pattern A** — `media_id` FK | Integer FK column → `media.id` | **3** (testimonials, section_definitions, section content JSON) |
| **Pattern B** — Path string | Raw path stored as `string` or `json` | **14** columns across 5 tables + 2 JSON sub-keys |
| **Pattern C** — ID→Path at write | Media ID accepted, path stored | **5** controller sites covering all Pattern B writes |

**Critical finding discovered by audit:** `templates.image` accepts direct file uploads that create files WITHOUT a corresponding `media` record — these rows cannot be migrated to Pattern A without a data backfill step.

**Ghost finding:** `Section::image()` BelongsTo relation is declared in the model but `image_id` column was never added to the `sections` table by any migration. Dead code.

---

## Pattern A Inventory

### Real FK Columns

| Table | Column | FK Target | Model | Relation |
|-------|--------|-----------|-------|----------|
| `testimonials` | `image_id` | `media.id` nullOnDelete | `Testimonial` | `image()` BelongsTo |
| `section_definitions` | `preview_media_id` | `media.id` nullOnDelete | `SectionDefinition` | `previewMedia()` BelongsTo |

### Pattern A via JSON (section content fields)

Dynamic section fields of type `media` are stored as **integer IDs** inside `sections.content` JSON. At render time, `SectionFrontendMediaResolver::resolve()` and `::resolveMany()` convert these IDs to public URLs via `Media::find()`. Used in:

| Blade Section | Media field(s) |
|---------------|---------------|
| `faq/faq_section.blade.php` | `image` |
| `hero/hero_campaign.blade.php` | `image`, repeater `icon_media` |
| `hero/hero_featured.blade.php` | repeater `icon_media` |
| `promo/promo_image_features.blade.php` | `image`, repeater `icon_media` |
| `promo/website_protection.blade.php` | repeater `icon_media` (resolveMany) |
| `promo/wordpress_ai_promo.blade.php` | `background_image` |
| `services/process_steps.blade.php` | repeater `icon_media` |
| `services/service_gallery_showcase.blade.php` | repeater `image` |
| `services/service_masonry_gallery.blade.php` | repeater `image`, repeater `icon_media` |
| `services/service_showcase.blade.php` | `image`, repeater `icon_media` |
| `services/tech_stack_logos.blade.php` | repeater `image` |

> ✅ Section JSON media fields are already fully Pattern A. No migration needed here.

### Ghost Pattern A (declared but column does not exist)

`Section::image()` BelongsTo is declared in `app/Models/Section.php` (line 179) with a comment:
```php
// NOTE: This assumes there is an `image_id` column on the `sections` table
return $this->belongsTo(Media::class, 'image_id');
```
No migration has ever added `image_id` to the `sections` table. The relation silently returns `null` for every section. This is dead code.

**Required fix before any related work:** Remove or document this ghost relation.

---

## Pattern B Inventory

### Table: `general_settings` — 7 direct columns

| Column | Type | How stored | Blade read pattern |
|--------|------|------------|-------------------|
| `logo` | `string nullable` | `ltrim(media.file_path, '/')` | `asset('storage/' . $settings->logo)` |
| `dark_logo` | `string nullable` | same | same |
| `sticky_logo` | `string nullable` | same | same |
| `dark_sticky_logo` | `string nullable` | same | same |
| `admin_logo` | `string nullable` | same | same |
| `admin_dark_logo` | `string nullable` | same | same |
| `favicon` | `string nullable` | same | `asset('storage/' . $settings->favicon)` |

Written by `HomeController::normalizeMediaPath()` (7 calls).

### Table: `general_settings` — JSON sub-keys in `header_variant_settings`

`header_variant_settings` is a `json` column storing per-variant settings. Each variant object contains:

| JSON key | Type | How stored | Writer |
|----------|------|------------|--------|
| `logo_override` | path string (nullable) | `ltrim(media.file_path, '/')` after stripping `storage/` | `AppearanceController::normalizeMediaPath()` |
| `payment_logos` | JSON array of paths | array of `ltrim(file_path, '/')` | `AppearanceController::normalizeMediaPathList()` |

⚠️ **These cannot be given a true FK constraint** — they live inside a JSON column. Pattern A migration for these would require a separate `general_setting_media` junction table or storing IDs inside the JSON instead of paths.

### Table: `portfolios` — 2 columns

| Column | Type | How stored | Read pattern |
|--------|------|------------|-------------|
| `default_image` | `string nullable` | `media.file_path` (no ltrim!) | `asset('storage/' . ltrim($portfolio->default_image, '/'))` via `SectionQueryResolver::portfolioImageUrl()` |
| `images` | `json nullable` | `json_encode(array of file_paths)` | iterated in Blade |

Written by `PortfolioController::resolveMediaIdsToPaths()`.

⚠️ **`portfolios.images` is a JSON array** — migrating to Pattern A requires a `portfolio_media` pivot table.

### Table: `templates` — 1 column

| Column | Type | How stored | Source |
|--------|------|------------|--------|
| `image` | `string` | raw path | direct file upload (no media record) **OR** `ltrim(media.file_path, '/')` |

**Two write paths exist in `TemplateController`:**
1. `$request->file('image')->store('templates', 'public')` — writes file, stores path, **creates NO media record**
2. `Media::whereKey($id)->value('file_path')` — resolves media picker selection to path

⚠️ **CRITICAL**: Rows written via path #1 have an orphaned path string with no `media.id` to reference. Migration to Pattern A requires a **data backfill** to create `media` records for these rows first.

### Table: `clients` — 1 column

| Column | Type | How stored | Read pattern |
|--------|------|------------|-------------|
| `avatar` | `string nullable` | path from media picker `data-store-value="path"` | `asset('storage/' . $client->avatar)` |

Written by `ClientController` — stores `$request->input('avatar')` directly (no explicit ID→path conversion, the media picker already stores the path).

### Table: `services` — 1 column

| Column | Type | How stored | Read pattern |
|--------|------|------------|-------------|
| `icon` | `string nullable` | file path (image/SVG) | `asset('storage/' . $service->icon)` |

Confirmed from `resources/views/components/template/sections/services.blade.php:26`:
```blade
<img src="{{ asset('storage/' . $service->icon) }}" alt="..." />
```
Note: section definition fields also have `icon` text fields (CSS class string like `ti ti-server`). The `services` DB table `icon` is a **file path**, not a CSS class.

---

## Pattern C Inventory

Every Pattern B write goes through a controller-level ID→path conversion. There are **5 distinct converter sites**:

### C-1: `HomeController::normalizeMediaPath()` (line 874)

```php
// If numeric → find Media record → return ltrim(file_path, '/')
if (ctype_digit($normalized)) {
    $media = Media::find((int) $normalized);
    if ($media && !empty($media->file_path)) {
        return ltrim((string) $media->file_path, '/');
    }
}
// Falls through to URL handling if not numeric
```

**Writes to:** `general_settings.logo`, `.dark_logo`, `.sticky_logo`, `.dark_sticky_logo`, `.admin_logo`, `.admin_dark_logo`, `.favicon` (7 columns, 7 calls in `updateSettings()`).

**Also handles full URLs** via `extractStoragePathFromUrl()` — extracts the path component after `/storage/`.

### C-2: `AppearanceController::normalizeMediaPath()` (line 676)

Same digit→find logic as C-1, but **additionally strips `storage/` prefix** if value starts with it:
```php
if (str_starts_with($normalized, 'storage/')) {
    $normalized = substr($normalized, strlen('storage/'));
}
```

**Writes to:** `header_variant_settings.{variant}.logo_override`

⚠️ **Inconsistency:** C-1 and C-2 are duplicated implementations with slightly different behavior. C-2 strips `storage/` prefix; C-1 does not. If a URL like `https://host/storage/media/x.jpg` passes through C-1 and fails `extractStoragePathFromUrl()`, it may store the full URL as-is.

### C-3: `AppearanceController::normalizeMediaPathList()` (line 706)

Parses a comma-separated string of values, calls C-2 on each, returns array of paths.

**Writes to:** `header_variant_settings.{variant}.payment_logos` (JSON array)

### C-4: `PortfolioController::resolveMediaIdsToPaths()` (line 120)

```php
// Single ID:
$media = Media::find((int) $input);
return $media?->file_path;  // ← no ltrim! stores with possible leading /

// Multiple IDs (comma-separated):
$paths = Media::whereIn('id', $ids)->pluck('file_path')->filter()->values()->toArray();
return json_encode($paths);
```

**Writes to:** `portfolios.default_image`, `portfolios.images`

⚠️ **Inconsistency:** C-4 does NOT strip leading `/` from `file_path` (unlike C-1/C-2 which do `ltrim`). `SectionQueryResolver::portfolioImageUrl()` compensates for this by checking for leading `/` at read time.

### C-5: `TemplateController` (inline, lines 151-157 and 260-265)

```php
// Path 1: direct upload — NO media record created
$imagePath = $request->file('image')->store('templates', 'public');

// Path 2: media picker — resolves existing media record
$rawPath = Media::query()->whereKey($id)->value('file_path');
$imagePath = ltrim((string) $rawPath, '/');
```

**Writes to:** `templates.image`

⚠️ **Path 1 is Pattern B-with-no-media-backing** — cannot be reversed to media_id.

---

## Migration Complexity Analysis

| Table / Column | Pattern | Complexity | Risk | Notes |
|---|---|---|---|---|
| `testimonials.image_id` | **A** | ✅ Done | None | Already clean |
| `section_definitions.preview_media_id` | **A** | ✅ Done | None | Already clean |
| Section content JSON media fields | **A** | ✅ Done | None | Via SectionFrontendMediaResolver |
| `clients.avatar` | B→A | Low | Low | 1 column; media picker always stores path; media records should exist |
| `general_settings.logo` ×7 | B→A | Medium | Low-Medium | 7 columns; all through normalizeMediaPath; media records exist; match by file_path |
| `portfolios.default_image` | B→A | Medium | Medium | 1 path column; file_path may or may not have leading `/` (C-4 inconsistency) |
| `services.icon` | B→A | Medium | Medium | File path; need to verify all existing rows have media records |
| `templates.image` | B→A | **High** | **Critical** | Mix of upload-only rows (no media record) and media-picker rows; needs pre-migration backfill |
| `portfolios.images` | B→A | **Very High** | High | JSON array → needs `portfolio_media` pivot table; architectural decision |
| `general_settings.header_variant_settings.*.logo_override` | B→A | **High** | High | Nested inside JSON; true FK impossible; would need junction table or keep as path |
| `general_settings.header_variant_settings.*.payment_logos` | B→A | **Very High** | High | JSON array inside JSON column; architectural decision required |

---

## Path Matching Risk Analysis

During data migration (converting existing stored paths → `media_id`), matching works by:
```sql
SELECT id FROM media WHERE file_path = <stored_path_after_normalization>
```

### Risk scenarios where matching may fail:

**1. Leading `/` inconsistency (Medium risk)**
- C-4 (`PortfolioController`) stores `file_path` as-is — may have leading `/`
- C-1/C-2/C-5 strip it with `ltrim($path, '/')`
- `media.file_path` itself stores without leading `/` (set by `MediaController::saveMediaFile()` which uses `$path` from `Storage::putFile()` — no leading `/`)
- **Match query must normalize** with `ltrim($stored, '/')` before comparing

**2. `storage/` prefix inconsistency (Low-Medium risk)**
- C-2 strips `storage/` prefix; C-1 does not BUT C-1 also uses `extractStoragePathFromUrl()` which strips it for full URLs
- If any admin used C-1 path and pasted a URL like `https://site.com/storage/media/x.jpg`, and `extractStoragePathFromUrl()` returned `null` for it (possible if the URL structure doesn't match), the full URL is stored → **unmatchable**

**3. Full URL stored (Low risk, but unrecoverable)**
- `normalizeMediaPath()` in both controllers: if value is full URL AND `extractStoragePathFromUrl()` returns null → returns the full URL
- A stored value like `https://cdn.example.com/logo.png` has no match in `media.file_path`
- These rows need manual intervention

**4. `templates.image` direct-upload rows (Critical — no media record exists)**
- File at `storage/app/public/templates/xxx.jpg` exists on disk
- `media` table has NO row for it
- `SELECT id FROM media WHERE file_path = 'templates/xxx.jpg'` → **0 rows**
- Cannot be migrated to Pattern A without creating the media record first
- Must be a pre-migration backfill: `Media::create(['file_path' => $path, 'disk' => 'public', ...])`

**5. `services.icon` unknown backfill status**
- Origin of existing data unclear (no ServiceController found)
- Some icons may have been set before media picker existed
- Need to verify existing rows have corresponding `media` records

**6. `portfolios.images` JSON structure**
- Stored as `["media/2025/06/a.jpg", "media/2025/06/b.jpg"]`
- Each path needs individual matching
- After match, must design a `portfolio_media` pivot table → requires new migration + model changes

---

## Required Pre-Migration Fixes

### Fix 1 (BLOCKER for templates Phase) — Backfill `media` records for direct-upload template images

All rows where `templates.image` stores a path starting with `templates/` and no `media` record exists must have a `media` record created:

```php
// Pseudocode — must be run as a seeder or migration before Phase templates
Template::whereNotNull('image')->each(function ($template) {
    $exists = Media::where('file_path', ltrim($template->image, '/'))->exists();
    if (! $exists && Storage::disk('public')->exists($template->image)) {
        Media::create([
            'file_path' => ltrim($template->image, '/'),
            'disk' => 'public',
            'mime_type' => 'image/jpeg', // approximate
            'original_name' => basename($template->image),
        ]);
    }
});
```

### Fix 2 (CLEANUP) — Remove ghost `Section::image()` relation

`app/Models/Section.php` declares `belongsTo(Media::class, 'image_id')` but the column doesn't exist. Remove this to avoid confusion.

### Fix 3 (CLEANUP) — Deduplicate `normalizeMediaPath()`

`HomeController` and `AppearanceController` both have private `normalizeMediaPath()` implementations with different behavior. Extract to a shared trait or service before Phase 1 to prevent divergence after migration.

### Fix 4 (DECISION) — `portfolios.images` and `payment_logos` architecture

Before attempting these two, a formal decision is needed:
- Option A: Add `portfolio_media` pivot table (fully Pattern A)
- Option B: Convert JSON arrays from path-arrays to id-arrays (half-way — still JSON, but IDs)
- Option C: Defer indefinitely (these are internal CMS fields, not public APIs)

---

## Recommended Phase Order (Revised)

Based on code-first analysis, the original phase order is revised:

| Phase | Scope | Columns | Prerequisite | Effort |
|-------|-------|---------|--------------|--------|
| **Phase 0** | Audit | — | — | ✅ Done |
| **Phase 1** | `clients.avatar` | 1 | None | **XS** — 1 column, 1 model, 1 controller, 1 Blade update |
| **Phase 2** | `general_settings` logo ×7 | 7 | Fix 3 (deduplicate normalizeMediaPath) | **S** — 7 columns, same table, same writer |
| **Phase 3** | `portfolios.default_image` | 1 | None | **S** — 1 path column, ltrim normalization needed |
| **Phase 4** | `services.icon` | 1 | Verify existing rows have media records | **S** — if clean, simple; if not, needs backfill |
| **Phase 5** | `templates.image` | 1 | **Fix 1 (media backfill) REQUIRED** | **M** — two write paths, backfill, model + controller changes |
| **Phase 6** | `portfolios.images` | 1 (JSON array) | **Fix 4 (architecture decision)** | **L** — pivot table, model changes, JS changes in admin |
| **Phase 7** | `header_variant_settings` JSON keys | 2 sub-keys | **Fix 4 (architecture decision)** | **L** — cannot use FK; must decide JSON-IDs or junction |

**Estimated total effort (excluding Phase 0):** 3–5 hours for Phases 1–5 (simple columns), + 6–10 hours for Phases 6–7 (JSON/pivot).

---

## ADR-005 Phase Order vs ADR-003 Phase Order

**Question:** Should ADR-005 still proceed before ADR-003?

**Analysis from code:**
- ADR-005 touches: `general_settings`, `portfolios`, `templates`, `clients`, `services` — **zero overlap with money columns**
- ADR-003 touches: `subscriptions.price`, `plans.monthly_price`/`annual_price`, `coupons.discount_amount`, `domain_tld_prices.*_price` — **zero overlap with media columns**
- Both ADRs can be executed **in parallel or in either order** — no blocking dependency between them

**Revised recommendation:** ADR-005 Phases 1–4 (simple columns) can begin independently. ADR-003 can begin at any point. No ordering constraint exists between the two ADRs at the code level.

**However:** ADR-005 Phase 5 (templates.image) requires a pre-migration backfill of media records. That backfill should NOT be rushed. ADR-003 can be started while ADR-005 Phase 5 is in preparation.

---

## Final Verdict

```
READY FOR PHASED MIGRATION
(with one pre-migration blocker for Phase 5: templates.image)
```

**Phases 1–4** (`clients.avatar`, `general_settings` ×7, `portfolios.default_image`, `services.icon`) are ready to start immediately after Fix 3 (deduplicate `normalizeMediaPath`).

**Phase 5** (`templates.image`) requires Fix 1 (media backfill for direct-upload rows) before migration can proceed safely.

**Phases 6–7** (`portfolios.images`, `header_variant_settings` JSON keys) require an architectural decision (Fix 4) before any implementation begins.

---

## Appendix: File Reference Map

| File | Role in ADR-005 |
|------|----------------|
| `app/Models/Media.php` | Central entity — `file_path`, `disk`, `url` accessor |
| `app/Models/GeneralSetting.php` | 7 Pattern B logo columns |
| `app/Models/Portfolio.php` | `default_image` (string) + `images` (JSON array) |
| `app/Models/Template.php` | `image` (string) |
| `app/Models/Client.php` | `avatar` (string) |
| `app/Models/Service.php` | `icon` (string, file path) |
| `app/Models/Section.php` | Ghost `image()` relation — dead code |
| `app/Http/Controllers/Admin/HomeController.php` | C-1: `normalizeMediaPath()` for GeneralSetting logos |
| `app/Http/Controllers/Admin/AppearanceController.php` | C-2/C-3: `normalizeMediaPath()` / `normalizeMediaPathList()` for header_variant_settings |
| `app/Http/Controllers/Admin/PortfolioController.php` | C-4: `resolveMediaIdsToPaths()` |
| `app/Http/Controllers/Admin/TemplateController.php` | C-5: inline path resolution + direct upload |
| `app/Support/Sections/SectionFrontendMediaResolver.php` | Pattern A resolver for section JSON media fields |
| `app/Support/Sections/SectionMediaPreviewBuilder.php` | Admin preview builder for media fields |
| `app/Support/Sections/SectionQueryResolver.php` | `portfolioImageUrl()` — reads Pattern B paths for frontend |
