# Section Definitions

> **Last Updated:** 2026-06-15 · **Status:** Verified  
> **Source files merged:** `section-definitions.md` + `section-definitions-system.md`  
> This document is the single source of truth for the Section Definitions system.

---

## 1. Purpose

This document is the authoritative technical reference for the Section Definitions system.  
Read this when you need to:

- understand how new section types are created without code changes
- add or modify fields on an existing section type
- trace content from admin input through to frontend HTML
- write a Blade template from the admin panel
- debug the dynamic editor or rendering pipeline
- understand authorization, endpoints, and controller contracts

---

## 2. The Problem This Solves

Every `Page` in Palgoals is built from ordered `Section` content blocks. Before this system existed, each section type was hardcoded: a fixed `section_type` string, a manually registered Blade view, and PHP logic handling each case.

**Adding a new section type required:**
- Writing a new Blade view on the server
- Registering it in `SectionRegistry`
- Redeploying code

**The solution — Section Definitions:** a section type is now a database record (`SectionDefinition`). Its fields, editor behavior, and Blade template are all configured from the admin panel. No redeploy needed.

---

## 3. Two-Layer Architecture

The system is split into two layers with a clear responsibility boundary:

```
┌─────────────────────────────────────────────────────────────┐
│                 Definition Layer (Blueprint)                 │
│  SectionDefinition  ───  SectionDefinitionField             │
│  "What does this section type look like?"                   │
└──────────────────────────────┬──────────────────────────────┘
                               │  section_definition_id FK
                               │  (nullable — legacy sections have no FK)
┌──────────────────────────────▼──────────────────────────────┐
│                 Content Layer (Page Data)                    │
│  Section  ───  SectionTranslation                           │
│  "The actual content of one section on one page"            │
└─────────────────────────────────────────────────────────────┘
```

| Layer | Models | Answers |
|-------|--------|---------|
| **Definition** | `SectionDefinition`, `SectionDefinitionField`, `SectionTemplate` | What fields exist? What are their types? Which Blade file renders this? |
| **Content** | `Section`, `SectionTranslation` | What is the content of this specific section on this specific page? |

---

## 4. Data Model

### `section_definitions` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `section_key` | varchar unique | Stable developer identifier e.g. `hero_split`. Regex: `/^[a-z0-9_-]+$/` |
| `label` | varchar | Admin-facing display name |
| `description` | text nullable | Internal maintainer notes |
| `category` | varchar nullable | Grouping key; also drives Blade file path convention |
| `editor_mode` | varchar | Always `dynamic` (normalized by migration `2026_04_27`) |
| `custom_editor_key` | varchar nullable | Deprecated legacy field; retained for compatibility |
| `blade_source` | longtext nullable | Blade template code stored in DB — written to disk via Blade Editor |
| `blade_written_at` | timestamp nullable | Last time `blade_source` was successfully written to disk |
| `preview_media_id` | bigint nullable FK → `media.id` | Thumbnail for library cards (`nullOnDelete`) |
| `settings` | json nullable | Editor/runtime metadata; no render logic |
| `schema` | json nullable | Reserved for future tooling |
| `is_active` | boolean | Inactive definitions are stored but not rendered or offered in the editor |
| `is_visible` | boolean | Controls visibility in admin library UIs |
| `sort_order` | unsigned int | Display order |

### `section_definition_fields` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `section_definition_id` | bigint FK `cascadeOnDelete` | Parent definition |
| `field_key` | varchar | Stable per-definition identifier (unique per definition). Regex: `/^[a-z0-9_]+$/` |
| `label` | varchar | Admin-facing label |
| `group_name` | varchar nullable | Dashboard grouping label for the fields UI |
| `help_text` | text nullable | Hint shown below the field in the admin editor |
| `field_type` | varchar | One of the supported types — see §5 |
| `field_scope` | varchar | `shared` or `translatable` — see §6 |
| `default_value` | json nullable | `{"value": ...}` for shared; `{"ar": ..., "en": ...}` for translatable |
| `options` | json nullable | `[{"value": "...", "label": "..."}]` — for `select` fields only |
| `settings` | json nullable | Editor behavior metadata |
| `schema` | json nullable | For `repeater` type: `{"item_schema": [...]}` |
| `is_required` | boolean | Whether the admin UI enforces a value |
| `is_active` | boolean | Inactive fields stay stored for compatibility but are not shown |
| `validation_rules` | json nullable | Array of Laravel rule strings (stored but not yet applied at runtime — see §15 Known Gaps) |
| `sort_order` | unsigned int | Display order within the definition |

