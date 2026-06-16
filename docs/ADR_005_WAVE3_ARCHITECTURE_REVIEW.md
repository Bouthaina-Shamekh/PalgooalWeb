# ADR-005 Wave 3 — Architecture Review

**Date:** 2026-06-16  
**Status:** REVIEW — Pending Decision  
**Author:** Architecture Review (Claude)  
**Scope:** portfolios.images · header/footer logo_override · footer payment_logos

---

## Executive Summary

> **OPTION A RECOMMENDED** — JSON of Media IDs (with write-time path resolution for high-traffic JSON columns)

All three Wave 3 targets share a common trait: they are **collections of media items stored in JSON columns**, with no active requirement for per-item metadata (sort order, captions, alt text). A pivot-table approach would introduce significant complexity — new tables, new relations, new admin UI — for zero functional gain over what the project currently needs. Option A delivers full alignment with ADR-005 Pattern A at a fraction of the implementation cost.

The one nuance: `logo_override` and `payment_logos` are read on **every page request** (header/footer). For these, the ID must be resolved to a path at **write time** and stored alongside — not at render time. `portfolios.images` has no such constraint.

---

## Current State Analysis

### 1. `portfolios.images`

| Aspect | Current State |
|--------|--------------|
| Column | `portfolios.images` — TEXT, cast to `array` |
| Storage format | JSON array of **path strings**: `["media/2024/01/img1.jpg", "media/2024/02/img2.jpg"]` |
| Write path | Admin form → media picker sends comma-separated IDs → `resolveMediaIdsToPaths()` → queries media table → `json_encode(paths)` → saved |
| Read path | `$portfolio->images` → PHP array of paths → `asset('storage/' . $img)` in front-end |
| Sort order | None — items render in the order stored |
| Captions | None |
| Alt text | Hardcoded: `"صورة للمشروع رقم {{ $i + 1 }}"` |
| Admin UI | `<x-dashboard.media-picker multiple="true">` — no reorder, no caption input |
| Delete | No per-image delete in admin form |
| Orphan risk | **Yes** — if media row is deleted, the path string stays in JSON and becomes a 404 |
| Pattern | **C** (path string stored at write time, media ID used transiently) |

**Key observation:** The admin form already sends media IDs. The controller converts them to paths before saving. The path is the stored value. To reach Pattern A, the controller simply stops the conversion and stores the IDs directly.

---

### 2. `header_variant_settings.*.logo_override`

| Aspect | Current State |
|--------|--------------|
| Location | `general_settings.header_variant_settings` (JSON column) — nested under variant key, e.g., `header_variant_settings['purple_topbar']['logo_override']` |
| Storage format | Single **path string**: `"media/2024/05/logo.png"` or `null` |
| Write path | Admin form → `pv_logo_override` field (media ID or URL) → `normalizeMediaPath()` → resolves media ID to path → stored in JSON |
| Read path | `$variantSettings['logo_override']` → `asset('storage/' . $logoPath)` in header Blade |
| Cardinality | **One** image per header variant |
| Orphan risk | **Yes** |
| Pattern | **C** |
| Traffic | **High** — rendered on every single front-end page request |

---

### 3. `footer_variant_settings.*.logo_override`

Same pattern as header logo_override, located under `footer_variant_settings['palgoals_marketing']['logo_override']`.

---

### 4. `footer_variant_settings.*.payment_logos`

| Aspect | Current State |
|--------|--------------|
| Location | `general_settings.footer_variant_settings['palgoals_marketing']['payment_logos']` |
| Storage format | JSON **array of path strings**: `["media/2024/05/visa.png", "media/2024/05/mc.png"]` |
| Write path | Admin form → `fm_payment_logos` (comma-separated IDs/paths) → `normalizeMediaPathList()` → resolves each item → stored as `[path, path, ...]` |
| Read path | `$variantSettings['payment_logos']` → `foreach` → `asset('storage/' . $path)` in footer Blade |
| Cardinality | **Many** — 0–N logos |
| Sort order | Preserved by array order |
| Captions | None |
| Orphan risk | **Yes** |
| Pattern | **C** |
| Traffic | **High** — rendered on every single front-end page request |

---

## Future Requirements Analysis

| Feature | portfolios.images | logo_override | payment_logos |
|---------|------------------|--------------|--------------|
| Sort order (drag-reorder) | Not in use, not planned | N/A (single item) | Not in use |
| Per-item captions | Not in use, not in view | N/A | N/A |
| Per-item alt text | Hardcoded, no custom | N/A | N/A |
| Query individual images by ID | Not needed | Not needed | Not needed |
| Media metadata (dimensions, type) | Not used in views | Not used | Not used |
| Multiple variants in one column | No | 4 header variants share one JSON column | 4 footer variants share one JSON column |

