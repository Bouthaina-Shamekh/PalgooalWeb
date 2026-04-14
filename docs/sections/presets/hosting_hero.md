# Preset: hosting_hero
## Internal Developer Documentation

---

## 1. Overview

| Property | Value |
|----------|-------|
| **Name** | Hosting Hero |
| **Section Key** | `hosting_hero` |
| **Template Key** | `hosting_hero` |
| **Editor Mode** | `custom_preset` |
| **Custom Editor Key** | `hosting_hero` |
| **Category** | *(not set in section_definitions record — check DB)* |
| **Legacy Bridge** | `hosting_hero` → `hosting_hero` (in `legacy_section_key_bridge`) |

**Activation note**: This preset currently has a legacy bridge registered. Definitions using `editor_mode = custom_preset` + `custom_editor_key = 'hosting_hero'` activate formally. Definitions linked only by `section_key = 'hosting_hero'` activate via bridge. Both paths reach the same builder method.

---

## 2. Purpose

Renders a full-width hero section designed for hosting service pages. Contains:
- Breadcrumb navigation (home label/URL + current page label)
- Main content: title + subtitle + feature checklist
- Side card: card title + CTA button
- Background image

Expected placement: top of a dedicated hosting or web hosting landing page.

---

## 3. Content Shape

```json
{
    "breadcrumb_home_label": "Home",
    "breadcrumb_home_url": "index.html",
    "breadcrumb_current_label": "Hosting",
    "title": "Professional Web Hosting",
    "subtitle": "Fast, reliable, and secure hosting for your business.",
    "features": [
        {
            "text": "24/7 technical support",
            "icon": "ti ti-headset",
            "icon_source": "class",
            "icon_svg": "",
            "icon_media": ""
        },
        {
            "text": "99.9% uptime guarantee",
            "icon": "",
            "icon_source": "class",
            "icon_svg": "",
            "icon_media": ""
        }
    ],
    "card_title": "Start Hosting Today",
    "card_button_label": "Get Started",
    "card_button_url": "https://example.com/hosting",
    "background_image": 42
}
```

All fields are **translatable** (stored per locale in `section_translations`). No shared fields.

---

## 4. Scalar Fields

| Field Key | Type | Scope | Required |
|-----------|------|-------|----------|
| `breadcrumb_home_label` | text | translatable | optional |
| `breadcrumb_home_url` | url | translatable | optional |
| `breadcrumb_current_label` | text | translatable | optional |
| `title` | text | translatable | optional |
| `subtitle` | textarea | translatable | optional |
| `card_title` | text | translatable | optional |
| `card_button_label` | text | translatable | optional |
| `card_button_url` | url | translatable | optional |
| `background_image` | media (ID) | translatable | optional |

Default values (from `buildHostingHeroPreset`):
- `breadcrumb_home_label` → `__('Home')`
- `breadcrumb_home_url` → `'index.html'`
- `breadcrumb_current_label` → `__('Hosting')`

---

## 5. Repeater Fields

### `features` (Campaign Features Repeater)

| Item Field | Type | Notes |
|-----------|------|-------|
| `text` | string | Required — empty items are filtered out |
| `icon` | string | Tabler CSS class (e.g., `ti ti-server`) |
| `icon_source` | enum | `class` \| `svg` \| `media` — default: `class` |
| `icon_svg` | string | Raw SVG string — used when `icon_source = svg` |
| `icon_media` | string/int | Media ID — used when `icon_source = media` |

Factory: `SectionEditorRepeaterFactory::buildLocaleCampaignFeatureItems()`  
Content key: `features`  
Repeater partial: `dashboard.pages.sections.partials.repeaters.campaign-features-repeater`

**Frontend fallback**: if `features` is empty, the frontend template renders 4 hardcoded placeholder items.

---

## 6. Admin Editor Mapping

| Element | Value |
|---------|-------|
| Builder method | `SectionCustomPresetEditorRenderer::buildHostingHeroPreset()` |
| Admin Blade | `dashboard.pages.sections.partials.custom-presets.hosting-hero` |
| File path | `resources/views/dashboard/pages/sections/partials/custom-presets/hosting-hero.blade.php` |
| Repeater partial | `campaign-features-repeater.blade.php` (via `@include`) |
| Media preview | `SectionMediaPreviewBuilder::build($backgroundImageValue)` |

**Blade variables consumed** (passed by `editor-form.blade.php`):

| Variable | Source |
|----------|--------|
| `$customPresetEditor` | array from `buildHostingHeroPreset()` |
| `$code` | current locale code |
| `$contentGridClass` | CSS class string from parent |
| `$sectionTitleValue` | section title for current locale |
| `$usesInternalLabel` | bool — hides/shows section title field |
| `$mediaPreviewBuilder` | `SectionMediaPreviewBuilder` instance |

