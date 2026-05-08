# Section Definitions System

## Overview

The section-definitions system is a developer-facing registry of reusable section
blueprints (called "definitions"). Each definition describes the metadata and field
schema for one type of content section. Definitions do not hold page-level content —
they describe the *shape* of content that `Section` instances will eventually store.

The system has two nested resource layers:

- **SectionDefinition** — the blueprint record (key, label, category, template link,
  active/visible flags).
- **SectionDefinitionField** — the per-field schema entries belonging to one definition
  (key, type, scope, default value, options, sort order, repeater item_schema).

---

## Core Files

| File | Role |
|------|------|
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | CRUD for definitions (6 endpoints) |
| `app/Http/Controllers/Admin/SectionDefinitionFieldController.php` | CRUD + reorder for fields (7 endpoints) |
| `app/Http/Requests/Admin/StoreSectionDefinitionRequest.php` | Validation + authorization for store |
| `app/Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | Validation + authorization for update |
| `app/Http/Requests/Admin/StoreSectionDefinitionFieldRequest.php` | Validation + authorization for field store |
| `app/Http/Requests/Admin/UpdateSectionDefinitionFieldRequest.php` | Validation + authorization for field update |
| `app/Models/Sections/SectionDefinition.php` | Eloquent model — fillable, casts, relations |
| `app/Models/Sections/SectionDefinitionField.php` | Eloquent model — field types, repeater schema |
| `app/Policies/SectionDefinitionPolicy.php` | Delegates to `ModelPolicy.__call()` |
| `app/Policies/SectionDefinitionFieldPolicy.php` | Delegates to `ModelPolicy.__call()` |
| `app/Support/Sections/SectionDefinitionFieldFormDataFactory.php` | Build + normalize form data and persistable attributes |
| `app/Support/Sections/SectionDefinitionLocaleProvider.php` | Provides active locale list for field form |
| `resources/views/dashboard/section_definitions/index.blade.php` | Definition listing |
| `resources/views/dashboard/section_definitions/create.blade.php` | Create form wrapper |
| `resources/views/dashboard/section_definitions/edit.blade.php` | Edit form wrapper |
| `resources/views/dashboard/section_definitions/form.blade.php` | Shared definition form partial |
| `resources/views/dashboard/section_definitions/fields/index.blade.php` | Field listing + reorder |
| `resources/views/dashboard/section_definitions/fields/create.blade.php` | Field create form wrapper |
| `resources/views/dashboard/section_definitions/fields/edit.blade.php` | Field edit form wrapper |
| `resources/views/dashboard/section_definitions/fields/form.blade.php` | Shared field form partial |
| `database/migrations/2026_04_11_000001_create_section_definitions_table.php` | Base definitions schema |
| `database/migrations/2026_04_11_000002_create_section_definition_fields_table.php` | Base fields schema |
| `database/migrations/2026_04_11_000004_create_section_definition_template_table.php` | Pivot: definition ↔ template |
| `database/migrations/2026_04_11_000005_add_builder_columns_to_section_definition_fields_table.php` | Adds `group_name`, `validation_rules` |
| `database/migrations/2026_04_18_000002_add_preview_media_id_to_section_definitions_table.php` | Adds `preview_media_id` FK |
| `database/migrations/2026_04_27_000001_normalize_section_definition_editor_mode_to_dynamic.php` | Normalizes all rows to `editor_mode = dynamic` |

---

## Data Model

### `section_definitions` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `section_key` | varchar unique | Stable developer identifier e.g. `hero_split` |
| `label` | varchar | Admin-facing display name |
| `description` | text nullable | Internal maintainer notes |
| `category` | varchar nullable | Grouping key for admin lists |
| `editor_mode` | varchar | Always `dynamic` (normalized by migration) |
| `custom_editor_key` | varchar nullable | Deprecated legacy field; kept for compatibility |
| `preview_media_id` | bigint nullable FK → `media.id` | Thumbnail used in library cards (`nullOnDelete`) |
| `settings` | json nullable | Editor/runtime metadata; no render logic |
| `schema` | json nullable | Reserved for future tooling |
| `is_active` | boolean | Inactive definitions are stored but not offered |
| `is_visible` | boolean | Controls visibility in admin library UIs |
| `sort_order` | unsigned int | Display order |

### `section_definition_fields` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `section_definition_id` | bigint FK `cascadeOnDelete` | Parent definition |
| `field_key` | varchar | Stable per-definition field identifier (unique per definition) |
| `label` | varchar | Admin-facing label |
| `group_name` | varchar nullable | Dashboard grouping label |
| `help_text` | text nullable | Admin help copy |
| `field_type` | varchar | One of the supported types (see below) |
| `field_scope` | varchar | `shared` or `translatable` |
| `default_value` | json nullable | `{"value": ...}` for shared; `{"ar": ..., "en": ...}` for translatable |
| `options` | json nullable | `[{"value": "...", "label": "..."}]` array for select fields |
| `settings` | json nullable | Editor behavior metadata |
| `schema` | json nullable | For repeater: `{"item_schema": [...]}` |
| `is_required` | boolean | Whether the admin UI requires a value |
| `is_active` | boolean | Inactive fields stay stored for compatibility |
| `validation_rules` | json nullable | Array of Laravel rule strings for future validation |
| `sort_order` | unsigned int | Display order within the definition |

---

## Supported Field Types

Defined as constants on `SectionDefinitionField` and as the single source-of-truth
for validation via `supportedFieldTypes()`:

| Constant | Value | Notes |
|----------|-------|-------|
| `FIELD_TYPE_TEXT` | `text` | Single-line text |
| `FIELD_TYPE_TEXTAREA` | `textarea` | Multi-line text |
| `FIELD_TYPE_RICHTEXT` | `richtext` | HTML rich-text editor |
| `FIELD_TYPE_URL` | `url` | URL string |
| `FIELD_TYPE_MEDIA` | `media` | Media library reference |
| `FIELD_TYPE_NUMBER` | `number` | Numeric value |
| `FIELD_TYPE_BOOLEAN` | `boolean` | True/false toggle |
| `FIELD_TYPE_SELECT` | `select` | Dropdown from `options` array |
| `FIELD_TYPE_REPEATER` | `repeater` | Ordered list of structured items (see below) |

Adding a type to `supportedFieldTypes()` automatically unlocks it in validation and
the admin form select; no other changes are needed.

---

## Authorization

Both policies extend `ModelPolicy.__call()`. Ability slugs follow the
`Str::plural(Str::lower(class_basename))` pattern:

### SectionDefinitionPolicy → `sectiondefinitions.*`

| Controller method | Authorize call | Role slug |
|-------------------|---------------|-----------|
| `index()` | `viewAny, SectionDefinition::class` | `sectiondefinitions.view` |
| `create()` / `store()` | `create, SectionDefinition::class` | `sectiondefinitions.create` |
| `edit()` / `update()` | `update, $sectionDefinition` | `sectiondefinitions.update` |
| `destroy()` | `delete, $sectionDefinition` | `sectiondefinitions.delete` |

### SectionDefinitionFieldPolicy → `sectiondefinitionfields.*`

| Controller method | Authorize call | Role slug |
|-------------------|---------------|-----------|
| `index()` | `viewAny, SectionDefinitionField::class` | `sectiondefinitionfields.view` |
| `create()` / `store()` | `create, SectionDefinitionField::class` | `sectiondefinitionfields.create` |
| `edit()` / `update()` | `update, $field` | `sectiondefinitionfields.update` |
| `reorder()` | `update, SectionDefinitionField::class` | `sectiondefinitionfields.update` |
| `destroy()` | `delete, $field` | `sectiondefinitionfields.delete` |

`super_admin` users bypass all checks via `Gate::before()` in `AppServiceProvider`.

Authorization is enforced at two levels: the **FormRequest** (`authorize()`) and
the **controller** (`$this->authorize()`). Both must pass independently.

---

## Endpoints

### SectionDefinition (prefix: `/admin/section-definitions`)

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/` | `section_definitions.index` | List definitions |
| GET | `/create` | `section_definitions.create` | Create form |
| POST | `/` | `section_definitions.store` | Store new definition |
| GET | `/{id}/edit` | `section_definitions.edit` | Edit form |
| PUT/PATCH | `/{id}` | `section_definitions.update` | Update definition |
| DELETE | `/{id}` | `section_definitions.destroy` | Hard-delete definition + linked sections |
| GET | `/export` | `section_definitions.export` | Export all as JSON |
| POST | `/export-selected` | `section_definitions.export-selected` | Export selected IDs as JSON |
| GET | `/import` | `section_definitions.import` | Import form |
| POST | `/import/preview` | `section_definitions.import.preview` | Preview import diff |
| POST | `/import/apply` | `section_definitions.import.apply` | Apply import |

