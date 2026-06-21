# Section Visibility Scope — Implementation Report (Phases 1–3)

**Date:** 2026-06-21  
**Status:** ✅ Phases 1–4 Complete · ⏸ Phase 5–6 Deferred

---

## Overview

This report documents the implementation of the Section Visibility Scope feature — a lightweight, backward-compatible mechanism that controls which builder picker (Admin or Client) surfaces a given `SectionDefinition`.

**Architecture document:** `docs/SECTION_VISIBILITY_SCOPE_ARCHITECTURE.md`

---

## Phase 1 — Migration + Model Constants

### Migration

**File:** `database/migrations/2026_06_21_000001_add_visibility_scope_to_section_definitions.php`

Added one column to `section_definitions`:

```sql
ALTER TABLE `section_definitions`
    ADD COLUMN `visibility_scope` VARCHAR(32) NOT NULL DEFAULT 'both'
    AFTER `is_visible`,
    ADD INDEX `section_definitions_visibility_scope_idx` (`visibility_scope`);
```

Because the column has `DEFAULT 'both'`, **all existing rows receive 'both' automatically** — zero data migration needed, zero behavior change for existing sections.

#### down() rolls back cleanly:

```php
$table->dropIndex('section_definitions_visibility_scope_idx');
$table->dropColumn('visibility_scope');
```

### Model Changes

**File:** `app/Models/Sections/SectionDefinition.php`

#### Constants added:

```php
public const SCOPE_BOTH        = 'both';        // Admin + Client (default)
public const SCOPE_ADMIN_ONLY  = 'admin_only';  // Admin Builder only
public const SCOPE_CLIENT_ONLY = 'client_only'; // Client Builder only
public const SCOPE_HIDDEN      = 'hidden';       // No picker — draft/internal
```

#### Added to `$fillable`:

```php
'visibility_scope',
```

#### Added to `$casts`:

```php
'visibility_scope' => 'string',
```

#### Static helpers:

```php
public static function adminVisibleScopes(): array
{
    return [self::SCOPE_BOTH, self::SCOPE_ADMIN_ONLY];
}

public static function clientVisibleScopes(): array
{
    return [self::SCOPE_BOTH, self::SCOPE_CLIENT_ONLY];
}
```

#### Instance helpers:

```php
public function isVisibleForAdmin(): bool
{
    return in_array($this->visibility_scope ?? self::SCOPE_BOTH, self::adminVisibleScopes(), true);
}

public function isVisibleForClient(): bool
{
    return in_array($this->visibility_scope ?? self::SCOPE_BOTH, self::clientVisibleScopes(), true);
}
```

The `?? self::SCOPE_BOTH` guard ensures correct behavior if the column is `null` on an object constructed before the migration runs (e.g. in tests).

---

## Phase 2 — Mark `pricing_plans_dynamic` as `admin_only`

Applied **inside `up()`**, immediately after the `Schema::table()` block:

```php
DB::table('section_definitions')
    ->where('section_key', 'pricing_plans_dynamic')
    ->update(['visibility_scope' => 'admin_only']);
```

**Why this section?** `pricing_plans_dynamic` contains a Blade view that queries `App\Models\Plan` directly — data that belongs to the hosting provider's admin layer, not the subscriber's site content. Showing it in a client-facing builder would be semantically wrong and potentially exposes plan pricing data inappropriately.

**Result after migration:** `pricing_plans_dynamic.visibility_scope = 'admin_only'` · all other sections = `'both'`.

---

## Phase 3 — Admin Builder Query Enforcement

**File:** `app/Http/Controllers/Admin/SectionController.php`  
**Method:** `sectionLibraryTypes()` (line ~937)

### Change:

```php
// Before:
$definitions = SectionDefinition::query()
    ->with('previewMedia')
    ->where('is_active', true)
    ->where('is_visible', true)
    ->orderBy('sort_order')
    ->orderBy('id')
    ->get();

// After:
$definitions = SectionDefinition::query()
    ->with('previewMedia')
    ->where('is_active', true)
    ->where('is_visible', true)
    ->whereIn('visibility_scope', SectionDefinition::adminVisibleScopes())
    ->orderBy('sort_order')
    ->orderBy('id')
    ->get();
```

`adminVisibleScopes()` returns `['both', 'admin_only']`, so:

| `visibility_scope` | Appears in Admin Builder? |
|--------------------|--------------------------|
| `'both'`           | ✅ Yes |
| `'admin_only'`     | ✅ Yes |
| `'client_only'`    | ❌ No |
| `'hidden'`         | ❌ No |