### Related tables

| Table | Purpose |
|-------|---------|
| `section_templates` | One template record per `template_key`; holds the key string and label |
| `section_definition_template` | Pivot: links definitions to templates (many-to-many, with `sort_order`) |
| `sections` | Content layer — has `section_definition_id` FK (nullable) |
| `section_translations` | Localized field values as JSON `content` column |

---

## 5. Field Types Reference

Defined as constants on `SectionDefinitionField`. Adding a type to `supportedFieldTypes()` automatically enables it in validation and the admin form without further changes.

| Constant | Value | Use Case |
|----------|-------|----------|
| `FIELD_TYPE_TEXT` | `text` | Short single-line text: titles, labels |
| `FIELD_TYPE_TEXTAREA` | `textarea` | Multi-line plain text: descriptions, summaries |
| `FIELD_TYPE_RICHTEXT` | `richtext` | HTML rich text via CKEditor 5 |
| `FIELD_TYPE_URL` | `url` | URL string: CTA links, external references |
| `FIELD_TYPE_MEDIA` | `media` | Media library reference: images, icons |
| `FIELD_TYPE_NUMBER` | `number` | Numeric value: counts, percentages |
| `FIELD_TYPE_BOOLEAN` | `boolean` | True/false toggle: show/hide elements |
| `FIELD_TYPE_SELECT` | `select` | Dropdown from `options` array: alignment, color |
| `FIELD_TYPE_REPEATER` | `repeater` | Ordered list of structured items — see §12 |

---

## 6. Field Scope: shared vs translatable

Every field has a `field_scope` that determines how its value is stored and retrieved:

| Scope | Storage | When to use |
|-------|---------|-------------|
| `translatable` | `SectionTranslation.content` JSON, one entry per locale | Field content differs per language (titles, descriptions, body text) |
| `shared` | `Section.settings` JSON | Field content is the same for all languages (background color, display order, boolean toggles) |

```php
// In a Blade view, $fields is already resolved for the current locale.
// shared and translatable fields are accessed identically:
{{ $fields['title'] ?? '' }}           // translatable
{{ $fields['background_color'] ?? '' }} // shared
```

---

## 7. Template Resolution

When `SectionRenderer` needs to render a definition-driven section, it resolves the Blade view path through `SectionTemplateRegistry` using two methods in order:

### Method 1 — Explicit registry entry (`config/sections.php`)

```php
// config/sections.php
'template_registry' => [
    'templates' => [
        'portfolio_slider' => [
            'view' => 'front.sections.portfolio.portfolio_slider',
        ],
    ],
],
```

### Method 2 — Convention-based path (most common)

If the `template_key` is not in the registry, the path is derived by convention:

```
template_key : hero_main
category     : hero
               ↓
Blade view   : front.sections.hero.hero_main
               ↓
File path    : resources/views/front/sections/hero/hero_main.blade.php
```

The category is taken from `SectionDefinition.category`. If no category is set, the file lands under `uncategorized/`.

### section_key vs template_key

| Key | Where it lives | Purpose |
|-----|---------------|---------|
| `section_key` | `section_definitions.section_key` | Unique identifier for the definition itself |
| `template_key` | `section_templates.template_key` | Determines which Blade file renders the definition |

They are typically identical, but the separation allows one definition to have multiple template variants, or to be renamed without breaking saved template references.

---

## 8. Render Flow

```
GET /some-page
      │
      ▼
Front\PageController → loads Page + Sections
      │
      ▼ (for each Section)
SectionRenderer::render($section)
      │
      ├── renderDefinitionDriven($section)  ← attempted first
      │       │
      │       ├── SectionDefinitionRuntimeResolver::resolveRenderableDefinition()
      │       │       checks: section_definition_id set?
      │       │       checks: definition is_active = true?
      │       │       checks: definition has a primaryTemplate with template_key?
      │       │       → returns null if any check fails
      │       │
      │       ├── SectionDefinitionFrontendViewDataFactory::build()
      │       │       resolves field values for current locale
      │       │       builds $fields array (shared + translatable merged)
      │       │       returns ['view' => '...', 'viewData' => ['fields' => ...]]
      │       │
      │       └── view($view, $viewData)->render()  → HTML
      │
      └── renderRegisteredSection($section)  ← fallback (legacy)
              checks LEGACY_FRONTEND_SECTION_TYPES[]
              renders via SectionRegistry (code-side)
```

