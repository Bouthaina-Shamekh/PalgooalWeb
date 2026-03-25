# Sections System

## Purpose

This document explains how the structured page sections system works today.

Use it when you need to:

- understand how sections are stored
- add or change a section type
- trace data from admin input to frontend output
- avoid breaking existing section content contracts

## What A Section Is

A section is a typed, ordered content block that belongs to a page.

Each section has two layers of data:

- structural data on `Section`
- localized content on `SectionTranslation`

This is the core mental model for the system.

## Core Models

### Page

Role:

- owns many sections
- owns many translations
- decides which builder mode is active

Important implications:

- sections do not exist independently of pages
- any section workflow is page-scoped

### Section

Role:

- defines the section type
- stores shared state across locales
- controls ordering and visibility

Important fields:

- `page_id`
- `type`
- `variant`
- `style`
- `order`
- `is_active`

### SectionTranslation

Role:

- stores locale-specific title
- stores locale-specific JSON content payload

Important fields:

- `section_id`
- `locale`
- `title`
- `content`

Production warning:

- The `content` payload is not schema-less in practice. It is type-specific and functions like an implicit contract.

## Data Model Example

Example conceptual split:

### Structural Record

```php
[
    'page_id' => 15,
    'type' => 'programming_showcase',
    'variant' => null,
    'order' => 3,
    'is_active' => true,
]
```

### Localized Content Record

```json
{
    "brand_prefix": "PAL",
    "brand_suffix": "GOALS",
    "title": "PROGRAMMING",
    "description": "The Programming Department is the core of our web development company.",
    "outputs_heading": "What Are Our Outputs?",
    "outputs": [{ "text": "Landing Sites" }, { "text": "E-Commerce Stores" }],
    "primary_button": {
        "label": "Send Your idea",
        "url": "#",
        "new_tab": false
    }
}
```

## Section Lifecycle

### 1. Create

Admin users create a section for a page through the sections workspace.

Typical flow:

1. The page is opened in the admin workspace.
2. A section type is selected.
3. `SectionController` creates the structural `Section` row.
4. Default translations and starter content are created for active locales.

Real-world example:

- Quick-adding `hero_default` creates a section and seeds title, subtitle, button, and media defaults so the section is not visually empty.

Edge case:

- The set of active locales can change over time.
- New sections use currently active locales, but old sections may not have translations for every later-added language.

### 2. Edit

Editing is supported through two admin surfaces:

- standalone edit page
- inline workspace sidebar editor

Both use the same shared form partial and the same persistence logic.

### 3. Normalize On Save

The controller normalizes input by section type before persistence.

Examples of save-time concerns already handled in code:

- shared content synchronization across locales for selected types
- scalar cleanup
- array and repeater normalization
- button normalization
- media field normalization

This is why saved content is usually cleaner and more stable than raw request input.

Production warning:

- If you change request input names or normalization rules, you are changing the persisted contract for future saves.
- Existing section records may still use older shapes, so backward compatibility matters.

### 4. Render On Frontend

Sections can contribute to page rendering through multiple page rendering paths, but the section-based path typically works like this:

1. load active sections in page order
2. choose the best translation for the current locale
3. read localized `content`
4. enrich with dynamic data if needed
5. render the Blade component or partial

## Supported Section Types

The admin controller currently exposes these section types:

- `hero_default`
- `hero_minimal`
- `hero_campaign`
- `programming_showcase`
- `mobile_app_showcase`
- `how_we_build`
- `design_showcase`
- `digital_marketing_showcase`
- `tech_stack_showcase`
- `reviews_showcase`
- `our_work_showcase`
- `hosting_pricing_showcase`
- `domains_showcase`
- `templates_slider_showcase`
- `templates_listing_showcase`
- `features_grid`
- `services_grid`
- `templates_showcase`

Important note:

- These are currently defined in controller metadata, not in a single dedicated section-type domain object.

## Content Patterns By Section Family

### Simple Scalar Sections

Examples:

- `hero_minimal`
- `features_grid`