**Conclusion:** None of the three targets have active or near-term requirements for per-item metadata. The current admin UIs have no caption inputs, no alt-text fields, no drag-reorder. The front-end views render images as a flat list with no metadata consumption.

---

## Option A — JSON of Media IDs

**What it looks like:**

```json
// portfolios.images (after Wave 3)
[7, 12, 15]

// footer_variant_settings['palgoals_marketing'] (after Wave 3)
{
  "payment_logos": [3, 8, 9],
  "payment_logo_ids": [3, 8, 9],
  "logo_override_media_id": 23,
  "logo_override": "media/2024/05/logo.png"
}
```

**Read pattern for portfolios.images (low-traffic individual page):**
```php
$mediaIds = $portfolio->images ?? [];  // [7, 12, 15]
$paths = Media::whereIn('id', $mediaIds)->pluck('file_path', 'id');
// One query, O(n) results
```

**Read pattern for logo_override / payment_logos (high-traffic header/footer):**
- IDs stored in JSON, but **path also stored at write time** (existing `normalizeMediaPath()` behavior preserved)
- Render code reads the path key — zero additional DB queries
- The ID key exists purely for audit and backfill purposes

| Criterion | Assessment |
|-----------|-----------|
| Implementation complexity | **Low** — stop converting IDs to paths in controller; update read side |
| Migration | **Straightforward** — convert stored path arrays back to IDs via `MediaPathNormalizer` |
| Performance (portfolios.images) | **Acceptable** — one `whereIn` query per portfolio detail page |
| Performance (logo_override / payment_logos) | **Zero overhead** — path preserved at write time |
| Orphan risk | **Eliminated for portfolios.images** (ID is FK-logically linked) · **Reduced for JSON settings** (ID present for audit) |
| Consistency with ADR-005 | **High** — aligns with Pattern A across all targets |
| Admin UI changes needed | **Minimal** — media picker already sends IDs |
| New tables | **None** |
| New FK columns | **None** (JSON cannot have FK constraints by definition) |

---

## Option B — Pivot Tables

**What it looks like:**

```sql
-- Portfolio gallery images
CREATE TABLE portfolio_media (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id   BIGINT UNSIGNED NOT NULL,
    media_id       BIGINT UNSIGNED NOT NULL,
    sort_order     INT DEFAULT 0,
    caption        TEXT NULL,
    alt_text       VARCHAR(500) NULL,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- Footer payment logos
CREATE TABLE appearance_payment_logos (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_id   BIGINT UNSIGNED NOT NULL,
    media_id     BIGINT UNSIGNED NOT NULL,
    sort_order   INT DEFAULT 0,
    FOREIGN KEY (setting_id) REFERENCES general_settings(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);
```

**For logo_override (single image per variant):** Cannot use a simple pivot table because the image is keyed by **variant name** inside a JSON column. Would require a dedicated table:
```sql
CREATE TABLE appearance_variant_logos (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_id    BIGINT UNSIGNED NOT NULL,
    variant_key   VARCHAR(100) NOT NULL,     -- 'purple_topbar', 'palgoals_marketing'
    context       VARCHAR(50) NOT NULL,      -- 'header', 'footer'
    media_id      BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY (setting_id, variant_key, context)
);
```

| Criterion | Assessment |
|-----------|-----------|
| Implementation complexity | **High** — 2–3 new migrations, new Model relations, updated Controller CRUD, updated admin form UI |
| Migration | **Complex** — backfill from JSON path strings to pivot rows across multiple tables |
| Performance | **Better** (FK joins with eager loading) for portfolio gallery · **Worse** (extra JOIN on every page) for header/footer |
| Orphan risk | **Eliminated** via `ON DELETE CASCADE` |
| Consistency with ADR-005 | **High** — true relational pattern |
| Admin UI changes needed | **Major** — new add/remove/reorder rows UI for gallery |
| New tables | **2–3** |
| New FK columns | Multiple |
| Unlock features | sort_order ✓, captions ✓, alt_text ✓ — **but none of these are needed** |

---

## Complexity Comparison

| Area | Option A (JSON IDs) | Option B (Pivot Tables) |
|------|--------------------|-----------------------|
| New migrations | 0 | 2–3 |
| New models | 0 | 2–3 |
| Controller changes | Minor (stop resolving IDs → paths) | Major (pivot CRUD) |
| Admin form changes | None | Major (reorder UI, caption inputs) |
| View changes | Minor (resolve IDs → paths on render) | Minor (use relation) |
| Backfill scope | Convert paths → IDs in JSON column | Convert JSON paths → pivot rows |
| Testing surface | Small | Large |
| Estimated effort (total) | **Medium** (3–4 days) | **High** (8–12 days) |