**Definition-driven rendering is attempted first.** Legacy rendering is only used when:
- `section.section_definition_id` is null (section predates the definitions system)
- The linked definition is inactive
- The definition has no associated `template_key`

---

## 9. Dynamic Editor Mode

When a section is linked to a definition with `editor_mode = dynamic`, the admin section editor renders a **field-by-field form** instead of a raw textarea.

```
Admin: GET /admin/pages/{page}/sections/{section}/edit
      │
      ▼
SectionController@edit
      │
      ├── DynamicSectionEditorRenderer::buildForSection($section, $languages)
      │       │
      │       ├── runtimeResolver->resolveDynamicDefinition($section)
      │       │       checks: editor_mode === 'dynamic'
      │       │       checks: has primaryTemplate
      │       │
      │       └── builds payload:
      │               definition { id, key, label, fields[] }
      │               fieldGroups (grouped by group_name)
      │               defaultLocale
      │               localeCodes
      │               existingValues (current SectionTranslation content)
      │
      └── Blade renders one input per field
              shared fields: shown once
              translatable fields: shown per language tab
```

`editor_mode = dynamic` is the **only active editor mode**. All definitions were normalized to `dynamic` by migration `2026_04_27_000001_normalize_section_definition_editor_mode_to_dynamic`. The `custom_editor_key` column is retained for compatibility but not used by the active editor path.

---

## 10. Section ↔ SectionDefinition Binding

A `Section` is linked to a definition via the nullable `section_definition_id` FK.

```php
// Linking a section to a definition
$section->section_definition_id = $definition->id;
$section->save();
```

`SectionDefinitionRuntimeResolver::resolveLinkedDefinition()` handles the lookup:

```php
public function resolveLinkedDefinition(Section $section, ?string $editorMode = null): ?SectionDefinition
{
    if (! $this->runtimeTablesAvailable()) {
        return null;  // tables not migrated yet
    }

    if (! $section->section_definition_id) {
        return null;  // no link → legacy render path
    }

    $query = SectionDefinition::where('id', $section->section_definition_id)
        ->where('is_active', true);

    if ($editorMode !== null) {
        $query->where('editor_mode', $editorMode);
    }

    return $query->first();
}
```

---

## 11. Blade File Writer

The Blade Editor allows writing the definition's Blade template directly from the browser. See `docs/08-section-blade-editor.md` for the full workflow. Summary:

```
Monaco Editor (browser)
      │
      │  blade_source encoded as base64
      │  (base64 required: ModSecurity WAF blocks raw PHP in POST bodies)
      ▼
POST /admin/section-definitions/{id}/write-blade
      │
      ├── decode base64 in PHP
      ├── SectionTemplateFileWriter::write($definition)
      │       resolvedPath() → resources/views/front/sections/{category}/{key}.blade.php
      │       path traversal check (must stay within resources/views/front/sections/)
      │       mkdir() if directory doesn't exist
      │       file_put_contents()
      │       $definition->blade_written_at = now()
      │
      └── returns JSON: { ok: true, path: '...' }
```

### File status values

| Status | Meaning |
|--------|---------|
| `missing` | No file on disk — section cannot render until written |
| `exists` | File on disk, managed by admin panel (`blade_written_at` is set) |
| `external` | File exists on disk but `blade_source` is null — written outside admin panel |
| `invalid` | `category` or `section_key` fail the regex — path cannot be resolved |

---

## 12. Admin Workflow: Creating a New Section Type

Full walkthrough for creating a `services_grid` section:

### Step 1 — Create the definition

`GET /admin/section-definitions/create`

```
section_key : services_grid
label       : Our Services
category    : services
is_active   : true
```

### Step 2 — Define fields

`GET /admin/section-definitions/{id}/fields/create`

