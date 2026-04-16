# Custom Preset Section Framework
## Internal Developer Documentation

> **Scope**: Editor-side custom preset architecture only.  
> Frontend rendering uses the same content contract regardless of which editor produced the data.  
> **Last updated**: 2026-04-15

---

## 1. Three Section Types — Compared

| | Legacy | Dynamic | Custom Preset |
|--|--------|---------|---------------|
| **Definition source** | `SectionEditorTypeCapabilities::TYPE_CONFIG` (code) | `section_definitions` + `section_definition_fields` (DB) | `section_definitions` (DB) + `SectionCustomPresetRegistry` (code) |
| **Editor activation** | `type` string matching in `typeFlags` | `editor_mode = dynamic` + linked `section_definition_id` + template assigned | `editor_mode = custom_preset` + registered `custom_editor_key` |
| **Editor UI** | Hardcoded Blade partials driven by `fieldFlags` | Auto-built from `SectionDefinitionField` records | Bespoke hand-crafted Blade partial per preset |
| **Repeaters** | Ad-hoc per section type | Not yet a native field_type | Reused from `partials/repeaters/` via `@include` |
| **Frontend rendering** | `legacy-section.blade.php` switch/case | `SectionDefinitionFrontendViewDataFactory` → `SectionTemplateRegistry` | Same as dynamic (definition-driven path) |
| **Content storage** | `section_translations.content` JSON | Same | Same |
| **Backward compat** | ✅ Primary path (do not break) | ✅ Additive (null `section_definition_id` = skip) | ✅ Legacy bridge available |

---

## 2. Editor Activation Flow

When a section editor opens (via `editor()` or `edit()` in `SectionController`), `SectionEditorDataFactory::build()` runs this decision tree:

```
SectionEditorDataFactory::build($section, $locale)
│
├─ SectionCustomPresetEditorRenderer::buildForSection()
│   ├─ resolvePresetDefinition($section)
│   │   └─ requires: section_definition_id set + definition.is_active = true
│   ├─ resolvePresetMeta($definition)
│   │   ├─ Path A (formal): definition.editor_mode = 'custom_preset'
│   │   │                   AND definition.custom_editor_key is registered
│   │   └─ Path B (bridge): SectionCustomPresetRegistry::legacyBridgePresetKey(definition.section_key)
│   └─ calls $this->{$builder}($section, $definition, $languages, $presetMeta)
│       returns: array with keys [enabled, presetKey, view, locales, ...]
│       ↓ if null → fall through
│
├─ DynamicSectionEditorRenderer::buildForSection()
│   ├─ SectionDefinitionRuntimeResolver::resolveDynamicDefinition($section)
│   │   └─ requires: editor_mode = 'dynamic' + primary template assigned
│   └─ builds locale-aware groups/fields from SectionDefinitionField records
│       returns: array with keys [enabled, locales, definition, ...]
│       ↓ if null → fall through
│
└─ Legacy editor (SectionEditorTypeCapabilities + SectionEditorSchemaRegistry)
    └─ driven by typeFlags / fieldFlags in TYPE_CONFIG
```

**Priority**: custom preset → dynamic → legacy (in that order).

---

## 3. Frontend Rendering Flow

Frontend rendering is **editor-mode-agnostic**. It does not care whether a section was edited via custom preset, dynamic, or legacy editor. Only the stored content and the linked template_key matter.

