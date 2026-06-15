# Editor System

## Purpose

The editor system is the shared sections workspace used to create, edit, reorder, and manage sections for a page.

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

This document covers the shared sections editor system across:

- admin page sections workspace
- client tenant homepage editor
- client tenant page editor
- client tenant site header editor
- client tenant site footer editor

It does not cover:

- the GrapesJS visual builder implementation in depth
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

Client wrappers around the shared controller pattern:

- `app/Http/Controllers/Client/SubscriptionHomepageEditorController.php`
- `app/Http/Controllers/Client/SubscriptionPageEditorController.php`
- `app/Http/Controllers/Client/SubscriptionSiteShellEditorController.php`

These wrappers:

- enforce client ownership checks
- adapt route generation
- adapt workspace labels and back links
- limit section types where needed
- keep the same save/update logic through the shared base controller

### Editor Support Classes

Primary files:

- `app/Support/Sections/SectionEditorDataFactory.php`
- `app/Support/Sections/SectionEditorRepeaterFactory.php`
- `app/Support/Sections/SectionMediaPreviewBuilder.php`

Responsibilities today:

- `SectionEditorDataFactory` builds the top-level `editorState` payload, including selected type handling, field visibility flags, locale scalar values, and preload datasets
- `SectionEditorRepeaterFactory` prepares normalized locale repeater rows for features, outputs, services, build steps, and pricing structures
- `SectionMediaPreviewBuilder` resolves preview URLs from media IDs, relative asset paths, and absolute URLs

These classes now hold most read-time editor preparation that previously lived directly in Blade.

### Shared Editor Form

Primary file:

- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`

Responsibilities today:

- render the input markup
- consume prepared `editorState` from the support layer
- preserve the shared input and `data-*` contract for both editor surfaces
- resolve view-time media previews through `SectionMediaPreviewBuilder`

This file remains the main shared UI surface, but it is no longer the primary place for editor state preparation.

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

Client-facing editor routes live in `routes/client.php`.

Important groups:

- `subscriptions/{subscription}/homepage/editor`
- `subscriptions/{subscription}/pages/{page}/editor`
- `subscriptions/{subscription}/site-header/editor`
- `subscriptions/{subscription}/site-footer/editor`

## Load And Save Flow

### 1. Load Editor

The controller loads:

- page
- section
- active languages
- section type metadata

`sectionEditorViewData()` now delegates read-time editor preparation to `SectionEditorDataFactory`, which returns the shared `editorState` payload used by both editor surfaces.

### 2. Render Shared Form

The shared form partial now primarily renders the prepared editor state.

Current responsibilities inside Blade include:

- rendering fields from precomputed flags and locale values
- preserving the shared input naming contract
- rendering repeater rows from prepared arrays
- generating preview image lists through `SectionMediaPreviewBuilder`

For client shell editors, the same partial is reused with shell-specific flags so the form can render:

- header brand name
- header CTA fields
- footer description
- footer contact fields
- footer copyright

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

In current reality, the shared partial now supports multiple workspace wrappers:

- admin pages
- client homepage
- client inner pages
- client site header
- client site footer

### Mixed Responsibilities In Blade

The shared partial is still a high-risk contract surface, but the heaviest preparation work has moved out of Blade.

The remaining Blade responsibilities are mainly:

- view rendering
- shared input structure
- `data-*` integration with admin JavaScript
- view-time preview rendering

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

Most read-time preparation now happens in support classes before the shared form renders.

### Section Type Rules

`SectionEditorDataFactory` decides which fields should render based on the section type.

Examples:

- whether title is internal or user-editable
- whether description appears
- whether button fields appear
- whether media fields appear
- whether a repeater is shown

### Locale Value Preparation

`SectionEditorDataFactory` merges:

- request `old()` values
- translation content values
- fallback defaults

This keeps validation error reloads stable while moving the fallback logic into a testable support class.

### Repeater Preparation

`SectionEditorRepeaterFactory` prepares nested editor arrays for:

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

`SectionMediaPreviewBuilder` builds preview URLs for:

- single media fields
- gallery-like image groups
- icon media attachments
- tech stack logos

### Admin Option Queries

`SectionEditorDataFactory` currently preloads DB-backed option sets needed by the editor, such as plan categories for hosting pricing visibility.

Production warning:

- do not move these queries back into Blade, because that hides performance and dependency assumptions inside the shared UI layer

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

### Shell-Only Section Types

`site_header` and `site_footer` must remain editable in dedicated client shell editors without leaking into the normal page block picker unless that product decision changes deliberately.

## Safe Refactor Direction

The editor should be improved incrementally, not rewritten.

Recommended order:

1. keep extending `SectionEditorDataFactory` for top-level editor state and preload data
2. keep repeater shaping inside `SectionEditorRepeaterFactory`
3. keep preview URL resolution inside `SectionMediaPreviewBuilder`
4. preserve the shared Blade and JavaScript contract while iterating
5. keep save-time normalization unchanged unless the persistence contract is intentionally changing

## Example Target Contract

The current controller payload follows this shape:

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

- `SectionEditorDataFactory` prepares editor state
- `SectionEditorRepeaterFactory` contributes normalized nested collections within that state
- Blade renders the prepared data and uses `SectionMediaPreviewBuilder` for preview output
- JavaScript contracts remain unchanged

## Rules For Developers Working Here

- Do not change input names unless you have traced the save pipeline.
- Do not change `data-*` hooks unless you have traced the related JavaScript.
- Do not mix save-time normalization refactors with read-time preparation refactors in one risky step.
- Keep both editor entry points working in every change.
- Keep shell editors working too when changing shared workspace code.
- Prefer `SectionEditorDataFactory`, `SectionEditorRepeaterFactory`, and `SectionMediaPreviewBuilder` over new Blade-side preparation logic.

## Related Documents

- `docs/sections-system.md`
- `docs/refactor-plan.md`
- `docs/developer-guide.md`