```
Field 1:
  key   : title
  label : Section Title
  type  : text
  scope : translatable

Field 2:
  key   : subtitle
  label : Short Description
  type  : textarea
  scope : translatable

Field 3:
  key   : items
  label : Services List
  type  : repeater
  scope : translatable
  item_schema:
    - key: icon  | type: text
    - key: title | type: text
    - key: desc  | type: textarea
```

### Step 3 — Write the Blade view

`GET /admin/section-definitions/{id}/edit` → Blade Editor tab

```blade
{{-- resources/views/front/sections/services/services_grid.blade.php --}}
<section class="services-section py-16">
    <div class="container">
        <h2>{{ $fields['title'] ?? '' }}</h2>
        <p>{{ $fields['subtitle'] ?? '' }}</p>

        <div class="grid grid-cols-3 gap-6">
            @foreach ($fields['items'] ?? [] as $item)
                <div class="service-card">
                    <i class="{{ $item['icon'] ?? '' }}"></i>
                    <h3>{{ $item['title'] ?? '' }}</h3>
                    <p>{{ $item['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

Click **"Write File"** → writes to `resources/views/front/sections/services/services_grid.blade.php`

### Step 4 — Link the template key

In the definition edit form, set `template_key: services_grid`.  
This links the definition to the convention-resolved Blade path via `SectionTemplate`.

### Step 5 — Add the section to a page

`GET /admin/pages/{page}/sections` → Add Section  
The dynamic editor appears automatically with the defined fields.  
Admin fills in content per language. `SectionTranslation` records are saved on submit.

---

## 13. Using `$fields` in Blade Views

Inside a definition-driven Blade view, the `$fields` variable contains resolved field values for the current locale. Shared and translatable fields are accessed identically.

```blade
{{-- text / textarea / richtext / url --}}
{{ $fields['title'] ?? '' }}
{!! $fields['body_html'] ?? '' !!}   {{-- richtext: output raw HTML --}}

{{-- media (returns the stored path string) --}}
<img src="{{ $fields['hero_image'] ?? '' }}" alt="">

{{-- boolean --}}
@if (!empty($fields['show_cta']))
    <a href="{{ $fields['cta_url'] ?? '#' }}">{{ $fields['cta_label'] ?? '' }}</a>
@endif

{{-- select --}}
<div class="text-{{ $fields['alignment'] ?? 'center' }}">

{{-- repeater --}}
@foreach ($fields['items'] ?? [] as $item)
    <div class="card">
        <h3>{{ $item['title'] ?? '' }}</h3>
        <p>{{ $item['desc'] ?? '' }}</p>
    </div>