---

## Migration Complexity

### Option A

| Step | Detail | Effort |
|------|--------|--------|
| portfolios.images: backfill | `MediaPathNormalizer::resolveToMediaId()` for each path in array, re-encode as `[id, id, id]` | Low |
| logo_override: enrich JSON | Add `logo_override_media_id` key alongside existing path; `normalizeMediaPath()` already resolves at write | Low |
| payment_logos: enrich JSON | Add `payment_logo_ids` array alongside existing paths array | Low |
| Controller: stop path-conversion for images | Remove `resolveMediaIdsToPaths()` call in `store()`/`update()` for `images` field | Trivial |
| Views: resolve IDs for portfolio gallery | Add one `Media::whereIn()` query per portfolio detail render | Trivial |
| Backfill artisan command | Same pattern as wave 2 command | Low |
| **Total** | | **~3–4 days** |

### Option B

| Step | Detail | Effort |
|------|--------|--------|
| 3 new migrations | portfolio_media, appearance_payment_logos, appearance_variant_logos | Medium |
| 3 new models + relations | PortfolioMedia, AppearancePaymentLogo, AppearanceVariantLogo | Medium |
| Controller updates | Portfolio: pivot insert/delete; Appearance: pivot insert/delete per variant | High |
| Admin UI: portfolio gallery | New drag-reorder UI, add/remove per image | High |
| Admin UI: appearance logos | New add/remove logo rows | Medium |
| Front-end: load pivot data | Eager load `with('galleryImages.media')`, change render loop | Medium |
| Backfill from JSON | Parse existing JSON, create pivot rows | Medium |
| Delete old JSON columns | After migration + verification | Low |
| **Total** | | **~10–14 days** |

---

## Long-Term Maintainability

### Consistency with ADR-005

| Approach | ADR-005 Alignment |
|----------|------------------|
| Option A — JSON IDs | Pattern A for all columns (IDs stored, paths resolved). Consistent with Wave 1 (FK columns on Model) and Wave 2 (FK column on Template). The spirit is identical even though JSON columns can't have DB-level FK constraints. |
| Option B — Pivot tables | Technically purer relational model. But introduces a **different pattern** (hasMany pivot) vs. the BelongsTo FK pattern used in Waves 1 and 2. |

### Consistency with Media Library

The media picker component already operates on IDs throughout the codebase. The entire admin form layer sends IDs. Wave 3 Option A simply stops discarding these IDs before reaching storage.

### Consistency across Waves

| Wave | Target | Pattern stored |
|------|--------|---------------|
| Wave 1 | GeneralSetting logos, Client avatar, Portfolio default_image | FK column on model (`*_media_id`) |
| Wave 2 | Template image | FK column on model (`image_media_id`) |
| Wave 3 (Option A) | portfolios.images, logo_override, payment_logos | JSON array of IDs / JSON integer ID |

There is an inherent difference: Waves 1 and 2 used DB FK columns; Wave 3 uses JSON IDs. This is acceptable — FK columns are impossible for JSON-embedded fields. The alternative (Option B) introduces a third pattern (pivot tables) which is also different from Waves 1 and 2.

**Option A is more consistent with Wave 1 and Wave 2** in spirit (ID → media.id) even if the mechanism differs (JSON vs. FK column). Option B would be a third distinct pattern.

---

## Recommendation

**OPTION A RECOMMENDED.**

With the following sub-rules:

### Rule 1: `portfolios.images` — Pure JSON IDs

Store integer media IDs directly:
```json
[7, 12, 15]
```

- The model's `'images' => 'array'` cast is unchanged
- The controller stops calling `resolveMediaIdsToPaths()` for this field and saves raw IDs
- The front-end portfolio view resolves IDs to paths at render time with a single `whereIn` query
- `MediaPathNormalizer::resolveToMediaId()` drives the backfill command

### Rule 2: `logo_override` — JSON Integer ID (write-time path also preserved)

Store both:
```json
{
  "logo_override": "media/2024/05/logo.png",
  "logo_override_media_id": 23
}
```

- The path is still resolved and stored at write time — zero render-time overhead
- The media ID is stored alongside as an audit reference and for future cleanup
- `normalizeMediaPath()` continues to work as-is; the controller additionally extracts and stores the ID
- At render time: Blade reads `logo_override` path key — unchanged