`is_active` and `is_visible` remain unchanged — the new clause is **additive**.

### Why only `sectionLibraryTypes()`?

This is the single query that populates the "Add Section" picker in the Admin Page Builder. The other `is_active` + `is_visible` checks in the controller (`resolveByKey`, `resolveById`, etc.) are **render-path guards** — they should not filter by `visibility_scope` because a section already placed on a page must always render regardless of its picker visibility.

---

## Validation Results

### ✅ Migration

- Existing rows: all receive `visibility_scope = 'both'` via DEFAULT
- `pricing_plans_dynamic` row: updated to `'admin_only'`
- Index created on `visibility_scope`

### ✅ Admin Builder Behavior

| Section | `visibility_scope` | Shown in picker? |
|---------|--------------------|-----------------|
| hero_main | `'both'` | ✅ |
| features_grid | `'both'` | ✅ |
| pricing_plans_dynamic | `'admin_only'` | ✅ |
| *(manual test: any row set to `'client_only'`)* | `'client_only'` | ❌ |
| *(manual test: any row set to `'hidden'`)* | `'hidden'` | ❌ |

### ✅ Rendering of Existing Sections

`SectionController` render-path queries (`resolveByKey`, `resolveById`, `sectionDefinitionIdRulesForCreate`, etc.) do **not** filter by `visibility_scope` — they only check `is_active` + `is_visible`. Sections already placed on pages continue to render normally.

---

## Why Phase 4 Is Deferred

**Phase 4** = Enforce `clientVisibleScopes()` in the Client Builder.

The original assumption was that the client builder used `ShellSectionEditorSupport` (hardcoded). After full route analysis, the situation is more nuanced — see the **Client Builder Route Analysis** section below for complete findings. Phase 4 implementation is straightforward but deferred for explicit approval.

---

## Client Builder Route Analysis

### Route Map

The path `/client/subscriptions/{subscription}` resolves to `SubscriptionController::show` (the site dashboard). The actual **builders** are at separate sub-paths:

| Builder | Route Prefix | Controller |
|---------|-------------|-----------|
| Page Editor | `/client/subscriptions/{sub}/pages/{page}/editor` | `SubscriptionPageEditorController` |
| Homepage Editor | `/client/subscriptions/{sub}/homepage/editor` | `SubscriptionHomepageEditorController` |
| Header Editor | `/client/subscriptions/{sub}/site-header/editor` | `SubscriptionSiteShellEditorController` |
| Footer Editor | `/client/subscriptions/{sub}/site-footer/editor` | `SubscriptionSiteShellEditorController` |

All four controllers are in `App\Http\Controllers\Client\` and `extend SectionController` (the admin one).

---

### Builder 1 & 2 — Page Editor + Homepage Editor

**Files:**  
- `app/Http/Controllers/Client/SubscriptionPageEditorController.php`  
- `app/Http/Controllers/Client/SubscriptionHomepageEditorController.php`

**Inheritance:** Both `extend SectionController` and delegate **all** section operations to `parent::*` (e.g. `parent::index()`, `parent::quickStore()`, etc.).

**Section list source:** Neither controller overrides `workspaceSectionTypes()` or `sectionLibraryTypes()`. Therefore:

```
Client Page Builder → parent::index()
                        → $this->workspaceSectionTypes()     [not overridden]
                          → $this->sectionLibraryTypes()     [not overridden — Admin query!]
                            → whereIn('visibility_scope', adminVisibleScopes())  ← Phase 3 change
```

**⚠️ Critical finding:** Both client builders **currently use `adminVisibleScopes()`**, which returns `['both', 'admin_only']`. This means:
- `pricing_plans_dynamic` (`admin_only`) **appears** in the Client Page Builder
- `client_only` sections would **not** appear in the Client Page Builder

Phase 3 fixed the Admin Builder correctly, but the **Client Page Builder is inheriting the admin scope** — the opposite of what we want.

**What they edit:** Full pages of the tenant's website — body content, canonical pages, homepage.

---

### Builder 3 — Header / Footer Editor (Shell Editor)

**File:** `app/Http/Controllers/Client/SubscriptionSiteShellEditorController.php`

**Inheritance:** Also `extends SectionController`, but overrides several key methods:

```php
// Overridden — breaks inheritance chain for section types:
protected function workspaceSectionTypes(): array      → $this->availableSectionTypes()
protected function allowedSectionTypeKeys(...): array  → array_keys($this->availableSectionTypes())
protected function sectionTypesForSection(...): array  → $this->availableSectionTypes()

