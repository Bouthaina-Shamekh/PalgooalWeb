# Rendering Flow

> **Last Updated:** 2026-06-15 · **Status:** Verified against source code  
> **Source files read:** `SectionRenderer`, `SectionDefinitionRuntimeResolver`, `SectionTemplateRegistry`, `SectionDefinitionFrontendViewDataFactory`, `SectionQueryResolver`, `Front\PageController`, `page.blade.php`, `definition-section.blade.php`, `config/sections.php`, `SectionDefinition` model

---

## 1. Purpose

This document traces the complete journey of a page request from the HTTP entry point through to rendered HTML. It is the authoritative runtime reference for anyone debugging a rendering failure, adding a new section type, or understanding why a section shows or does not show.

Do **not** use this document to understand how to create or configure section definitions — see `docs/07-section-definitions.md` for that.

---

## 2. High-Level Overview

```
GET /some-slug
        │
        ▼
Front\PageController@show
        │  loads Page + Sections + SectionTranslations
        │
        ▼
front/pages/page.blade.php
        │  iterates $page->sections->where('is_active', true)
        │  @includes definition-section partial for each
        │
        ▼
front/pages/partials/definition-section.blade.php
        │  calls SectionRenderer::renderDefinitionDriven()
        │  with disable_legacy_fallback = true
        │
        ▼
SectionDefinitionFrontendViewDataFactory::build()
        │
        ├── runtimeTablesAvailable()?  ──NO──► return null → _missing-template
        │
        ├── resolveRenderableDefinition(section)?  ──NO──► return null → _missing-template
        │
        ├── primaryTemplateKey()?  ──NO──► return null → _missing-template
        │
        ├── normalizeContent()  ← field defaults + SectionQueryResolver
        │
        ├── SectionTemplateRegistry::resolve(templateKey, category)
        │       ├── check config/sections.php template_registry
        │       └── fallback: convention path front.sections.{category}.{key}
        │
        ├── View::exists(candidateView)?
        │       ├── YES → return ['view' => ..., 'viewData' => [...]]
        │       └── NO  → return ['view' => '_missing-template', 'viewData' => [...]]
        │
        ▼
view($view, $viewData)->render()
        │
        ▼
HTML Output
```

---

## 3. Core Components

| Class / File | Role | Path |
|---|---|---|
| `Front\PageController` | HTTP entry point for public pages | `app/Http/Controllers/Front/PageController.php` |
| `page.blade.php` | Page layout: iterates sections, handles SEO meta | `resources/views/front/pages/page.blade.php` |
| `definition-section.blade.php` | Per-section render dispatcher | `resources/views/front/pages/partials/definition-section.blade.php` |
| `SectionRenderer` | Static render orchestrator; public API | `app/Support/Sections/SectionRenderer.php` |
| `SectionDefinitionFrontendViewDataFactory` | Core definition-driven render pipeline | `app/Support/Sections/SectionDefinitionFrontendViewDataFactory.php` |
| `SectionDefinitionRuntimeResolver` | Runtime checks: tables available? definition active? | `app/Support/Sections/SectionDefinitionRuntimeResolver.php` |
| `SectionTemplateRegistry` | Maps template_key → Blade view path | `app/Support/Sections/SectionTemplateRegistry.php` |
| `SectionQueryResolver` | Injects live DB data into section content for dynamic section types | `app/Support/Sections/SectionQueryResolver.php` |
| `SectionRegistry` | Legacy code-side registry (type → view) | `app/Support/Sections/SectionRegistry.php` |
| `config/sections.php` | Static template_registry + icon_library | `config/sections.php` |

---

## 4. Request Lifecycle

### Step 1 — HTTP Routing

```
GET /      → Front\PageController@home
GET /{slug} → Front\PageController@show
```

`home()` finds the marketing page with `is_home = true` (falls back to first active marketing page).  
`show()` finds the marketing page whose translation has `slug = $slug` for the current locale (falls back to any locale, then does a 301 redirect to the canonical locale slug).

Both load the page with eager loading:
```php
Page::with([
    'translations',
    'sections' => fn($q) => $q->orderBy('order'),
    'sections.translations',
])
->where('context', 'marketing')
->where('is_active', true)
```

Both return `view('front.pages.page', ['page' => $page])`.

### Step 2 — Page Blade (SEO + section loop)

