# Section Definition Source-of-Truth Policy

> Policy: use the database as the source of truth for reusable section definition metadata and library exposure, and use code/config as the source of truth for runtime wiring.

## Current Model Overview

`SectionDefinition` is now the primary source of truth for reusable section metadata in admin. For definition-backed sections, admin/library labels, descriptions, categories, preview media, visibility, and ordering should be treated as definition-driven first.

`Section` still owns page-level structural state, and `SectionTranslation.content` remains the source of truth for the actual localized content of each section instance.

The section library is now DB-first. Active, visible `SectionDefinition` records populate library cards first, and legacy config-backed section types fill the remaining gaps as fallback.

`config/sections.php` is still the technical registry for template registration, custom preset registration, icon options, and legacy fallback metadata. Template/view resolution is still code-side.

## What Is DB-Managed

- `section_key`
- `label`
- `description`
- `category`
- `editor_mode`
- `custom_editor_key` selection
- selected `template_key` reference
- preview media
- `is_active` and `is_visible`
- `sort_order`
- `SectionDefinitionField` records
- field ordering, scopes, defaults, options, and required flags
- section library appearance for DB-backed definitions
- definition-first admin labels and secondary metadata for linked section instances
- actual per-page, per-locale section instance content in `SectionTranslation.content`

## What Is Still Code/Config-Managed

- `template_key` -> Blade view mapping
- which `template_key` values are registered at all
- `custom_editor_key` -> custom preset editor implementation
- icon library registry
- legacy fallback section types
- legacy renderer fallback and type alias handling
- dynamic runtime enrichment and other remaining runtime logic tied to type
- any remaining normalization logic tied to a named legacy/static type

## Normal Dynamic Section Workflow

1. Create a `SectionDefinition` in the dashboard.
2. Keep `editor_mode` as `dynamic`.
3. Choose an already-registered `template_key`.
4. Save the definition.
5. Add and order `SectionDefinitionField` records.
6. Set preview media if needed.
7. Mark the definition active and visible in the library.
8. Open a page’s section library and add the section from there.
9. Edit the created section instance; its authored content is stored in `SectionTranslation.content`.

## When Dashboard Alone Is Enough

The dashboard is enough when the section is a normal dynamic section that can use an already-registered `template_key` and the existing dynamic field system.

Examples:

- adding a new normal dynamic section using an already registered `template_key`
- editing definition metadata
- changing the preview image
- adjusting fields, defaults, options, scopes, or sort order
- activating/deactivating a definition
- showing/hiding a definition in the section library
- creating and editing section instances from the section library after the definition is in place

## When Code Changes Are Still Required

Code changes are still required when the request affects runtime wiring rather than definition metadata.

Examples:

- a new frontend template/view
- a new `template_key` not present in the registry
- a new custom preset editor
- a new `custom_editor_key` not present in the registry
- custom runtime rendering behavior
- new live query/resolver behavior
- new normalization logic for a legacy/static type
- a new field type or editor behavior not supported by the current dynamic field system

## Do / Don’t

- DO create normal reusable dynamic sections from the dashboard when the `template_key` already exists.
- DO treat `SectionDefinition` as the primary admin/library metadata source for definition-backed sections.
- DO treat `SectionTranslation.content` as the source of actual section instance content.
- DO remember that the database stores stable references such as `template_key` and `custom_editor_key`, not their implementations.
- DON’T assume the database stores Blade paths.
- DON’T assume changing a definition rewrites old section instance content.
- DON’T use `custom_preset` mode unless the matching code-side preset already exists.
- DON’T assume legacy config-based section types are gone; they still exist as fallback.
- DON’T expect a new runtime behavior to appear just because a definition record exists.

## Practical Summary

The current rule is simple: the database now owns reusable section definition metadata and section-library exposure, but code still owns template resolution, preset implementation, and remaining type-specific runtime behavior.
