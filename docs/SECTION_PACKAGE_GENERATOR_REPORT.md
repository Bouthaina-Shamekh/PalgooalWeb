# Section Package Generator — Implementation Report

> Phase 7 of the Section Definitions system.  
> Date: 2026-06-19

---

## Summary

Phase 7 implements a **one-click Section Package workflow**: the user picks a template, clicks one button, and gets a fully-wired Section Package — SectionDefinition + Fields + Generated Blade + Written Blade File — with a redirect straight to the Edit page (Blade tab ready).

Before Phase 7, creating a usable section required:
1. Create From Template → Definition + Fields (from-template page)
2. Open Edit page → Blade tab
3. Click "⚡ Scaffold" → generate scaffold
4. Click "Generate & Write" → write file to disk

After Phase 7:
1. Click "🚀 Create Section Package" → done. Redirect to Edit page.

---

## Files Created / Modified

### New Files

| File | Lines | Purpose |
|------|-------|---------|
| `app/Support/Sections/SectionPackageGenerator.php` | ~360 | Orchestration service |
| `docs/SECTION_PACKAGE_GENERATOR_ARCHITECTURE.md` | ~165 | Architecture documentation |
| `docs/SECTION_PACKAGE_GENERATOR_REPORT.md` | this file | Implementation report |

### Modified Files

| File | Change |
|------|--------|
| `routes/dashboard.php` | Added `POST /from-template/package` route |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | Added `createPackageFromTemplate()` method |
| `resources/views/dashboard/section_definitions/from-template.blade.php` | Added `pkg-form`, `🚀` button, warning flash, `createPackage()` JS |
| `database/seeders/DashboardTranslationsSeeder.php` | Added 6 translation keys |

---

## Implementation Details

### SectionPackageGenerator

Pure orchestration — delegates to existing services:

```
SectionTemplateLibrary  → template definition + resolved fields
ComponentLibrary        → (via resolveTemplateFields)
BladeGenerator          → scaffold generation
SectionTemplateFileWriter → path resolution + disk write
FileStatusResolver      → final status descriptor
```

The 10-step workflow is detailed in `SECTION_PACKAGE_GENERATOR_ARCHITECTURE.md`.

### Route

```php
// routes/dashboard.php — registered BEFORE /{sectionDefinition} wildcard
Route::post('/from-template/package', [...'createPackageFromTemplate'])->name('package');
// full name: dashboard.section_definitions.package
```

### Controller Method

```php
public function createPackageFromTemplate(Request $request): RedirectResponse
```

Validates `template_key` against `SectionTemplateLibrary::keys()`, calls `SectionPackageGenerator::generate()`, maps the 3 status values to redirect responses.

### UI Changes (from-template.blade.php)

- Added `session('warning')` flash block (for `definition_only` status)
- Added `session('ok')` flash block (for `ready` status)  
- Added hidden `#pkg-form` pointing to the package route
- Each template card footer now has **two buttons** (when not already created):
  - **Primary** `🚀 إنشاء حزمة السكشن` — full package (recommended)
  - **Secondary** `⚡ تعريف + حقول فقط` — old behaviour (no Blade write)
- Added `createPackage()` JS function with confirmation dialog

### Translation Keys Added

| Key | Value |
|-----|-------|
| `dashboard.Create_Section_Package` | إنشاء حزمة السكشن |
| `dashboard.Create_Definition_Only` | تعريف + حقول فقط |
| `dashboard.Template_Already_Created` | موجود بالفعل |
| `dashboard.Package_Create_Error` | حدث خطأ أثناء إنشاء الحزمة. راجع السجلات. |
| `dashboard.Package_Created` | تم إنشاء الحزمة ":name" بنجاح! :fields حقل · :components component · View: :view · الملف: :path |
| `dashboard.Package_Definition_Only` | تم إنشاء السكشن ":name" مع :fields حقل، لكن لم يتم كتابة ملف Blade (:path). :reason |

---

## Status Mapping

| `generate()` result | Flash key | Flash content |
|---------------------|-----------|---------------|
| `ready` | `ok` | Full success with view name + path |
| `definition_only` | `warning` | Partial success — reason for skipped write |
| `failed` | `error` | First error from `$result['errors']` |

