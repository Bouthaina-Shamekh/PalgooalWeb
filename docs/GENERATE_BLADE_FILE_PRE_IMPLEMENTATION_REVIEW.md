# Generate Blade File — Pre-Implementation Review

**Date:** 2026-06-19  
**Phase:** Pre-Phase 2 Architecture Confirmation (read-only analysis)  
**No code was changed in this document.**

---

## 1. Current State — What Already Exists

**Important discovery:** The file-writing infrastructure is **already fully implemented**. Phase 2 is not "build the writer from scratch" — it is "add a combined Generate + Write flow to the UI."

### Already implemented:

| Component | File | Status |
|-----------|------|--------|
| File path resolver | `SectionTemplateFileWriter::resolvedPath()` | ✅ Done |
| Directory + file writer | `SectionTemplateFileWriter::write()` | ✅ Done |
| File status checker | `SectionTemplateFileWriter::fileStatus()` | ✅ Done |
| Path security validation | `SectionTemplateFileWriter` (path traversal check) | ✅ Done |
| Controller method | `SectionDefinitionController::writeBladeFile()` | ✅ Done |
| Route | `POST /{sectionDefinition}/write-blade` → `write_blade` | ✅ Done |
| Scaffold generator | `BladeGenerator::generate()` | ✅ Done |
| Scaffold preview endpoint | `GET /{sectionDefinition}/blade-scaffold` → `blade_scaffold` | ✅ Done |
| Preview Modal UI | `section_definitions/edit.blade.php` | ✅ Done |

### Current user flow (Phase 1 — Preview Only):

```
1. Developer opens SectionDefinition edit page (Blade tab)
2. Clicks "⚡ Scaffold من الحقول"
3. Preview Modal shows the generated scaffold + stats bar
4. Developer clicks "إدراج في المحرر" → scaffold goes into Monaco editor
5. Developer edits the code manually in Monaco
6. Developer clicks "كتابة الملف" button → POST write-blade → writes blade_source to disk
```

### What Phase 2 adds:

```
New option in Preview Modal:
"⬇ توليد وكتابة مباشرة" (Generate & Write Directly)
→ Takes the generated scaffold
→ Saves it as blade_source
→ Writes it to disk immediately
→ No Monaco intermediary required

Use case: Developer wants a scaffold on disk to edit in their IDE, not in Monaco.
```

---

## 2. Path Architecture — Where the System Looks for Views

### Resolution Chain (SectionTemplateRegistry)

When the frontend renderer needs a section view, it follows this chain:

```
SectionRenderer::render()
  → SectionDefinitionFrontendViewDataFactory::build()
    → SectionTemplateRegistry::resolve($templateKey, $category)
      → 1. Explicit registry override (config/sections.php templates array)
      → 2. Convention-based: "front.sections.{category}.{template_key}"
      → 3. Fallback: "front.sections._missing-template"
```

### Convention-based view name (Tier 2):

```php
// SectionTemplateRegistry::conventionView()
return 'front.sections.' . normalizeCategory($category) . '.' . trim($templateKey);

// Example: category="hero", template_key="hero_main"
// → "front.sections.hero.hero_main"
// → resources/views/front/sections/hero/hero_main.blade.php
```

### Explicit registry (Tier 1 — only one entry currently):

```php
// config/sections.php
'portfolio_slider' => [
    'label' => 'Portfolio Slider',
    'view' => 'front.sections.portfolio.portfolio_slider',
    'category' => 'portfolio',
],
```

All other existing views (hero_campaign, content_showcase, faq_section, etc.) are resolved via **convention**, not registry. Registry is only needed when the view path deviates from convention.

---

## 3. The Correct Write Path

**Confirmed path pattern:**

```
resources/views/front/sections/{category}/{section_key}.blade.php
```

**This is already implemented in `SectionTemplateFileWriter::resolvedPath()`:**

```php
// SectionTemplateFileWriter.php (existing)
return $this->baseDir . DIRECTORY_SEPARATOR
    . $category . DIRECTORY_SEPARATOR
    . $key . '.blade.php';
// where baseDir = resource_path('views/front/sections')
```

**Real examples from the current codebase:**

