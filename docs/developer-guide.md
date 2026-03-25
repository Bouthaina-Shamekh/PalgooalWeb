# Developer Guide

## Purpose

This guide is the practical entry point for developers working on the Palgoals codebase.

It focuses on:

- local setup
- day-to-day commands
- where to start when changing specific subsystems
- how to make safe changes in a codebase with transitional content flows

## Read This First

Before making non-trivial changes, read these files in order:

1. `NOTES.md`
2. `docs/architecture.md`
3. `docs/sections-system.md`
4. `docs/editor-system.md`
5. `docs/refactor-plan.md`

This gives you the project conventions first, then the system model, then the risky editor-specific details.

## Local Environment Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- a configured database
- working `.env` values for app, DB, mail, queue, and storage needs in your environment

Production note:

- Do not assume the development environment matches production queue, cache, or storage drivers.
- If a feature touches uploads, queues, or external providers, verify the relevant `.env` settings explicitly.

## Installation

### Backend

```bash
composer install
php artisan key:generate
php artisan migrate
```

If you do not have a local environment file yet, create one from the example first.

Example:

```bash
cp .env.example .env
```

On Windows, use the equivalent copy command if `cp` is not available in your shell.

### Frontend

```bash
npm install
```

## Running The Project

### Recommended Development Flow

Use the Composer dev script:

```bash
composer run dev
```

This starts:

- Laravel development server
- queue listener
- Vite dev server

### Alternative Manual Flow

Backend:

```bash
php artisan serve
```

Frontend with Vite:

```bash
npm run dev:vite
```

Frontend with Mix:

```bash
npm run dev:mix
```

Production warning:

- The project currently contains both Vite and Mix tooling.
- Before changing asset build behavior, confirm which pipeline is active for the affected screens.

## Common Commands

### Tests

```bash
composer test
```

or:

```bash
php artisan test
```

### Formatting

```bash
./vendor/bin/pint
```

### Clear Cached State

```bash
php artisan optimize:clear
```

## Project Entry Points By Concern

### If You Are Changing Admin Page Sections

Start with:

- `routes/dashboard.php`
- `app/Http/Controllers/Admin/SectionController.php`
- `resources/views/dashboard/pages/sections/**`

### If You Are Changing Frontend Section Rendering

Start with:

- `resources/views/front/pages/page.blade.php`
- `resources/views/front/pages/_render-builder-sections.blade.php`
- `app/Support/Sections/SectionQueryResolver.php`

### If You Are Changing Page Content Models

Start with:

- `app/Models/Page.php`
- `app/Models/Section.php`
- `app/Models/SectionTranslation.php`

### If You Are Changing Tenancy Or Subscription Workflows

Start with:

- `app/Models/Tenancy/**`
- `app/Jobs/**`
- `app/Services/**`
- `routes/client.php`

## Daily Working Rules

### 1. Identify The Active Content Path First

Before debugging or changing page output, answer this question:

- Is the page using published builder HTML, builder sections JSON, or legacy sections?

Why this matters:

- a fix in one path may not affect the others

### 2. Preserve Translation Behavior

When changing content models or content rendering:

- use `translation()` helpers where available
- tolerate missing locale rows
- do not assume every page or section has complete locale coverage

Example:

```php
$pageTitle = $page->translation($locale)?->title ?? $page->translation()?->title;
```

### 3. Keep Structural State Separate From Localized Content

For sections:

- `type`, `order`, and `is_active` belong to `Section`
- `title` and `content` belong to `SectionTranslation`

This separation is not optional. Many flows depend on it.

### 4. Prefer Support Classes For Preparation Logic

Good candidates for extraction out of Blade or controllers:

- dynamic section enrichment
- editor state preparation
- media preview preparation
- reusable value normalization

### 5. Avoid Heavy Blade Logic

Blade should ideally render prepared data.

Avoid adding new:

- database queries
- large rule matrices
- schema normalization logic
- duplicated fallback handling

## Real-World Workflow Examples

### Example 1: Add A New Static Section Type

Typical work items:

1. add type metadata in the admin controller
2. add default content if quick-create should work well
3. add save-time normalization rules if needed
4. add editor fields in the shared form
5. add frontend component or partial

Questions to answer:

- does the section need localization?
- does it need media?
- does it need a repeater?
- does it require dynamic DB data?

### Example 2: Add A Database-Driven Section Type

Typical work items:

1. define editorial fields that configure the section
2. add save-time normalization
3. add frontend resolver behavior in `SectionQueryResolver`
4. add or update frontend rendering component
5. validate empty-state behavior when source data is missing

Example:

- a template slider section may store labels and a limit
- the resolver loads actual template records at render time

### Example 3: Modify The Admin Section Editor

Start with:

- `SectionController`
- the shared editor partial
- the wrapper views

Safety checklist:

- keep input names stable
- keep locale structure stable
- keep `data-*` hooks stable
- verify validation re-render behavior
- verify both full-page and sidebar editing

## Testing And Verification Checklist

When changing sections or builders, verify at minimum:

- admin page list loads
- sections workspace loads
- inline editor opens
- standalone editor opens
- saving works in at least one locale and preferably two
- validation errors repopulate fields correctly
- reorder still works
- hidden sections remain editable
- frontend output still works for the affected rendering path
- dynamic sections still load expected live data

## Risk Warnings

### Dual Asset Tooling

Risk:

- this project includes both Vite and Mix

What to do:

- check the active asset path before making build-tool changes

### Dual Page Rendering Modes

Risk:

- the page may render from sections, builder JSON, or published HTML

What to do:

- reproduce the issue on the actual mode used by the page under test

### Shared Editor Partial

Risk:

- one change can break both admin editing surfaces

What to do:

- verify both wrappers after every editor change

### Implicit Section Contracts

Risk:

- section `content` is stored as JSON without explicit versioning

What to do:

- preserve backward compatibility when renaming keys or changing repeater shapes

## Troubleshooting Shortcuts

### A Section Saves But Renders Wrong On Frontend

Check in this order:

1. save-time normalization in `SectionController`
2. stored `SectionTranslation->content`
3. frontend resolver behavior
4. frontend component expectations

### A Validation Error Clears Repeater Rows

Check in this order:

1. input names in the shared form
2. `old()` fallback logic
3. repeater preparation logic
4. JavaScript template row naming

### A Page Change Has No Visible Effect

Check in this order:

1. page builder mode
2. published builder HTML presence
3. section active state
4. translation locale resolution

## Related Documents

- `docs/architecture.md`
- `docs/sections-system.md`
- `docs/editor-system.md`
- `docs/refactor-plan.md`
