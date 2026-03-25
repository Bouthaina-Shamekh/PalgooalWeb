# Editor System

## Purpose

The editor system is the admin-facing interface used to create, edit, reorder, and manage sections for a page.

It currently supports two main editing surfaces for section content:

- A standalone section edit page
- An inline sidebar editor inside the page sections workspace

Both surfaces are backed by the same controller and the same shared Blade form partial.

## Main Entry Points

### Controller

- `app/Http/Controllers/Admin/SectionController.php`

This controller is responsible for:

- Listing sections for a page
- Rendering the page sections workspace preview
- Creating sections
- Quick-adding sections
- Rendering edit and inline editor screens
- Updating sections and translations
- Toggling visibility
- Renaming
- Moving and reordering
- Duplicating and deleting

### Shared Form Partial

- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`

This is the core admin editor UI and currently contains both markup and substantial render-time preparation logic.

### Wrappers Around The Shared Partial

- `resources/views/dashboard/pages/sections/edit.blade.php`
- `resources/views/dashboard/pages/sections/partials/sidebar-editor.blade.php`

These wrapper views provide different chrome and layout while delegating the actual form UI to the same partial.

## Routing

The editor-related routes live in `routes/dashboard.php` under the page sections route group.

Important endpoints:

# Editor System

## Purpose

This document explains the admin section editor as it exists today, including how data is loaded, how the shared form is reused, and where the main maintenance risks are.

Use it when you need to:

- trace a section edit from route to database
- understand why the editor is difficult to change safely
- refactor the editor without breaking behavior
- add a new section type to the admin interface

## Scope

This document covers the page sections editor under the admin dashboard.

It does not cover:

- the GrapesJS visual builder implementation in depth
- client-facing tenant editors
- low-level media library implementation details

## Main Components

### Controller

Primary file:

- `app/Http/Controllers/Admin/SectionController.php`

Responsibilities:

- list page sections
- render sections workspace preview
- create and quick-create sections
- render standalone edit page
- render inline sidebar editor
- validate updates
- normalize content before save
- manage reorder, move, duplicate, toggle, rename, and delete operations

### Shared Editor Form

Primary file:

- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`

Responsibilities today:

- render the input markup
- calculate which fields are visible for a section type
- merge `old()` input with saved values
- shape repeater data for UI rendering
- prepare media preview URLs
- load some admin-only option data

This file is the main maintenance hotspot.

### Wrapper Views

Primary files:

- `resources/views/dashboard/pages/sections/edit.blade.php`
- `resources/views/dashboard/pages/sections/partials/sidebar-editor.blade.php`

These wrappers provide layout and chrome while delegating the form itself to the shared partial.

## Route Map

The section editor lives under the page sections route group in `routes/dashboard.php`.

Important routes:

- `GET /admin/pages/{page}/sections/{section}/edit`
- `GET /admin/pages/{page}/sections/{section}/editor`
- `PUT|PATCH|POST /admin/pages/{page}/sections/{section}`

Related operational routes:

- list sections
- preview workspace
- create section
- quick-add section
- reorder sections
- toggle active
- rename
- move
- duplicate
- delete

## Load And Save Flow

### 1. Load Editor

The controller loads:

- page
- section
- active languages
- section type metadata

Today, `sectionEditorViewData()` returns a deliberately thin payload.

That is good for controller simplicity, but it pushes too much preparation into Blade.

### 2. Render Shared Form

The shared form partial computes render-time editor state.

Current responsibilities inside Blade include:

- selected type canonicalization
- alias handling such as `templates-pages`
- field visibility booleans
- locale-specific field defaults
- nested repeater preparation
- media preview URL generation
- admin-only preload queries

### 3. Submit Update

On save:

1. request input is validated
2. translations may be synchronized for shared-content section types
3. section content is normalized by section type
4. `Section` and `SectionTranslation` records are updated

The save pipeline in the controller is currently the strongest source of truth for data shape.

## Why This Area Is Risky

### Shared Partial Coupling

One Blade partial supports two editor surfaces.

That means one change can break:

- full-page editing
- inline workspace editing
- admin JavaScript repeaters and tab logic

