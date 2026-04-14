# Custom Preset Section — Verification Checklist
## Reusable template for each new preset

> Copy this checklist into the preset's own `.md` file (Section 9) before starting work.  
> A preset is **not complete** until every applicable item is checked.

---

## Phase 1 — Registration

### 1.1 Section Definition (Database)
- [ ] `section_definitions` record exists with the correct `section_key`
- [ ] `editor_mode = 'custom_preset'`
- [ ] `custom_editor_key` is set and matches the intended preset key
- [ ] `is_active = true`
- [ ] `is_visible = true` (unless intentionally hidden)
- [ ] `label` is set to a human-readable name
- [ ] `section_definition_template` pivot has an entry linking to the primary template
- [ ] The linked template has `is_active = true`

### 1.2 Template Registry (`config/sections.php`)
- [ ] `template_registry.templates` has an entry with the correct `template_key`
- [ ] `view` path points to an existing Blade file
- [ ] `category` is set (or intentionally omitted for preset-only templates)
- [ ] `SectionTemplateRegistry::get('your_template_key')` returns a non-null array

### 1.3 Custom Preset Registry (`config/sections.php`)
- [ ] `custom_preset_registry.presets` has an entry with the correct `preset_key`
- [ ] `label` is set
- [ ] `view` points to an existing admin Blade partial
- [ ] `builder` points to an existing method in `SectionCustomPresetEditorRenderer`
- [ ] `SectionCustomPresetRegistry::get('your_preset_key')` returns a non-null array

### 1.4 Legacy Bridge (if applicable)
- [ ] If a legacy bridge is needed, it is added to `custom_preset_registry.legacy_section_key_bridge`
- [ ] If no bridge is needed, confirm that no entry exists (to avoid accidental activation)

---

## Phase 2 — Definition Integrity

- [ ] `SectionCustomPresetEditorRenderer::resolvePresetMeta()` resolves the correct preset (not null)
- [ ] `SectionDefinitionRuntimeResolver::resolveRenderableDefinition()` returns the definition for frontend use
- [ ] The definition has a primary template with a valid `template_key`
- [ ] `SectionTemplateRegistry::resolveView($templateKey)` returns the intended view (not the fallback)
- [ ] If `section_definition_id` is null on a section instance, the legacy renderer is used (not this preset) — confirm this is acceptable

---

## Phase 3 — Editor Behavior

### 3.1 Editor Activation
- [ ] Opening the section editor activates the custom preset UI (not dynamic, not legacy)
- [ ] `$customPresetEditor['enabled']` is `true` in the Blade context
- [ ] `$customPresetEditor['presetKey']` matches the expected preset key
- [ ] `$customPresetEditor['activationSource']` is `'custom_editor_key'` (or `'legacy_section_key_bridge'` if bridge is intended)

### 3.2 Scalar Fields
- [ ] All scalar fields render in the admin editor
- [ ] `old()` values restore correctly after a failed form submission
- [ ] Field labels are correct and readable
- [ ] Placeholder text is helpful and locale-appropriate

### 3.3 Repeater Fields (if applicable)
- [ ] The correct repeater partial is included
- [ ] Existing items render with correct values on edit
- [ ] "Add item" button creates a new item using the `<template>` element
- [ ] New item names use `__INDEX__` placeholder that gets replaced by JS
- [ ] "Remove item" removes the item from DOM and updates empty state
- [ ] "Duplicate item" creates a copy with correct field names
- [ ] Drag-to-reorder works (SortableJS `data-feature-drag-handle`)
- [ ] Accordion toggle (expand/collapse) works per item
- [ ] Empty state message appears when no items exist

### 3.4 Media Fields (if applicable)
- [ ] Media picker opens and allows selection
- [ ] Selected media ID populates the hidden input correctly
- [ ] Admin preview shows the resolved image URL (`SectionMediaPreviewBuilder`)
- [ ] Re-opening the editor shows the previously saved image preview

### 3.5 Icon Fields (if applicable)
- [ ] Icon source selector (`class` / `media` / `svg`) toggles the correct input panel
- [ ] Icon library modal opens and populates the icon class input
- [ ] Media picker for `icon_source = media` opens and saves the ID to `icon_media`
- [ ] Icon preview updates visually when selection changes

---

## Phase 4 — Saving

### 4.1 Scalar fields
- [ ] Each scalar field saves to `section_translations.content[{field_key}]`
- [ ] `translations[{locale}][content][{field_key}]` is the correct input name format
- [ ] Data persists after page reload