### Rule 3: `payment_logos` — JSON Array of IDs (write-time paths also preserved)

Store both:
```json
{
  "payment_logos": ["media/2024/05/visa.png", "media/2024/05/mc.png"],
  "payment_logo_ids": [3, 8]
}
```

- Same dual-write pattern as Rule 2
- Render time reads `payment_logos` array — unchanged
- `payment_logo_ids` array is the source of truth for Wave 3

---

## Final Verdict

### 1. What option is recommended?

**Option A — JSON of Media IDs**, with write-time path preservation for high-traffic JSON settings (logo_override, payment_logos).

### 2. Why?

Three reasons:

**No functional need for pivot tables.** The project currently uses zero per-image metadata (no captions, no custom alt text, no reorder UI). Building a pivot table infrastructure for three unused features violates YAGNI. If these features are needed in the future, they can be added then — at which point an incremental pivot migration will be straightforward.

**Option A is the same spirit as Waves 1 and 2.** Both prior waves stored media IDs alongside old path columns. Option A extends this to JSON columns using the only available mechanism (embedding the ID in the JSON). The developer mental model stays consistent.

**The write-time path preservation rule neutralizes the only real risk.** The reason to prefer pivot tables for `logo_override` and `payment_logos` would be performance (avoid render-time queries). But Rule 2 and Rule 3 above preserve paths at write time, so there are **zero additional queries on render**. This eliminates the performance argument for Option B on the high-traffic columns.

### 3. Does Wave 3 need a new ADR?

No. ADR-005 covers media storage unification across the entire codebase. Wave 3 is an implementation wave within ADR-005. The write-time path preservation rule is a tactical detail, not a new architecture decision. It should be documented in the CLAUDE.md Wave 3 session notes.

### 4. Can Wave 3 begin immediately after this review?

Yes. This review provides a complete specification. No architectural ambiguity remains.

The implementation sequence is:
1. Backfill artisan command for `portfolios.images` (convert paths → IDs)
2. Update `PortfolioController::store()` / `update()` — store IDs directly in `images` field
3. Update portfolio front-end view — resolve IDs to paths with `whereIn`
4. Update `AppearanceController::updateHeaderSettings()` — also store `logo_override_media_id` in JSON
5. Update `AppearanceController::updateFooterSettings()` — also store `logo_override_media_id` and `payment_logo_ids` in JSON
6. Validation + report

---

## Post-Wave 3: Is ADR-005 Complete?

| Wave | Status | Target |
|------|--------|--------|
| Wave 1 | ✅ Complete | GeneralSetting (7 logos), Client avatar, Portfolio default_image |
| Wave 2 | ✅ Complete (migration pending) | Template image |
| Wave 3 | 🔜 This wave | portfolios.images, logo_override ×2, payment_logos |

**After Wave 3:** ADR-005 will have covered all identified Pattern C and Pattern B media columns in the codebase. `services.icon` remains explicitly excluded (static asset paths, not user-uploaded media).

**Wave 3 will be the last wave for ADR-005**, barring discovery of new media columns during implementation.

---

## Confidence Level and Risks

**Confidence:** HIGH

This review is based on a full read of:
- `Portfolio.php` model
- `PortfolioController.php` — both `store()` and `update()`
- `AppearanceController.php` — complete (810 lines)
- `GeneralSetting.php` model
- `front_layouts.php` config
- `portfolios/_form.blade.php` — images field rendering
- `front/pages/portfolio.blade.php` — front-end gallery rendering
- `front/layouts/footers/palgoals_marketing.blade.php` — payment logos render
- `front/layouts/headers/purple_topbar.blade.php` — logo_override render
- `dashboard/appearance/header.blade.php` and `footer.blade.php` — admin form patterns

**Remaining risks:**

| Risk | Likelihood | Mitigation |
|------|-----------|-----------|
| Some portfolio rows have path strings (not IDs) in the `images` JSON | Medium — the form recently switched to IDs; older rows may have paths | Backfill command handles both (MediaPathNormalizer detects paths vs IDs) |
| Other appearance variants added in future may not follow the dual-write rule | Low | Document Rule 2/3 in CLAUDE.md; the helpers are centralized in AppearanceController |
| `payment_logo_ids` key not read anywhere causes silent inconsistency | Low | Wave 3 render switch will transition reads to ID-based resolution where feasible |
| Media records deleted after wave 3 but paths already stored (logo_override) | Low | Existing behavior — no regression; ID now present for future cleanup |

**Wave 3 estimated effort:** 3–4 developer days (lean toward 4 given the appearance settings complexity).
