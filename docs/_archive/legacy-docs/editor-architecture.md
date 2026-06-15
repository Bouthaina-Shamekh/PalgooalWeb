# Editor Architecture

## 1. Overview

The section editor system is now organized around a definition-driven runtime with two explicit editor paths:

- Dynamic Editor for definition-linked page sections
- Shell Editor for tenant site chrome (`site_header` and `site_footer`)

At a high level, the system works as follows:

- Frontend section output is definition-driven and resolves templates from section definitions rather than from legacy type-switch rendering.
- Admin page section editing uses a dynamic editor payload prepared from definition metadata and locale-aware saved content.
- Tenant shell editing uses a separate shell-only payload and a separate Blade form.
- Controller-driven `editorFormPartial` routing explicitly selects the correct form partial.
- Frontend template resolution follows the categorized convention `front.sections.{category}.{template_key}`.

This architecture intentionally removes older runtime ambiguity:

- Visual Builder is archived and not part of the active section runtime.
- `custom_preset` is not part of the active editor architecture.
- old compatibility payload keys such as dynamic/shell routing flags are no longer part of the live contract.
- older schema/type helper systems are not part of the active editor path.
- `components.template.sections.*` is not an active frontend runtime path.

The result is a smaller runtime model:

- definitions decide how sections render
- controllers decide which editor form is used
- each editor path has its own minimal payload contract
- frontend templates live under one explicit convention

## 2. Frontend Rendering Architecture

The canonical frontend page rendering flow is:

`resources/views/front/pages/page.blade.php`
-> `resources/views/front/pages/partials/definition-section.blade.php`
-> `App\Support\Sections\SectionRenderer::renderDefinitionDriven()`
-> `App\Support\Sections\SectionDefinitionRuntimeResolver`
-> `App\Support\Sections\SectionTemplateRegistry`
-> `front.sections.{category}.{template_key}`

### Active Flow

1. `page.blade.php` loads active page sections and renders each section through `definition-section.blade.php`.
2. `definition-section.blade.php` calls `SectionRenderer::renderDefinitionDriven()` and disables legacy fallback for the public page path.
3. `SectionRenderer::renderDefinitionDriven()` asks `SectionDefinitionFrontendViewDataFactory` to prepare the resolved view and view data.
4. `SectionDefinitionRuntimeResolver` verifies that the section is linked to an active definition and that a primary template key is available.
5. `SectionTemplateRegistry` resolves the template using either:
    - an explicit code-side registry entry, or
    - the categorized convention path
6. The resolved Blade view is rendered from `front.sections.{category}.{template_key}`.

### Definition-Driven Rendering

Definition-driven rendering means that frontend rendering is determined by:

- the linked `SectionDefinition`
- the definition's selected primary template
- the resolved template category and template key
- normalized locale content plus any query-time enrichment

The active frontend runtime does not depend on the old component include path or on a large per-type Blade switch.

### Template Resolution

`SectionTemplateRegistry` is the code-side resolver for template metadata and safe view lookup.

Resolution priority is:

1. explicit registry metadata
2. convention-based view resolution
3. missing-template fallback view

The convention-based view path is:

```text
front.sections.{category}.{template_key}
```

If a template key is valid but not explicitly registered, the runtime still attempts the convention path.

### Category-Based Convention

Categories are part of the active view organization model.

- They partition templates into stable frontend folders.
- They keep the filesystem predictable for developer-managed templates.
- They avoid growing a large centralized runtime switch.

If a category is missing or invalid, the runtime normalizes it to `uncategorized`.

### Fallback Behavior

For the public marketing page path, frontend rendering is definition-driven first and does not route back into the removed legacy section partial runtime.

When a renderable definition or template cannot be resolved:

- the system prepares a missing-template payload
- it renders the configured fallback view
- the fallback view defaults to `front.sections._missing-template`

### Missing Template Handling

Missing-template handling is explicit and safe.

The fallback payload includes diagnostic context such as:

- `template_key`
- `category`
- `section_key`
- attempted view names
- resolution source
- section and definition identifiers

This keeps failures debuggable without restoring removed runtime systems.

## 3. Dynamic Editor Architecture

The Dynamic Editor is the active editor path for definition-linked page sections.

### Main Components

- Controller: `app/Http/Controllers/Admin/SectionController.php`
- Top-level state builder: `app/Support/Sections/SectionEditorDataFactory.php`
- Definition field renderer: `app/Support/Sections/DynamicSectionEditorRenderer.php`
- Form surface: `resources/views/dashboard/pages/sections/partials/dynamic-editor-form.blade.php`
- Field/group renderer: `resources/views/dashboard/pages/sections/partials/dynamic-editor/renderer.blade.php`