@endforeach
```

**Always use `?? ''` or `?? []`** — a field may be absent from `$fields` if the section was saved before the field was added to the definition.

---

## 14. Repeater Fields

A `repeater` field holds an ordered list of structured items. Its structure is defined by `item_schema` on the `SectionDefinitionField`.

### Item schema shape (stored in `schema` column as JSON)

```json
{
  "item_schema": [
    {
      "key":          "title",
      "label":        "Card Title",
      "type":         "text",
      "required":     true,
      "translatable": true
    },
    {
      "key":          "icon",
      "label":        "Icon Class",
      "type":         "text",
      "required":     false,
      "translatable": false
    },
    {
      "key":          "color",
      "label":        "Background Color",
      "type":         "select",
      "required":     false,
      "translatable": false,
      "options":      "blue|Blue\nred|Red\ngreen|Green"
    }
  ]
}
```

### V1 allowed sub-field types

`repeaterSubFieldTypes()` restricts sub-fields to:  
`text`, `textarea`, `url`, `media`, `boolean`, `select`

Intentionally excluded: `repeater` (no nested repeaters in V1), `richtext` (complex editor dependencies), `number`.

### Malformed row handling

Both `repeaterItemSchema()` (model accessor) and `normalizeItemSchemaForPersistence()` silently drop any item with an empty `key` or an unrecognized `type`. If all items are dropped on a repeater field, a `LogicException` is thrown and surfaced as a validation error — not a 500 page.

```php
} catch (\LogicException $e) {
    return back()->withInput()->withErrors(['item_schema' => $e->getMessage()]);
}
```

---

## 15. Authorization

Both `SectionDefinitionPolicy` and `SectionDefinitionFieldPolicy` extend `ModelPolicy.__call()`. `super_admin` users bypass all checks via `Gate::before()` in `AppServiceProvider`. Authorization is enforced at two levels — **FormRequest** and **controller** — both must pass independently.

### SectionDefinitionPolicy → `sectiondefinitions.*`

| Action | Ability | Role slug |
|--------|---------|-----------|
| `index()` | `viewAny, SectionDefinition::class` | `sectiondefinitions.view` |
| `create()` / `store()` | `create, SectionDefinition::class` | `sectiondefinitions.create` |
| `edit()` / `update()` | `update, $definition` | `sectiondefinitions.update` |
| `destroy()` | `delete, $definition` | `sectiondefinitions.delete` |

### SectionDefinitionFieldPolicy → `sectiondefinitionfields.*`

| Action | Ability | Role slug |
|--------|---------|-----------|
| `index()` | `viewAny, SectionDefinitionField::class` | `sectiondefinitionfields.view` |
| `create()` / `store()` | `create, SectionDefinitionField::class` | `sectiondefinitionfields.create` |
| `edit()` / `update()` | `update, $field` | `sectiondefinitionfields.update` |
| `reorder()` | `update, SectionDefinitionField::class` | `sectiondefinitionfields.update` (class-level) |
| `destroy()` | `delete, $field` | `sectiondefinitionfields.delete` |

---

## 16. API Endpoints

### SectionDefinition — prefix: `/admin/section-definitions`

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/` | `section_definitions.index` | List all definitions |
| GET | `/create` | `section_definitions.create` | Create form |
| POST | `/` | `section_definitions.store` | Store new definition |
| GET | `/{id}/edit` | `section_definitions.edit` | Edit form + Blade Editor |
| PUT/PATCH | `/{id}` | `section_definitions.update` | Update definition |
| DELETE | `/{id}` | `section_definitions.destroy` | Hard-delete + cascade |
| POST | `/{id}/write-blade` | `section_definitions.write-blade` | Write `blade_source` to disk |
| GET | `/export` | `section_definitions.export` | Export all as JSON |
| POST | `/export-selected` | `section_definitions.export-selected` | Export selected IDs as JSON |
| GET | `/import` | `section_definitions.import` | Import form |
| POST | `/import/preview` | `section_definitions.import.preview` | Preview import diff |
| POST | `/import/apply` | `section_definitions.import.apply` | Apply import |

### SectionDefinitionField — prefix: `/admin/section-definitions/{id}/fields`

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/` | `section_definitions.fields.index` | List fields (grouped by `group_name`) |
| GET | `/create` | `section_definitions.fields.create` | Create form |
| POST | `/` | `section_definitions.fields.store` | Store new field |
| GET | `/{field}/edit` | `section_definitions.fields.edit` | Edit form |
| PUT/PATCH | `/{field}` | `section_definitions.fields.update` | Update field |
| DELETE | `/{field}` | `section_definitions.fields.destroy` | Delete field |
| POST | `/reorder` | `section_definitions.fields.reorder` | Persist sort order |

---

## 17. Controller Design Patterns

### Ownership guard

All field write operations call `ensureFieldBelongsToDefinition()` before proceeding. Prevents cross-definition tampering even with manually crafted URLs:

```php
abort_unless((int) $field->section_definition_id === (int) $sectionDefinition->id, 404);
```

### Template sync

`store()` and `update()` call `syncTemplateSelection()`, which validates the `template_key` against `SectionTemplateRegistry`, does a `firstOrCreate` on `section_templates`, then syncs the pivot:

```php
$sectionDefinition->templates()->sync([$template->id => ['sort_order' => 0]]);
// Passing an empty key clears the relation:
$sectionDefinition->templates()->sync([]);
```

### Destroy cascade

`destroy()` runs inside `DB::transaction()` and removes in order:
1. `SectionTranslation` rows for all linked section instances
2. The linked `Section` rows
3. All `SectionDefinitionField` rows — via `each(fn($f) => $f->delete())` to fire model events
4. Template pivot rows (`templates()->detach()`)
5. The `SectionDefinition` record itself

**Not touched:** Blade files on disk, Media records, uploaded files, config entries.

### Redirect after save

`store()` redirects to the fields management screen (`after_save=fields`).  
Subsequent `update()` calls redirect back to the edit form (`after_save=edit`).

---

## 18. FormRequest Design

### StoreSectionDefinitionRequest / UpdateSectionDefinitionRequest

`prepareForValidation()` normalizes input before rules run:

- `section_key` — lowercased, trimmed; must match `/^[a-z0-9_-]+$/`; unique in `section_definitions.section_key` (with `ignore` on update)
- `template_key` — lowercased, trimmed; validated against `SectionTemplateRegistry::isValidTemplateKey()`
- `editor_mode` — always forced to `EDITOR_MODE_DYNAMIC` regardless of input
- `is_active` / `is_visible_in_library` — parsed via `$request->boolean()`

### StoreSectionDefinitionFieldRequest / UpdateSectionDefinitionFieldRequest

Peeks at `$request->input('type')` before building rules to conditionally require `item_schema` when the field type is `repeater`:

```php
$isRepeater = $this->input('type') === SectionDefinitionField::FIELD_TYPE_REPEATER;