Typical fields:

- title
- subtitle
- description
- simple button

### Media-Heavy Sections

Examples:

- `hero_default`
- `mobile_app_showcase`
- `design_showcase`
- `tech_stack_showcase`

Typical concerns:

- media IDs versus asset paths
- preview URL generation
- gallery ordering

### Repeater-Heavy Sections

Examples:

- `hero_campaign`
- `programming_showcase`
- `design_showcase`
- `how_we_build`
- `hosting_pricing_showcase`

Typical concerns:

- nested arrays
- blank row filtering
- icon source handling
- boolean coercion

### Database-Enriched Sections

Examples:

- `reviews_showcase`
- `our_work_showcase`
- `hosting_pricing_showcase`
- `templates_slider_showcase`
- `templates_listing_showcase`

Typical concerns:

- editor content stores labels and filters
- runtime resolver loads actual records

## Dynamic Data Enrichment

The main class for dynamic section enrichment in frontend flows is:

- `app/Support/Sections/SectionQueryResolver.php`

Examples of current behavior:

- `reviews_showcase` injects approved testimonials
- `our_work_showcase` injects portfolios
- `hosting_pricing_showcase` injects active categories and plans
- `templates_slider_showcase` injects recent templates
- `templates_listing_showcase` injects templates and listing labels
- `search-domain` injects TLD defaults and pricing metadata

Example usage:

```php
$data = \App\Support\Sections\SectionQueryResolver::resolve($type, $data);
```

Production warning:

- Resolver output can override or supplement editorial content.
- Before changing a resolver method, confirm whether editors expect saved content to win over live data or only to configure it.

## Current Support Layer Roles

### `SectionQueryResolver`

Recommended use:

- dynamic frontend section enrichment
- reusable DB-backed data preparation

### `BuilderSectionDataResolver`

Current reality:

- overlaps with `SectionQueryResolver`
- appears to support older or parallel builder enrichment behavior

Warning:

- do not extend both classes for the same concern unless you intentionally want duplicated behavior paths

### `SectionRegistry`

Current role:

- lightweight registry for render configuration

Limitation:

- not the current source of truth for admin editor section capabilities

### `SectionRenderer`

Current role:

- render registered sections

Limitation:

- not the main path for admin editor preparation

## Routing And Admin Operations

Key admin routes live under `routes/dashboard.php` and include:

- list sections
- preview workspace
- create section
- quick-add section
- edit section
- inline editor panel
- update section
- reorder sections
- toggle active state
- rename section
- move section up or down
- duplicate section
- delete section

Production note:

- Ordering and visibility are managed independently of localized content. A translation edit should not change structural ordering behavior.

## Edge Cases You Must Handle

### Missing Translation

Scenario:

- a page has `en` and `ar`
- a section exists only in `ar`

Expected behavior:

- frontend and admin tooling should fall back safely instead of crashing

### Legacy Type Alias

Scenario:

- old content or UI paths still use `templates-pages`

Expected behavior:

- the system must preserve alias compatibility until data migration is complete

### Inactive Section

Scenario:

- a section is valid and translated but `is_active = false`

Expected behavior:

- admin should still be able to edit it
- frontend should not render it in the sections path

### Empty Dynamic Data Source

Scenario:

- a `hosting_pricing_showcase` section exists, but there are no active plan categories

Expected behavior:

- editor and frontend should fail gracefully with empty state behavior, not exceptions

### Type Contract Drift

Scenario:

- save-time normalization changes, but old rows still contain previous key names

Expected behavior:

- read-time code must remain backward compatible during rollout

## Developer Rules

- Treat section content as a versioned contract even if there is no explicit version field.
- Do not rename section types casually after data exists.
- Prefer support classes over Blade for new preparation logic.
- Keep renderer behavior and editor behavior aligned when changing content shapes.
- Validate both admin save flows and frontend output when touching section schemas.

## Related Documents

- `docs/architecture.md`
- `docs/editor-system.md`
- `docs/refactor-plan.md`