### Current Payload Contract

The current Dynamic Editor payload contract is exactly:

```php
[
    'selectedType' => '...',
    'defaultLocale' => '...',
    'dynamicEditor' => [...],
    'localeScalarValues' => [...],
]
```

#### Payload Keys

- `selectedType`
    - canonical section type for the current editor session
    - used for hidden `type` submission and type-aware UI context

- `defaultLocale`
    - primary locale tab for shared or default rendering behavior

- `dynamicEditor`
    - normalized definition-driven editor structure
    - contains per-locale groups and fields derived from definition metadata

- `localeScalarValues`
    - locale-indexed scalar values used by the shared edit flow
    - currently preserves the localized section title bridge used by the form and save pipeline

### How Editor State Is Built

`SectionController` delegates editor-state construction to `SectionEditorDataFactory`.

That factory:

- normalizes the selected type
- resolves the default locale
- asks `DynamicSectionEditorRenderer` for the definition-driven field tree
- produces a safe empty dynamic state when no dynamic definition is available
- prepares locale scalar values needed by the form contract

`DynamicSectionEditorRenderer` then builds a Blade-friendly payload from the linked definition:

- per-locale editor groups
- field payloads
- field partial references
- repeater payloads
- media preview URLs for dynamic media fields

### How `dynamicEditor` Is Consumed

`dynamic-editor-form.blade.php` consumes `dynamicEditor` as the source of the dynamic field UI.

The form:

- reads the top-level `selectedType`
- reads the structured `dynamicEditor` payload
- renders locale tabs
- passes locale-specific field groups into `dynamic-editor/renderer.blade.php`

`renderer.blade.php` then:

- resolves the current locale editor payload
- emits the hidden localized title input
- renders each dynamic group
- renders each field through its prepared field partial

The Blade layer is a consumer of prepared state, not the place where editor mode is inferred.

### How `localeScalarValues` Are Used

In the Dynamic Editor path, `localeScalarValues` exists as a narrow compatibility bridge for the active save contract.

Current responsibilities are intentionally limited:

- hydrate the localized section title hidden input
- preserve locale-aware scalar state expected by the existing save pipeline

It is not used as a replacement for the dynamic field tree. The definition-driven `dynamicEditor` payload is the authoritative read-time editor structure.

## 4. Shell Editor Architecture

The Shell Editor is the active editor path for tenant-global site chrome.

It is limited to:

- `site_header`
- `site_footer`

### Main Components

- Controller: `app/Http/Controllers/Client/SubscriptionSiteShellEditorController.php`
- Support layer: `app/Support/Sections/ShellSectionEditorSupport.php`
- Form surface: `resources/views/dashboard/pages/sections/partials/shell-editor-form.blade.php`

### Current Shell Payload Contract

The current Shell Editor payload contract is exactly:

```php
[
    'selectedType' => '...',
    'defaultLocale' => '...',
    'footerEditorConfig' => [...],
    'localeScalarValues' => [...],
    'localeHeaderLogoPreviewUrls' => [...],
]
```

#### Payload Keys

- `selectedType`
    - active shell type
    - expected values are `site_header` or `site_footer`

- `defaultLocale`
    - primary locale for shell editing

- `footerEditorConfig`
    - footer-specific field labels and grouping metadata
    - only meaningful for the footer shell flow

- `localeScalarValues`
    - locale-indexed shell scalar values
    - intentionally reduced to active shell fields only

- `localeHeaderLogoPreviewUrls`
    - per-locale prepared logo preview URLs for the header flow

### Shell-Only Editing

`SubscriptionSiteShellEditorController` is a client-area wrapper around the shared workspace pattern, but it does not use the dynamic editor payload.

Instead, it delegates shell state preparation to `ShellSectionEditorSupport`, which:

- validates the allowed shell section types for the current shell workspace
- builds a shell-only editor state
- normalizes shell translations and content
- prepares locale-scoped scalar values for header and footer editing

This keeps shell editing isolated from definition-driven page-section editing.

### `footerEditorConfig`

`footerEditorConfig` is a shell-only bridge for footer UI metadata.

It supplies labels and grouped configuration for footer editing concerns such as:

- footer links group label
- footer links item label
- copyright label
- social field labels

This metadata belongs only to the shell editor path and is not shared with the dynamic editor architecture.

### Logo Preview Flow

Header logo previews are prepared server-side and delivered through `localeHeaderLogoPreviewUrls`.

