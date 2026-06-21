# Section Design Tokens — Architecture Analysis

**Date:** 2026-06-21  
**Status:** Analysis Only — No implementation in this document  
**Scope:** Design token field registry for the Section Definition system

---

## Table of Contents

1. [Current State](#1-current-state)
2. [Problems](#2-problems)
3. [Candidate Designs](#3-candidate-designs)
4. [Recommended Design](#4-recommended-design)
5. [Proposed Initial Tokens (5 only)](#5-proposed-initial-tokens)
6. [Integration Plan](#6-integration-plan)
7. [Migration Strategy](#7-migration-strategy)
8. [Explicit Non-Goals](#8-explicit-non-goals)

---

## 1. Current State

### 1.1 How Design Fields Are Defined

Design fields in this system are plain `SectionDefinitionField` DB rows — identical in structure to content fields. There is no separate "design token" schema, flag, or type. A design field is distinguished solely by its `field_key` value matching an entry in `SectionFieldClassifier::DESIGN_FIELD_KEYS`.

**`ComponentLibrary`** defines 8 components (`intro`, `description`, `cta`, `image`, `features`, `highlight`, `faq`, `testimonials`, `seo`). Of the 21 known design field keys, only **one** is defined inside a component: `image_position` in the `image` component. All other design keys (`background_token`, `text_token`, `layout_style`, `background_color`, `theme_variant`, `spacing_*`, `padding_*`, etc.) are either:

- Added manually as `extra_fields` in `SectionTemplateLibrary`, or
- Added manually via the Section Definition admin form, or
- Not present at all in the DB (only read from `$data[]` in a Blade file)

**`SectionTemplateLibrary`** currently has 6 templates. None of them include `background_token`, `text_token`, or any spacing field in their `extra_fields`. The only design field that gets auto-added from a template is `image_position` (via the `image` component).

### 1.2 How Design Fields Are Discovered

Discovery happens at two layers:

**Layer 1 — DB at section-definition time:**  
`DynamicSectionEditorRenderer::buildGroupsForLocale()` queries `SectionDefinitionField` rows for a given definition. It has no awareness of "design vs content" — it returns all fields as one flat list.

**Layer 2 — Classifier at render time:**  
`SectionFieldClassifier::splitGroups()` receives the flat list from the renderer and splits it into `content` and `design` buckets based on `DESIGN_FIELD_KEYS`. This is the only place "design fields" are formally identified.

The classifier is a static whitelist (21 keys). If a developer adds a `background_tone` field instead of `background_token`, it will appear in the Content tab, not Design.

### 1.3 How Design Fields Are Displayed in the "التنسيق" Tab

`resources/views/dashboard/pages/sections/partials/dynamic-editor/renderer.blade.php` calls `SectionFieldClassifier::splitGroups()` and renders two separate tab panels:

- **المحتوى** — content groups (visible by default)
- **التنسيق** — design groups (hidden, activated by JS)

If a section definition has no fields in `DESIGN_FIELD_KEYS`, the Design tab shows an empty-state SVG with the message `t('dashboard.No_Design_Fields', ...)`.

The tab remembers the last selection per section via `localStorage` key `section-editor-tab-{section.id}`. Badge counts display the number of fields in each tab.

### 1.4 How Design Tokens Resolve in Blade Files

There is **no shared resolver** for design tokens. Each Blade file handles them independently.

Example from `content_showcase.blade.php`:

```php
$background_token = trim((string) ($data['background_token'] ?? 'muted'));

$backgroundClass = match ($background_token) {
    'primary'   => 'bg-theme-primary',
    'secondary' => 'bg-theme-secondary',
    'surface'   => 'bg-theme-surface',
    'muted'     => 'bg-theme-muted',
    default     => '',
};

$text_token = trim((string) ($data['text_token'] ?? 'heading'));

$textClass = match ($text_token) {
    'body'      => 'text-theme-body',
    'primary'   => 'text-theme-primary',
    'secondary' => 'text-theme-secondary',
    'white'     => 'text-white',
    default     => 'text-theme-heading',
};
```

This match/map logic is **copy-pasted into each section** that uses these tokens. There is no central class that maps `background_token` → Tailwind class.

### 1.5 Current Inventory of Design Fields Actually in Use

| Field Key | Used In (Blade) | Values | Resolved By |
|---|---|---|---|
| `background_token` | `content_showcase.blade.php` | primary / secondary / surface / muted | Local `match()` |
| `text_token` | `content_showcase.blade.php` | body / primary / secondary / white / (default=heading) | Local `match()` |
| `image_position` | `content_showcase.blade.php` | left / right | Local `if` |
| `background_image` | `hero_campaign.blade.php`, `hero_featured.blade.php` | media path/id | `SectionFrontendMediaResolver` |

All other keys in `SectionFieldClassifier::DESIGN_FIELD_KEYS` (`layout_style`, `theme_variant`, `columns`, `button_style`, etc.) are **registered in the classifier but not yet used** in any deployed Blade file.

---

## 2. Problems

### 2.1 Naming Inconsistency — No Canonical Token Names

There is no authoritative list of what a "design token field" is called. A developer writing a new section could reasonably use any of these for the same concept:

| Intent | Possible field_key choices |
|---|---|
| Background palette | `background_token`, `background_color`, `section_background`, `bg_token`, `bg_style`, `color_scheme` |
| Text colour | `text_token`, `text_color`, `text_style`, `color_token` |
| Vertical spacing | `section_spacing`, `spacing`, `padding_top`+`padding_bottom`, `vertical_spacing` |
| Container width | `container_width`, `width`, `section_width`, `layout_width` |

Without a canonical list, two sections solving the same UI problem will use different field keys. This breaks:
- **BladeGenerator** (generates wrong field names)
- **SectionFieldClassifier** (unknown names default to Content tab instead of Design)
- **`ComponentLibrary` + `SectionPackageGenerator`** (can't auto-add design fields if names are unknown)
- **Cross-section consistency** (editors see different Design tabs for similar sections)

### 2.2 BladeGenerator Has No Token Awareness

`BladeGenerator::generate()` reads `SectionDefinitionField` rows and emits Blade code. For a field keyed `background_token`, it currently emits:

```php
$background_token = trim((string) ($data['background_token'] ?? ''));
```

It doesn't know:
- The valid values (`primary`, `secondary`, `surface`, `muted`)
- The default value (`'muted'`)
- The resolution map (`'muted'` → `'bg-theme-muted'`)

So the developer must hand-edit the scaffold to add the `match()` block — defeating the purpose of the generator for design fields.

### 2.3 No Design Component in ComponentLibrary

`ComponentLibrary` has no `design` component. When a developer creates a template using `SectionTemplateLibrary`, they must manually list every design field in `extra_fields`. If they forget `background_token`, the section gets no background control. There's no way to say "include standard background controls" via a component.

### 2.4 The Classifier Whitelist Drifts

`SectionFieldClassifier::DESIGN_FIELD_KEYS` is hand-maintained. Every time a new design field name is invented, a developer must remember to add it. If they don't, the field appears in the Content tab.

### 2.5 Field Options Are Undiscoverable

For a `select` field like `background_token`, valid options must be hardcoded in:
1. The `SectionDefinitionField` options JSON (for the admin form)
2. The Blade `match()` block (for rendering)

These two sources are separate and can diverge. There is no single truth for what values are valid.

---

## 3. Candidate Designs

### Option A — Static PHP Registry (DesignTokenRegistry class)

A single PHP class, similar to `SectionFieldClassifier` and `FieldGroupRegistry`, that contains:

- Canonical token names and their default values
- Valid option sets for select-type tokens
- Tailwind class resolution maps per token
- PHP method: `resolve(string $token, string $value): string`

```php
// Conceptual:
class DesignTokenRegistry {
    const TOKENS = [
        'background_token' => [
            'type'    => 'select',
            'default' => 'muted',
            'options' => ['primary','secondary','surface','muted','none'],
            'resolve' => [
                'primary'   => 'bg-theme-primary',
                'secondary' => 'bg-theme-secondary',
                'surface'   => 'bg-theme-surface',
                'muted'     => 'bg-theme-muted',
                'none'      => '',
            ],
        ],
        // ...
    ];
}
```

**Pros:**
- Zero DB changes
- Zero migration
- Works within current architecture (like `SectionFieldClassifier`)
- BladeGenerator can query it to emit correct `match()` blocks
- SectionFieldClassifier can derive its `DESIGN_FIELD_KEYS` from it (single source of truth)
- Options can be auto-populated in `FieldPresetLibrary` presets

**Cons:**
- Adding a new token requires a code deploy
- Token→CSS mappings are PHP; the front-end Blade must still call the resolver
- Theme variants must be hardcoded in PHP (no DB customisation per site)

---

### Option B — Database-Driven (design_tokens table)

A DB table stores token definitions, valid values, and CSS mappings. The admin can add or edit tokens via a UI. Blade files call a service that queries this table.

```sql
CREATE TABLE design_tokens (
    id INT PRIMARY KEY,
    token_key VARCHAR(100) UNIQUE,
    default_value VARCHAR(100),
    field_type ENUM('select','text','color'),
    options JSON,    -- [{"value":"muted","css":"bg-theme-muted"}, ...]
    sort_order INT
);
```

**Pros:**
- Fully dynamic — new tokens without a deploy
- Per-tenant customisation possible in the future
- UI-editable CSS mappings

**Cons:**
- New migration (a new table)
- DB query per section render (or cache required)
- Significant complexity for a concept that has only 2 deployed tokens (`background_token`, `text_token`)
- Over-engineered for the current scale (1 section using design tokens)

---

### Option C — Hybrid (Static Registry + DB override layer)

A static `DesignTokenRegistry` class (Option A) as the base, with an optional DB override table that can add or override tokens. The registry checks DB first (with cache), falls back to static PHP.

**Pros:**
- Static defaults work without any DB setup
- DB layer enables future customisation without rebuilding the registry

**Cons:**
- Two sources of truth to maintain
- Cache invalidation complexity
- Still requires a migration for the DB layer
- Prematurely complex for the current problem scope

---

## 4. Recommended Design

**Recommendation: Option A — Static PHP Registry**

### Justification

The project currently has exactly **2 design tokens in actual production use** (`background_token`, `text_token`) across **1 deployed dynamic section** (`content_showcase`). The problem to solve right now is:

1. Establish canonical names so new sections use consistent field keys
2. Enable BladeGenerator to emit correct resolution code
3. Keep SectionFieldClassifier in sync automatically

Option B's DB table and Option C's hybrid are premature for this scale. The static approach matches the project's existing pattern (see `SectionFieldClassifier`, `FieldGroupRegistry`, `ComponentLibrary`) and requires no migration, no new DB tables, and no cache warming.

When the platform reaches 10+ deployed sections with design token diversity across multiple tenants, a migration path to Option C becomes sensible. That is when the cost of a static class begins to exceed the cost of DB flexibility.

### Design Principles for the Registry

1. **Single source of truth** — token names, defaults, options, and CSS maps all in one place
2. **SectionFieldClassifier derives from it** — no duplicate list maintenance
3. **BladeGenerator queries it** — emits correct `match()` + default instead of a blank stub
4. **ComponentLibrary can reference tokens by name** — a future `design` component lists token field keys without duplicating the options
5. **No Blade file changes required** — existing `content_showcase` `match()` blocks remain valid; they just stop being the only source of truth

---

## 5. Proposed Initial Tokens

Only 5 tokens are proposed for the initial registry. These are the minimum needed to establish the pattern without over-specifying.

### Token 1: `background_token`

| Property | Value |
|---|---|
| Field type | `select` |
| Default | `muted` |
| Scope | `shared` |
| Group | `design` |

**Options:**

| Value | CSS Class | Arabic Label |
|---|---|---|
| `none` | `` (empty) | بدون خلفية |
| `primary` | `bg-theme-primary` | خلفية رئيسية |
| `secondary` | `bg-theme-secondary` | خلفية ثانوية |
| `surface` | `bg-theme-surface` | خلفية السطح |
| `muted` | `bg-theme-muted` | خلفية خفيفة |

**Currently used in:** `content_showcase.blade.php`

---

### Token 2: `text_token`

| Property | Value |
|---|---|
| Field type | `select` |
| Default | `heading` |
| Scope | `shared` |
| Group | `design` |

**Options:**

| Value | CSS Class | Arabic Label |
|---|---|---|
| `heading` | `text-theme-heading` | لون العناوين |
| `body` | `text-theme-body` | لون النص |
| `primary` | `text-theme-primary` | اللون الرئيسي |
| `secondary` | `text-theme-secondary` | اللون الثانوي |
| `white` | `text-white` | أبيض |

**Currently used in:** `content_showcase.blade.php`

---

### Token 3: `image_position`

| Property | Value |
|---|---|
| Field type | `select` |
| Default | `right` |
| Scope | `shared` |
| Group | `image` (already in ComponentLibrary `image` component) |

**Options:**

| Value | Behaviour | Arabic Label |
|---|---|---|
| `right` | Image on right, content on left | الصورة يميناً |
| `left` | Image on left, content on right | الصورة يساراً |

**Note:** This token is already defined in `ComponentLibrary` → `image` component. The registry adds the canonical options and CSS resolution info that the generator needs.

**Currently used in:** `content_showcase.blade.php`

---

### Token 4: `section_spacing`

| Property | Value |
|---|---|
| Field type | `select` |
| Default | `md` |
| Scope | `shared` |
| Group | `design` |

**Options:**

| Value | CSS Class | Arabic Label |
|---|---|---|
| `none` | `py-0` | بدون مسافة |
| `sm` | `py-8 md:py-12` | مسافة صغيرة |
| `md` | `py-16 md:py-24` | مسافة متوسطة |
| `lg` | `py-24 md:py-32` | مسافة كبيرة |
| `xl` | `py-32 md:py-40` | مسافة كبيرة جداً |

**Not yet in any section.** Proposed as a unified replacement for the individual `padding_top` + `padding_bottom` pair, which exposes implementation details to non-technical editors.

**Rationale:** Editors do not think in `padding_top` units. They think "how much space does this section need?" A single select is clearer and covers ~90% of use cases.

---

### Token 5: `container_width`

| Property | Value |
|---|---|
| Field type | `select` |
| Default | `default` |
| Scope | `shared` |
| Group | `design` |

**Options:**

| Value | CSS Class | Arabic Label |
|---|---|---|
| `narrow` | `max-w-3xl` | ضيق |
| `default` | `max-w-5xl` | عادي |
| `wide` | `max-w-7xl` | واسع |
| `full` | `max-w-full` | كامل العرض |

**Not yet in any section.** Proposed as a replacement for hardcoded `container mx-auto` widths in section Blade files.

---

## 6. Integration Plan

### 6.1 SectionFieldClassifier

Current: maintains its own `DESIGN_FIELD_KEYS` array (21 items, manually curated).

After registry: derives from `DesignTokenRegistry::keys()` + any extra structural keys (`background_image`, `custom_classes`, `animation`, `align`, etc.) that are design-oriented but not "token" fields. The classifier becomes:

```php
// Conceptual — future implementation:
public static function isDesignKey(string $key): bool {
    return DesignTokenRegistry::has($key)
        || in_array($key, self::EXTRA_DESIGN_KEYS, true);
}
```

**Impact:** `DESIGN_FIELD_KEYS` shrinks to only non-token design keys. Token keys are maintained in one place.

### 6.2 ComponentLibrary

A new `design` component would be added containing the core design tokens as fields. Sections that want standard background + text colour controls simply include `'design'` in their `components[]` array in `SectionTemplateLibrary`.

```php
// Conceptual — future implementation:
'design' => [
    'fields' => [
        ['field_key' => 'background_token', 'field_type' => 'select', ...],
        ['field_key' => 'text_token',       'field_type' => 'select', ...],
        ['field_key' => 'section_spacing',  'field_type' => 'select', ...],
    ],
],
```

The `options` arrays would be sourced from `DesignTokenRegistry` rather than hardcoded in `ComponentLibrary`.

**Impact:** New templates get design fields automatically without manually listing them in `extra_fields`.

### 6.3 SectionPackageGenerator

`SectionPackageGenerator` creates `SectionDefinitionField` rows. Currently the options JSON for `image_position` is hardcoded inside the generator or inherited from `ComponentLibrary`.

After registry: all token options come from `DesignTokenRegistry::options($tokenKey)`. This ensures the DB row for `background_token` always has the correct `{"select_options": [...]}` JSON.

### 6.4 BladeGenerator

`BladeGenerator::generate()` currently emits for a `select` field:

```php
$background_token = trim((string) ($data['background_token'] ?? ''));
```

After registry, for any key in `DesignTokenRegistry`:

```php
// Emitted by BladeGenerator when field_key = 'background_token':
$background_token = trim((string) ($data['background_token'] ?? 'muted'));
$backgroundClass = match ($background_token) {
    'primary'   => 'bg-theme-primary',
    'secondary' => 'bg-theme-secondary',
    'surface'   => 'bg-theme-surface',
    'muted'     => 'bg-theme-muted',
    default     => '',
};
```

The generator queries `DesignTokenRegistry::forKey('background_token')` to build the correct `match()` stub instead of emitting a generic `trim(...)` line.

**Impact:** Scaffolded Blade files are production-ready for design token fields with no hand-editing.

### 6.5 Page Builder (renderer.blade.php)

The Page Builder's Design tab already renders any field whose key is in `SectionFieldClassifier::DESIGN_FIELD_KEYS`. After the registry:

- Token fields appear automatically in the Design tab
- Select fields for tokens would ideally display their Arabic option labels (not raw values)
- The `DynamicSectionEditorRenderer` would need to call `DesignTokenRegistry::labeledOptions($key)` for select rendering — currently options are stored in the `SectionDefinitionField.options` JSON, which is the correct place

**No change needed** to the Page Builder's rendering logic if `SectionDefinitionField` rows are created with the correct `options` JSON. The integration point is upstream at package generation time.

---

## 7. Migration Strategy

### Constraint: Do Not Break Existing Sections

The following deployed sections must continue to work unchanged:

| Section | File | Design Fields in Use |
|---|---|---|
| `content_showcase` | `front/sections/showcase/content_showcase.blade.php` | `background_token`, `text_token`, `image_position` |
| `hero_campaign` | `front/sections/hero/hero_campaign.blade.php` | `background_image` |
| `hero_featured` | `front/sections/hero/hero_featured.blade.php` | `background_image` |
| `features_grid` | *(generated, via SectionPackageGenerator)* | none currently |
| `pricing_plans_dynamic` | `front/sections/pricing/pricing_plans_dynamic.blade.php` | none currently |
| `faq` | `front/sections/faq/faq_section.blade.php` | none currently |

### Migration Steps (future, sequential)

**Step 1 — Create `DesignTokenRegistry`** (5 tokens only, as in Section 5 above)

No DB changes. No Blade changes. Only a new PHP class. Safe to merge at any time.

**Step 2 — Update `SectionFieldClassifier`** to derive token keys from the registry

The 21-item static array shrinks. Existing behaviour is preserved — tokens remain in the Design tab. This is a pure refactor with no observable UI change.

**Step 3 — Update `BladeGenerator`** to query the registry for token resolution stubs

Affects only newly generated scaffolds. No deployed Blade file is touched.

**Step 4 — Add `design` component to `ComponentLibrary`**

No existing templates are affected (they don't include a `design` component yet). New templates created after this step can opt in.

**Step 5 — Update `SectionPackageGenerator`** to source token options from registry

Affects only newly created Section Packages. Existing `SectionDefinitionField` rows in DB are not touched.

**Step 6 — (Optional) Backfill `SectionDefinitionField` options JSON** for `content_showcase` tokens

If `content_showcase`'s `background_token` DB field currently has no `options` JSON, an artisan command can backfill it from `DesignTokenRegistry`. This enables the Page Builder Design tab to show labelled dropdowns instead of a free-text input. Existing content data is unaffected.

### What Must Not Change

- `content_showcase.blade.php` local `match()` blocks remain valid — the registry does not replace them at runtime, it guides new code generation
- `SectionDefinitionField` DB schema is unchanged
- `section_definitions` table is unchanged
- All save/load pipelines are unchanged
- `image_position` handling in `ComponentLibrary` remains as-is (registry adds options metadata alongside it, not instead of it)

---

## 8. Explicit Non-Goals

The following are **out of scope** for this analysis and the implementation it will guide:

- **No new DB tables** — no `design_tokens` table, no `token_values` table
- **No new migrations** — zero schema changes required
- **No rewriting existing section Blade files** — `content_showcase`, `features_grid`, `pricing_plans_dynamic`, `faq`, and `hero` variants keep their current implementation
- **No per-tenant token customisation** — the registry is platform-level PHP; sites do not configure their own token maps
- **No client-facing token UI** — tokens are an admin/developer concern; no client builder design controls are added
- **No CSS variable system** — tokens map to Tailwind utility classes; CSS custom properties (`--bg-primary`) are not introduced
- **No token import/export** — tokens are PHP constants, not DB records, so there is nothing to import or export
- **No implementation in this document** — this is an architecture analysis only. No classes are created, no code is written, no DB changes are made as a result of this document.

---

## Quick Reference

### The 5 Proposed Tokens

| Token Key | Type | Default | CSS Resolution |
|---|---|---|---|
| `background_token` | select | `muted` | `bg-theme-{value}` |
| `text_token` | select | `heading` | `text-theme-{value}` |
| `image_position` | select | `right` | layout logic (order classes) |
| `section_spacing` | select | `md` | `py-{size}` |
| `container_width` | select | `default` | `max-w-{size}` |

### Integration Touch Points (in implementation order)

```
Step 1: DesignTokenRegistry.php          ← new class, no deps
Step 2: SectionFieldClassifier           ← derives DESIGN_FIELD_KEYS from registry
Step 3: BladeGenerator                   ← queries registry for match() stubs
Step 4: ComponentLibrary (design comp)   ← new component using registry options
Step 5: SectionPackageGenerator          ← sources options from registry
Step 6: SectionDefinitionField backfill  ← optional, artisan command
```

### Architecture Pattern Alignment

This follows the same static-class pattern used throughout the section system:

| Class | Responsibility | Pattern |
|---|---|---|
| `SectionFieldClassifier` | field_key → content/design bucket | static whitelist |
| `FieldGroupRegistry` | group_key → translation label | static map |
| `ComponentLibrary` | component_key → field definitions | static declarations |
| `DesignTokenRegistry` *(proposed)* | token_key → options + CSS map | static map |

---

*Document created: 2026-06-21*  
*No code was written, no files were modified, and no DB changes were made as part of this analysis.*