---

## 7. Frontend Mapping

| Element | Value |
|---------|-------|
| Frontend Blade | `front.sections.hero.hosting` |
| File path | `resources/views/front/sections/hero/hosting.blade.php` |
| Template Key | `hosting_hero` |

**Data consumed from `$data`**:

| Key | Fallback if missing |
|-----|---------------------|
| `breadcrumb_home_label` | `__('Home')` |
| `breadcrumb_home_url` | `'index.html'` |
| `breadcrumb_current_label` | `__('Hosting')` |
| `title` | *(not rendered)* |
| `subtitle` | *(not rendered)* |
| `features` | 4 hardcoded placeholder items |
| `card_title` | *(not rendered)* |
| `card_button_label` | *(not rendered)* |
| `card_button_url` | `'#'` |
| `background_image` | No background — overlay layer remains |

**Media resolution** (in Blade, not via helper):
```php
$media = \App\Models\Media::find($data['background_image']);
$bgUrl = $media?->url ?? null;
```

**Icon handling**: `features` items are text-only in the frontend template. Icons stored in the repeater are **not rendered** in the current `hosting.blade.php` implementation. Only `text` is used.

---

## 8. Dependencies

| Dependency | Value |
|-----------|-------|
| `config/sections.php` — template_registry | `hosting_hero` → `front.sections.hero.hosting` |
| `config/sections.php` — custom_preset_registry | `hosting_hero` → builder + admin view |
| `config/sections.php` — legacy_section_key_bridge | `hosting_hero` → `hosting_hero` |
| `SectionCustomPresetRegistry::get('hosting_hero')` | must return valid array |
| `SectionEditorRepeaterFactory::buildLocaleCampaignFeatureItems()` | builds `features` per locale |
| `SectionMediaPreviewBuilder::build()` | resolves `background_image` ID for admin preview |
| Repeater JS contract | `data-feature-repeater`, `data-add-feature-item`, `data-feature-item-template`, `data-name-template`, `__INDEX__` placeholder |

---

## 9. Verification Checklist

- [ ] `section_definitions` record exists with `section_key = 'hosting_hero'`
- [ ] Record has `editor_mode = 'custom_preset'` AND `custom_editor_key = 'hosting_hero'` **OR** legacy bridge is active
- [ ] `section_definition_template` pivot has entry linking to template with `template_key = 'hosting_hero'`
- [ ] `SectionCustomPresetRegistry::get('hosting_hero')` returns non-null
- [ ] `SectionTemplateRegistry::get('hosting_hero')` returns non-null with valid view path
- [ ] Admin editor opens custom preset UI (not dynamic or legacy editor)
- [ ] All scalar fields render in admin with correct `old()` support
- [ ] `features` repeater: add/remove/reorder/duplicate items works
- [ ] Saving stores correct JSON structure in `section_translations.content`
- [ ] `background_image` saves as integer Media ID
- [ ] `features` items save as array with `text`, `icon`, `icon_source`, `icon_svg`, `icon_media`
- [ ] Frontend renders `front.sections.hero.hosting` (not the fallback template)
- [ ] Background image resolves to URL from Media ID (or renders without background)
- [ ] `features` list renders correctly (or shows hardcoded fallback)
- [ ] Card title, button label, button URL render when set
- [ ] Breadcrumb renders correctly in both locales
- [ ] Other sections on the same page are unaffected
- [ ] Legacy sections (null `section_definition_id`) render unchanged
- [ ] `docs/sections/presets/hosting_hero.md` is current

---

## 10. Known Notes

- **Icons in features are stored but not rendered** on the frontend. The `hosting.blade.php` template reads only `$featureItem['text']`. Icons are stored in content but silently ignored at render time.
- **Legacy bridge active**: `hosting_hero` → `hosting_hero` in `legacy_section_key_bridge`. This means definitions linked by `section_key = 'hosting_hero'` without formal `editor_mode/custom_editor_key` values still activate the preset editor. Bridge should be removed once all records are backfilled.
- The frontend fallback feature list contains duplicate entries (`'Private Domain'` appears twice) — this is from the static hardcoded placeholder in `hosting.blade.php`.

---

## 11. Future Improvements

- Render feature icons (`icon`, `icon_source`, `icon_media`) in `hosting.blade.php`.
- Backfill all `hosting_hero` definition records with `editor_mode = custom_preset` + `custom_editor_key = hosting_hero`, then remove the legacy bridge entry.
