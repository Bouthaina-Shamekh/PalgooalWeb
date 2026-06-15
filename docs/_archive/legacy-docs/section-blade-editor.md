# Section Blade Editor — From Admin Panel

## Overview

A developer-facing feature that allows writing and saving Blade template code
directly from the admin panel, eliminating the need to access the server file
system manually. The written code is persisted in the database and simultaneously
written to the correct path on disk.

---

## Problem

Currently, developers write Blade files outside the admin panel directly on the
server. There is no link between a `SectionDefinition` record in the database and
its corresponding Blade file on disk. If the file is missing, the section falls
back to `_missing-template.blade.php` silently.

---

## Solution: Two Synchronized Layers

```
DB (blade_source)  ←→  disk: resources/views/front/sections/{category}/{key}.blade.php
```

- **`blade_source`** — new column on `section_definitions`; the authoritative source
  of the template code as written in the admin editor.
- **`SectionTemplateFileWriter`** — a new service that writes the in-database source
  to the correct path on disk whenever the developer requests it.

The Laravel rendering pipeline (`SectionTemplateRegistry` → `front.sections.{category}.{template_key}`)
is **not changed** — it continues to read from disk as before. The admin panel simply
gives developers a way to create and update those disk files without leaving the browser.

---

## Developer Workflow

```
Define fields  →  open "Blade Template" tab  →  write code (or scaffold)
→  click "Save & Write File"  →  file written to disk
→  section renders on frontend immediately
```

---

## Database Changes

### New Migration

```sql
ALTER TABLE section_definitions
    ADD blade_source    LONGTEXT    NULL AFTER settings,
    ADD blade_written_at TIMESTAMP  NULL AFTER blade_source;
```

| Column | Type | Notes |
|--------|------|-------|
| `blade_source` | longText nullable | Blade code as authored in the admin editor |
| `blade_written_at` | timestamp nullable | When the file was last written from the admin panel |

---

## New Service: SectionTemplateFileWriter

**Path:** `app/Support/Sections/SectionTemplateFileWriter.php`

**Responsibilities:**
- Construct the target path: `resources/views/front/sections/{category}/{template_key}.blade.php`
- Validate the path (no `../`, must stay inside `resources/views/front/sections/`)
- Create the category subdirectory if it does not exist
- Write the file and update `blade_written_at` on success
- Return a typed result object (`success`, `error`, `path`)

**Path formula:**
```php
$category = SectionTemplateRegistry::normalizeCategory($definition->category);
$path     = resource_path("views/front/sections/{$category}/{$definition->section_key}.blade.php");
```

**Security guards:**
- Only `super_admin` users may trigger a file write (enforced in controller + policy)
- Category and template_key validated against `regex:/^[a-z0-9_-]+$/` before path construction
- Resolved path must start with `resource_path('views/front/sections/')` — any traversal attempt is rejected with a 403

---

## Admin UI Changes

A new **"Blade Template"** card is added below the main definition form on the
`edit.blade.php` page. It is only shown when `$sectionDefinition->exists` (i.e. not on create).

### Layout

```
┌─────────────────────────────────────────────────────────┐
│  📝  Blade Template                                      │
│                                                          │
│  File status:                                            │
│    ✅  File exists — last written 3 hours ago            │
│    ❌  File not found on disk — write needed             │
│    ⚠️  File exists but blade_source is empty             │
│        (written externally — editor is empty)            │
│                                                          │
│  Expected path:                                          │
│    resources/views/front/sections/hero/hero_x.blade.php  │
│                                                          │
│  ┌── Code Editor ────────────────────────────────────┐  │
│  │  @php                                              │  │
│  │      $title = trim((string)($data['title'] ?? ''));│  │
│  │  @endphp                                           │  │
│  │  <section>                                         │  │
│  │      <h1>{{ $title }}</h1>                         │  │
│  │  </section>                                        │  │
│  └────────────────────────────────────────────────────┘  │
│                                                          │
│  [📋 Scaffold from fields]   [💾 Save & Write File]     │
└─────────────────────────────────────────────────────────┘
```

### Code Editor

- Phase 1: `<textarea>` with `font-mono dir="ltr"` — simple, no dependencies
- Phase 4 (optional): Monaco Editor via CDN for Blade/HTML syntax highlighting

### File Status Badge

Computed in the controller by checking `file_exists($resolvedPath)` and comparing
the result with `blade_written_at`:

| State | Condition | Badge |
|-------|-----------|-------|
| `exists` | File on disk, `blade_written_at` set | ✅ ملف موجود |
| `missing` | File not on disk | ❌ لم يُكتب بعد |
| `external` | File on disk, `blade_source` is null | ⚠️ كُتب خارجياً |

---

## Scaffold Generator

A "Scaffold from fields" button generates a Blade stub automatically from the
`SectionDefinitionField` records attached to the definition.

**Output example** (for a definition with `title: text`, `description: textarea`, `image: media`):

```blade
@php
    // auto-generated scaffold — edit as needed
    $title       = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $image       = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
@endphp

<section id="{{ $data['section_id'] ?? '' }}" class="">
    {{-- TODO: add your markup --}}
    <h2>{{ $title }}</h2>
    <p>{{ $description }}</p>
    @if ($image)
        <img src="{{ $image }}" alt="">
    @endif
</section>
```

Repeater fields get a `@foreach` loop stub. Media fields get a `SectionFrontendMediaResolver::resolve()` call. Boolean fields get an `@if` stub.

The scaffold is generated client-side via JavaScript and inserted into the editor
textarea — it does not make a server request.

---

## Controller Changes

### SectionDefinitionController

**`edit()`** — add:
```php
$bladeFilePath   = $writer->resolvedPath($sectionDefinition);
$bladeFileExists = file_exists($bladeFilePath);
$bladeFileStatus = $this->resolveBladeFileStatus($sectionDefinition, $bladeFilePath);
// pass to view
```

**`update()`** — after the standard save, if `$request->filled('blade_source')`:
```php
$result = $writer->write($sectionDefinition);
if ($result->failed()) {
    // flash warning — definition saved, file write failed
    session()->flash('warning', t('dashboard.Blade_Write_Failed', '...'));
}
```

**New method `writeBladeFile()`** — standalone POST endpoint for writing without
changing any other definition data (for the "Write file" button without re-submitting
the full form):

```
POST /admin/section-definitions/{id}/write-blade
```

---

## Routing

```php
Route::post('{sectionDefinition}/write-blade', [SectionDefinitionController::class, 'writeBladeFile'])
     ->name('section_definitions.write_blade');
```

---

## Authorization

File write actions require `super_admin` role (checked in controller via `$this->authorize('update', $sectionDefinition)` — existing policy is sufficient since only admins can reach this screen, but the file write itself adds an explicit `abort_unless(auth()->user()->isSuperAdmin(), 403)`).

---

## What Does NOT Change

| Component | Status |
|-----------|--------|
| `SectionTemplateRegistry` | Unchanged — continues resolving from disk |
| `SectionDefinitionRuntimeResolver` | Unchanged |
| `front.sections.{category}.{template_key}` convention | Unchanged |
| Existing Blade files on disk | Unchanged — can still be edited manually |
| `SectionDefinitionField` CRUD | Unchanged |

---

## Implementation Phases

| Phase | Scope |
|-------|-------|
| **1** | Migration (`blade_source`, `blade_written_at`) + `SectionTemplateFileWriter` service + Controller hooks |
| **2** | Admin UI: file status badge + `<textarea>` editor + "Save & Write File" button |
| **3** | Scaffold generator (JS client-side, builds stub from field definitions) |
| **4** | Monaco Editor for Blade/HTML syntax highlighting (optional, CDN) |

---

## Files To Create / Modify

| File | Action |
|------|--------|
| `database/migrations/XXXX_add_blade_source_to_section_definitions_table.php` | Create |
| `app/Support/Sections/SectionTemplateFileWriter.php` | Create |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | Modify — add `writeBladeFile()`, update `edit()` and `update()` |
| `resources/views/dashboard/section_definitions/edit.blade.php` | Modify — add Blade Template card |
| `routes/dashboard.php` (or equivalent) | Modify — add `write-blade` route |
| `docs/section-blade-editor.md` | This file |

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Arbitrary file write / path traversal | Strict regex on category + key; resolved path must start with `resource_path('views/front/sections/')` |
| Accidental overwrite of existing file | Show a confirmation dialog when file already exists on disk |
| File write permission failure | Wrap in try/catch; flash a warning; definition save still succeeds |
| Blade syntax errors crashing the frontend | The admin editor does not validate Blade syntax — developer responsibility; consider a "Preview" sandbox in a later phase |
| `blade_source` growing large | `longText` column handles up to 4 GB; no practical concern for Blade templates |