`resources/views/front/pages/page.blade.php` runs a `@php` block first to prepare:
- `$pageTitle`, `$pageDescription`, `$pageKeywords` — from `PageTranslation` meta fields
- `$pageOgImage` — resolves numeric Media IDs, URL strings, or falls back to `config('seo.default_image')`
- `$pageSchema` — Schema.org JSON-LD (WebSite for homepage, WebPage for others)

Then it renders sections:

```php
$legacySections = $page->sections->where('is_active', true)->values();

if ($legacySections->isNotEmpty()) {
    // — iterate and include definition-section partial —
} else {
    // WYSIWYG fallback: output raw $pageTranslation->content as HTML
}
```

> **Note:** The "WYSIWYG fallback" only runs if the page has zero active sections.  
> It outputs `$pageTranslation->content` directly as raw HTML via `{!! ... !!}`.

### Step 3 — Per-Section Dispatch Partial

`resources/views/front/pages/partials/definition-section.blade.php` is included for each section.  
This partial is the **real render entry point** — not `SectionRenderer::render()`.

```php
$definitionDrivenSectionHtml = SectionRenderer::renderDefinitionDriven(
    $section,
    $currentLocale,
    ['disable_legacy_fallback' => true],   // ← KEY: bypasses ALL legacy fallback
);

if (empty(trim($definitionDrivenSectionHtml))) {
    // Build missingTemplate payload and render _missing-template view
    $fallbackHtml = view(SectionTemplateRegistry::fallbackView(), [
        'missingTemplate' => $missingTemplate,
    ])->render();
}
```

**Critical architectural fact:** With `disable_legacy_fallback: true`, if a section has no linked definition, no active template, or a Blade file that doesn't exist on disk — it **always** shows `_missing-template`. It never falls back to `SectionRegistry` (legacy).

`SectionRenderer::render()` (which includes the legacy fallback) is the **public API** but is **not used** by the active front-end rendering path.

---

## 5. Definition-Driven Rendering

`SectionRenderer::renderDefinitionDriven()` delegates immediately to the factory:

```php
public static function renderDefinitionDriven(
    Section $section,
    ?string $locale = null,
    array $extraViewData = [],
): ?string {
    $renderPayload = app(SectionDefinitionFrontendViewDataFactory::class)->build(
        $section, $locale, $extraViewData,
    );

    if (! is_array($renderPayload)) {
        return null;
    }

    return view($renderPayload['view'], $renderPayload['viewData'])->render();
}
```

The factory's `build()` method returns one of three outcomes:

| Return value | Meaning |
|---|---|
| `null` | Section must use legacy renderer or show missing-template |
| `['view' => '...', 'viewData' => [...]]` where view = resolved path | Success: render this Blade file |
| `['view' => '...', 'viewData' => [...]]` where view = `_missing-template` | Blade file not found; show debug info |

### Factory decision chain

```
build($section, $locale, $extraViewData)
    │
    ├─ runtimeTablesAvailable()?
    │      checks: section_definitions, section_templates, section_definition_template tables exist
    │              AND sections.section_definition_id column exists
    │      NO → return null
    │
    ├─ resolveRenderableDefinition($section)
    │      checks: $section->section_definition_id is set
    │      loads: SectionDefinition where is_active=true
    │             WITH templates (is_active=true, ordered by pivot sort_order)
    │             WITH fields (is_active=true, ordered by sort_order)
    │      checks: hasPrimaryTemplate() — first template has a template_key
    │      NO → return null
    │
    ├─ primaryTemplateKey()?
    │      $definition->templates->first()->template_key
    │      EMPTY → return null
    │
    ├─ $translation = $section->translation($locale)
    │  $content = $translation->content (JSON cast to array)
    │
    ├─ normalizeContent($content, $definition, $locale, ...)
    │      ← see §7
    │
    ├─ SectionTemplateRegistry::resolve($templateKey, $definition->category)
    │      ← see §6
    │
    ├─ $resolvedView = $templateResolution['view']
    │
    ├─ $resolvedView found?
    │      YES → return ['view' => $resolvedView, 'viewData' => [...]]
    │
    └─ $resolvedView NOT found:
           ├─ disable_legacy_fallback = false AND shouldUseLegacyFallback()?
           │      checks: resolution_source is 'convention'
           │              AND SectionRenderer::hasLegacyRenderer($resolvedType)
           │      YES → return null  (→ legacy renderer takes over in SectionRenderer::render())
           │
           └─ otherwise (disable_legacy_fallback = true, or no legacy renderer exists)
                  → return ['view' => _missing-template, 'viewData' => missingTemplatePayload]
```