The flow is:

1. shell content is normalized per locale
2. each locale's header logo value is resolved
3. `SectionMediaPreviewBuilder` produces preview URLs
4. the form consumes the prepared preview list for the current locale tab

This keeps preview generation out of Blade and avoids leaking dynamic-editor assumptions into shell editing.

### Separation From Dynamic Editor Architecture

The Shell Editor is intentionally separate from the Dynamic Editor.

Key boundaries:

- no `dynamicEditor` payload
- no definition-field renderer dependency
- no editor-state mode flag to decide which form to show
- shell-only config and preview keys remain local to the shell path

The two editor systems share workspace patterns, not payload shape.

## 5. Editor Form Routing

The current form split is controller-driven through `editorFormPartial`.

### Dynamic Editor Form Routing

`SectionController` returns:

```php
'dashboard.pages.sections.partials.dynamic-editor-form'
```

This is the active form for definition-linked admin page sections.

### Shell Editor Form Routing

`SubscriptionSiteShellEditorController` overrides the form selection and returns:

```php
'dashboard.pages.sections.partials.shell-editor-form'
```

This is the active form for tenant site header and footer editing.

### Why Editor-State-Based Routing Was Removed

The old approach relied on compatibility keys and implicit branching inside shared editor state.

That approach was removed because it created hidden coupling between:

- payload shape
- editor form selection
- compatibility flags
- legacy mixed editor behavior

Explicit routing is now preferred because it:

- makes the chosen editor surface obvious at the controller boundary
- prevents shell and dynamic payload contracts from drifting into one another
- removes the need for compatibility booleans such as dynamic/shell mode flags
- makes each editor path easier to extend independently

## 6. Section Template Convention

The active frontend template convention is:

```text
front.sections.{category}.{template_key}
```

### Category Organization

Categories organize frontend templates into predictable folders under `resources/views/front/sections`.

Examples of category intent:

- `hero`
- `promo`
- `services`
- `uncategorized`

This keeps developer-managed templates discoverable without introducing a large runtime registry requirement.

### Template Resolution Rules

`SectionTemplateRegistry` resolves templates using these rules:

1. accept only safe template keys
2. normalize category to a safe category name
3. check explicit registry metadata first
4. otherwise resolve the convention path
5. if no view exists, use the missing-template fallback view

### Naming Rules

Both template keys and categories are sanitized to a restricted naming contract.

Current rules:

- template keys must match `^[a-z0-9_-]+$`
- categories must match `^[a-z0-9_-]+$`
- invalid or missing categories normalize to `uncategorized`

Developers should treat these names as stable runtime identifiers, not display labels.

### Sanitization Rules

`SectionTemplateRegistry` rejects unsafe or malformed names before building a view path.

This protects the runtime from:

- arbitrary view-name injection
- invalid filesystem/path conventions
- inconsistent template naming across definitions

## 7. Active Runtime Boundaries

### Active Runtime Architecture

The following are active runtime architecture:

- definition-driven frontend section rendering
- `front.sections.{category}.{template_key}` view resolution
- Dynamic Editor for admin page sections
- Shell Editor for tenant site header and footer
- explicit controller-selected `editorFormPartial` routing
- `SectionDefinitionRuntimeResolver` for renderability checks
- `SectionTemplateRegistry` for safe template resolution
- `SectionDefinitionFrontendViewDataFactory` for frontend render payload preparation

### Not Active Runtime Architecture

The following are not active runtime architecture:

- Visual Builder runtime
- PageBuilder frontend runtime path
- `custom_preset` editor mode
- legacy section renderer as the active public page runtime
- `components.template.sections.*` as an active frontend runtime path
- old editor compatibility payload keys and routing flags
- old schema/type helper systems used by earlier editor generations

These systems may still exist on disk in archived or dormant form, but they are not the architecture developers should target for new work.

## 8. Archived Systems

Several older systems were archived to simplify runtime behavior while preserving historical code and data context.

### What Was Archived

- Visual Builder runtime
- PageBuilder route/controller runtime surface
- `custom_preset` editor mode
- legacy section-renderer runtime path
- old component runtime path under `components.template.sections`
- old editor compatibility payloads and schema helpers

### Why These Systems Were Removed From Active Architecture

They were removed from the live architecture because they created too many overlapping decisions:

- multiple editor-selection mechanisms
- multiple frontend rendering paths for the same conceptual section flow
- compatibility payload bloat
- registry and schema indirection beyond what the active runtime needs
- harder reasoning about where section behavior was actually controlled

The current design favors directness:

