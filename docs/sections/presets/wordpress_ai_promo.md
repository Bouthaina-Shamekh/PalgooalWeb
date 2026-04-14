# Preset: wordpress_ai_promo
## Internal Developer Documentation

---

## 1. Overview

| Property | Value |
|----------|-------|
| **Name** | WordPress AI Promo |
| **Section Key** | `wordpress_ai_promo` |
| **Template Key** | `wordpress_ai_promo` |
| **Editor Mode** | `custom_preset` |
| **Custom Editor Key** | `wordpress_ai_promo` |
| **Category** | *(check DB record)* |
| **Legacy Bridge** | None registered |

**Activation**: formal path only — `editor_mode = custom_preset` + `custom_editor_key = 'wordpress_ai_promo'`.

---

## 2. Purpose

A two-column promotional section designed for WordPress hosting or AI-powered WordPress products. Layout:
- Left column: eyebrow label, title, feature checklist, pricing display, CTA button
- Right column: background/product image

Expected placement: mid-page on a hosting or product landing page, below the main hero.

---

## 3. Content Shape

```json
{
    "eyebrow": "WordPress Hosting",
    "title": "Launch and manage WordPress with AI",
    "features": [
        {
            "text": "Easy one-click installs",
            "icon": "",
            "icon_source": "class",
            "icon_svg": "",
            "icon_media": ""
        },
        {
            "text": "AI-powered performance",
            "icon": "ti ti-bolt",
            "icon_source": "class",
            "icon_svg": "",
            "icon_media": ""
        }
    ],
    "pricing": "$9.99/month",
    "button_label": "Get Started",
    "button_url": "https://example.com/wordpress",
    "image_alt": "WordPress hosting dashboard preview",
    "background_image": 55
}
```

All fields are **translatable**. No shared fields.

---

## 4. Scalar Fields

| Field Key | Type | Scope | Required |
|-----------|------|-------|----------|
| `eyebrow` | text | translatable | optional |
| `title` | text | translatable | optional |
| `pricing` | text | translatable | optional |
| `button_label` | text | translatable | optional |
| `button_url` | url | translatable | optional |
| `image_alt` | text | translatable | optional |
| `background_image` | media (ID) | translatable | optional |

No default values are injected by the builder; all default to empty string.

---

## 5. Repeater Fields

### `features` (Campaign Features Repeater)

| Item Field | Type | Notes |
|-----------|------|-------|
| `text` | string | Required — empty items are filtered out |
| `icon` | string | Tabler CSS class |
| `icon_source` | enum | `class` \| `svg` \| `media` — default: `class` |
| `icon_svg` | string | Raw SVG |
| `icon_media` | string/int | Media ID |

Factory: `SectionEditorRepeaterFactory::buildLocaleCampaignFeatureItems()`  
Content key: `features`  
Repeater partial: `dashboard.pages.sections.partials.repeaters.campaign-features-repeater`

**Frontend note**: Only `text` is rendered. `icon`, `icon_source`, and `icon_media` are stored but not rendered in the current `wordpress_ai_promo.blade.php`.

**Fallback**: if `features` is empty, the frontend renders 4 hardcoded placeholder strings (same placeholder as `hosting_hero`).

---

## 6. Admin Editor Mapping

| Element | Value |
|---------|-------|
| Builder method | `SectionCustomPresetEditorRenderer::buildWordPressAIPromoPreset()` |
| Admin Blade | `dashboard.pages.sections.partials.custom-presets.wordpress-ai-promo` |
| File path | `resources/views/dashboard/pages/sections/partials/custom-presets/wordpress-ai-promo.blade.php` |
| Repeater partial | `campaign-features-repeater.blade.php` (via `@include`) |
| Media preview | `SectionMediaPreviewBuilder::build($backgroundImageValue)` |

**Blade variables consumed**:

| Variable | Source |
|----------|--------|
| `$customPresetEditor` | array from `buildWordPressAIPromoPreset()` |
| `$code` | current locale code |
| `$contentGridClass` | CSS class string from parent |
| `$sectionTitleValue` | section title for current locale |
| `$usesInternalLabel` | bool |
| `$mediaPreviewBuilder` | `SectionMediaPreviewBuilder` instance |

