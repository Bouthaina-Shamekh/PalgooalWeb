# Architecture

## Purpose

This document explains how the Palgoals application is organized at runtime and where the major responsibilities live.

Use this file when you need to answer questions such as:

- Where should a new feature live?
- Which layer is responsible for content preparation?
- How do marketing pages, sections, builders, and tenancy fit together?
- Which parts of the app are stable, and which parts are transitional?

## System Summary

The project is a Laravel 12 application that combines multiple business areas in one codebase:

- Public marketing website
- Admin dashboard
- Client area
- Page builder system
- Structured sections system
- Subscription and tenancy workflows

This is not a simple CMS. It is closer to a business platform with multiple interfaces sharing the same models and infrastructure.

## Technology Stack

### Backend

- PHP 8.2+
- Laravel 12
- Eloquent ORM
- Laravel Fortify
- Livewire 3 in selected areas

### Frontend Tooling

- Vite
- Laravel Mix
- Tailwind CSS 4
- Axios
- SortableJS
- TinyMCE
- GrapesJS
- Preline

### Quality Tooling

- Pest
- Laravel test runner
- Laravel Pint

## Major Runtime Areas

### Public Frontend

Purpose:

- Render marketing pages
- Render templates, reviews, portfolio, and related public content
- Support multiple content sources for a page

Primary locations:

- `app/Http/Controllers/Front/**`
- `resources/views/front/**`
- `app/Support/Sections/SectionQueryResolver.php`

### Admin Dashboard

Purpose:

- Manage pages and sections
- Manage templates, media, services, testimonials, and portfolios
- Manage plans, domains, orders, invoices, and subscriptions

Primary locations:

- `app/Http/Controllers/Admin/**`
- `routes/dashboard.php`
- `resources/views/dashboard/**`

### Client Area

Purpose:

- Provide subscription-facing and tenant-facing screens
- Support client-owned page and section workflows where applicable

Primary locations:

- `routes/client.php`
- `resources/views/client/**`
- `app/Models/Tenancy/**`

### Tenancy and Provisioning

Purpose:

- Provision customer subscriptions
- Manage tenant content state
- Trigger background jobs and provider synchronization

Primary locations:

- `app/Jobs/**`
- `app/Models/Tenancy/**`
- `app/Notifications/Tenancy/**`
- `app/Services/**`

## Core Architectural Concepts

### 1. Page Is The Content Aggregate Root

For marketing content, the top-level aggregate is the page.

The `Page` model owns:

- page context such as marketing or tenant
- publish and active state
- builder mode
- page translations
- ordered sections
- visual builder structures

Example relationships:

```php
$page->translations();
$page->sections();
$page->builderStructure();
$page->builderStructures();
$page->subscription();
```

Production note:

- When adding page-related features, start from the `Page` aggregate and work outward.
- Avoid treating `Section` as a top-level content owner. It is a child of a page.

### 2. Translation Tables Hold Display Content

The application separates structural state from localized content.

For the page and section system:

- `Page` stores structural metadata
- `PageTranslation` stores localized title and slug
- `Section` stores structural metadata such as type, order, and visibility
- `SectionTranslation` stores localized title and JSON content

Benefits:

- locale changes do not require duplicating structural records
- frontend rendering can resolve the best translation at runtime
- admin workflows can edit localized content independently

Real-world example:

- One `Section` row may represent a `hero_campaign` block for a page.
- The Arabic translation can have Arabic title and content.
- The English translation can have English title and content.
- The section order and visibility still remain shared.

Edge case:

- A section can exist even if one locale translation is missing.
- Rendering and admin tools must be written to tolerate partial translation coverage.

### 3. The App Supports Multiple Page Rendering Paths

The current project supports more than one rendering path for a page.

Observed paths in the codebase:

- published visual-builder HTML snapshot
- normalized builder sections JSON
- legacy database-backed sections

This is one of the most important architectural facts in the system.

It means the application is in a transitional state, not a single-mode page builder.

Production warning:

- Never assume that changing one rendering path changes all page output.
- A fix in legacy sections does not automatically fix visual-builder pages.
- A fix in builder JSON rendering does not automatically fix published snapshot HTML.

### 4. Dynamic Section Data Is Resolved At Render Time

Some section types are not fully static. They require live database data during rendering.

Examples:

- reviews and testimonials
- portfolio items
- hosting plans and plan categories
- template listings
- domain TLD metadata

The main support class for this is:

- `app/Support/Sections/SectionQueryResolver.php`

Example:

```php
$data = \App\Support\Sections\SectionQueryResolver::resolve($type, $data);
```

This gives the project a practical separation:

- persisted section content stores intent and overrides
- resolver logic injects live database-backed payloads

### 5. Admin Screens Reuse Shared Blade Partials

The admin area uses shared Blade partials to avoid duplicating large form trees.

Important example:

- the standalone section edit page and the inline workspace sidebar both render the same editor form partial