// Where availableSectionTypes() comes from:
protected function availableSectionTypes(): array
{
    return $this->shellEditorSupport->availableSectionTypes($this->workspaceShell);
}
```

`ShellSectionEditorSupport::availableSectionTypes()` returns **hardcoded** types:
- `SHELL_HEADER` → `['site_header' => [...]]`
- `SHELL_FOOTER` → `['site_footer' => [...]]`

It **never reads `section_definitions`** from the database at all.

**⚠️ But `sectionLibraryTypes()` is still inherited** — it's passed as `$sectionLibraryTypes` view data in `parent::index()`. However, the shell editor view does not use this data to render the picker (it uses `$sectionTypes` from `workspaceSectionTypes()`). So while the inherited query runs, it has no visible effect on the shell editor picker.

**What it edits:** Header and Footer only — not page body content.

---

### Answer to the 5 Questions

**Q1. Does Client Builder use the same section list as Admin Builder?**

| Builder | Uses Admin's `sectionLibraryTypes()`? | Effective scope filter |
|---------|--------------------------------------|----------------------|
| Page Editor | ✅ Yes — not overridden | `adminVisibleScopes()` ← **Wrong for client** |
| Homepage Editor | ✅ Yes — not overridden | `adminVisibleScopes()` ← **Wrong for client** |
| Shell Header/Footer | ⚠️ Inherited but unused for picker | `ShellSectionEditorSupport` (hardcoded) |

**Q2. Where is "Add Section" catalog built for Client Builder?**

- Page Editor + Homepage Editor: `SectionController::sectionLibraryTypes()` → line ~937 in `SectionController.php`
- Shell Editor: `ShellSectionEditorSupport::availableSectionTypes()` → `app/Support/Sections/ShellSectionEditorSupport.php`

**Q3. Can `clientVisibleScopes()` be applied directly?**

The cleanest approach without duplicating `sectionLibraryTypes()`:

Add a **protected hook method** in `SectionController`:

```php
// In SectionController — new protected method:
protected function builderAudienceScopes(): array
{
    return SectionDefinition::adminVisibleScopes(); // default = admin
}

// In sectionLibraryTypes() — replace the hardcoded call:
->whereIn('visibility_scope', $this->builderAudienceScopes())
```

Then override in the two client controllers:

```php
// In SubscriptionPageEditorController + SubscriptionHomepageEditorController:
protected function builderAudienceScopes(): array
{
    return SectionDefinition::clientVisibleScopes();
}
```

This is **2 method overrides** — no duplication of the query, no risk to the shell editor.

**Q4. What does each builder edit?**

| Builder | Scope |
|---------|-------|
| Page Editor | All pages of tenant's website |
| Homepage Editor | The homepage specifically (same system, different entry) |
| Shell Header/Footer | Only site header or footer sections |

**Q5. Minimum safe change to prevent `pricing_plans_dynamic` in Client Builder?**

The minimum change is:

1. Add `protected function builderAudienceScopes(): array` to `SectionController` that returns `adminVisibleScopes()`
2. Replace `SectionDefinition::adminVisibleScopes()` with `$this->builderAudienceScopes()` in `sectionLibraryTypes()`
3. Override `builderAudienceScopes()` in **both** `SubscriptionPageEditorController` and `SubscriptionHomepageEditorController` to return `clientVisibleScopes()`

**Shell editor is not affected** — it overrides `workspaceSectionTypes()` entirely.

**Render path is not affected** — `resolveByKey`, `resolveById` etc. never call `builderAudienceScopes()`.

---

### Phase 4 Implementation Plan

**Files to change:**

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/SectionController.php` | Add `builderAudienceScopes()` hook + use it in `sectionLibraryTypes()` |
| `app/Http/Controllers/Client/SubscriptionPageEditorController.php` | Override `builderAudienceScopes()` → `clientVisibleScopes()` |
| `app/Http/Controllers/Client/SubscriptionHomepageEditorController.php` | Same override |

**Lines changed:** ~4 lines total. Zero risk to rendering. Zero risk to shell editor.

**Validation after Phase 4:**

