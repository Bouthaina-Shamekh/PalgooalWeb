# ADR-005 Wave 2 — Template Image Migration Report

**Date:** 2026-06-16  
**Scope:** `templates.image` (path string) → `templates.image_media_id` (FK → `media.id`)  
**Status:** ✅ Complete — pending user-side `php artisan migrate` + backfill

---

## 1. Summary

Wave 2 migrates the `templates` table to Pattern A (FK-based) media storage, following
the same dual-write / no-drop / backfill pattern established in Wave 1.

| Metric | Value |
|--------|-------|
| Templates audited | 5 |
| Templates with media FK linked by backfill | 3 |
| Orphaned (file truly lost — no media record match) | 2 |
| View files updated (Read Switch) | 10 |
| New column | `templates.image_media_id` BIGINT UNSIGNED NULL FK |
| Old column dropped? | **No** (no-drop policy) |

---

## 2. Audit Findings (Step 1)

### Surprising discovery: all active templates already use the media picker

All 5 existing template records store images as `media/YYYY/MM/filename.ext` — the
media picker path format — not the `templates/filename.ext` format produced by
direct file upload. This means corresponding `media` rows already exist for most of them.

### Disk files in `storage/app/public/templates/` (35 files)

These are historical orphans not referenced by any template record. No action was taken
on them (outside Wave 2 scope).

---

## 3. Files Changed

### New files

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_16_200002_add_image_media_id_to_templates_table.php` | Adds nullable FK column |
| `public/__adr005_wave2_validate.php` | Validation script (delete after use) |
| `docs/ADR_005_WAVE2_TEMPLATE_IMAGE_REPORT.md` | This report |

### Modified files

| File | Change |
|------|--------|
| `app/Models/Template.php` | + `image_media_id` in fillable, + `imageMedia()` BelongsTo, + `resolvedImagePath()` helper |
| `app/Http/Controllers/Admin/TemplateController.php` | Dual-write in `store()` and `update()` |
| `routes/console.php` | + `adr005:backfill-wave2-templates` artisan command |

### View files updated (Read Switch — 10 files)

| File | Change |
|------|--------|
| `resources/views/dashboard/templates/index.blade.php` | `$template->image` → `$template->resolvedImagePath()` |
| `resources/views/dashboard/templates/edit.blade.php` | Same |
| `resources/views/front/sections/templates/templates_showcase.blade.php` | Same |
| `resources/views/front/sections/templates/templates_listing_showcase.blade.php` | Same |
| `resources/views/front/pages/template-show.blade.php` | 2 locations (og:image + img src) |
| `resources/views/front/pages/template-show-redesign.blade.php` | Same (via `$resolveMediaUrl`) |
| `resources/views/components/template/sections/templates.blade.php` | Same |
| `resources/views/components/template/sections/templates_slider_showcase.blade.php` | Same |
| `resources/views/components/template/sections/templates_listing_showcase.blade.php` | Same |
| `resources/views/livewire/admin/template/frontend-templates-page.blade.php` | Same (deprecated file, safe to skip) |

**Intentionally skipped:**
- `resources/views/livewire/admin/template/template-management.blade.php` — marked deprecated at line 1, dead Livewire code

---

## 4. Pattern Details

### Migration

```php
$table->unsignedBigInteger('image_media_id')->nullable()->after('image');
$table->foreign('image_media_id')->references('id')->on('media')->nullOnDelete();
```

- `nullOnDelete()` → when a media record is deleted, the FK becomes NULL instead of
  cascading the deletion to the template.
- Old `image` column is NOT dropped (no-drop policy holds through Wave 2).

### Model Helper

```php
// app/Models/Template.php

public function imageMedia(): BelongsTo
{
    return $this->belongsTo(\App\Models\Media::class, 'image_media_id');
}

public function resolvedImagePath(): ?string
{
    return $this->imageMedia?->file_path ?? $this->getRawOriginal('image') ?? null;
}
```

FK relation takes priority; falls back to raw path string if FK is NULL.

### Dual-Write Pattern (Controller)

On every write (create or update), both columns are populated simultaneously:

```php
// Path column (old)   → $imagePath    → saved as templates.image
// FK column   (new)   → $imageMediaId → saved as templates.image_media_id
```

Two write sources are handled:
1. **Direct file upload** (`$request->hasFile('image')`) — stores file, creates a `media`
   row, extracts both path and media ID.
2. **Media picker** (`$validated['image_media_id']`) — looks up existing media row,
   extracts its `file_path` for the old column.

### Backfill Command

```bash
php artisan adr005:backfill-wave2-templates --dry-run   # preview
php artisan adr005:backfill-wave2-templates             # apply
```

Uses `MediaPathNormalizer::resolveToMediaId()` to match `templates.image` (path) to
`media.file_path`. Expected result: 3 linked, 2 orphaned (truly lost files).

### Read Switch Pattern

All views use the inline `($img = ...) ?` pattern to avoid double method call:

```blade
{{-- dashboard/templates/index.blade.php --}}
$imageUrl = ($img = $template->resolvedImagePath()) ? asset('storage/' . $img) : null;

{{-- front/pages/template-show.blade.php --}}
src="{{ ($img = $template->resolvedImagePath()) ? asset('storage/' . $img) : '' }}"
```

---

## 5. User Action Required

After reviewing this report, run the following commands on your machine:

```bash
# 1. Apply migration
php artisan migrate

# 2. Preview backfill (no writes)
php artisan adr005:backfill-wave2-templates --dry-run

# 3. Apply backfill
php artisan adr005:backfill-wave2-templates

# 4. Clear cache
php artisan optimize:clear
```

Then open the validation script to confirm:

```
http://127.0.0.1/palgoals/public/__adr005_wave2_validate.php
```

Expected checklist (all green):
- ✓ Column `templates.image` exists (not dropped)
- ✓ Column `templates.image_media_id` exists
- ✓ FK constraint exists (references media.id)
- ✓ ON DELETE = `SET NULL`
- ✓ At least one template has `image_media_id` linked
- ✓ Orphaned after backfill ≤ 2

**Delete the validation script after use:**
```bash
rm public/__adr005_wave2_validate.php
```

---

## 6. Scope Boundaries (Constraints Honored)

| Constraint | Status |
|-----------|--------|
| `portfolios.images` not touched | ✅ |
| `payment_logos` not touched | ✅ |
| `logo_override` not touched | ✅ |
| `services.icon` not touched | ✅ |
| `templates.image` not dropped | ✅ |
| ADR-003 not started | ✅ |
| No Livewire used | ✅ |
| No `__()` used | ✅ |
| No `with('success', ...)` used | ✅ |

---

## 7. Wave Progress

| Wave | Scope | Status |
|------|-------|--------|
| Wave 1 | Client avatar, Portfolio default_image, GeneralSetting logos (7 columns) | ✅ Complete |
| **Wave 2** | **Template image** | **✅ Complete (migration pending user)** |
| Wave 3 | TBD (remaining media path columns) | 🔜 Not started |