---

## 6. Template Resolution

`SectionTemplateRegistry::resolve($templateKey, $category)` resolves a `template_key` to a Blade view path in two phases:

### Phase 1 — Explicit registry lookup

Checks `config('sections.template_registry.templates')` (merged with any runtime `SectionTemplateRegistry::register()` calls):

```php
// config/sections.php — current registered templates
'template_registry' => [
    'fallback_view' => 'front.sections._missing-template',
    'templates' => [
        'portfolio_slider' => [
            'label' => 'Portfolio Slider',
            'view'  => 'front.sections.portfolio.portfolio_slider',
            'category' => 'portfolio',
        ],
    ],
],
```

If found → `resolution_source = 'registry'`  
View must still pass `View::exists()` — a registered entry with a wrong path will still fail.

### Phase 2 — Convention-based path

If not in registry → construct: `'front.sections.' . normalizeCategory($category) . '.' . $templateKey`

```
template_key : hero_main
category     : hero
               ↓
convention   : front.sections.hero.hero_main
               ↓
file path    : resources/views/front/sections/hero/hero_main.blade.php
```

`normalizeCategory()`: lowercases and validates against `/^[a-z0-9_-]+$/`; returns `'uncategorized'` if empty or invalid.

If `View::exists()` passes → `resolution_source = 'convention'`, `found = true`  
If `View::exists()` fails → `found = false`, view = null

### `resolve()` return shape

```php
[
    'template_key'    => 'hero_main',
    'found'           => true,          // or false
    'view'            => 'front.sections.hero.hero_main',  // or null
    'source'          => 'registry' | 'convention' | 'invalid',
    'descriptor'      => [...],         // full normalized template entry
    'attempted_views' => ['front.sections.hero.hero_main'],
]
```

---

## 7. View Data Construction

### normalizeContent()

Before rendering, `SectionDefinitionFrontendViewDataFactory::normalizeContent()` enriches the raw `SectionTranslation.content` JSON:

**Step A — Field defaults**
For each active `SectionDefinitionField` on the definition:
- If `field_key` is already present in `$content` → skip (never overwrite saved values)
- If `field_key` is absent → resolve default via `resolvedDefaultValue()`:
  - Translatable fields: try `default_value[$locale]`, then `default_value[$fallbackLocale]`, then `reset($default_value)` (first available)
  - Shared fields: read from `default_value['value']`

**Step B — Title fallback**
If `'title'` key is still not in `$content`:
- Try `$translation->title`
- Try `$definition->label`

**Step C — SectionQueryResolver injection**
```php
$resolvedData = SectionQueryResolver::resolve($definition->section_key, $normalizedContent);

return $resolvedData === $normalizedContent && $templateKey
    ? SectionQueryResolver::resolve($templateKey, $normalizedContent)
    : $resolvedData;
```

`SectionQueryResolver` injects live DB data for specific section types:

| Type identifier | Data injected |
|---|---|
| `testimonials`, `reviews_showcase` | `$data['testimonials']` — approved Testimonial models |
| `reviews_slider` | `$data['reviews_items']` — normalized testimonial payloads |
| `services` | `$data['services']` — Service models |
| `our_work_showcase` | `$data['portfolios']` — Portfolio models |
| `portfolio_slider`, `portfolio_showcase` | `$data['portfolio_items']` — normalized portfolio payloads |
| `hosting_pricing_showcase` | `$data['plan_categories']` — PlanCategory models with hosting plans |
| `templates_slider_showcase`, `templates_showcase` | `$data['templates']` — Template models |
| `templates_listing_showcase` | `$data['templates']` — all Template models (paginated by the Blade view) |
| `search-domain` | `$data['default_tlds']`, `$data['fallback_prices']` |
| anything else | `$data` unchanged (pure static content) |

The resolver is called twice: once with `section_key`, once with `template_key`. If the first call changes `$data`, the second call is skipped.

### Variables available in Blade views

The factory passes these to the rendered Blade view:

| Variable | Type | Contents |
|---|---|---|
| `$data` | `array` | The normalized content: saved field values + defaults + injected DB data. **This is the primary content variable.** |
| `$content` | `array` | Alias for `$data` (same reference) |
| `$section` | `Section` | The Eloquent Section model |
| `$title` | `string\|null` | `$translation->title` |
| `$translation` | `SectionTranslation\|null` | The translation for current locale |
| `$variant` | `string\|null` | `$section->variant` |
| `$currentLocale` | `string` | Current locale (e.g. `'ar'`) |
| `$sectionDefinition` | `SectionDefinition` | The linked definition model |
| `$sectionDefinitionFields` | `Collection` | Collection of `SectionDefinitionField` model objects (the schema — **not** the values) |
| `$sectionTemplate` | `SectionTemplate\|null` | The primary template model |
| `$sectionTemplateKey` | `string` | The `template_key` string |
| `$sectionTemplateMeta` | `array\|null` | Template descriptor from registry |
| `$resolvedSectionType` | `string\|null` | Alias-normalized `section_key` |

> **Important:** The primary content variable is `$data` (and `$content` as alias), **not** `$fields`.  
> `$sectionDefinitionFields` is a Collection of field **schema objects** (type, label, scope), not a key→value map.

Access pattern in Blade views:
```blade
{{-- text, textarea, url, boolean, select --}}
{{ $data['title'] ?? '' }}
{{ $data['subtitle'] ?? '' }}

{{-- media (path string) --}}
<img src="{{ $data['hero_image'] ?? '' }}" alt="">

{{-- boolean --}}
@if (!empty($data['show_cta']))

{{-- repeater --}}
@foreach ($data['items'] ?? [] as $item)
    {{ $item['title'] ?? '' }}
@endforeach

{{-- dynamic DB data (injected by SectionQueryResolver) --}}
@foreach ($data['testimonials'] ?? [] as $testimonial)
@foreach ($data['plan_categories'] ?? [] as $category)
```

---

## 8. Legacy Rendering Fallback

`SectionRenderer::renderRegisteredSection()` is the legacy path. It only activates when:
1. The caller uses `SectionRenderer::render()` (not `renderDefinitionDriven()` directly)
2. `renderDefinitionDriven()` returned `null`

This path uses `SectionRegistry::get($section->type)` to look up a code-side config array with a `'view'` key. If the type is not registered, it returns an HTML comment: `<!-- Section type 'X' not registered -->`.

**Currently registered legacy types** (in `LEGACY_FRONTEND_SECTION_TYPES`):

```php
protected const LEGACY_FRONTEND_SECTION_TYPES = [
    'hero', 'hero_campaign', 'programming_showcase', 'mobile_app_showcase',
    'how_we_build', 'design_showcase', 'digital_marketing_showcase',
    'tech_stack_showcase', 'reviews_showcase', 'our_work_showcase',
    'hosting_pricing_showcase', 'domains_showcase', 'templates_slider_showcase',
    'templates_listing_showcase', 'features_grid', 'services_grid',
    'templates_showcase',
];
```

The legacy path also normalizes `'templates-pages'` to `'templates_listing_showcase'` via `resolvedLegacySectionType()`.

The `features` type gets special normalization via `normalizeFeaturesData()` which restructures the JSON content into a consistent shape expected by the Blade view.

**The active front page rendering path (`definition-section.blade.php`) always uses `disable_legacy_fallback: true`** — so this entire path is bypassed on public pages. The legacy path can be reached through `SectionRenderer::render()` if called directly by other controllers.

---

## 9. Runtime Resolution Rules

### resolveRenderableDefinition() vs resolveDynamicDefinition()

| Check | `resolveRenderableDefinition()` (frontend) | `resolveDynamicDefinition()` (editor) |
|---|---|---|
| `is_active = true` | ✓ required | ✓ required |
| `editor_mode` filter | ✗ not filtered | ✓ must be `dynamic` |
| `hasPrimaryTemplate()` | ✓ required | ✓ required |
| Purpose | Frontend rendering | Admin dynamic section editor |

### resolveLinkedDefinition() checks (in order)