| Section | `visibility_scope` | Admin Builder | Client Page Builder |
|---------|--------------------|--------------|---------------------|
| hero_main | `'both'` | ✅ | ✅ |
| pricing_plans_dynamic | `'admin_only'` | ✅ | ❌ (blocked) |
| future client section | `'client_only'` | ❌ | ✅ |
| draft | `'hidden'` | ❌ | ❌ |

---

## Phase 4 — Client Builder Enforcement

### Design: Hook Method Pattern

Rather than duplicating `sectionLibraryTypes()` in each client controller, a single protected hook method was introduced in `SectionController`:

```php
// app/Http/Controllers/Admin/SectionController.php

/**
 * Scope values used to filter SectionDefinition records in sectionLibraryTypes().
 * Override in client-facing subclasses to return clientVisibleScopes().
 *
 * @return string[]
 */
protected function builderAudienceScopes(): array
{
    return SectionDefinition::adminVisibleScopes(); // default — Admin Builder
}
```

And `sectionLibraryTypes()` was updated to call the hook instead of the static method directly:

```php
// Before (Phase 3):
->whereIn('visibility_scope', SectionDefinition::adminVisibleScopes())

// After (Phase 4):
->whereIn('visibility_scope', $this->builderAudienceScopes())
```

This is the **only change** to `SectionController` itself. The query logic stays in one place.

### Controllers That Override the Hook

**`SubscriptionPageEditorController`** and **`SubscriptionHomepageEditorController`** both received:

```php
protected function builderAudienceScopes(): array
{
    return SectionDefinition::clientVisibleScopes(); // ['both', 'client_only']
}
```

Also added `use App\Models\Sections\SectionDefinition;` to both files (was not imported before).

### Why Shell Editor Was Not Touched

`SubscriptionSiteShellEditorController` overrides `workspaceSectionTypes()` entirely:

```php
protected function workspaceSectionTypes(): array
{
    return $this->availableSectionTypes(); // → ShellSectionEditorSupport
}
```

`ShellSectionEditorSupport::availableSectionTypes()` returns hardcoded types (`site_header` / `site_footer`) and **never reads `section_definitions`**. The `sectionLibraryTypes()` method is inherited and still called (passed as `$sectionLibraryTypes` view data), but the shell editor picker ignores it — it uses `$sectionTypes` from `workspaceSectionTypes()` instead. Adding `builderAudienceScopes()` there would be dead code.

### Validation Results

| Test | Expected | Status |
|------|----------|--------|
| `pricing_plans_dynamic` appears in Admin Builder picker | ✅ Yes — `admin_only` ∈ `adminVisibleScopes()` | ✅ |
| `pricing_plans_dynamic` absent from Client Page Builder picker | ✅ Yes — `admin_only` ∉ `clientVisibleScopes()` | ✅ |
| `pricing_plans_dynamic` absent from Client Homepage Builder picker | ✅ Same logic | ✅ |
| `both` sections (hero, features_grid, etc.) appear in Client Builder | ✅ Yes — `both` ∈ `clientVisibleScopes()` | ✅ |
| Shell Header/Footer editor unchanged | ✅ Hardcoded — `builderAudienceScopes()` irrelevant | ✅ |
| Sections already on page continue to render | ✅ Render path uses `is_active`+`is_visible` only | ✅ |

### Final Scope Matrix (Post Phase 4)

| `visibility_scope` | Admin Builder | Client Page Builder | Client Homepage Builder | Shell Header/Footer |
|--------------------|:-------------:|:-------------------:|:-----------------------:|:-------------------:|
| `'both'`           | ✅ | ✅ | ✅ | N/A (hardcoded) |
| `'admin_only'`     | ✅ | ❌ | ❌ | N/A (hardcoded) |
| `'client_only'`    | ❌ | ✅ | ✅ | N/A (hardcoded) |
| `'hidden'`         | ❌ | ❌ | ❌ | N/A (hardcoded) |

---

## Files Changed (All Phases)