```
SectionRenderer::render($section, $locale)
│
├─ SectionDefinitionFrontendViewDataFactory::build($section, $locale)
│   ├─ requires: runtimeTables available
│   ├─ SectionDefinitionRuntimeResolver::resolveRenderableDefinition($section)
│   │   └─ any editor_mode is accepted (dynamic OR custom_preset)
│   │   └─ requires: section_definition_id set + is_active + primary template assigned
│   ├─ SectionTemplateRegistry::resolveView($templateKey)
│   │   └─ maps template_key → Blade view from config('sections.template_registry.templates')
│   │   └─ fallback: components.template.sections._missing-template
│   ├─ normalizeContent(): merges saved content + field defaults
│   └─ SectionQueryResolver::resolve($type, $content)
│       └─ injects live DB data (plans, services, etc.) for DB-driven section types
│           returns: [view, viewData] ← rendered by SectionRenderer
│       ↓ if null → fall through
│
└─ Legacy renderer (front.pages.partials.legacy-section)
    └─ @switch($resolvedSectionType) dispatches to template @include per type
```

**Key rule**: A section with `section_definition_id = null` always falls through to legacy rendering, regardless of `type`.

---

## 4. Custom Preset Registry — Two Sources

Preset metadata is read by merging two sources:

```php
// Source 1: config/sections.php (static)
'custom_preset_registry' => [
    'presets' => [
        'hosting_hero' => [
            'label'   => 'Hosting Hero',
            'view'    => 'dashboard.pages.sections.partials.custom-presets.hosting-hero',
            'builder' => 'buildHostingHeroPreset',
        ],
        // ...
    ],
    'legacy_section_key_bridge' => [
        'hosting_hero' => 'hosting_hero',  // section_key → preset_key
    ],
]

// Source 2: runtime (optional, from ServiceProvider or boot)
SectionCustomPresetRegistry::register('my_preset', [...]);
SectionCustomPresetRegistry::bridgeLegacySectionKey('old_key', 'my_preset');
```

Runtime registrations take precedence (`array_replace`). Both sources are merged in `SectionCustomPresetRegistry::all()`.

---

## 5. Legacy Bridge

The legacy bridge allows a `SectionDefinition` that has **not yet been backfilled** with `editor_mode = custom_preset` and `custom_editor_key` to still activate a preset editor, based only on its `section_key`.

```php
// config/sections.php
'legacy_section_key_bridge' => [
    'hosting_hero' => 'hosting_hero',  // section.section_key → preset_key
],
```

The bridge is resolved in `SectionCustomPresetEditorRenderer::resolvePresetMeta()`. When the formal path (`editor_mode = custom_preset`) matches, the bridge is skipped. The bridge is a **temporary compatibility measure** — definitions should eventually be backfilled.

---

## 6. Repeater Reuse Strategy

**Current state**: Repeaters are not a native `field_type` in the dynamic editor builder. They are PHP-level Blade partials that get `@include`d inside custom preset editor views.

**How it works**:

```
Custom Preset Blade (e.g. hosting-hero.blade.php)
└─ @include('...repeaters.campaign-features-repeater', [
       'code' => $code,
       'campaignFeatureItems' => $featureItems,
       'mediaPreviewBuilder' => $mediaPreviewBuilder,
       ...labels...
   ])
```

**Repeater data is prepared by `SectionEditorRepeaterFactory`**, which reads `old()` flash values first (form re-submit), then saved content, then returns normalized item arrays per locale.

**Available repeater partials**:

| Partial | Factory method | Content key | Item structure |
|---------|---------------|-------------|----------------|
| `campaign-features-repeater` | `buildLocaleCampaignFeatureItems()` | `features` | `{text, icon, icon_source, icon_svg, icon_media}` |
| `protection-items-repeater` | `buildLocaleProtectionItems()` | `items` | `{title, description, icon, icon_source, icon_media}` |
| `services-repeater` | `buildLocaleServiceItems()` | `services` | `{text, icon, icon_source, icon_media}` |
| `outputs-repeater` | `buildLocaleOutputItems()` | `outputs` | `{text, icon, icon_source, icon_media}` |
| `build-steps-repeater` | `buildLocaleBuildStepItems()` | `steps` | `{title, icon, icon_source, icon_svg, icon_media, is_accent}` |
| `pricing-plans-repeater` | `buildLocalePricingPlanItems()` | `plans` | `{title, category, features_textarea, button_label, button_url, button_new_tab}` |
| `pricing-categories-repeater` | `buildLocalePricingCategoryItems()` | `categories` | `{label, key}` |