1. `runtimeTablesAvailable()` — all 3 tables and the FK column exist in `schema`
2. `$section->section_definition_id` is set (non-null)
3. `SectionDefinition::where('is_active', true)` — definition must be active
4. (optional) `where('editor_mode', $editorMode)` — only for editor path
5. Eager loads: `templates` (where `is_active=true`, ordered by pivot `sort_order`, then `id`) and `fields` (where `is_active=true`, ordered by `sort_order`, `id`)

### runtimeTablesAvailable() — what it checks

```php
Schema::hasTable('section_definitions')
&& Schema::hasTable('section_templates')
&& Schema::hasTable('section_definition_template')
&& Schema::hasColumn('sections', 'section_definition_id')
```

This is a Schema-level check. It runs once per request (Schema facade caches results).

---

## 10. Error Handling

### Outcome: `_missing-template` view

Shown when definition-driven rendering fails to produce HTML and `disable_legacy_fallback = true`. The `_missing-template` view receives a `$missingTemplate` array:

```php
[
    'title'                => 'Section renderer not found',
    'message'              => 'Template key "..." could not be resolved for definition "...".',
    'details'              => ['...source-specific message...'],
    'template_key'         => 'hero_main',
    'category'             => 'hero',
    'section_key'          => 'hero_main',
    'resolved_section_type'=> 'hero_main',
    'resolution_source'    => 'convention' | 'registry' | 'missing' | 'invalid',
    'attempted_views'      => ['front.sections.hero.hero_main'],
    'section_id'           => 42,
    'section_definition_id'=> 7,
]
```

This payload lets the admin panel display useful debug information — what view was attempted and why it failed.

### Outcome: HTML comment

Only from legacy path: `<!-- Section type 'X' not registered -->`. Invisible to users, visible in page source. Indicates a `SectionRegistry` lookup failure.

### Outcome: 404

`Front\PageController@show` calls `firstOrFail()` — throws `ModelNotFoundException` → 404.  
If a page is found but is inactive or not `marketing` context → `abort(404)`.

### No exception from rendering

The rendering pipeline never throws. `null` returns propagate gracefully, and the Blade render is wrapped in the parent template's `@section('content')` block.

---

## 11. Runtime Decision Matrix

Full decision table for `SectionDefinitionFrontendViewDataFactory::build()`. Each row is evaluated in order; the first matching condition determines the outcome.

| # | Condition | `disable_legacy_fallback` | Outcome | Fallback? |
|---|-----------|--------------------------|---------|-----------|
| 1 | `runtimeTablesAvailable()` = false | any | `null` → `_missing-template` | ✗ no fallback |
| 2 | `section.section_definition_id` is null | any | `null` → `_missing-template` | ✗ no fallback |
| 3 | Linked `SectionDefinition.is_active` = false | any | `null` → `_missing-template` | ✗ no fallback |
| 4 | Definition has no active `SectionTemplate` (pivot empty) | any | `null` → `_missing-template` | ✗ no fallback |
| 5 | `primaryTemplateKey()` is empty/null | any | `null` → `_missing-template` | ✗ no fallback |
| 6 | `SectionTemplateRegistry::resolve()` → `found = true` | any | ✅ render resolved Blade view | — success |
| 7 | `resolve()` → `found = false`, source = `convention`, type has legacy renderer | `false` | `null` → `SectionRegistry` legacy path | ✓ legacy |
| 8 | `resolve()` → `found = false`, source = `convention`, type has legacy renderer | `true` | `_missing-template` with full debug payload | ✗ blocked |
| 9 | `resolve()` → `found = false`, source = `registry` (explicit entry, view missing) | any | `_missing-template` | ✗ no fallback |
| 10 | `resolve()` → `found = false`, source = `convention`, no legacy renderer | any | `_missing-template` | ✗ no fallback |

> **Active front page always uses `disable_legacy_fallback = true` (rows 7 vs 8):**  
> `definition-section.blade.php` passes this flag explicitly, so row 7 never fires on public pages — legacy sections always show `_missing-template`, not their old content.

---

## 12. Common Failure Scenarios

### Scenario A — Section shows `_missing-template`

**What happened:** `renderDefinitionDriven()` returned `null` or empty.

**Diagnosis checklist:**
1. Does `$section->section_definition_id` have a value? (null = no link)
2. Is the linked `SectionDefinition.is_active = true`?
3. Does the definition have a `SectionTemplate` linked via the pivot (`section_definition_template`)?
4. Is that `SectionTemplate.is_active = true`?
5. Does the Blade file exist at `resources/views/front/sections/{category}/{template_key}.blade.php`?