Current implementation note:

- `SectionController` prepares `editorState` through `SectionEditorDataFactory`
- repeater data is delegated to `SectionEditorRepeaterFactory`
- media preview resolution is handled by `SectionMediaPreviewBuilder`

This is useful, but it introduces a strong contract between:

- controller payloads
- Blade markup
- admin JavaScript hooks

Production warning:

- A change to the shared partial can break both editing surfaces at once.
- Any refactor in this area must preserve input names and `data-*` hooks unless the JavaScript is updated in the same change.

## System Map

### Application Layer

- `app/Http/Controllers/Admin/**`: admin workflows
- `app/Http/Controllers/Front/**`: public-site workflows
- `app/Livewire/**`: interactive dashboard and client components
- `app/Models/**`: Eloquent models and aggregate roots
- `app/Models/Tenancy/**`: tenant and subscription domain models
- `app/Support/**`: reusable support classes and adapters
- `app/Services/**`: business services and orchestration logic

### Routing Layer

- `routes/web.php`: shared and public entry routing
- `routes/dashboard.php`: admin routing
- `routes/client.php`: client routing

### View Layer

- `resources/views/front/**`: public site UI
- `resources/views/dashboard/**`: admin UI
- `resources/views/client/**`: client UI

### Frontend Assets

- `resources/js/**`: JavaScript sources
- `resources/css/**`: styles
- `public/**`: compiled and public assets

### Data Layer

- `database/migrations/**`: schema
- `database/seeders/**`: seeders
- `database/factories/**`: test factories

## Request Flow Examples

### Frontend Marketing Page Flow

Typical flow:

1. A frontend route resolves a `Page`.
2. The page loads translations, sections, and optionally visual-builder structures.
3. The view decides which rendering path has priority.
4. If a section type needs live data, `SectionQueryResolver` enriches the payload.
5. Blade components or partials render the final output.

Real-world example:

- A page in `sections` mode loads active `Section` records.
- A `reviews_showcase` section uses persisted title and description fields.
- At render time, testimonials are loaded from the database and injected into the final section data.

### Admin Section Edit Flow

Typical flow:

1. The admin route resolves a page and section.
2. `SectionController` loads the base context and asks `SectionEditorDataFactory` to prepare `editorState`.
3. Shared Blade partial renders the edit UI for both editing surfaces, using the prepared state and `SectionMediaPreviewBuilder` for preview output.
4. On submit, the controller validates request input.
5. The controller normalizes content by section type.
6. `SectionTranslation` rows are created or updated.

Production warning:

- Save-time normalization is currently the most stable source of truth for section content shape.
- Read-time editor preparation is now centralized in support classes, but the shared Blade and JavaScript contract still must be treated carefully during refactors.

## Data Model Summary

### Page

Responsibilities:

- content container
- builder mode state
- marketing or tenant context
- active and publish state

### Section

Responsibilities:

- belongs to a page
- identifies the section type
- stores variant and style metadata
- controls display order
- controls frontend visibility

### SectionTranslation

Responsibilities:

- stores localized section title
- stores localized JSON content payload

Example payload:

```json
{
    "title": "PROGRAMMING",
    "description": "Department intro",
    "primary_button": {
        "label": "Send Your Idea",
        "url": "#"
    },
    "outputs": [{ "text": "Landing Sites" }, { "text": "E-Commerce Stores" }]
}
```

## Invariants Developers Should Preserve

- A section belongs to exactly one page.
- Section order is page-scoped, not global.
- Section visibility is structural, not per-locale.
- Translation content is type-specific JSON and must remain backward compatible.
- Dynamic render-time data should not silently overwrite persisted editorial intent unless that is the documented contract for the section type.

## Risky Areas

### Dual Builder Paths

Risk:

- fixing one rendering path does not guarantee parity with the others

Mitigation:

- always verify whether a page is using `visual`, `sections`, or a fallback path

### Overlapping Support Classes

Risk:

- similar responsibilities exist in `SectionQueryResolver` and `BuilderSectionDataResolver`

Mitigation:

- prefer the actively used resolver before extending older or parallel classes

### Heavy Blade Preparation

Risk:

- business rules hidden in Blade are harder to test and easier to duplicate, especially in shared admin forms

Mitigation:

- extend existing support classes such as `SectionEditorDataFactory`, `SectionEditorRepeaterFactory`, and `SectionMediaPreviewBuilder` before adding new Blade-side preparation

## Practical Guidance

- Start with the route, then the controller, then the model relationships, then the view.
- When adding a feature, identify whether it belongs to the frontend, admin, client, or tenancy boundary first.
- Before refactoring sections, check all three page rendering paths.
- Before changing content shape, inspect both save-time normalization and frontend render-time expectations.

## Related Documents

- `docs/sections-system.md`
- `docs/editor-system.md`
- `docs/developer-guide.md`
- `docs/refactor-plan.md`