'item_schema'        => $isRepeater ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
'item_schema.*.key'  => $isRepeater ? ['required', 'string', 'regex:/^[a-z0-9_]+$/'] : ['nullable'],
'item_schema.*.type' => $isRepeater ? ['required', Rule::in(repeaterSubFieldTypes())] : ['nullable'],
```

---

## 19. SectionDefinitionFieldFormDataFactory

Keeps locale-aware form shaping out of controllers and views.

### `build(SectionDefinitionField $field, array $locales): array`

Returns view-ready data including:
- `fieldTypeOptions` — type → label map
- `selectedFieldType` — from `old('type')` or saved type
- `sharedDefaultValue` / `translatableDefaultValues` — shaped per locale
- `optionsTextarea` — select options serialized as `value|label` per line
- `validationRulesTextarea` — one rule per line
- `settingsJson` — pretty-printed JSON string
- `repeaterItemSchema` — from `old('item_schema')` or from model
- `repeaterSubFieldTypeOptions` — filtered options for repeater sub-fields

### `persistableAttributes(array $validated, array $localeCodes): array`

Maps the validated payload to exact column values for `create()`/`update()`:
- `field_scope` derived from `is_translatable` boolean
- `default_value` shaped as `{"value": ...}` (shared) or `{"ar": ..., "en": ...}` (translatable)
- `options` parsed from `value|label` textarea lines
- `settings` decoded from JSON textarea
- `validation_rules` split from newline-separated textarea
- `schema` produced by `normalizeItemSchemaForPersistence()` (null for non-repeater)

---

## 20. File Map

```
app/
├── Models/Sections/
│   ├── SectionDefinition.php                  Model — fillable, casts, relations, blade_source
│   ├── SectionDefinitionField.php             Model — field types, repeater schema, scopes
│   └── SectionTemplate.php                    Model — template_key → view pivot
│
├── Support/Sections/
│   ├── SectionRenderer.php                    Entry point for frontend rendering
│   ├── SectionTemplateRegistry.php            Maps template_key → Blade view path
│   ├── SectionTemplateFileWriter.php          Writes blade_source to disk
│   ├── SectionDefinitionRuntimeResolver.php   Runtime checks (active? has template?)
│   ├── SectionDefinitionFrontendViewDataFactory.php  Builds $fields for Blade views
│   ├── DynamicSectionEditorRenderer.php       Builds admin dynamic editor payload
│   ├── SectionDefinitionFieldFormDataFactory.php     Form data + persistence normalization
│   └── SectionDefinitionLocaleProvider.php    Active locale list for field forms
│
├── Http/Controllers/Admin/
│   ├── SectionDefinitionController.php        CRUD + writeBlade endpoint
│   ├── SectionDefinitionFieldController.php   CRUD + reorder
│   └── SectionDefinitionImportExportController.php  JSON import/export
│
├── Http/Requests/Admin/
│   ├── StoreSectionDefinitionRequest.php
│   ├── UpdateSectionDefinitionRequest.php
│   ├── StoreSectionDefinitionFieldRequest.php
│   └── UpdateSectionDefinitionFieldRequest.php
│
├── Policies/
│   ├── SectionDefinitionPolicy.php
│   └── SectionDefinitionFieldPolicy.php

config/
└── sections.php                               icon_library + template_registry