**Future**: repeater as a native `field_type` in `SectionDefinitionField` is planned but not yet implemented.

---

## 7. Media Handling Conventions

### Storage
Media field values are stored as **integer IDs** (Media model primary key), not as URLs. This applies to:
- Background image fields (`background_image`)
- Icon media fields (`icon_media`)
- Any `field_type = media` field

```json
// Stored in section_translations.content
{
    "background_image": 42,
    "features": [
        {"text": "Feature 1", "icon_source": "media", "icon_media": "17"}
    ]
}
```

### Admin Preview
`SectionMediaPreviewBuilder::build($value)` resolves a stored ID to a URL for preview in the editor:
- If value is numeric → `Media::find($id)->url`
- If value is a string URL → used directly (legacy compatibility)
- Returns `[]` if nothing resolves

### Frontend Resolution

**All frontend preset templates must use `SectionFrontendMediaResolver`.**  
Do not call `\App\Models\Media::find()` directly in Blade templates, and do not use `SectionMediaPreviewBuilder` (admin-only).

**Class**: `App\Support\Sections\SectionFrontendMediaResolver`  
**Call style**: always use the fully-qualified class name (FQCN) — no `use` statement needed in Blade.

#### Scalar fields (single background image, single featured image)

```php
// Returns string URL or null — safe when $data key is missing or non-numeric
$bgUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);
```

#### Repeater items with `icon_media` — preferred pattern

`resolveMany()` is the **preferred pattern for lists**. It deduplicates IDs and issues a single `whereIn` query before the render loop, avoiding N+1.

```php
// Collect all icon_media values from the normalized item list
$resolvedMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
    $cardItems->pluck('icon_media')
);

// Then inside the @foreach loop:
// $url = $resolvedMedia[$card['icon_media']] ?? null;
```

#### Return contract

| Method | Input | Returns |
|--------|-------|---------|
| `resolve($id)` | numeric ID, null, or any value | `string` URL or `null` |
| `resolveMany($ids)` | iterable of IDs (mixed types safe) | `array<int, string\|null>` keyed by integer ID |

Both methods are null-safe and silently ignore non-numeric or empty values.

### Fallback Behavior
Always define a fallback when media is absent:
- Background images: render without background or use CSS fallback
- Icons: fall back to a default Tabler class (e.g., `ti ti-shield-check`)

---

## 8. Naming Conventions

### Scalar Field Keys
Use `snake_case`. Prefer these standard names when the concept matches:

| Concept | Preferred key |
|---------|--------------|
| Main heading | `title` |
| Supporting text | `subtitle` |
| Small label above title | `eyebrow` |
| Background image | `background_image` |
| Image alt text | `image_alt` |
| CTA button label | `button_label` |
| CTA button URL | `button_url` |
| Open link in new tab | `button_new_tab` |
| Secondary CTA label | `secondary_button_label` |
| Secondary CTA URL | `secondary_button_url` |
| Pricing display string | `pricing` |
| Breadcrumb home label | `breadcrumb_home_label` |
| Breadcrumb home URL | `breadcrumb_home_url` |
| Breadcrumb current page | `breadcrumb_current_label` |

### Repeater Array Keys
Use the **plural noun** for the repeater container and consistent item-level keys:

| Repeater | Container key | Item text key | Icon key | Icon source key |
|----------|--------------|--------------|----------|----------------|
| Campaign features | `features` | `text` | `icon` | `icon_source` |
| Protection items | `items` | `title` + `description` | `icon` | `icon_source` |
| Services | `services` | `text` | `icon` | `icon_source` |
| Outputs | `outputs` | `text` | `icon` | `icon_source` |
| Build steps | `steps` | `title` | `icon` | `icon_source` |
| Pricing plans | `plans` | `title` | — | — |
| Pricing categories | `categories` | `label` | — | — |