### 4.2 Repeater data
- [ ] Each item saves with the correct index: `content[{repeater_key}][{n}][{item_field}]`
- [ ] Items save in the correct order (drag-reorder order is respected)
- [ ] Removed items do not appear in saved content
- [ ] Empty items (all-blank required fields) are filtered by the factory

### 4.3 Media IDs
- [ ] `background_image` (or equivalent) saves as an integer ID, not a URL
- [ ] `icon_media` saves as a string or integer ID, not a URL
- [ ] No media URL is hardcoded into the saved content

### 4.4 Translatable fields
- [ ] Each locale saves its own content independently
- [ ] Switching locale tab and saving does not overwrite the other locale
- [ ] Shared fields (if any) save to all locale records via replica inputs

---

## Phase 5 — Frontend Rendering

### 5.1 Template resolution
- [ ] Frontend uses `SectionDefinitionFrontendViewDataFactory` path (not legacy switch/case)
- [ ] The correct Blade view is rendered (verify via page source or DevTools)
- [ ] `$data` array contains all expected keys from saved content

### 5.2 Dynamic values
- [ ] Text fields render with the saved content values (not empty, not hardcoded)
- [ ] Repeater items render in saved order
- [ ] Conditional rendering blocks (title, subtitle, CTA) appear only when the field is set

### 5.3 Media resolution
- [ ] Media IDs are resolved to URLs in the Blade template using `\App\Models\Media::find()`
- [ ] Resolved media URLs render correctly (images load)
- [ ] If media ID is null/invalid, the fallback behavior is correct (no broken `<img>` tags)

### 5.4 Icon/media fallback
- [ ] `icon_source = class` → Tabler `<i>` renders with correct class
- [ ] `icon_source = media` + valid ID → `<img>` renders
- [ ] `icon_source = media` + invalid/missing ID → fallback icon renders
- [ ] No icon at all → default icon (e.g., `ti ti-shield-check`) renders

### 5.5 No unintended hardcoded content
- [ ] No text in the template is hardcoded that should come from `$data`
- [ ] No placeholder/Lorem Ipsum is visible in production rendering
- [ ] Alt text uses `$data['image_alt']` (or equivalent), not a static string

---

## Phase 6 — Compatibility

- [ ] Other sections on the same page render correctly (no regression)
- [ ] Sections with `section_definition_id = null` still use the legacy path (no crash)
- [ ] Dynamic sections (`editor_mode = dynamic`) are unaffected
- [ ] Other existing custom presets (`hosting_hero`, `wordpress_ai_promo`, `website_protection`) render correctly
- [ ] The `SectionRegistry` legacy path still works for non-definition sections
- [ ] Frontend page controller still serves pages normally

---

## Phase 7 — Basic Readiness

- [ ] `php artisan view:clear` — no Blade compilation errors
- [ ] `php artisan config:clear` — no config parse errors
- [ ] `php artisan route:list` — routes remain intact
- [ ] No PHP syntax errors in new files (`php -l app/... resources/...`)
- [ ] No missing view errors in admin editor or frontend
- [ ] No N+1 query regressions on the frontend (review eager loading if applicable)
- [ ] Per-preset documentation file is written and accurate (`docs/sections/presets/{preset_key}.md`)
- [ ] Framework doc updated if a new pattern was introduced (`docs/sections/preset-framework.md`)

---

## Quick Reference — What Each Class Must Receive

| Class | What it needs |
|-------|--------------|
| `SectionCustomPresetRegistry` | preset_key in `config/sections.php` OR runtime `register()` call |
| `SectionCustomPresetEditorRenderer` | method named exactly as `builder` value in registry |
| `SectionTemplateRegistry` | template_key in `config/sections.php` OR runtime `register()` call |
| `SectionDefinitionFrontendViewDataFactory` | `section_definition_id` set on section + `is_active` definition + primary template |
| Admin Blade partial | `$customPresetEditor['locales'][$code]['values']` + `$code` + `$contentGridClass` + `$mediaPreviewBuilder` |
| Frontend Blade | `$data` array with all expected keys (may be sparse — handle missing keys gracefully) |

---

## Known Framework Limitations (as of 2026-04-15)

| Limitation | Workaround |
|-----------|-----------|
| Repeaters are not a native `field_type` in dynamic definitions | Use custom preset + repeater partial |
| No built-in validation on `content` JSON shape | Normalize in factory builder method |
| `icon_svg` source not supported in all repeaters | Use `class` or `media` instead; or add support per-repeater |
| Legacy bridge is temporary — definitions need backfill | Track in per-preset Known Notes |
| Frontend templates resolve media inline (potential N+1) | Eager-resolve all IDs before the render loop (see `website_protection.blade.php`) |