```
resources/views/front/sections/hero/hero_campaign.blade.php
resources/views/front/sections/hero/hero_featured.blade.php
resources/views/front/sections/faq/faq_section.blade.php
resources/views/front/sections/showcase/content_showcase.blade.php
resources/views/front/sections/services/service_showcase.blade.php
resources/views/front/sections/portfolio/portfolio_slider.blade.php
```

**What we must NOT use:**

```
❌ resources/views/components/sections/    ← does not exist in this project
❌ resources/views/sections/               ← not the convention
❌ resources/views/front/sections/{key}.blade.php  ← flat (only for legacy files like hero.blade.php)
```

The flat `hero.blade.php` at the root of `front/sections/` is a **legacy file** and is not part of the convention-based system.

---

## 4. File Naming Rules

### Source: `$sectionDefinition->section_key`

**Not:** `id`, `name`, `slug`, `template_key`

**Reason:** `section_key` is the stable, unique identifier stored in `section_definitions.section_key`. The convention-based renderer looks up `template_key` (which links via `section_templates` table to the definition), but by convention `template_key = section_key`.

```php
// SectionTemplateFileWriter::resolvedPath()
$key = trim((string) $definition->section_key);  // ← uses section_key
```

### Allowed characters: `/^[a-z0-9_-]+$/`

```
hero_main.blade.php         ✓  (underscores)
content-showcase.blade.php  ✓  (dashes)
faq_accordion.blade.php     ✓
hero Main.blade.php         ✗  (space — invalid)
Hero_Main.blade.php         ✗  (uppercase — invalid)
../etc/passwd.blade.php     ✗  (path traversal — blocked)
```

### Existing convention in the codebase uses underscores:

Looking at actual files: `hero_campaign`, `hero_featured`, `faq_section`, `content_showcase`, `portfolio_slider` — all use `_`. This is the preferred style for `section_key` values.

---

## 5. Validation & Security — Already Implemented

`SectionTemplateFileWriter` already enforces all required protections:

### Path Traversal Prevention

```php
// SectionTemplateFileWriter::write()
$normalizedPath = str_replace(['\\', '//'], ['/', '/'], $path);
$normalizedBase = str_replace(['\\', '//'], ['/', '/'], $this->baseDir);

if (! str_starts_with($normalizedPath, $normalizedBase)) {
    return ['ok' => false, 'error' => 'Path traversal detected — write refused.'];
}
```

### Invalid category/key Prevention

```php
// SectionTemplateFileWriter::resolvedPath()
if (! preg_match(SectionTemplateRegistry::TEMPLATE_KEY_REGEX, $key)) {
    return null;  // → write() will catch null and return error
}
// TEMPLATE_KEY_REGEX = '/^[a-z0-9_-]+$/'
```

### Empty content Prevention

```php
if (empty($definition->blade_source)) {
    return ['ok' => false, 'error' => 'blade_source is empty — nothing to write.'];
}
```

### Overwrite Protection

`SectionTemplateFileWriter::write()` currently does **not** check if the file already exists before writing — it always overwrites. This is by design: the admin explicitly clicked "Write Blade File" and is presumed to intend the overwrite.

For Phase 2 (Generate & Write directly), we should add an **overwrite guard**:
- If file exists on disk AND it was not generated by the system (i.e., `blade_written_at` is null), require explicit `force=true` param.
- If file exists and was system-generated, allow overwrite (the developer generated it previously).

---

## 6. Configuration Review — Should We Add `config/sections.php` Path Entry?

### Current state:

```php
// SectionTemplateFileWriter::__construct()
$this->baseDir = resource_path('views/front/sections');  // ← hardcoded
```

### Analysis:

The path is currently hardcoded. The user asks: should we add this to `config/sections.php`?

**Recommendation: YES, for the following reason:**

If the project ever moves sections to a different views directory (e.g., a package, a multi-tenant structure, or a client-site subfolder), the path would need to be changed in the source file. A config entry makes this trivial to override.

**Proposed addition to `config/sections.php`:**

```php
'template_file_writer' => [
    'base_path' => resource_path('views/front/sections'),
],
```