- definitions control rendering
- controllers control editor form routing
- payloads are minimal and purpose-specific

### Why Archives Were Preserved

Archives remain useful for:

- historical reference during cleanup
- safe comparison with older behavior
- migration and data-compatibility analysis
- staged removal of dormant code

### Where Archived Code Exists

Primary archive location:

- `legacy/visual-builder/**`

There are also a small number of dormant archive-oriented remnants in the active tree, such as:

- archived `builder_mode` messaging
- dormant builder controller code still on disk
- not-yet-removed fallback methods

Those remnants should be treated as cleanup candidates, not as active design guidance.

## 9. Current Safe Extension Points

### Add New Dynamic Sections

Recommended path:

1. create or update the `SectionDefinition`
2. add the definition fields needed by the section
3. assign a primary template key
4. create the frontend Blade template under `front.sections.{category}.{template_key}`
5. rely on the dynamic editor path to render fields from the definition

Guidelines:

- prefer definition fields over ad hoc Blade conditionals
- keep frontend rendering definition-driven
- use `SectionQueryResolver` only when the section needs live data enrichment at render time

### Add New Frontend Templates

Recommended path:

1. choose a safe category and template key
2. add the Blade view under `resources/views/front/sections/{category}/{template_key}.blade.php`
3. link the template key from the relevant definition
4. add a registry entry only when explicit metadata or override behavior is needed

Prefer the convention path first. Use explicit registry metadata only when the convention alone is insufficient.

### Add New Shell Editor Functionality

Recommended path:

1. extend `ShellSectionEditorSupport`
2. keep new state inside the shell payload contract
3. update `shell-editor-form.blade.php`
4. use server-prepared preview/config data where needed

Rules:

- do not reuse Dynamic Editor keys for shell behavior
- keep shell-only labels/config under shell-only payload keys
- keep shell locale scalar values limited to fields that the shell form actually edits

### Add New Definition-Driven Sections

Recommended path:

1. define the section in the developer definition system
2. assign fields and template selection
3. implement the frontend Blade template using the resolved definition-driven payload
4. add optional query-time enrichment through `SectionQueryResolver` when needed

New work should target the definition-driven architecture directly and should not restore archived renderer paths.

## 10. Future Cleanup Candidates

The following items are possible cleanup candidates but are **NOT YET REMOVED**.

### `SectionRegistry` Fallback Path

Status:

- **NOT YET REMOVED**

Context:

- `SectionRegistry` and the broader registered-section fallback path still exist on disk.
- They are not part of the preferred active page rendering flow.

### Legacy Render Fallback Methods

Status:

- **NOT YET REMOVED**

Context:

- fallback methods still exist in `SectionRenderer`
- they remain potential cleanup targets once all remaining dormant fallback needs are formally retired

### `builder_mode` Archive Remnants

Status:

- **NOT YET REMOVED**

Context:

- `builder_mode` still exists as an archived-state marker in parts of the admin UI and page model
- it is no longer the decision point for the active frontend section runtime

### Archived Builder Controller

Status:

- **NOT YET REMOVED**

Context:

- archived builder controller code still exists on disk in the active app tree
- active routes do not expose it as part of the canonical section-editor architecture

These items should be removed only as deliberate cleanup work, not folded back into active runtime design.

## 11. Architectural Principles

The frozen editor architecture follows these principles.

### Definition-Driven Rendering

- frontend section rendering should be controlled by linked definitions and template keys
- rendering should not depend on legacy include switches or archived builder runtime behavior

### Explicit Routing Over Implicit Flags

- controllers choose the editor form directly
- editor selection should not depend on payload booleans or compatibility switches

### Minimal Payload Contracts

- each editor path should expose only the state it actively needs
- compatibility keys should not survive once active consumers are removed

### Isolated Editor Systems

- Dynamic Editor and Shell Editor are separate systems with separate purposes
- shared workspace chrome does not imply shared editor payload shape

### Convention Over Registry Complexity

- frontend templates should resolve through a predictable categorized convention whenever possible
- explicit registry metadata should be additive, not the default dependency

### Runtime Simplicity

- the active runtime should be traceable from page render to resolved template without hidden branches
- failure modes should degrade to explicit missing-template handling rather than silent fallback complexity

### Frontend Template Organization

- frontend section templates should live under `front.sections` and be organized by category
- archived paths such as `components.template.sections` should not be used for new runtime work

### Stable Boundaries

- `Section` remains structural state
- `SectionTranslation` remains localized content
- support classes prepare data
- Blade consumes prepared state

These principles are the basis for future work on sections, definitions, and shell editing.
