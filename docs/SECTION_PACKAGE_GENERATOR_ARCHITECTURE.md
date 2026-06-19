# Section Package Generator — Architecture

> Phase 7 of the Section Definitions system.  
> One click: Template → Definition → Fields → Blade → Written File → Edit Page.

---

## Overview

`SectionPackageGenerator` is a **pure orchestration service** that wires together five existing services to create a complete, ready-to-render Section Package in a single action. It contains no business logic of its own — it delegates entirely.

---

## Service Responsibilities

| Service | Responsibility |
|---------|----------------|
| `SectionTemplateLibrary` | Provides the template definition and resolved field list |
| `ComponentLibrary` | Called implicitly by `resolveTemplateFields()` to merge component fields |
| `BladeGenerator` | Generates a readable, opinionated Blade scaffold from field definitions |
| `SectionTemplateFileWriter` | Resolves the disk path and writes the Blade file |
| `FileStatusResolver` | Produces the final status descriptor after write |
| `SectionTemplate` (Model) | Created/found and linked via pivot so `primaryTemplateKey()` returns a non-null value |

---

## Workflow (10 Steps)

```
User clicks "Create Section Package"
        │
Step 1  │  Validate template_key in SectionTemplateLibrary
        │
Step 2  │  Guard: section_key must not exist in DB
        │
Step 3  │  Guard: SectionTemplateFileWriter::resolvedPath() must return non-null
        │  Phase 1 sub-check: if file already on disk → skip write, return definition_only
        │
Step 4  │  DB::transaction()
        │      ├── SectionDefinition::create([...]) 
        │      ├── foreach resolveTemplateFields() → fields()->create([...])
        │      ├── SectionTemplate::firstOrCreate(['template_key' => $sectionKey], [...])
        │      └── $definition->templates()->sync([$sectionTemplate->id => ['sort_order' => 0]])
        │  → Rollback on any Throwable → status: 'failed'
        │
Step 5  │  $definition->load('fields')
        │  BladeGenerator::generate($definition) → scaffold string
        │
Step 6  │  if scaffold is empty && blade_stub exists → use blade_stub (fallback)
        │
Step 7  │  $definition->blade_source = $scaffold
        │  $definition->saveQuietly()   ← no model events fired
        │
Step 8  │  SectionTemplateFileWriter::write($definition) → disk
        │  → on failure → status: 'definition_only' (definition kept, no rollback)
        │
Step 9  │  $definition->refresh()
        │  FileStatusResolver::resolve($definition) → file status descriptor
        │
Step 10 │  Return Result DTO
```

---

## Result DTO

```php
[
    'definition_id'    => int|null,       // null only on status='failed'
    'section_key'      => string,
    'view_name'        => string|null,    // e.g. 'front.sections.hero.hero_main'
    'blade_path'       => string,         // e.g. 'resources/views/front/sections/hero/hero_main.blade.php'
    'fields_count'     => int,
    'components_count' => int,
    'component_names'  => string[],       // e.g. ['intro', 'cta', 'image']
    'status'           => 'ready'|'definition_only'|'failed',
    'warnings'         => string[],
    'errors'           => string[],
]
```

---

## Status Values

| Status | Meaning | Redirect |
|--------|---------|----------|
| `ready` | Definition + Fields + Blade file all created | Edit page + success flash |
| `definition_only` | Definition + Fields created; Blade NOT written | Edit page + warning flash |
| `failed` | DB transaction failed; nothing created | Back + error flash |

---

## Failure Scenarios

### DB Failure → `status: 'failed'`
- Triggered by any `Throwable` inside `DB::transaction()`
- Full rollback: nothing is persisted
- Error message is logged via `report()`

### Write Failure → `status: 'definition_only'`
- `SectionTemplateFileWriter::write()` returns `['ok' => false, ...]`
- Definition and Fields remain in DB
- `blade_source` is persisted; file is just not on disk
- No rollback — the Definition is valid and usable

### Existing File (Phase 1 No-Overwrite) → `status: 'definition_only'`
- `file_exists($resolvedPath)` is true before the write step
- Definition and Fields are created normally
- Write is skipped; warning is added to `$result['warnings']`
- User can manually write the file from the Edit page

### Invalid Path → `status: 'failed'`
- `SectionTemplateFileWriter::resolvedPath()` returns null
- Happens when `category` or `section_key` fail the regex validation
- No DB work is done

### Duplicate section_key → `status: 'failed'`
- `SectionDefinition::where('section_key', ...)->exists()` is true
- No DB work is done

---

## Route

```
POST /admin/section-definitions/from-template/package
Name: dashboard.section_definitions.package
```

Must be registered **before** the `/{sectionDefinition}` wildcard routes to avoid route collision.

---

## Controller

```php
// SectionDefinitionController::createPackageFromTemplate()
$generator = app(SectionPackageGenerator::class);
$result    = $generator->generate($validated['template_key']);

// status: 'failed'          → back()->with('error', ...)
// status: 'definition_only' → redirect to edit + warning flash
// status: 'ready'           → redirect to edit + success flash
```

---

## UI Flow

```
from-template.blade.php
    │
    ├── [🚀 إنشاء حزمة السكشن]  ← new primary button → pkg-form → package route
    │
    └── [⚡ تعريف + حقول فقط]   ← secondary button → tpl-form → store_from_template route
```

The `🚀 Create Section Package` button submits `pkg-form` (POST to `package` route).  
The `⚡ Definition Only` button submits `tpl-form` (POST to `store_from_template` route, old behaviour).

---

## Phase 2 (Future)

- [ ] Force Overwrite option for existing files
- [ ] Marketplace Export / ZIP packaging
- [ ] Auto-publish multiple sections from a Theme Bundle