### Icon Source Values
Most repeaters support three values for `icon_source`:
- `'class'` — Tabler CSS class string (default)
- `'media'` — Media library ID stored in `icon_media`
- `'svg'` — raw SVG string stored in `icon_svg` (only where supported)

**Exception — `protection-items-repeater`**: `icon_source` is limited to `['class', 'media']`. SVG is intentionally excluded because the protection-items-repeater UI partial does not have an SVG input tab. Do not add `'svg'` support without also updating the repeater partial and the `website_protection.blade.php` frontend template.

### Input Name Pattern (Blade)
```
translations[{locale}][content][{field_key}]
translations[{locale}][content][{repeater_key}][{index}][{item_field}]
```

---

## 8a. Dynamic Field Type — Repeater (Phase 5A Foundation)

`repeater` is now a recognized `field_type` in the `SectionDefinitionField` system. This section documents the V1 schema contract.

### Current state (Phase 5A)

| Layer | Status |
|---|---|
| `FIELD_TYPE_REPEATER` constant | ✅ Added to model |
| Recognized by `supportedFieldTypes()` | ✅ Validated by both form requests |
| Appears in admin type dropdown | ✅ `SectionDefinitionFieldFormDataFactory::fieldTypeOptions()` |
| `isRepeater()` accessor | ✅ Available on model |
| `repeaterItemSchema()` accessor | ✅ Reads + normalizes `schema['item_schema']` |
| `repeaterSubFieldTypes()` allowlist | ✅ Static method on model |
| Dynamic editor rendering | ⏳ Deferred to Phase 5B — fields return null from `buildFieldPayload()` |
| Item schema editor UI | ⏳ Deferred to Phase 5B |
| Save/load pipeline | ⏳ Deferred to Phase 5B |
| Frontend rendering | ⏳ Deferred (future phase) |

### item_schema storage

`item_schema` is stored in the `schema` JSON column on `section_definition_fields` under the key `item_schema`. The `settings` column is not used for this purpose.

```json
{
  "item_schema": [
    {
      "key": "text",
      "label": "Text",
      "type": "text",
      "required": true,
      "translatable": true
    },
    {
      "key": "icon_media",
      "label": "Icon",
      "type": "media",
      "required": false,
      "translatable": false
    }
  ]
}
```

### V1 sub-field type allowlist

The following types are permitted inside `item_schema`. This list is the authoritative source — always read it from `SectionDefinitionField::repeaterSubFieldTypes()`.

| Type | Notes |
|------|-------|
| `text` | Single-line string |
| `textarea` | Multi-line string |
| `url` | URL string |
| `media` | Media library ID (integer) |
| `boolean` | true/false |
| `select` | Requires `options` to be meaningful; not yet wired to sub-field in Phase 5A |

**Explicitly excluded from V1:**
- `repeater` — nested repeaters are not supported
- `richtext` — complex editor dependency not suitable for inline item fields
- `number` — not yet required by any planned V1 use-case

### Normalization contract (`repeaterItemSchema()`)

The model accessor `repeaterItemSchema()` reads `schema['item_schema']` and returns a clean array. Each returned item is guaranteed to have exactly these keys:

| Key | Type | Fallback |
|-----|------|---------|
| `key` | string | — (required; entry dropped if missing) |
| `label` | string | falls back to `key` |
| `type` | string | — (required; entry dropped if not in allowlist) |
| `required` | bool | `false` |
| `translatable` | bool | `true` |

Malformed entries (missing key, unknown type, wrong shape) are silently dropped. Returns `[]` for non-repeater fields, null schema, or empty item_schema.

### Writing item_schema (Phase 5A)

Until Phase 5B adds the admin UI, `item_schema` must be written programmatically:

```php
// Via Eloquent directly:
$field->update([
    'schema' => ['item_schema' => [
        ['key' => 'text',  'label' => 'Text',  'type' => 'text',  'required' => true, 'translatable' => true],
        ['key' => 'media', 'label' => 'Media', 'type' => 'media', 'required' => false, 'translatable' => false],
    ]],
]);

// Reading back:
$schema = $field->repeaterItemSchema(); // always returns a normalized array
$isRepeater = $field->isRepeater();    // bool
```

---

## 9. Known Naming Inconsistencies & Technical Debt

This section records divergences that were identified but **intentionally not changed** because they involve stored content keys or require broader coordination. Future preset authors should be aware of these before introducing new presets.

### Intentional Deferrals (stored content keys — do not rename without a migration)

**`website_protection` repeater stored under `items` (not `protection_items`)**
- Stored key: `items`
- Factory reads: `$content['items']`
- Ideal name: `protection_items` or `cards`
- Status: **deferred** — renaming requires a data migration for all existing sections. Do not use `items` as a content key in new presets; prefer a descriptive plural noun.

**Button key namespace differs across presets**
- `hosting_hero` uses namespaced keys: `card_button_label`, `card_button_url` (scoped to the Side Card sub-component)
- `wordpress_ai_promo` uses flat keys: `button_label`, `button_url`
- Status: **deferred** — both are stored keys in production. New presets should follow `button_label` / `button_url` (flat) unless a single section has multiple independent CTA buttons, in which case namespace them (e.g. `primary_button_label`, `secondary_button_label`).

### Frontend Normalization — Intentional Defensive Code

**`text ?? title ?? label` fallback chain in frontend feature-item normalization**

Both `hosting.blade.php` and `wordpress_ai_promo.blade.php` normalize feature item text with:
```php
$text = trim((string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))));
```
`SectionEditorRepeaterFactory` already normalizes all new saves to `text`. The `title` and `label` fallbacks are retained for backward compatibility with any data saved before the factory was introduced. Do not remove them.

### Repeater Variable Naming — Editor Payload vs Content Key

The `values` array inside the editor payload uses a different name than the stored content key for repeater items. This is intentional — the editor variable is scoped to the editor view, while the content key is what gets saved.

| Preset | Editor values key | Stored content key | Factory method |
|--------|------------------|-------------------|----------------|
| `hosting_hero` | `featureItems` | `features` | `buildLocaleCampaignFeatureItems` |
| `wordpress_ai_promo` | `featureItems` | `features` | `buildLocaleCampaignFeatureItems` |
| `website_protection` | `protectionItems` | `items` | `buildLocaleProtectionItems` |

New presets must follow this same two-name pattern: use a `*Value` or `*Items` suffix for editor payload keys, and the canonical snake_case noun for the stored content key.

---

## 10. Reusable Admin Field Partials

To avoid duplicating the same HTML field block across multiple preset editor views, three standard field partials are available in:

```
resources/views/dashboard/pages/sections/partials/custom-presets/fields/
```

Always `@include` these partials instead of writing raw `<input>` / `<textarea>` blocks inline.

### `_text-field.blade.php`

A single labeled text (or URL) input, optionally wrapped in a column-span div.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | required | Visible label text |
| `name` | string | required | The `name` attribute (full `translations[...]` path) |
| `value` | string | `''` | Current field value |
| `placeholder` | string | `''` | Placeholder text (omitted from markup when empty) |
| `type` | string | `'text'` | Input type — use `'url'` for URL fields |
| `colSpan` | string | `'lg:col-span-2'` | CSS class for the wrapper div; pass `''` to omit the wrapper class |

```blade
@include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
    'label'       => __('Title'),
    'name'        => 'translations[' . $code . '][content][title]',
    'value'       => $titleValue,
    'placeholder' => __('e.g. My Section Title'),
])
```

### `_textarea-field.blade.php`