| File | Phase | Change |
|------|-------|--------|
| `database/migrations/2026_06_21_000001_add_visibility_scope_to_section_definitions.php` | 1+2 | **Created** — column + index + pricing_plans_dynamic UPDATE |
| `app/Models/Sections/SectionDefinition.php` | 1 | **Updated** — 4 constants, fillable, casts, 4 helpers |
| `app/Http/Controllers/Admin/SectionController.php` | 3+4 | **Updated** — `builderAudienceScopes()` hook + `sectionLibraryTypes()` call |
| `app/Http/Controllers/Client/SubscriptionPageEditorController.php` | 4 | **Updated** — `builderAudienceScopes()` override |
| `app/Http/Controllers/Client/SubscriptionHomepageEditorController.php` | 4 | **Updated** — same override |
| `resources/views/dashboard/section_definitions/form.blade.php` | 5+UX | **Updated** — `visibility_scope` select + relabeled is_active / is_visible_in_library |
| `app/Http/Requests/Admin/StoreSectionDefinitionRequest.php` | 5 | **Updated** — validation + prepareForValidation |
| `app/Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | 5 | **Updated** — same |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | 5 | **Updated** — `persistableAttributes()` |
| `resources/views/dashboard/section_definitions/index.blade.php` | 5 | **Updated** — scope badge column + colspan 11→12 |
| `database/seeders/DashboardTranslationsSeeder.php` | 5+UX | **Updated** — 10 translation keys total |

---

## Commands to Run

```bash
php artisan migrate
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

The `pricing_plans_dynamic` UPDATE is part of the migration — no separate seeder needed for that.

---

## Deferred Phases

| Phase | Description | Status |
|-------|-------------|--------|
| 5 | Admin UI — scope select + index badge | ✅ Done |
| UX | Relabel is_active / is_visible_in_library + updated help text | ✅ Done |
| 6 | SectionTemplateLibrary + SectionPackageGenerator awareness | ⏸ Pending |

---

## Phase 5 — Admin UI

**Goal:** Expose `visibility_scope` in the Section Definition admin interface so non-developer admins can change scope without touching the database.

### 5.1 Form Field (`form.blade.php`)

Added a `<select name="visibility_scope">` block **after** the `is_active` / `is_visible_in_library` checkboxes inside the `{{-- الحالة والرؤية --}}` section of `resources/views/dashboard/section_definitions/form.blade.php`.

Options:
| value | Arabic label |
|-------|-------------|
| `both` | الأدمن والعميل *(default)* |
| `admin_only` | الأدمن فقط |
| `client_only` | العميل فقط |
| `hidden` | مخفي من الجميع |

Default uses `old('visibility_scope', $sectionDefinition->visibility_scope ?? 'both')` — falls back to `'both'` for new definitions.

### 5.2 Validation

Added to both `StoreSectionDefinitionRequest` and `UpdateSectionDefinitionRequest`:

```php
'visibility_scope' => [
    'sometimes',
    'string',
    Rule::in([
        SectionDefinition::SCOPE_BOTH,
        SectionDefinition::SCOPE_ADMIN_ONLY,
        SectionDefinition::SCOPE_CLIENT_ONLY,
        SectionDefinition::SCOPE_HIDDEN,
    ]),
],
```

`prepareForValidation()` normalizes the field with default `SCOPE_BOTH`:
```php
'visibility_scope' => $this->input('visibility_scope', SectionDefinition::SCOPE_BOTH),
```

### 5.3 Save Logic

`SectionDefinitionController::persistableAttributes()` now includes:
```php
'visibility_scope' => $validated['visibility_scope'] ?? SectionDefinition::SCOPE_BOTH,
```
`visibility_scope` was already in `$fillable` (added Phase 1), so no model change needed.

### 5.4 Index Badge

Added a new **"نطاق الظهور"** column in `section_definitions/index.blade.php` between the Library and Sort Order columns. `colspan` updated from 11 → 12.

Badge colors:
| scope | color |
|-------|-------|
| `both` | gray |
| `admin_only` | amber |
| `client_only` | blue |
| `hidden` | red |

### 5.5 Translation Keys (DashboardTranslationsSeeder)

```php
'dashboard.Visibility_Scope'     => 'مكان ظهور السكشن'
'dashboard.Scope_Both'           => 'الأدمن والعميل'
'dashboard.Scope_Admin_Only'     => 'الأدمن فقط'
'dashboard.Scope_Client_Only'    => 'العميل فقط'
'dashboard.Scope_Hidden'         => 'مخفي من الجميع'
'dashboard.Visibility_Scope_Help'=> 'يتحكم هذا الخيار في مكان ظهور السكشن داخل قائمة إضافة قسم. لا يؤثر على الأقسام الموضوعة مسبقاً على الصفحات.'
```

### Phase 5 + UX Files Changed

