# Refactor Plan

## Purpose

This plan describes a safe, incremental refactor strategy for the admin page sections editor.

It is written for a codebase that already works and already stores live production content. The goal is not to redesign the system from scratch. The goal is to make the existing editor easier to maintain without breaking behavior.

## Success Criteria

A successful refactor should produce:

- a smaller, clearer shared Blade partial
- a better controller-to-view contract
- less duplicated editor preparation logic
- preserved save behavior
- preserved frontend rendering behavior
- easier support for new section types

## Scope

In scope:

- admin section editor read-time preparation
- support-layer extraction for editor-specific state
- reuse boundaries with existing `app/Support/Sections/**` classes

Out of scope for early phases:

- rewriting the sections system
- replacing save-time normalization wholesale
- renaming section types globally
- migrating all existing section payloads
- merging all support classes immediately

## Current Problem Statement

The admin editor is working, but responsibilities are poorly distributed.

Today:

- `SectionController` owns save-time validation and normalization
- the shared editor partial owns a large amount of read-time preparation
- some support classes already exist, but their responsibilities do not cleanly cover admin editor preparation

This creates three risks:

- Blade is harder to reason about and test
- read-time and write-time rules can drift apart
- adding a new section type is more expensive than it should be

## Current File Boundaries

### Core Files Involved

- `app/Http/Controllers/Admin/SectionController.php`
- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`
- `resources/views/dashboard/pages/sections/edit.blade.php`
- `resources/views/dashboard/pages/sections/partials/sidebar-editor.blade.php`
- `app/Support/Sections/SectionQueryResolver.php`
- `app/Support/Sections/BuilderSectionDataResolver.php`
- `app/Support/Sections/SectionRegistry.php`
- `app/Support/Sections/SectionRenderer.php`

## File Strategy

### Keep

Keep these files in place:

- `app/Http/Controllers/Admin/SectionController.php`
- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`
- `resources/views/dashboard/pages/sections/edit.blade.php`
- `resources/views/dashboard/pages/sections/partials/sidebar-editor.blade.php`
- `app/Support/Sections/SectionQueryResolver.php`
- `app/Support/Sections/BuilderSectionDataResolver.php`
- `app/Support/Sections/SectionRegistry.php`
- `app/Support/Sections/SectionRenderer.php`

Reason:

- the goal is targeted extraction, not file churn

### Extend

#### `SectionController`

Extend it by delegating read-time editor preparation from `sectionEditorViewData()` to a dedicated support class.

Do not move or rewrite the save-time normalization logic in early phases.

#### `SectionQueryResolver`

Extend only if a later phase needs to reuse existing DB-backed enrichment logic for both frontend rendering and admin preload behavior.

This is optional and should not be the first step.

### Add Only If Necessary

Recommended new file:

- `app/Support/Sections/SectionEditorDataFactory.php`

Purpose:

- prepare editor read-time state
- keep Blade focused on rendering
- avoid changing request contracts or persisted content shape

Production rule:

- phase 1 should add at most one new support file unless a second file is clearly justified

## Existing Support Classes: Use Or Avoid

### Reuse: `SectionQueryResolver`

Good fit for:

- frontend dynamic data enrichment
- later shared DB-backed query rules

Why:

- already active in frontend rendering
- already knows about reviews, portfolios, hosting pricing, templates, and domain data

### Avoid Extending In Phase 1: `BuilderSectionDataResolver`

Why:

- overlaps with `SectionQueryResolver`
- extending it now increases ambiguity rather than reducing it

### Avoid Promoting In Phase 1: `SectionRegistry`

Why:

- current registry entries are too coarse for admin editor field visibility and per-type editor behavior

### Avoid Reusing For Editor Preparation: `SectionRenderer`

Why:

- it is render-oriented, not editor-oriented

## What Must Move Out Of Blade

The shared editor partial currently contains logic that belongs in support code.

Priority list:

- selected type canonicalization
- legacy alias handling
- field visibility rules
- default editor locale calculation
- admin preload queries
- media preview preparation
- locale-aware scalar fallback preparation
- repeater preparation

## What Must Stay Stable During Refactor

These are hard constraints for safe incremental work:

- request input names
- translation array shape
- existing route contract
- admin JavaScript hooks and repeater template structure
- save-time normalization behavior
- stored content compatibility with existing rows

Production warning:

- breaking any of the above may not be obvious until a real editor workflow or a previously saved section is loaded again

## Phase Plan

### Phase 1: Extract Top-Level Editor State

Move out of Blade:

- selected type resolution
- alias handling such as `templates-pages`
- top-level field visibility booleans
- `usesInternalLabel`
- default editor locale selection
- hosting pricing category preload

Why first:

- low risk
- high readability gain
- minimal effect on nested data contracts

Expected output:

- controller passes a prepared editor-state array
- Blade consumes that state without computing it itself

#### Example Target Output

```php
[
	'selectedType' => 'programming_showcase',
	'defaultLocale' => 'en',
	'flags' => [
		'showDescriptionField' => true,
		'showPrimaryButtonFields' => true,
		'showMediaUrlField' => true,
	],
	'hostingPricingAvailableCategories' => $categories,
]
```

### Phase 2: Extract Media Preview Preparation

Move out of Blade:

- media ID to preview URL resolution
- asset-relative path handling
- preview array creation for galleries and logo lists

Why second:

- clearly not presentation logic
- reusable across multiple section types
- removes direct `Media` lookups from Blade

Edge case coverage required:

- numeric media ID
- absolute URL
- root-relative path
- asset-relative path
- empty value

### Phase 3: Extract Locale-Aware Scalar Preparation

Move out of Blade:

- `old()` precedence
- saved value fallback
- default values
- string coercion
- button field extraction

Why third:

- still relatively contained
- large readability improvement
- less risky than nested repeater extraction

### Phase 4: Extract Repeater Preparation Incrementally

Recommended order:

1. campaign features
2. programming outputs
3. service items
4. build steps
5. hosting pricing categories and plans

Why this order:

- each family has a slightly different shape
- smaller steps reduce regression scope
- hosting pricing is the most coupled to dynamic data and should move last

Production warning:

- repeater extraction is the phase most likely to break validation re-renders and nested input naming

### Phase 5: Reassess Support Layer Boundaries

After the editor read path is stabilized:

- decide whether `SectionQueryResolver` should absorb shared DB-backed rules
- decide whether `BuilderSectionDataResolver` should be merged, retired, or left isolated
- decide whether a richer section metadata registry is justified

Do not start here.

## First Safe Implementation Step

Recommended first coding step:

### Add

- `app/Support/Sections/SectionEditorDataFactory.php`

### Extend

- `app/Http/Controllers/Admin/SectionController.php`

### Move In This Step Only

- selected type resolution
- alias handling
- field visibility flags
- default locale selection
- hosting pricing category preload

### Explicitly Leave In Blade For Now

- per-locale scalar preparation
- repeater preparation
- media preview preparation
- nested `old()` fallback logic

## Validation Plan

After every phase, verify all of the following:

- standalone section edit page loads
- inline sidebar editor loads
- expected fields appear for every affected section type
- validation errors repopulate fields correctly
- saving still persists the same content shape
- preview/workspace continues to behave correctly
- frontend output remains unchanged for affected sections

## Regression Checklist

### Structural Checks

- route names still resolve
- controller methods still receive the same input shapes
- wrapper views still render the shared partial correctly

### Section-Type Checks

- `hero_default` still shows its expected simple hero fields
- `hero_campaign` still supports feature repeaters
- `programming_showcase` still supports outputs and CTA fields
- `how_we_build` still preserves icon and accent flags
- `hosting_pricing_showcase` still supports category visibility filters
- `templates_listing_showcase` still preserves legacy alias and title fallback behavior

### Data Contract Checks

- old saved rows still load
- validation re-render still works for nested arrays
- inactive sections remain editable
- missing locale translations do not crash the editor

## Anti-Patterns To Avoid

- rewriting Blade and normalization logic in the same large change
- creating multiple new support abstractions before one is proven useful
- moving dynamic query logic into both `SectionQueryResolver` and a new class at the same time
- renaming persisted content keys without compatibility handling
- changing JavaScript hooks as an incidental side effect of a PHP refactor

## Expected Outcome

If this plan is executed carefully, the result should be:

- a clearer editor architecture
- smaller and more readable Blade templates
- lower risk when adding new section types
- less drift between what the editor displays and what the controller persists

## Related Documents

- `docs/editor-system.md`
- `docs/sections-system.md`
- `docs/architecture.md`
- `docs/developer-guide.md`