**Hint:** The `$missingTemplate['resolution_source']` in the rendered debug info tells you which step failed:
- `'invalid'` → `template_key` failed regex validation
- `'convention'` or `'registry'` with `found = false` → Blade file missing from disk

### Scenario B — Section shows nothing (blank space)

`renderDefinitionDriven()` returned `null` and `disable_legacy_fallback = true` AND `$fallbackHtml` is also empty. This should not happen in the current flow — `_missing-template` is always returned when the factory returns null with `disable_legacy_fallback`.

If it does happen: check that `fallback_view` in `config/sections.php` points to a view that exists.

### Scenario C — Section renders but shows wrong/stale content

Most likely cause: `SectionTranslation.content` was not saved correctly, or the wrong locale translation is being loaded.

Check:
- `$section->translation($locale)` returns the right record
- `SectionTranslation.content` is a valid JSON array (the column is cast)
- `normalizeContent()` is not overwriting the saved value with a default

### Scenario D — Page 404 despite the page existing

- Check `$page->context === 'marketing'` — tenant pages are not served by `Front\PageController`
- Check `$page->is_active === true`
- Check the slug for the current locale in `page_translations`
- Check that the locale middleware is not misidentifying the current locale

### Scenario E — `runtimeTablesAvailable()` returns false

Happens when a migration hasn't been run. All definition-driven sections will return `null` from the factory and fall through to `_missing-template`. Run `php artisan migrate` to resolve.

### Scenario F — Definition has a template_key but Blade file was never written

`SectionTemplateFileWriter` was never called, or it failed. `View::exists()` returns false. Resolution shows `attempted_views: ['front.sections.{category}.{key}']` with `found: false`.

Fix: go to `GET /admin/section-definitions/{id}/edit`, write the Blade template via the editor, click "Write File".

### Scenario G — SectionQueryResolver not injecting expected DB data

The `resolve()` match uses `strtolower(trim($type))`. If `section_key` or `template_key` differs by case or spacing from the match arm, the resolver returns `$data` unchanged.

Check that `SectionDefinition.section_key` exactly matches one of the resolver's `match` arms.

---

## 12. Debugging Guide

### Quick debug flow for a broken section

```bash
# 1. Identify the section's definition
# In tinker or a nova-like tool:
Section::find($sectionId)->section_definition_id

# 2. Check definition status
SectionDefinition::find($defId)->only(['section_key', 'is_active', 'editor_mode'])

# 3. Check template
SectionDefinition::find($defId)->primaryTemplateKey()

# 4. Check view resolution
SectionTemplateRegistry::resolve($templateKey, $category)
# Look at: found, view, source, attempted_views

# 5. Check if Blade file exists on disk
View::exists('front.sections.hero.hero_main')
```

### Reading `_missing-template` debug output

The `_missing-template` view renders the full `$missingTemplate` array. In production it may be hidden behind admin-only visibility. In local dev, the array contains everything you need to diagnose the failure.

### Important: Schema cache

`Schema::hasTable()` and `Schema::hasColumn()` results are cached within a request. If you add columns or tables mid-test without restarting, `runtimeTablesAvailable()` may return stale results. Use `Schema::refreshDatabaseCache()` or restart the dev server.

---

## 13. File Map

```
app/
├── Http/Controllers/Front/
│   └── PageController.php                      Home + show actions
│
├── Support/Sections/
│   ├── SectionRenderer.php                     Static orchestrator — render() + renderDefinitionDriven()
│   ├── SectionDefinitionFrontendViewDataFactory.php  Main factory — build() decision chain
│   ├── SectionDefinitionRuntimeResolver.php    Runtime guards: tables? active? primary template?
│   ├── SectionTemplateRegistry.php             template_key → Blade view path
│   ├── SectionQueryResolver.php                Injects live DB data by section type
│   ├── SectionRegistry.php                     Legacy type → view registry
│   └── SectionTemplateFileWriter.php           Writes blade_source to disk (editor path)
│
└── Models/
    └── Sections/
        ├── SectionDefinition.php               primaryTemplate(), primaryTemplateKey()
        └── SectionTemplate.php                 template_key column

config/
└── sections.php                                template_registry (one explicit entry: portfolio_slider)

resources/views/front/
├── pages/
│   ├── page.blade.php                          SEO prep + section loop (includes definition-section)
│   └── partials/definition-section.blade.php  Per-section dispatcher (disable_legacy_fallback=true)
└── sections/
    ├── _missing-template.blade.php             Shown when Blade file not found
    └── {category}/{template_key}.blade.php     Definition-driven Blade views
```

