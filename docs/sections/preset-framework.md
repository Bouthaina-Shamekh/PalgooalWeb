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
**Do not use `SectionMediaPreviewBuilder` in frontend templates.** Resolve IDs in the Blade template directly:

```php
// Correct pattern (used in hosting.blade.php, website_protection.blade.php)
if (!empty($data['background_image'])) {
    $media = \App\Models\Media::find($data['background_image']);
    $bgUrl = $media?->url ?? null;
}

// For repeater items with icon_media
$media = \App\Models\Media::find($item['icon_media']);
$resolvedMedia[$item['icon_media']] = $media?->url ?? null;
```

Resolve `icon_media` IDs eagerly (collect all IDs first, then batch-resolve or resolve per item at most once) to avoid N+1 queries.

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
Always use these three values for `icon_source`:
- `'class'` — Tabler CSS class string (default)
- `'media'` — Media library ID stored in `icon_media`
- `'svg'` — raw SVG string stored in `icon_svg` (only where supported)

### Input Name Pattern (Blade)
```
translations[{locale}][content][{field_key}]
translations[{locale}][content][{repeater_key}][{index}][{item_field}]
```

---

## 9. Adding a New Custom Preset — Steps

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
   - Read `$customPresetEditor['locales'][$code]['values']` for field values.
   - Use `$code` (current locale), `$contentGridClass`, `$sectionTitleValue`, `$usesInternalLabel`.
   - `@include` existing repeater partials as needed.

6. **Create the frontend Blade template** at the `template_registry` view path.
   - Read from `$data[...]` (the normalized content array).
   - Resolve media IDs using `\App\Models\Media::find()`.
   - Always define icon/media fallbacks.

7. **Run the verification checklist** (see `docs/sections/preset-checklist.md`).

---

## 10. Class & File Reference

| Class / File | Location | Responsibility |
|---|---|---|
| `SectionCustomPresetRegistry` | `app/Support/Sections/` | Stores + resolves preset metadata from config and runtime |
| `SectionCustomPresetEditorRenderer` | `app/Support/Sections/` | Builds the editor payload per preset (one method per preset) |
| `SectionEditorRepeaterFactory` | `app/Support/Sections/` | Prepares repeater item arrays per locale for editor use |
| `SectionMediaPreviewBuilder` | `app/Support/Sections/` | Resolves media IDs to preview URLs for admin editor only |
| `SectionTemplateRegistry` | `app/Support/Sections/` | Maps `template_key` → Blade view path |
| `SectionDefinitionRuntimeResolver` | `app/Support/Sections/` | Resolves which definition applies (dynamic vs renderable) |
| `SectionDefinitionFrontendViewDataFactory` | `app/Support/Sections/` | Builds frontend render payload for definition-driven sections |
| `SectionRenderer` | `app/Support/Sections/` | Entry point: tries definition-driven first, falls back to legacy |
| `SectionQueryResolver` | `app/Support/Sections/` | Injects live DB data (plans, services, etc.) by section type |
| `DynamicSectionEditorRenderer` | `app/Support/Sections/` | Builds the dynamic editor payload from DB field definitions |
| `config/sections.php` | `config/` | Static registry for templates, presets, icon library |
| `custom-presets/*.blade.php` | `resources/views/dashboard/pages/sections/partials/custom-presets/` | Admin editor UI per preset |
| `repeaters/*.blade.php` | `resources/views/dashboard/pages/sections/partials/repeaters/` | Reusable repeater UI partials |
| `front/sections/**` | `resources/views/front/sections/` | Frontend template views |
