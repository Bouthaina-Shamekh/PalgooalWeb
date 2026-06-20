# Collapsible Field Groups — Phase 3 Implementation Report

> Date: 2026-06-20
> Status: Completed
> Depends on: Phase 1 (FieldGroupRegistry) + Phase 2 (ComponentLibrary groups)

---

## Summary

Converted every field group card in the Page Builder section editor from a static always-visible panel into a collapsible `<details>` / `<summary>` accordion. Collapse preference is remembered per-section via `localStorage`. No change to the save pipeline, field rendering, or Content / Design tab logic.

---

## Files Changed

| File | Change |
|------|--------|
| `resources/views/dashboard/pages/sections/partials/dynamic-editor/renderer.blade.php` | Group `<div>` → `<details>` + `<summary>` (both content and design loops) |
| `resources/views/dashboard/pages/sections/layouts/workspace.blade.php` | Added `<style>` block (chevron CSS) + `window.initGroupAccordion` function + call in DOMContentLoaded |
| `resources/views/dashboard/pages/sections/index.blade.php` | Added `runEditorInitializer(window.initGroupAccordion)` in `bindSectionEditor()` |

No migration, no seeder, no DB change, no new library.

---

## HTML Structure

### Before
```html
<div class="rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
    <div class="mb-4">
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
            المقدمة
        </h3>
    </div>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-1">
        <!-- fields -->
    </div>
</div>
```

### After
```html
<details class="rounded-3xl border border-slate-200 bg-white overflow-hidden"
         data-group-key="intro">
    <summary class="flex cursor-pointer select-none items-center justify-between px-5 py-4
                    hover:bg-slate-50 transition-colors duration-150">
        <div class="flex items-center gap-2.5">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                المقدمة
            </h3>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                3
            </span>
        </div>
        <svg class="group-chevron h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" ...>
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </summary>
    <div class="border-t border-slate-100 px-5 pb-5 pt-4">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-1">
            <!-- fields — always in DOM, always submitted -->
        </div>
    </div>
</details>
```

---

## CSS (workspace.blade.php `<head>`)

```css
/* Remove native disclosure triangle in all browsers */
details[data-group-key] > summary { list-style: none; }
details[data-group-key] > summary::-webkit-details-marker { display: none; }
/* Rotate chevron SVG when group is open */
details[data-group-key][open] .group-chevron { transform: rotate(180deg); }
```

The `.group-chevron` SVG has `transition-transform duration-200` via Tailwind for a smooth 200 ms animation.

---

## localStorage Key Format

```
section-group-state-{sectionId}
```

Examples:
- `section-group-state-83` — section with ID 83
- `section-group-state-0` — fallback when section ID is not resolvable

**Value format:**
```json
{
  "intro": true,
  "image": false,
  "cta": false,
  "features": true
}
```

`true` = open · `false` = closed · Key absent = closed (safe default).

---

## Default State (no saved state)

When a user opens a section editor for the first time (or after clearing localStorage):
- First `<details>` group → `open`
- All other groups → closed

Groups are ordered by `sort_order` from `DynamicSectionEditorRenderer`, so the topmost group (usually `intro` / `المقدمة`) opens automatically.

---

## State Persistence Flow

1. User opens section editor → AJAX fetches editor HTML → `bindSectionEditor()` runs
2. `runEditorInitializer(window.initGroupAccordion)` is called with `sidebarEditorPanel` as scope
3. `initGroupAccordion` resolves `sectionId` from `[data-section-editor-form]` → builds `STORAGE_KEY`
4. Reads `localStorage.getItem(STORAGE_KEY)` → parses JSON state
5. Applies `detail.open = true/false` for each `details[data-group-key]`
6. Attaches `toggle` event listener on each `<details>` → persists updated state on every open/close

After Save → Refresh:
- Page reloads → editor re-opens → same `STORAGE_KEY` → same state restored

All `localStorage` operations are wrapped in `try/catch` for private-browsing safety.

---

## Scoping

- `initGroupAccordion` scopes to the passed `scope` element (the sidebar editor panel)
- This means multiple sections can be open simultaneously without key collisions (each has its own `sectionId`)
- Content panel and Design panel share the same `STORAGE_KEY` — group keys across both panels are typically different (`intro`/`cta` in Content, `background`/`layout_style` in Design)
- If the same group key appears in both panels (unusual), they share the same open/closed bit — acceptable trade-off for simplicity

---

## Validation — Expected Results

### Content Showcase (intro + image + cta + features)

```
▼ المقدمة (3)          ← open (first group)
  eyebrow / title / subtitle

▶ الصورة (3)           ← closed
▶ الدعوة للعمل (3)    ← closed
▶ المميزات (1)         ← closed
```

### Features Grid (intro + features)

```
▼ المقدمة (3)          ← open (first group)
  eyebrow / title / subtitle

▶ المميزات (1)         ← closed
```

### After user opens الصورة then Saves + Refreshes

```
▼ المقدمة (3)          ← open (was open before save)
▼ الصورة (3)           ← open (user opened it)
▶ الدعوة للعمل (3)    ← closed
▶ المميزات (1)         ← closed
```

---

## What Did NOT Change

- `DynamicSectionEditorRenderer` — data structure unchanged
- `FieldGroupRegistry` — unchanged
- `SectionFieldClassifier` — unchanged
- Content / Design tab switching — fully independent; accordion works within each tab panel
- Save payload — `<details>` closed state does **not** suppress form field submission (all inputs remain in DOM)
- Field rendering partials — all `@include($field['partial'])` calls unchanged
- Replica inputs — still rendered inside the `<details>` body; still submitted

---

## Architecture Notes

### Why `<details>` / `<summary>` instead of Alpine or custom JS toggle

- No extra library needed
- Native browser behavior — no JS required for basic toggle
- Content inside closed `<details>` stays in DOM → inputs always submitted
- Works correctly even if JS fails to load
- CSS-only chevron animation via `details[open] .group-chevron`

### Why not `display:none` on the container

`display:none` on a container **would** suppress form submission for inputs inside it. The `<details>` approach avoids this problem entirely — a closed `<details>` visually hides content but keeps it in the form submission path.

### Empty groups

Groups with zero fields are never rendered (the `@foreach` loop over `$contentGroups` / `$designGroups` only emits groups that have fields, per `SectionFieldClassifier::splitGroups()`). No empty `<details>` elements are produced.