### SectionDefinitionField (prefix: `/admin/section-definitions/{id}/fields`)

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/` | `section_definitions.fields.index` | List fields (grouped) |
| GET | `/create` | `section_definitions.fields.create` | Create form |
| POST | `/` | `section_definitions.fields.store` | Store new field |
| GET | `/{field}/edit` | `section_definitions.fields.edit` | Edit form |
| PUT/PATCH | `/{field}` | `section_definitions.fields.update` | Update field |
| DELETE | `/{field}` | `section_definitions.fields.destroy` | Delete field |
| POST | `/reorder` | `section_definitions.fields.reorder` | Persist sort order changes |

---

## Controller Design

### Ownership Guard

All field endpoints call `ensureFieldBelongsToDefinition()` before any write
operation. This method aborts with 404 if the field's `section_definition_id` does
not match the route-bound `$sectionDefinition`:

```php
abort_unless((int) $field->section_definition_id === (int) $sectionDefinition->id, 404);
```

This prevents cross-definition tampering even when the URL is manually crafted.

### Template Sync (`syncTemplateSelection`)

`store()` and `update()` delegate template association to `syncTemplateSelection()`.
The method validates the provided `template_key` against `SectionTemplateRegistry`,
then does a `firstOrCreate` on the `templates` table and syncs the pivot:

```php
$sectionDefinition->templates()->sync([$template->id => ['sort_order' => 0]]);
```

Passing an empty key clears the relation (`sync([])`).

### Destroy Cascade

`destroy()` runs inside `DB::transaction()` and explicitly removes:

1. `SectionTranslation` rows for all linked section instances
2. The linked `Section` rows themselves
3. All `SectionDefinitionField` rows — individually via `each(fn($f) => $f->delete())`
   to ensure model events fire
4. The template pivot rows (`templates()->detach()`)
5. The `SectionDefinition` record itself

Blade files, Media records, uploaded files, and config entries are intentionally
untouched — the controller only owns DB-stored definition-driven content.

### Redirect After Save (`redirectAfterSave`)

After `store()` or when `after_save=fields` is submitted, the user is redirected to
the field management screen for the saved definition. For subsequent updates
(`after_save=edit`), the redirect returns to the edit form.

---

## FormRequest Design

### StoreSectionDefinitionRequest / UpdateSectionDefinitionRequest

Both requests share the same `prepareForValidation()` normalization:

- `name` — trimmed string
- `key` — lowercased, trimmed; `regex:/^[a-z0-9_-]+$/`; unique in `section_definitions.section_key` (with `ignore` on update)
- `template_key` — lowercased, trimmed; validated against `SectionTemplateRegistry::isValidTemplateKey()`
- `editor_mode` — always forced to `EDITOR_MODE_DYNAMIC` regardless of input
- `is_active` / `is_visible_in_library` — `$request->boolean()`

`UpdateSectionDefinitionRequest::authorize()` fetches the route-bound model and calls
`$this->user()->can('update', $sectionDefinition)`.

### StoreSectionDefinitionFieldRequest / UpdateSectionDefinitionFieldRequest

Both peek at `$request->input('type')` before building rules to conditionally require
`item_schema` when the field type is `repeater`:

```php
$isRepeater = $this->input('type') === SectionDefinitionField::FIELD_TYPE_REPEATER;

