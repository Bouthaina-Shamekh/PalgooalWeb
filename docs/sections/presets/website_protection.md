# Preset: website_protection
## Internal Developer Documentation

---

## 1. Overview

| Property | Value |
|----------|-------|
| **Name** | Website Protection Promo |
| **Section Key** | `website_protection` |
| **Template Key** | `website_protection` |
| **Editor Mode** | `custom_preset` |
| **Custom Editor Key** | `website_protection` |
| **Category** | *(check DB record)* |
| **Legacy Bridge** | None registered |

**Activation**: formal path only — `editor_mode = custom_preset` + `custom_editor_key = 'website_protection'`.

---

## 2. Purpose

A feature card grid section that showcases website security or protection features. Layout:
- Centered heading + subtitle text
- Grid of feature cards (1/2/4 columns responsive)
- Each card: icon (Tabler or media SVG), title, description

Expected placement: mid-page on a security, hosting, or protection product landing page.

---

## 3. Content Shape

```json
{
    "title": "Website Protection Features",
    "subtitle": "Comprehensive security features to keep your website safe.",
    "items": [
        {
            "title": "Automatic malware removal",
            "description": "We scan for threats and remove malicious files.",
            "icon": "ti ti-shield-check",
            "icon_source": "class",
            "icon_media": ""
        },
        {
            "title": "DDoS Protection",
            "description": "Defend against distributed denial-of-service attacks.",
            "icon": "",
            "icon_source": "media",
            "icon_media": 88
        }
    ]
}
```

All fields are **translatable**. No shared fields. No background image field.

---

## 4. Scalar Fields

| Field Key | Type | Scope | Required |
|-----------|------|-------|----------|
| `title` | text | translatable | optional |
| `subtitle` | textarea | translatable | optional |

No default values injected — both default to empty string.

---

## 5. Repeater Fields

### `items` (Protection Items Repeater)

This repeater uses a **different partial and factory method** than the campaign features repeater.

| Item Field | Type | Notes |
|-----------|------|-------|
| `title` | string | Card title — item filtered if both title and description are empty |
| `description` | string | Card body text |
| `icon` | string | Tabler CSS class |
| `icon_source` | enum | `class` \| `media` — default: `class` (no `svg` option) |
| `icon_media` | string/int | Media ID — used when `icon_source = media` |

Factory: `SectionEditorRepeaterFactory::buildLocaleProtectionItems()`  
Content key: `items`  
Repeater partial: `dashboard.pages.sections.partials.repeaters.protection-items-repeater`

**Differences from `campaign-features-repeater`**:
- Item has `title` + `description` (not a single `text` field)
- `icon_source` supports only `class` and `media` (no `svg`)
- Summary line in collapsed accordion shows `description` (not `text`)
- Default icon preview: `ti ti-shield-check`

---

## 6. Admin Editor Mapping

| Element | Value |
|---------|-------|
| Builder method | `SectionCustomPresetEditorRenderer::buildWebsiteProtectionPreset()` |
| Admin Blade | `dashboard.pages.sections.partials.custom-presets.website-protection-promo` |
| File path | `resources/views/dashboard/pages/sections/partials/custom-presets/website-protection-promo.blade.php` |
| Repeater partial | `protection-items-repeater.blade.php` (via `@include`) |
| Media preview | `SectionMediaPreviewBuilder::build($item['icon_media'])` per item |

**Blade variables consumed**:

| Variable | Source |
|----------|--------|
| `$customPresetEditor` | array from `buildWebsiteProtectionPreset()` |
| `$code` | current locale code |
| `$contentGridClass` | CSS class string from parent |
| `$sectionTitleValue` | section title for current locale |
| `$usesInternalLabel` | bool |
| `$mediaPreviewBuilder` | `SectionMediaPreviewBuilder` instance (passed to repeater) |

**Admin editor sections**:
1. Main Content: `title`, `subtitle`
2. Protection Cards: `protection-items-repeater`

---

## 7. Frontend Mapping

| Element | Value |
|---------|-------|
| Frontend Blade | `front.sections.promo.website_protection` |
| File path | `resources/views/front/sections/promo/website_protection.blade.php` |
| Template Key | `website_protection` |

**Data consumed from `$data`**:

| Key | Fallback if missing |
|-----|---------------------|
| `title` | *(not rendered)* |
| `subtitle` | *(not rendered)* |
| `items` | Grid not rendered (empty collection) |

**Media resolution** (in Blade — resolved eagerly before loop):
```php
$resolvedMedia = [];
foreach ($cardItems as $ci) {
    $mediaId = $ci['icon_media'] ?? null;
    if ($ci['icon_source'] === 'media' && $mediaId && !isset($resolvedMedia[$mediaId])) {
        $media = \App\Models\Media::find($mediaId);
        $resolvedMedia[$mediaId] = $media?->url ?? null;
    }
}
```

**Icon fallback chain** (per card):
1. `icon_source = 'media'` + `icon_media` set + URL resolves → `<img>`
2. `icon_source = 'media'` + URL fails → fallback `ti ti-shield-check`
3. `icon` class set → `<i class="{{ $card['icon'] }}">`
4. No icon → `ti ti-shield-check` (default)

---

## 8. Dependencies

| Dependency | Value |
|-----------|-------|
| `config/sections.php` — template_registry | `website_protection` → `front.sections.promo.website_protection` |
| `config/sections.php` — custom_preset_registry | `website_protection` → builder + admin view (label: `'Website Protection Promo'`) |
| `SectionCustomPresetRegistry::get('website_protection')` | must return valid array |
| `SectionEditorRepeaterFactory::buildLocaleProtectionItems()` | builds `items` per locale |
| `SectionMediaPreviewBuilder::build()` | resolves `icon_media` IDs per item for admin preview |
| Repeater JS contract | `data-feature-repeater`, `data-add-feature-item`, `data-feature-item-template`, `data-name-template`, `__INDEX__` placeholder |

---

## 9. Verification Checklist

- [ ] `section_definitions` record exists with `section_key = 'website_protection'`
- [ ] Record has `editor_mode = 'custom_preset'` AND `custom_editor_key = 'website_protection'`
- [ ] `section_definition_template` pivot links to template with `template_key = 'website_protection'`
- [ ] `SectionCustomPresetRegistry::get('website_protection')` returns non-null
- [ ] `SectionTemplateRegistry::get('website_protection')` returns non-null with valid view
- [ ] Admin editor opens custom preset UI (not dynamic/legacy)
- [ ] `title` and `subtitle` fields render with `old()` support
- [ ] `items` repeater: add/remove/reorder/duplicate works
- [ ] Each item: `title`, `description`, `icon`, `icon_source`, `icon_media` all save correctly
- [ ] `icon_source = media` shows media picker; `icon_source = class` shows icon library
- [ ] Media IDs save as integer/string (not URL)
- [ ] Frontend renders `front.sections.promo.website_protection` (not fallback)
- [ ] `title` and `subtitle` render centered
- [ ] Feature cards render in responsive grid (1→2→4 columns)
- [ ] Tabler icon renders when `icon_source = class`
- [ ] Media SVG renders when `icon_source = media` and media resolves
- [ ] Fallback icon (`ti ti-shield-check`) renders when both icon options are empty/unresolvable
- [ ] Other sections on same page unaffected
- [ ] Legacy sections unchanged
- [ ] `docs/sections/presets/website_protection.md` is current

---

## 10. Known Notes

- **`icon_source` supports only `class` and `media`** in this repeater (unlike `campaign-features-repeater` which also supports `svg`). The factory enforces this: `in_array($iconSource, ['class', 'media'], true)`.
- **Media icons are resolved eagerly** in the frontend template (before the foreach loop), which avoids N+1 queries. Other preset templates resolve media inline — this is the better pattern.
- **No background image field** — unlike `hosting_hero` and `wordpress_ai_promo`, this preset has no section-level background. All visual context comes from the card grid.
- **Label in config**: the `custom_preset_registry` label is `'Website Protection Promo'` (with "Promo") but the `template_registry` label is `'Website Protection'` (without "Promo"). Be consistent when adding future entries.

---

## 11. Future Improvements

- Standardize icon resolution pattern across all frontend templates (use the eager-resolve pattern from `website_protection.blade.php` as the reference).
- Consider supporting `icon_source = svg` in the protection items repeater for consistency with other repeaters.
