# Sections System

## Purpose

This document explains how the structured page sections system works today.

Use it when you need to:

- understand how sections are stored
- add or change a section type
- trace data from admin input to frontend output
- avoid breaking existing section content contracts

This document now also covers tenant-owned site shell content:

- global site header
- global site footer

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
- carries a `context` that decides where the page is used

Important implications:

- sections do not exist independently of pages
- any section workflow is page-scoped
- tenant site shell content is page-scoped too, even when it behaves like a global header or footer

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
- `tenant_id` when the section belongs to tenant-owned canonical content

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

## Tenant Page Contexts

The canonical page model is also used for tenant runtime content.

Current important contexts:

- `marketing`
- `tenant`
- `tenant_header`
- `tenant_footer`

Meaning:

- `tenant` = normal tenant pages such as homepage and inner pages
- `tenant_header` = tenant-global site header shell
- `tenant_footer` = tenant-global site footer shell

Important implication:

- header and footer are not a separate storage system
- they are canonical pages with dedicated contexts

## Tenant Site Shells

Tenant-global chrome is orchestrated through:

- `app/Services/Tenancy/TenantSiteShellService.php`

Current behavior:

- ensures a tenant has one header shell page
- ensures a tenant has one footer shell page
- seeds default translations and starter section content when missing
- allows blueprints to define:
  - `site_header`
  - `site_footer`

Runtime behavior:

- `app/Http/Middleware/ServeTenantSite.php` resolves the normal tenant page
- then loads shell pages through `TenantSiteShellService`
- `resources/views/tenant/site.blade.php` renders:
  - header shell sections
  - current page sections
  - footer shell sections

Production warning:

- tenant shell pages must not be counted as normal `context = tenant` pages in provisioning checks or onboarding heuristics unless that is intentional

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

- Quick-adding `hero_campaign` creates a section and seeds title, subtitle, button, and media defaults so the section is not visually empty.
- In the workspace quick-add flow, the server also returns the new section ID, sidebar card HTML, and editor URL so the sidebar and preview can update immediately without a full page reload.

Edge case:

- The set of active locales can change over time.
- New sections use currently active locales, but old sections may not have translations for every later-added language.

### 2. Edit

Editing is supported through two admin surfaces:

- standalone edit page
- inline workspace sidebar editor

Both use the same shared form partial and the same persistence logic.

Read-time editor preparation is now split between `SectionEditorDataFactory`, `SectionEditorRepeaterFactory`, and `SectionMediaPreviewBuilder`, while Blade remains the shared rendering surface.

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

For tenant runtime pages, the practical render sequence is now:

1. load the tenant page with `context = tenant`
2. load `tenant_header` shell if present
3. load `tenant_footer` shell if present
4. render shell + page + shell in one page response

## Supported Section Types

The main admin controller currently exposes these shared/admin section types:

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

The tenant page builder path also supports simpler tenant-facing section types:

- `hero`
- `features`
- `cta`
- `testimonials`
- `faq`
- `menu`

The tenant shell editors additionally support shell-only section types:

- `site_header`
- `site_footer`

Important note:

- not every section type is exposed in every editor surface
- `site_header` and `site_footer` are intentionally limited to dedicated shell editors and are not part of the normal page block library

## Content Patterns By Section Family

### Simple Scalar Sections

Examples:

- `features_grid`
- `cta`

Typical fields:

- title
- subtitle
- description
- simple button

### Media-Heavy Sections

Examples:

- `hero_campaign`
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

Client-facing tenant section editors live under `routes/client.php` and currently include:

- homepage editor
- tenant page editor
- tenant site header editor
- tenant site footer editor

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

### Missing Tenant Shell

Scenario:

- a tenant site has canonical pages
- header or footer shell page has not been created yet

Expected behavior:

- the shell service should be able to create it safely
- the live tenant site should not break if the shell is missing during rollout

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
- `docs/adr/001-page-section-as-source-of-truth.md`