---

## 14. Behaviors to Verify

The following behaviors were observed in code but are worth confirming with a live test:

| Behavior | Code location | What to check |
|---|---|---|
| Legacy sections (`section_definition_id = null`) show `_missing-template`, not legacy content, on public pages | `definition-section.blade.php` | Visit a page that has old-style sections with no linked definition |
| `resolveRenderableDefinition()` does NOT filter by `editor_mode` | `SectionDefinitionRuntimeResolver.php:59` | A definition with any `editor_mode` value can render on the frontend |
| `SectionQueryResolver` is called twice: once with `section_key`, once with `template_key` | `SectionDefinitionFrontendViewDataFactory.php:204-208` | If `section_key` resolves data, `template_key` is skipped |
| `portfolio_slider` is the only explicitly registered template | `config/sections.php` | All other section types use convention-based path |
| `SectionRenderer::render()` (with legacy fallback) is NOT used by `page.blade.php` | `definition-section.blade.php` | Legacy rendering only possible through direct `render()` calls elsewhere |

---

## 15. FAQ

**Q: Why does `page.blade.php` use `renderDefinitionDriven()` directly instead of `render()`?**  
A: The current design intentionally bypasses legacy rendering for public pages. With `disable_legacy_fallback: true`, any section without a valid definition-driven path shows `_missing-template` — a visible error for the admin to diagnose and fix. Silently falling back to legacy behavior was considered less safe than explicit failure.

**Q: When is `SectionRenderer::render()` (with legacy fallback) actually used?**  
A: It is a public static method but the active page rendering path does not call it directly. It may be used by other controllers or test harnesses. Developers calling `SectionRenderer::render()` from custom code will get legacy fallback behavior.

**Q: Why is `resolveRenderableDefinition()` different from `resolveDynamicDefinition()`?**  
A: The admin dynamic editor only activates for definitions with `editor_mode = dynamic`. But the frontend renderer accepts any active definition that has a primary template key, regardless of editor mode. This allows definitions that were created before `editor_mode` was normalized to still render on the frontend.

**Q: What is `SectionQueryResolver` and why does it exist?**  
A: Some section types (pricing, testimonials, templates) need live DB data (the full list of plans, all approved testimonials, etc.) — data that cannot be stored in `SectionTranslation.content`. `SectionQueryResolver` is a central dispatcher that enriches the `$data` array with the right live DB query for each recognized type. Without it, a `hosting_pricing_showcase` section would have no plans to display.

**Q: The variable in the Blade view is `$data`, not `$fields` — where did `$fields` come from?**  
A: Earlier documentation described the intended final API (`$fields`). The current implementation uses `$data` (and `$content` as alias). The naming in earlier docs was aspirational. A future refactor may expose `$fields` as a cleaner contract, but today's Blade views must use `$data`.

> **View Contract note:** There is an unresolved naming gap between `$data` (current code) and `$fields` (earlier docs). An ADR (`docs/adr/`) should be created to decide whether to rename the variable in the factory output, add `$fields` as a second alias, or keep `$data` and update all documentation. Until that decision is made, Blade views must use `$data`.

**Q: What happens if two definitions share the same `template_key`?**  
A: `SectionTemplateRegistry` resolves `template_key` to a single Blade view — the view itself is shared. Both definitions would render the same Blade file but with their own `$data` (their own saved content). This is intentional: one Blade file, multiple configurable data contexts.

---

## Related Documents

| Document | Topic |
|----------|-------|
| [`docs/00-project-overview.md`](00-project-overview.md) | High-level architecture: what Palgoals is, major components, request flow summary |
| [`docs/07-section-definitions.md`](07-section-definitions.md) | Section Definitions system: data model, field types, admin workflow, authorization, endpoints |
| [`docs/08-section-blade-editor.md`](08-section-blade-editor.md) | Blade Editor: Monaco editor, `write-blade` endpoint, `SectionTemplateFileWriter`, Apache redirect fix |