resources/views/
├── dashboard/section_definitions/
│   ├── index.blade.php                        Definition listing
│   ├── create.blade.php / edit.blade.php      Wrappers
│   ├── form.blade.php                         Shared definition form + Monaco editor
│   └── fields/
│       ├── index.blade.php                    Field listing + reorder UI
│       ├── create.blade.php / edit.blade.php  Wrappers
│       ├── form.blade.php                     Shared field form
│       └── partials/
│           └── repeater-item-schema-editor.blade.php

└── front/sections/                            Blade files written from admin panel
    ├── hero/hero_main.blade.php
    ├── services/services_grid.blade.php
    └── {category}/{section_key}.blade.php

database/migrations/
├── 2026_04_11_000001_create_section_definitions_table.php
├── 2026_04_11_000002_create_section_definition_fields_table.php
├── 2026_04_11_000004_create_section_definition_template_table.php
├── 2026_04_11_000005_add_builder_columns_to_section_definition_fields_table.php
├── 2026_04_18_000002_add_preview_media_id_to_section_definitions_table.php
└── 2026_04_27_000001_normalize_section_definition_editor_mode_to_dynamic.php
```

---

## 21. Developer Patterns

### Runtime availability check

Always guard before using definition tables in code that may run before migrations:

```php
if (! app(SectionDefinitionRuntimeResolver::class)->runtimeTablesAvailable()) {
    return null; // tables not yet migrated — graceful fallback
}
```

### $languages must be passed to every tab view

Any controller method rendering a view with language tabs must pass `$languages`. Missing it causes a silent 500 error:

```php
$languages = Language::where('is_active', true)->orderBy('id')->get();
return view('dashboard.section_definitions.create', compact('languages'));
```

---

## 22. FAQ

**Q: Why is `blade_source` sent as base64?**  
A: The production server runs ModSecurity WAF, which blocks POST bodies containing raw PHP syntax (`<?php`, `->`, `{{`). The entire Blade source is base64-encoded in JavaScript before the request and decoded in PHP before writing to disk.

**Q: What is the difference between `section_key` and `template_key`?**  
A: `section_key` identifies the `SectionDefinition` record in the database. `template_key` is used by `SectionTemplateRegistry` to resolve the Blade view file path. They are typically identical, but the separation allows a definition to be renamed without breaking template references, or to have multiple template variants.

**Q: When does the legacy renderer run instead of definition-driven?**  
A: When `section.section_definition_id` is null, when the linked definition has `is_active = false`, or when the definition has no associated `template_key`. `SectionRenderer` falls back to `LEGACY_FRONTEND_SECTION_TYPES`.

**Q: Can I edit the Blade file directly on the server?**  
A: Yes. If the file exists on disk but `blade_source` in the DB is null, `fileStatus()` returns `external`. The render works normally, but the admin panel shows a warning that the file is outside panel control and cannot be edited from Monaco.

**Q: What happens if I delete a definition?**  
A: `destroy()` runs a full cascade inside a DB transaction: removes all linked `Section` and `SectionTranslation` rows, all `SectionDefinitionField` rows, pivot records, and the definition itself. There is **no soft delete** — recovery is impossible. The Blade file on disk is not deleted.

**Q: What if a definition exists but no Blade file has been written?**  
A: `fileStatus()` returns `missing`. The section falls back to `_missing-template.blade.php` and renders a placeholder. Write the Blade file before deploying the section to a live page.

---

## 23. Known Gaps

Features that are incomplete, deferred, or not yet wired up:

| Gap | Details |
|-----|---------|
| No SoftDeletes | `SectionDefinition` and `SectionDefinitionField` are permanently deleted. Consider adding `deleted_at` if accidental deletion is a risk. |
| `forceDelete()` not overridden | Dependent on the above. If SoftDeletes were added, a `forceDelete()` would not cascade correctly without an explicit override. |
| `validation_rules` not applied at runtime | The column is stored and normalized but no runtime layer reads these rules when saving section content. |
| Repeater content editor incomplete | `repeater` fields can be defined and stored, but the dynamic editor UI does not yet render them for content editing (Phase 5B deferred). |

---

## 24. Technical Notes

Architectural decisions that are intentional — not bugs or gaps:

| Note | Details |
|------|---------|
| `reorder()` uses class-level auth | `authorize('update', SectionDefinitionField::class)` checks without an instance. Functionally correct given `ModelPolicy` (`super_admin` bypasses all; role slug `sectiondefinitionfields.update` covers the action), but inconsistent with the instance-based pattern used elsewhere. |
| `custom_preset` fully removed | The migration `2026_04_27` irreversibly normalized all `editor_mode` rows to `dynamic`. The `custom_editor_key` column is retained for DB compatibility only. There is no runtime path that reads or activates `custom_preset`. |
| `destroy()` does not delete Blade files | Deleting a definition removes all DB records but leaves the Blade file on disk. This is intentional: the file may be shared across environments or referenced by git. Manual cleanup is required. |

---

## 25. Evolution

How the section system evolved — each phase added expressiveness while keeping backward compatibility:

```
Phase 1 — Legacy Sections
  section_type string (e.g. "hero_main")
  Hardcoded Blade views in SectionRegistry
  Adding a type = code change + redeploy

        ↓  (need for flexibility without redeploy)

Phase 2 — Section Registry
  SectionRegistry::register() moved type-to-view mapping to config
  Still code-side: developers register types, editors cannot add new ones

        ↓  (need for non-developer section creation)

Phase 3 — Section Definitions  [April 2026]
  SectionDefinition as a DB record — section types created from admin panel
  SectionDefinitionField describes each field: type, scope, options
  SectionDefinitionRuntimeResolver + SectionDefinitionFrontendViewDataFactory
  added to SectionRenderer as the preferred render path

        ↓  (need to edit Blade templates without server access)

Phase 4 — Blade Editor  [April 2026]
  blade_source stored in DB
  SectionTemplateFileWriter writes to disk from the browser
  Monaco Editor in the admin panel
  base64 encoding to bypass ModSecurity WAF

        ↓  (need for structured content editing per field)

Phase 5A — Dynamic Editor (schema foundation)  [May 2026]
  editor_mode = dynamic; all rows normalized, custom_preset removed
  DynamicSectionEditorRenderer builds field-by-field editor payload
  Repeater field type added at schema level (item_schema stored)

  [Phase 5B — Repeater content editor UI — deferred]
```

---

## 26. Changelog

| Date | Change |
|------|--------|
| 2026-06-15 | Merged `section-definitions.md` + `section-definitions-system.md` into this unified reference. Added `blade_source` / `blade_written_at` columns to data model table. |
| 2026-05-09 | `'edit'` ability renamed to `'update'` throughout. `try/catch(LogicException)` added to field controller for repeater schema errors. `fields()->delete()` replaced with `each(fn($f) => $f->delete())` to preserve model events. `@can` directives added to all action elements. |
| 2026-04-27 | All `editor_mode` rows normalized to `dynamic`. `custom_editor_key` retained for compatibility. |
| 2026-04-18 | `preview_media_id` FK added to `section_definitions` with `nullOnDelete`. |
| 2026-04-11 | Initial creation: `section_definitions`, `section_definition_fields`, `section_definition_template` tables. Repeater field type (Phase 5A schema foundation). |

---

## 27. Quick Reference

| Task | Location |
|------|----------|
| Create a new section type | `GET /admin/section-definitions/create` |
| Add / edit fields | `GET /admin/section-definitions/{id}/fields` |
| Write the Blade template | `GET /admin/section-definitions/{id}/edit` → Blade Editor tab |
| Frontend render entry point | `App\Support\Sections\SectionRenderer::render()` |
| Template → Blade view resolution | `App\Support\Sections\SectionTemplateRegistry` |
| Write Blade to disk | `App\Support\Sections\SectionTemplateFileWriter` |
| Runtime checks (active? has template?) | `App\Support\Sections\SectionDefinitionRuntimeResolver` |
| Build $fields for Blade views | `App\Support\Sections\SectionDefinitionFrontendViewDataFactory` |
| Admin dynamic editor payload | `App\Support\Sections\DynamicSectionEditorRenderer` |
| Registered templates config | `config/sections.php` → `template_registry.templates` |
| Further architecture details | `app/Support/Sections/DeveloperSectionManagementArchitecture.php` |
| Blade Editor full workflow | `docs/08-section-blade-editor.md` |