**Admin editor sections** (as rendered in the Blade):
1. Main Content: `eyebrow`, `title`
2. Pricing: `pricing`
3. Button: `button_label`, `button_url`
4. Features: `campaign-features-repeater`
5. Background: `background_image` + `image_alt`

---

## 7. Frontend Mapping

| Element | Value |
|---------|-------|
| Frontend Blade | `front.sections.promo.wordpress_ai_promo` |
| File path | `resources/views/front/sections/promo/wordpress_ai_promo.blade.php` |
| Template Key | `wordpress_ai_promo` |

**Data consumed from `$data`**:

| Key | Fallback if missing |
|-----|---------------------|
| `eyebrow` | *(not rendered)* |
| `title` | *(not rendered)* |
| `features` | 4 hardcoded placeholder items |
| `pricing` | *(not rendered)* |
| `button_label` + `button_url` | CTA not rendered (both required) |
| `background_image` | Image block not rendered |

**Media resolution** (in Blade):
```php
$media = \App\Models\Media::find($data['background_image']);
$bgUrl = $media?->url ?? null;
```

Image is rendered as `<img>` with `alt="Team collaboration"` hardcoded — `image_alt` is stored but not used in the current template.

---

## 8. Dependencies

| Dependency | Value |
|-----------|-------|
| `config/sections.php` — template_registry | `wordpress_ai_promo` → `front.sections.promo.wordpress_ai_promo` |
| `config/sections.php` — custom_preset_registry | `wordpress_ai_promo` → builder + admin view |
| `SectionCustomPresetRegistry::get('wordpress_ai_promo')` | must return valid array |
| `SectionEditorRepeaterFactory::buildLocaleCampaignFeatureItems()` | builds `features` per locale |
| `SectionMediaPreviewBuilder::build()` | resolves `background_image` ID for admin preview |
| Repeater JS contract | same as `hosting_hero` (same repeater partial) |

---

## 9. Verification Checklist

- [ ] `section_definitions` record exists with `section_key = 'wordpress_ai_promo'`
- [ ] Record has `editor_mode = 'custom_preset'` AND `custom_editor_key = 'wordpress_ai_promo'`
- [ ] `section_definition_template` pivot links to template with `template_key = 'wordpress_ai_promo'`
- [ ] `SectionCustomPresetRegistry::get('wordpress_ai_promo')` returns non-null
- [ ] `SectionTemplateRegistry::get('wordpress_ai_promo')` returns non-null with valid view
- [ ] Admin editor opens custom preset UI (not dynamic/legacy)
- [ ] All scalar fields (`eyebrow`, `title`, `pricing`, `button_label`, `button_url`, `image_alt`) render with `old()` support
- [ ] `features` repeater: add/remove/reorder/duplicate works
- [ ] Saving stores correct JSON in `section_translations.content`
- [ ] `background_image` saves as integer Media ID
- [ ] Frontend renders `front.sections.promo.wordpress_ai_promo` (not fallback)
- [ ] Background image resolves and renders
- [ ] `features` list renders (or shows hardcoded fallback when empty)
- [ ] `pricing` renders in red bold text
- [ ] CTA button renders when both `button_label` and `button_url` are set
- [ ] Other sections on same page unaffected
- [ ] Legacy sections unchanged
- [ ] `docs/sections/presets/wordpress_ai_promo.md` is current

---

## 10. Known Notes

- **`image_alt` is stored but not rendered**: the frontend template hardcodes `alt="Team collaboration"` instead of using `$data['image_alt']`. This should be fixed.
- **Feature icons not rendered**: same situation as `hosting_hero` — `icon`, `icon_source`, `icon_media` are stored in content but not read in the frontend template.
- **No legacy bridge**: unlike `hosting_hero`, this preset has no entry in `legacy_section_key_bridge`. It requires formal DB configuration (`editor_mode = custom_preset`).
- **Hardcoded fallback features** in `wordpress_ai_promo.blade.php` are identical to those in `hosting.blade.php` (copy-paste artifact).

---

## 11. Future Improvements

- Use `$data['image_alt']` in the `<img>` alt attribute instead of the hardcoded string.
- Render feature icons if icon data is present.
- Deduplicate the hardcoded fallback feature list (separate from `hosting_hero`).