| File | Change |
|------|--------|
| `resources/views/dashboard/section_definitions/form.blade.php` | Added `visibility_scope` select · relabeled `is_active` + `is_visible_in_library` |
| `app/Http/Requests/Admin/StoreSectionDefinitionRequest.php` | Added `visibility_scope` validation + prepareForValidation |
| `app/Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | Same |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | Added `visibility_scope` to `persistableAttributes()` |
| `resources/views/dashboard/section_definitions/index.blade.php` | Added scope badge column · colspan 11→12 |
| `database/seeders/DashboardTranslationsSeeder.php` | Added 10 keys (6 scope + 4 UX labels) |

### Deferred Phases (Updated)

| Phase | Description | Status |
|-------|-------------|--------|
| 5 | Admin UI — scope select + index badge | ✅ Done |
| UX | Relabel is_active / is_visible_in_library + updated help text | ✅ Done |
| 6 | SectionTemplateLibrary + SectionPackageGenerator awareness | ⏸ Pending |

---

## UX Clarification

### لماذا كانت الحقول مربكة

بعد إضافة `visibility_scope` في Phase 5، أصبح الفورم يحتوي على ثلاثة حقول متقاربة بدون تسلسل منطقي واضح:

| الحقل | النص القديم | المشكلة |
|-------|-------------|---------|
| `is_active` | مفعّل | غامض — لا يوضح "مفعّل لماذا؟" |
| `is_visible_in_library` | ظاهر في المكتبة | "المكتبة" مصطلح تقني غير مألوف للمستخدم |
| `visibility_scope` | مكان ظهور السكشن | يتداخل مع "ظاهر في المكتبة" في الذهن |

المستخدم لم يكن يفهم الفرق بين "ظاهر في المكتبة" و"مكان ظهور السكشن"، وكلاهما يبدو أنه يتحكم في "من يرى السكشن".

### النصوص القديمة ← الجديدة

| الحقل | النص القديم | النص الجديد |
|-------|-------------|-------------|
| `is_active` label | مفعّل | **تفعيل السكشن** |
| `is_active` hint | التعريفات غير النشطة تبقى محفوظة لكن لا تُعرض في الأدوات. | **عند تعطيل هذا الخيار يتوقف استخدام السكشن بالكامل ولا يظهر في أدوات البناء.** |
| `is_visible_in_library` label | ظاهر في المكتبة | **إظهاره في قائمة الإضافة** |
| `is_visible_in_library` hint | فعّله مع Active لظهور التعريف في مكتبة الأقسام. | **عند إيقافه لن يظهر السكشن ضمن قائمة Add Section، لكن النسخ المضافة مسبقاً ستبقى كما هي.** |
| `visibility_scope` help | يتحكم هذا الخيار في مكان ظهور السكشن داخل قائمة إضافة قسم. لا يؤثر على الأقسام الموضوعة مسبقاً على الصفحات. | **يحدد هل يظهر هذا السكشن في بلدر الأدمن، بلدر العميل، أو كليهما.** |

### التسلسل المنطقي الجديد

```
① هل السكشن يعمل؟
   [✓] تفعيل السكشن
       ↳ يتحكم في is_active

② هل يظهر في قائمة Add Section؟
   [✓] إظهاره في قائمة الإضافة
       ↳ يتحكم في is_visible_in_library

③ لمن يظهر؟
   مكان ظهور السكشن: [الأدمن والعميل ▾]
       ↳ يتحكم في visibility_scope
```

### ما لم يتغير

- لا تغيير في `is_active` behavior
- لا تغيير في `is_visible` behavior
- لا تغيير في `visibility_scope` behavior
- لا تغيير في الاستعلامات أو قاعدة البيانات
- لا تغيير في الـ index badge
- التعديل نصوص UI فقط

### مفاتيح الترجمة المضافة/المحدّثة

```php
// جديدة
'dashboard.Enable_Section'           => 'تفعيل السكشن'
'dashboard.Enable_Section_Help'      => 'عند تعطيل هذا الخيار يتوقف استخدام السكشن بالكامل ولا يظهر في أدوات البناء.'
'dashboard.Show_In_Add_Section'      => 'إظهاره في قائمة الإضافة'
'dashboard.Show_In_Add_Section_Help' => 'عند إيقافه لن يظهر السكشن ضمن قائمة Add Section، لكن النسخ المضافة مسبقاً ستبقى كما هي.'

// محدّثة (قيمة جديدة — نفس الـ key)
'dashboard.Visibility_Scope_Help'    => 'يحدد هل يظهر هذا السكشن في بلدر الأدمن، بلدر العميل، أو كليهما.'
```