### Mixed Responsibilities In Blade

The shared partial is doing too many jobs:

- view rendering
- editor state computation
- value fallback handling
- content normalization for UI display
- direct data loading in some cases

### Contract Coupling With JavaScript

The edit UI initializes several JavaScript helpers that depend on the current DOM structure.

Observed examples:

- editor tabs
- feature repeaters
- output repeaters
- service repeaters
- build-step repeaters
- review-related helpers

Production warning:

- input names, repeater templates, panel IDs, and `data-*` hooks are part of the working contract
- changing them casually can break the UI without producing obvious PHP errors

## Current Blade Responsibilities In Detail

### Section Type Rules

The form decides which fields should render based on the section type.

Examples:

- whether title is internal or user-editable
- whether description appears
- whether button fields appear
- whether media fields appear
- whether a repeater is shown

### Locale Value Preparation

The form merges:

- request `old()` values
- translation content values
- fallback defaults

This is why the current form reloads well after validation errors, but it also means the fallback logic is hard to test.

### Repeater Preparation

The form currently prepares nested editor arrays for:

- campaign features
- programming outputs
- services lists
- build steps
- hosting pricing categories and plans

Typical preparation tasks:

- drop empty rows
- normalize key names
- normalize icon source values
- coerce booleans
- derive textarea fallbacks

### Media Preparation

The form builds preview URLs for:

- single media fields
- gallery-like image groups
- icon media attachments
- tech stack logos

### Admin Option Queries

The form currently loads some DB-backed option sets directly in Blade, such as plan categories for hosting pricing visibility.

Production warning:

- database queries inside Blade are especially risky in a large shared partial because they hide performance and dependency assumptions.

## Real-World Example

### Editing `programming_showcase`

What the editor has to coordinate:

- brand prefix and suffix
- localized title and description
- outputs heading
- repeater-based outputs list
- CTA button fields
- media selection and preview

What can go wrong if changed carelessly:

- outputs may render in the UI but save in a different shape
- old validation input may no longer repopulate
- media preview may work for URLs but break for media IDs

### Editing `hosting_pricing_showcase`

What the editor has to coordinate:

- shared heading and CTA labels
- visibility filters for plan categories
- dynamic plan/category data loaded from database modules

What can go wrong if changed carelessly:

- saved filters stop matching active categories
- editor shows categories that frontend resolver ignores
- empty category states are not handled gracefully

## Edge Cases To Preserve

### Validation Error Re-render

The editor must preserve user input after failed validation, including nested repeaters.

### Missing Locale Translation

The editor must not assume every locale has an existing translation row.

### Legacy Type Alias

Older data or UI paths may still require alias mapping such as `templates-pages`.

### Mixed Media Value Formats

Media fields may contain:

- media IDs
- asset-relative strings
- absolute URLs
- empty values

All cases must remain safe.

### Hidden But Editable Sections

Inactive sections must remain editable in admin while remaining hidden on the frontend.

## Safe Refactor Direction

The editor should be improved incrementally, not rewritten.

Recommended order:

1. extract top-level editor state and visibility flags
2. extract admin preload queries
3. extract media preview preparation
4. extract locale scalar fallback preparation
5. extract repeater preparation one family at a time
6. keep save-time normalization unchanged until read-time extraction is stable

## Example Target Contract

Future controller payload shape could look like this:

```php
return [
    'page' => $page,
    'section' => $section,
    'languages' => $languages,
    'sectionTypes' => $sectionTypes,
    'editorState' => $preparedEditorState,
];
```

In that shape:

- the controller or support layer prepares editor state
- Blade renders the prepared data
- JavaScript contracts remain unchanged

## Rules For Developers Working Here

- Do not change input names unless you have traced the save pipeline.
- Do not change `data-*` hooks unless you have traced the related JavaScript.
- Do not mix save-time normalization refactors with read-time preparation refactors in one risky step.
- Keep both editor entry points working in every change.
- Prefer support classes over Blade for new preparation logic.

## Related Documents

- `docs/sections-system.md`
- `docs/refactor-plan.md`
- `docs/developer-guide.md`