---

## Artisan Commands After Deployment

None required. No migrations, no seeders to run (seeder updated but no new tables).

To apply the new translation keys:
```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

---

## Bug Fix — BladeGenerator Repeater Condition (Post-Phase 7)

**File**: `app/Support/Sections/BladeGenerator.php` — `renderRepeater()`

**Symptom**: Generated scaffold contained invalid PHP: `@if (!$empty($features))`.
PHP treats `$empty` as a variable reference and the `(...)` call fails at runtime.

**Root Cause**: Line 419 used a string concatenation trick that unintentionally added a `$` before `empty`:

```php
// ❌ Bug:
$lines[] = "{$indent}@if (!\$" . "empty(\${$key}))";
// → @if (!$empty($features))   ← $empty is not a function
```

**Fix**:

```php
// ✅ Correct:
$lines[] = "{$indent}@if (!empty(\${$key}))";
// → @if (!empty($features))    ← valid PHP/Blade
```

**Verification**: In PHP double-quoted strings, `\$` is a literal `$` and `{$key}` is an interpolated variable. The repaired string yields `@if (!empty($features))`.

**Technical Debt Note — Repeater Media Sub-fields**: `renderRepeaterSubField()` currently outputs:
```blade
<img src="{{ $feature['icon_media'] ?? '' }}" alt="">
```
If `icon_media` stores a Media ID (integer) rather than a path, the `src` will be incorrect. A future improvement should use `SectionFrontendMediaResolver::resolve()` for media sub-fields inside repeaters.

---

## Bug Fix — Template Binding (Post-Phase 7)

**Problem**: After Package Generator created a Section Package (`features_grid`), the edit page showed `template_key` blank. More critically, `SectionDefinitionRuntimeResolver::resolveRenderableDefinition()` returned `null`, making the section **unrenderable** even though the Blade file existed on disk.

**Root Cause**: `SectionPackageGenerator` (and `storeFromTemplate()` in the controller) created `SectionDefinition` + `SectionDefinitionField` records but never created a `section_templates` record or linked it via the `section_definition_template` pivot.

`SectionDefinition::primaryTemplateKey()` queries `section_templates` via this pivot. With no pivot record, it returns `null`. `hasPrimaryTemplate()` then returns `false`, blocking rendering.

**Fix** — added Steps 4c+4d inside the DB transaction in `SectionPackageGenerator::generate()`:

```php
use App\Models\Sections\Template as SectionTemplate;

// 4c. Create or find SectionTemplate with template_key = section_key
$sectionTemplate = SectionTemplate::firstOrCreate(
    ['template_key' => $definition->section_key],
    [
        'label'      => $definition->label,
        'category'   => $definition->category,
        'is_active'  => true,
        'is_visible' => true,
        'sort_order' => 0,
    ],
);

// 4d. Attach via pivot (sort_order = 0 = primary)
$definition->templates()->sync([
    $sectionTemplate->id => ['sort_order' => 0],
]);
```

The same fix was applied to `storeFromTemplate()` in `SectionDefinitionController`.

**Convention**: `template_key = section_key` for all library-generated definitions. This ensures:
- `SectionDefinitionFrontendViewDataFactory` can resolve `$templateKey = 'features_grid'`
- `SectionTemplateRegistry::resolve('features_grid', 'features')` → view `front.sections.features.features_grid`
- `FileStatusResolver::conventionViewName()` → same view (derived from `section_key`)

---

## Known Limitations (Phase 1)

- **No Force Overwrite**: if a Blade file already exists at the target path, the write is skipped and `definition_only` is returned.
- **No Marketplace Export / ZIP**: out of scope.
- **No Auto-publish Multiple**: out of scope.

---

## Validation Checklist

- [x] Template not found → `failed` + error flash, no DB write
- [x] Duplicate `section_key` → `failed` + error flash, no DB write
- [x] Invalid path (bad category/key) → `failed`, no DB write
- [x] Existing file on disk → `definition_only` + warning flash (Phase 1 no-overwrite)
- [x] DB transaction failure → `failed` + rollback
- [x] Write failure → `definition_only` (definition kept, error in flash)
- [x] Happy path → `ready` + redirect to Edit + success flash with view name + path