'item_schema'       => $isRepeater ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
'item_schema.*.key' => $isRepeater ? ['required', 'string', 'regex:/^[a-z0-9_]+$/'] : ['nullable', ...],
'item_schema.*.type'=> $isRepeater ? ['required', Rule::in(repeaterSubFieldTypes())] : ['nullable', ...],
```

This produces a visible validation error on the `item_schema` field instead of
silently discarding rows in the normalization layer.

---

## SectionDefinitionFieldFormDataFactory

Keeps locale-aware default-value shaping and normalization out of controllers and views.

### `build(SectionDefinitionField $field, array $locales): array`

Returns view-ready form data:
- `fieldTypeOptions` — type → translated label map
- `selectedFieldType` — `old('type')` or saved type
- `sharedDefaultValue` / `translatableDefaultValues` — per-locale or shared default
- `optionsTextarea` — select options serialized as `value|label` per line
- `validationRulesTextarea` — one rule per line
- `settingsJson` — pretty-printed JSON string
- `repeaterItemSchema` — from `old('item_schema')` on failed submission or from model
- `repeaterSubFieldTypeOptions` — filtered type options for repeater sub-fields

### `persistableAttributes(array $validated, array $localeCodes): array`

Maps the validated form payload to the exact column set for `create()`/`update()`.
Key normalizations:

- `field_scope` derived from `is_translatable` boolean
- `default_value` shaped as `{"value": ...}` (shared) or `{"ar": ..., "en": ...}` (translatable)
- `options` parsed from `value|label` textarea lines into `[{"value": "...", "label": "..."}]`
- `settings` decoded from JSON textarea
- `validation_rules` split into array from newline-separated textarea
- `schema` produced by `normalizeItemSchemaForPersistence()` (null for non-repeater)

---

## Repeater Field Type

A repeater field holds an ordered list of structured items defined by `item_schema`.

### Item Schema Shape

Each entry in `item_schema` is guaranteed to have this shape after normalization:

```json
{
  "key":          "title",
  "label":        "Title",
  "type":         "text",
  "required":     false,
  "translatable": true,
  "options":      "value|label\n..."
}
```

The `options` key is only present for `select` sub-fields.

### V1 Allowed Sub-Field Types

`repeaterSubFieldTypes()` restricts sub-fields to:
`text`, `textarea`, `url`, `media`, `boolean`, `select`

Intentionally excluded: `repeater` (no nested repeaters in V1), `richtext` (complex
editor dependencies), `number` (not yet needed by any planned V1 use case).

### Malformed Row Handling

Both the model accessor (`repeaterItemSchema()`) and the persistence normalizer
(`normalizeItemSchemaForPersistence()`) silently drop any item that has an empty
`key` or an unrecognized `type`. This ensures stored data can never break the system
even if `schema` was written directly or by a seeder.

If all items are dropped and the field type is `repeater`, a `LogicException` is
thrown. In controllers, this is caught and surfaced as a validation error on the
`item_schema` field (not a 500 page):

```php
} catch (\LogicException $e) {
    return back()->withInput()->withErrors(['item_schema' => $e->getMessage()]);
}
```

### `getItemSchema()` Alias

`getItemSchema()` on the model delegates to `repeaterItemSchema()` for backward
compatibility with Phase 5A/5B/5C callers.

---

## Known Gaps / Future Work

- **No SoftDeletes** — `SectionDefinition` and `SectionDefinitionField` are permanently
  deleted. The `destroy()` confirmation dialog counts linked section instances, but
  recovery is impossible once the operation runs. Consider adding `deleted_at` columns
  and `SoftDeletes` if accidental deletion is a concern.
- **`forceDelete()` not overridden** — if SoftDeletes were added later, a
  `forceDelete()` on `SectionDefinition` would not cascade to fields or sections
  because those relationships use DB-level `cascadeOnDelete` (fields) and manual
  transaction cleanup (sections). An override would be required.
- **`reorder()` uses class-level authorization** — `$this->authorize('update', SectionDefinitionField::class)`
  checks the ability without an instance. This is functionally correct given
  `ModelPolicy.__call()` does not use the model argument, but it differs from the
  instance-based checks used elsewhere.
- **Repeater Phase 5B deferred** — the dynamic editor rendering panel for repeater
  fields is not yet implemented. Fields of type `repeater` can be defined and stored,
  but the content editor does not yet render them.
- **`validation_rules` column not yet applied at runtime** — the column is stored and
  normalized, but no runtime layer reads these rules to validate content saves.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-09 | `'edit'` ability renamed to `'update'` throughout — controllers, FormRequests, and policy docblocks — to align with the rest of the codebase. `try/catch(\LogicException)` added to `SectionDefinitionFieldController::store()` and `update()` to surface repeater schema errors as validation messages instead of 500 pages. `fields()->delete()` in `SectionDefinitionController::destroy()` replaced with `each(fn($f) => $f->delete())` to preserve model event dispatch. `@can` directives added to all action elements in `index.blade.php` and `fields/index.blade.php`. |
| 2026-04-27 | All `section_definitions.editor_mode` rows normalized to `dynamic`; `custom_editor_key` column retained for compatibility. |
| 2026-04-18 | `preview_media_id` FK added to `section_definitions` with `nullOnDelete`. |
| 2026-04-11 | Initial creation: `section_definitions`, `section_definition_fields`, `section_definition_template` tables; `group_name` and `validation_rules` builder columns added; repeater field type (Phase 5A schema foundation). |