**Usage in `SectionTemplateFileWriter::__construct()`:**

```php
$this->baseDir = config(
    'sections.template_file_writer.base_path',
    resource_path('views/front/sections')   // safe default
);
```

**This is a 2-line change and carries zero risk.** The default value ensures backward compatibility if the config key is missing.

---

## 7. Potential Conflicts and Risks

| Risk | Severity | Status |
|------|----------|--------|
| Writing to wrong path | 🔴 High | Mitigated — `SectionTemplateFileWriter` restricts to `views/front/sections/` |
| Overwriting a manually crafted Blade file | 🟡 Medium | Needs overwrite guard for Phase 2 "Generate & Write" flow |
| Path traversal via category or section_key | 🔴 High | Mitigated — regex `/^[a-z0-9_-]+$/` enforced |
| Category/key mismatch (different from template_key) | 🟡 Medium | Low risk — by convention they match; writer uses `section_key` explicitly |
| File written but not yet linked to a template | 🟢 Low | Safe — file on disk is inert until a section instance uses template_key |
| Writing on shared hosting (ModSecurity blocking POST) | 🟡 Medium | Already solved — `writeBladeFile()` accepts `blade_source_b64` (base64) |
| Renderer can't find view after generation | 🟢 Low | Convention-based — if `category` and `section_key` are correct, view resolves automatically |

---

## 8. Final Decision — Phase 2 Implementation Plan

### Q1: Where does the system look for section files?

`SectionTemplateRegistry::conventionView()` resolves:
```
front.sections.{category}.{template_key}
→ resources/views/front/sections/{category}/{template_key}.blade.php
```

### Q2: Does it support categorization inside subdirectories?

**Yes, fully.** The convention is `{category}/{key}` and all current production files use this structure. The `category` field on `SectionDefinition` drives this.

### Q3: What is the correct write path?

```
resources/views/front/sections/{category}/{section_key}.blade.php
```

Already implemented in `SectionTemplateFileWriter::resolvedPath()`.

### Q4: Is there any conflict with the current system?

No conflicts. The file writer, path resolver, and renderer are all aligned on the same convention. A file written to disk by `SectionTemplateFileWriter` will be found automatically by the renderer the next request.

### Q5: Should we add a config entry?

**Yes.** Add `sections.template_file_writer.base_path` to `config/sections.php` and update `SectionTemplateFileWriter::__construct()` to use it. Safe, backward-compatible, 2-line change.

---

## 9. What Phase 2 Will Build (Implementation Scope)

Phase 2 adds **one new action**: Generate scaffold → Write to disk in a single click.

### New endpoint:
```
POST /admin/section-definitions/{id}/generate-and-write
```

Or alternatively, extend the existing `write-blade` endpoint to accept a `generate=true` parameter that calls `BladeGenerator::generate()` first, saves the result as `blade_source`, then writes to disk.

### New UI:
- A "Generate & Write ⬇" button in the Preview Modal footer (alongside existing "Insert into Editor" and "Copy")
- On success: show same toast as existing write-blade flow
- On conflict (file already exists and was external): show warning with overwrite confirmation

### Files to modify:
1. `config/sections.php` — add `template_file_writer.base_path`
2. `app/Support/Sections/SectionTemplateFileWriter.php` — read base_path from config
3. `app/Http/Controllers/Admin/SectionDefinitionController.php` — new `generateAndWrite()` method OR extend `writeBladeFile()`
4. `routes/dashboard.php` — add route (if new endpoint)
5. `resources/views/dashboard/section_definitions/edit.blade.php` — add "Generate & Write" button to Preview Modal
6. `database/seeders/DashboardTranslationsSeeder.php` — add translation keys

### Files NOT to modify:
- `SectionTemplateRegistry` — path resolution already correct
- `SectionRenderer` — rendering already works
- `BladeGenerator` — scaffold generation already correct
- All front-end views

---

## Summary

The architecture is correct and the foundation is complete. Phase 2 is a targeted UI + controller addition that connects two already-working pieces (`BladeGenerator::generate()` + `SectionTemplateFileWriter::write()`) into a single user action.

**Ready to implement Phase 2.**