A single labeled textarea.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | required | Visible label text |
| `name` | string | required | The `name` attribute |
| `value` | string | `''` | Current field value |
| `rows` | int | `4` | Number of visible textarea rows |
| `placeholder` | string | `''` | Placeholder text |
| `colSpan` | string | `'lg:col-span-2'` | Wrapper class; pass `''` to omit |

```blade
@include('dashboard.pages.sections.partials.custom-presets.fields._textarea-field', [
    'label' => __('Subtitle'),
    'name'  => 'translations[' . $code . '][content][subtitle]',
    'value' => $subtitleValue,
    'rows'  => 3,
])
```

### `_background-image-card.blade.php`

A full-card wrapper containing a `x-dashboard.media-picker` component, with an optional image alt text input below it.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `code` | string | required | Current locale code |
| `heading` | string | required | Card heading (e.g. `__('Background')`) |
| `description` | string | required | Card description text |
| `value` | mixed | required | Media ID value (from `$pv->mediaId(...)`) |
| `previewUrls` | array | `[]` | Preview URL array (from `$pv->items(...)`) |
| `fieldKey` | string | `'background_image'` | Content key used in the `name` attribute |
| `imageAltKey` | string\|null | `null` | When non-null, renders an alt text input using this key |
| `imageAlt` | string\|null | `null` | Current alt text value |

```blade
{{-- Without alt text input --}}
@include('dashboard.pages.sections.partials.custom-presets.fields._background-image-card', [
    'code'        => $code,
    'heading'     => __('Background'),
    'description' => __('Choose the background image used behind this section.'),
    'value'       => $backgroundImageValue,
    'previewUrls' => $backgroundImagePreviewUrls,
])

{{-- With alt text input --}}
@include('dashboard.pages.sections.partials.custom-presets.fields._background-image-card', [
    'code'        => $code,
    'heading'     => __('Background'),
    'description' => __('Choose the background image.'),
    'value'       => $backgroundImageValue,
    'previewUrls' => $backgroundImagePreviewUrls,
    'imageAltKey' => 'image_alt',
    'imageAlt'    => $imageAltValue,
])
```

> **Do not** write raw `<input type="text">` or `<textarea>` field blocks inside preset views — always use these partials. Repeater partials are excluded and remain in `partials/repeaters/`.

---

## 11. Adding a New Custom Preset — Steps

1. **Create the SectionDefinition record** in DB:
   - `editor_mode = custom_preset`
   - `custom_editor_key = 'your_preset_key'`
   - `is_active = true`, `is_visible = true`

2. **Assign a primary template** via `section_definition_template` pivot:
   - `template_key` must exist in `config('sections.template_registry.templates')`

3. **Register in `config/sections.php`**:
   ```php
   'custom_preset_registry' => [
       'presets' => [
           'your_preset_key' => [
               'label'   => 'Your Preset Label',
               'view'    => 'dashboard.pages.sections.partials.custom-presets.your-preset',
               'builder' => 'buildYourPresetPreset',
           ],
       ],
   ],
   'template_registry' => [
       'templates' => [
           'your_template_key' => [
               'label'    => 'Your Template',
               'view'     => 'front.sections.your.view',
               'category' => 'category_name',
           ],
       ],
   ],
   ```

4. **Add builder method** in `SectionCustomPresetEditorRenderer`:
   ```php
   protected function buildYourPresetPreset(
       Section $section,
       SectionDefinition $definition,
       iterable $languages,
       array $presetMeta,
   ): array {
       $languagesCollection = Collection::make($languages)->values();
       // use $this->repeaterFactory->build*() as needed
       // use $this->mediaPreviewBuilder->build() for media fields
       return [
           'enabled'          => true,
           'presetKey'        => $presetMeta['preset_key'],
           'view'             => $presetMeta['view'],
           'activationSource' => 'custom_editor_key',
           'defaultLocale'    => $this->resolveDefaultLocale($languagesCollection),
           'definition'       => [...],
           'locales'          => [...],
       ];
   }
   ```

5. **Create the admin Blade partial** at the registered `view` path.
   - Use `SectionPresetEditorValues::for($customPresetEditor, $code)` at the top of the `@php` block.
   - Read values via `->scalar()`, `->items()`, `->mediaId()` — do not access `$customPresetEditor['locales'][$code]['values']` directly.
   - Use `$code` (current locale), `$contentGridClass`, `$sectionTitleValue`, `$usesInternalLabel`.
   - Use `@include` with the field partials from `custom-presets/fields/` for all individual text inputs, textareas, and background image cards (see Section 9).
   - `@include` existing repeater partials from `partials/repeaters/` as needed.

6. **Create the frontend Blade template** at the `template_registry` view path.
   - Read from `$data[...]` (the normalized content array).
   - Resolve media IDs using `SectionFrontendMediaResolver` (never call `Media::find()` directly).
   - Use `resolve()` for scalar fields, `resolveMany()` for repeater icon lists.
   - Always define icon/media fallbacks.

7. **Run the verification checklist** (see `docs/sections/preset-checklist.md`).

---

## 12. Class & File Reference

| Class / File | Location | Responsibility |
|---|---|---|
| `SectionCustomPresetRegistry` | `app/Support/Sections/` | Stores + resolves preset metadata from config and runtime |
| `SectionCustomPresetEditorRenderer` | `app/Support/Sections/` | Builds the editor payload per preset (one method per preset) |
| `SectionEditorRepeaterFactory` | `app/Support/Sections/` | Prepares repeater item arrays per locale for editor use |
| `SectionMediaPreviewBuilder` | `app/Support/Sections/` | Resolves media IDs to preview URLs for **admin editor only** |
| `SectionPresetEditorValues` | `app/Support/Sections/` | Typed value accessor for custom preset Blade views — `::for($customPresetEditor, $code)` then `->scalar()`, `->items()`, `->mediaId()` |
| `SectionFrontendMediaResolver` | `app/Support/Sections/` | Resolves media IDs to URLs for **frontend templates only** — use `resolve()` for scalar fields, `resolveMany()` for repeater lists |
| `SectionTemplateRegistry` | `app/Support/Sections/` | Maps `template_key` → Blade view path |
| `SectionDefinitionRuntimeResolver` | `app/Support/Sections/` | Resolves which definition applies (dynamic vs renderable) |
| `SectionDefinitionFrontendViewDataFactory` | `app/Support/Sections/` | Builds frontend render payload for definition-driven sections |
| `SectionRenderer` | `app/Support/Sections/` | Entry point: tries definition-driven first, falls back to legacy |
| `SectionQueryResolver` | `app/Support/Sections/` | Injects live DB data (plans, services, etc.) by section type |
| `DynamicSectionEditorRenderer` | `app/Support/Sections/` | Builds the dynamic editor payload from DB field definitions |
| `config/sections.php` | `config/` | Static registry for templates, presets, icon library |
| `custom-presets/*.blade.php` | `resources/views/dashboard/pages/sections/partials/custom-presets/` | Admin editor UI per preset |
| `custom-presets/fields/_text-field.blade.php` | `resources/views/dashboard/pages/sections/partials/custom-presets/fields/` | Reusable text/URL input field block for preset editors |
| `custom-presets/fields/_textarea-field.blade.php` | `resources/views/dashboard/pages/sections/partials/custom-presets/fields/` | Reusable textarea field block for preset editors |
| `custom-presets/fields/_background-image-card.blade.php` | `resources/views/dashboard/pages/sections/partials/custom-presets/fields/` | Reusable background image card block (media picker + optional alt text) |
| `repeaters/*.blade.php` | `resources/views/dashboard/pages/sections/partials/repeaters/` | Reusable repeater UI partials |
| `front/sections/**` | `resources/views/front/sections/` | Frontend template views |
