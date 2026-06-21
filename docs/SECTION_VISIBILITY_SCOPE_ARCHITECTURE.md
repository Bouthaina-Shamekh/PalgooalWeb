# Section Visibility Scope Architecture

**Date:** 2026-06-21  
**Status:** Design Review — Not Yet Implemented  
**Author:** Architecture session, generated from full system audit  

---

## 1. Current State — How the System Works

### 1.1 Section Library Data Flow

```
SectionDefinition (DB)
    ↓ is_active=true, is_visible=true
SectionController::sectionLibraryTypes()
    ↓ returns array keyed by section_key
Admin Page Builder → create.blade.php       (sectionLibraryTypes)
Client Page Builder → same SectionController (inherited workspaceSectionTypes)
```

### 1.2 Key Classes and Their Roles

| Class / File | Role | Visibility Gate |
|---|---|---|
| `App\Models\Sections\SectionDefinition` | Blueprint record — the library unit | `is_active` + `is_visible` columns |
| `SectionController::sectionLibraryTypes()` | Builds the section-picker catalog | `where is_active=1 AND is_visible=1` |
| `SectionController::workspaceSectionTypes()` | Alias — calls `sectionLibraryTypes()` | Same |
| `SectionController::sectionTypesForSection()` | Section type switcher in edit | Same |
| `SubscriptionSiteShellEditorController` | Client header/footer shell editor | `ShellSectionEditorSupport::availableSectionTypes()` (hardcoded legacy list — completely separate from definitions) |
| `ShellSectionEditorSupport` | Legacy header/footer only | Hardcoded `site_header` / `site_footer_*` — never reads `section_definitions` |
| `SectionTemplateLibrary` | PHP static catalog of blueprints for "Create From Template" | No scope awareness |
| `SectionPackageGenerator` | Orchestrates definition + fields + blade | Passes through `is_visible=true` from template config |

### 1.3 Inheritance Chain

```
Admin Page Builder
  └── SectionController
        └── sectionLibraryTypes()  ← queries section_definitions

Client Shell Builder (header/footer)
  └── SubscriptionSiteShellEditorController extends SectionController
        └── workspaceSectionTypes()
              └── availableSectionTypes()  ← ShellSectionEditorSupport (hardcoded)
```

> **Key finding:** The client page builder (for page body sections) and the admin page builder currently use the **identical** `sectionLibraryTypes()` query. There is no audience distinction.

### 1.4 The Two Binary Flags Today

| Column | Purpose | Impact |
|---|---|---|
| `is_active` | Can this section render on the frontend? | Checked in `SectionDefinitionRuntimeResolver::hasPrimaryTemplate()` — inactive sections still in DB but won't render |
| `is_visible` | Should this section appear in the Add Section picker? | Filtered in `sectionLibraryTypes()`. Hidden from library but still renderable if already placed |

### 1.5 Where Section Types Are Displayed (UI)

1. **Admin Add Section screen** — `resources/views/dashboard/pages/sections/create.blade.php`  
   Receives `$sectionLibraryTypes` from `SectionController`, groups by `category`, filters out `library_hidden`.

2. **Admin workspace quick-add drawer** — `layouts/workspace.blade.php`  
   Receives `sectionTypes` which = `workspaceSectionTypes()` = `sectionLibraryTypes()`.

3. **Client Shell Editor** — completely separate, uses hardcoded `ShellSectionEditorSupport` types.

4. **Section Templates picker** — `section_definitions/from-template.blade.php`  
   Reads from `SectionTemplateLibrary::all()` (PHP static, no DB). Admin-only screen.

---

## 2. Problem Analysis

### 2.1 The Core Problem

`pricing_plans_dynamic` is a **system-data-driven** section. It executes:

```php
$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

This section:
- Reads live pricing data from the platform database
- Has no meaningful editable content fields for the client
- Requires admin configuration of plans first
- Could expose pricing structure details inappropriate for client editing

If a client adds this section, they see it render but cannot meaningfully configure it. If they duplicate or reorder it, they get confusing behavior.

### 2.2 Other Sections That Are Similarly System-Driven

Sections that **should be admin-only** (current or future):
- `pricing_plans_dynamic` — reads `Plan` model
- Any future `templates_gallery` — reads `Template` model
- Any future `testimonials_auto` — reads `Testimonial` model (DB-sourced, not editor-sourced)
- Any future `domain_checker` — requires server integration
- Any `analytics_dashboard` embed

Sections that are **suitable for clients**:
- `hero` — pure content, editor-driven
- `features_grid` — pure content
- `content_showcase` — pure content
- `cta_banner` — pure content
- `faq` — pure content
- `testimonials` — manual/editor-driven (different from auto-fetched DB testimonials)

### 2.3 Why `is_visible = false` Is Insufficient

`is_visible = false` hides a section from **all** builders (admin + client). It's all-or-nothing. We need to hide from **only client** while keeping visible for admin.

---

## 3. Possible Designs

### Option A — Boolean `is_admin_only`

Add a single boolean column:

```sql
ALTER TABLE section_definitions ADD COLUMN is_admin_only TINYINT(1) NOT NULL DEFAULT 0;
```

**Admin builder query:** no change (sees everything with `is_visible=true`)  
**Client builder query:** `->where('is_admin_only', false)`

**Pros:** Minimal change, simple to understand  
**Cons:**
- Can't express `client_only` (a section only for clients, hidden from admin builder)
- Can't express `hidden` semantics beyond `is_visible=false`
- Future expansion requires adding MORE boolean columns (`is_marketplace_only`, `is_tenant_only`)
- `is_admin_only=true AND is_visible=false` creates confusing combinations

**Verdict:** Too narrow. Rejected.

---

### Option B — String Enum `visibility_scope`

Add a single `visibility_scope` column:

```sql
ALTER TABLE section_definitions
    ADD COLUMN visibility_scope VARCHAR(32) NOT NULL DEFAULT 'both';
```

Values: `'both'` | `'admin_only'` | `'client_only'` | `'hidden'`

**Admin builder query:**
```php
->whereIn('visibility_scope', ['both', 'admin_only'])
```

**Client builder query:**
```php
->whereIn('visibility_scope', ['both', 'client_only'])
```

**Pros:**
- Single column, clear semantics
- Default `'both'` = backward compatible (no data migration for existing sections)
- Future expansion: add `'marketplace_only'`, `'internal_only'`, `'tenant_only'` just by allowing new enum values — no schema change needed if column is VARCHAR
- Can replace `is_visible=false` with `'hidden'` scope (or keep both for different purposes)
- Trivial to show in admin UI as a select dropdown

**Cons:**
- Does not support a section being visible in BOTH admin AND marketplace but not client — though this is unlikely to be needed
- No DB-level enum constraint (if VARCHAR) — needs application-level validation

**Verdict:** ✅ Recommended.

---

### Option C — JSON Array `visibility_scopes`

```sql
ALTER TABLE section_definitions
    ADD COLUMN visibility_scopes JSON NULL;
```

Store as: `["admin", "client"]`, `["admin"]`, `["marketplace", "tenant"]`

**Query:**
```php
->whereJsonContains('visibility_scopes', 'admin')
```

**Pros:** Maximum flexibility, multi-scope simultaneously  
**Cons:**
- JSON queries are slower than indexed VARCHAR
- MySQL `whereJsonContains` requires full table scan unless using generated columns + index
- Overkill for 2–4 audience types
- Empty array vs `null` edge case

**Verdict:** Over-engineered. Rejected.

---

### Option D — Relation Table `section_definition_audiences`

```sql
CREATE TABLE section_definition_audiences (
    section_definition_id BIGINT UNSIGNED NOT NULL,
    audience VARCHAR(32) NOT NULL,
    PRIMARY KEY (section_definition_id, audience)
);
```

**Pros:** Maximum relational flexibility, easy to extend  
**Cons:**
- Extra table, extra JOIN for every library query
- ORM complexity
- Admin UI needs a multi-select
- Complete overkill for a flag that has 4 states

**Verdict:** Severely over-engineered. Rejected.

---

## 4. Recommended Design — String Enum `visibility_scope`

### 4.1 Scope Values and Their Meaning

| Value | Visible in Admin Builder | Visible in Client Builder | Notes |
|---|---|---|---|
| `both` *(default)* | ✅ | ✅ | Standard content sections (hero, faq, cta…) |
| `admin_only` | ✅ | ❌ | System-data sections (pricing_plans_dynamic…) |
| `client_only` | ❌ | ✅ | Reserved for future client-specific blocks |
| `hidden` | ❌ | ❌ | Temporarily withdrawn; still renderable if already placed |

### 4.2 Relationship with Existing `is_visible`

The two columns serve different purposes and coexist:

| `is_visible` | `visibility_scope` | Outcome |
|---|---|---|
| `true` | `'both'` | Shown in all builders |
| `true` | `'admin_only'` | Admin only |
| `true` | `'client_only'` | Client only |
| `true` | `'hidden'` | Hidden from all builders (developer-withdrawn) |
| `false` | any | Hidden from all builders AND from admin library index (legacy path) |

**Going forward:** prefer `visibility_scope = 'hidden'` over `is_visible = false` for new sections. Existing `is_visible` behavior is preserved unchanged.

### 4.3 Model Constants

```php
// In App\Models\Sections\SectionDefinition
public const SCOPE_BOTH        = 'both';
public const SCOPE_ADMIN_ONLY  = 'admin_only';
public const SCOPE_CLIENT_ONLY = 'client_only';
public const SCOPE_HIDDEN      = 'hidden';

public static function adminVisibleScopes(): array
{
    return [self::SCOPE_BOTH, self::SCOPE_ADMIN_ONLY];
}

public static function clientVisibleScopes(): array
{
    return [self::SCOPE_BOTH, self::SCOPE_CLIENT_ONLY];
}

public function isVisibleForAdmin(): bool
{
    return $this->is_visible && in_array($this->visibility_scope, self::adminVisibleScopes(), true);
}

public function isVisibleForClient(): bool
{
    return $this->is_visible && in_array($this->visibility_scope, self::clientVisibleScopes(), true);
}
```

---

## 5. Database Impact

### 5.1 Table: `section_definitions`

**Add one column:**

```sql
ALTER TABLE section_definitions
    ADD COLUMN `visibility_scope` VARCHAR(32) NOT NULL DEFAULT 'both'
    AFTER `is_visible`;

ALTER TABLE section_definitions
    ADD INDEX `idx_section_def_scope` (`visibility_scope`);
```

**Migration file:** `add_visibility_scope_to_section_definitions`

No other tables are touched. `section_templates`, `section_definition_template`, `sections` — all unchanged.

### 5.2 Backfill

No backfill needed. The column defaults to `'both'`, which is the correct value for all existing sections (hero, features_grid, cta_banner, faq, testimonials, content_showcase).

The only targeted update needed post-migration:

```sql
UPDATE section_definitions
SET visibility_scope = 'admin_only'
WHERE section_key = 'pricing_plans_dynamic';
```

This can be done in the migration's `up()` method or in a one-time Artisan command.

### 5.3 Is the Column on the Right Table?

**Yes — `section_definitions` is the correct location.** Reasons:

- `section_definitions` is the **library catalog unit** — it's what appears in the Add Section picker
- `section_templates` is just a Blade template key registry — it doesn't represent a "library card"
- `sections` is a page-level instance — scope belongs at the definition level, not per-instance
- The query that needs filtering is `SectionController::sectionLibraryTypes()` which reads `section_definitions`

---

## 6. Migration Strategy

### Phase 1 — Schema Only (Zero Impact)

1. Create migration: `add_visibility_scope_to_section_definitions`
2. Column defaults to `'both'` — ALL existing sections unchanged
3. Add `visibility_scope` to `$fillable` in `SectionDefinition` model
4. Add constants + helpers to model
5. **No changes to any query yet** — system behavior identical to before

### Phase 2 — Apply to `pricing_plans_dynamic`

1. In the migration `up()`: `UPDATE section_definitions SET visibility_scope = 'admin_only' WHERE section_key = 'pricing_plans_dynamic'`
2. Update `SectionTemplateLibrary` entry (if/when added): `'visibility_scope' => 'admin_only'`
3. **Still no query changes** — admin sees it (correct), client doesn't yet see it anyway (because `ShellSectionEditorSupport` is separate)

### Phase 3 — Enforce in Admin Builder

1. Update `SectionController::sectionLibraryTypes()`:
   ```php
   $definitions = SectionDefinition::query()
       ->with('previewMedia')
       ->where('is_active', true)
       ->where('is_visible', true)
       ->whereIn('visibility_scope', SectionDefinition::adminVisibleScopes())  // ← ADD
       ->orderBy('sort_order')
       ->orderBy('id')
       ->get();
   ```
2. Admin builder now filters by scope. All existing `'both'` sections still appear. `'admin_only'` sections also appear for admin only.

### Phase 4 — Enforce in Client Builder

> **Note:** The client page builder (body sections) currently routes through the SAME `SectionController` as admin. Before Phase 4, we need to identify whether there is already a separate client page builder controller. If not, the separation must happen first.

1. Identify (or create) the client-facing `SectionController` subclass
2. Override `sectionLibraryTypes()` in the client subclass:
   ```php
   protected function sectionLibraryTypes(): array
   {
       $definitions = SectionDefinition::query()
           ->with('previewMedia')
           ->where('is_active', true)
           ->where('is_visible', true)
           ->whereIn('visibility_scope', SectionDefinition::clientVisibleScopes())  // 'both' + 'client_only'
           ->orderBy('sort_order')
           ->orderBy('id')
           ->get();

       // ... same mapping logic as parent
   }
   ```

### Phase 5 — Admin UI for Scope Field

1. Add `visibility_scope` select field to `section_definitions/form.blade.php`
2. Add `visibility_scope` to `StoreSectionDefinitionRequest` + `UpdateSectionDefinitionRequest` validation
3. Add translation keys:
   - `dashboard.Visibility_Scope`
   - `dashboard.Scope_Both`
   - `dashboard.Scope_Admin_Only`
   - `dashboard.Scope_Client_Only`
   - `dashboard.Scope_Hidden`
4. Show scope badge in `section_definitions/index.blade.php` (color-coded)

### Phase 6 — SectionTemplateLibrary Awareness

Add `'visibility_scope'` to each template definition in `SectionTemplateLibrary::ALL_TEMPLATES`:

```php
'hero' => [
    'label'            => 'Hero Section',
    'visibility_scope' => 'both',   // ← ADD to all templates
    'definition'       => [...],
    ...
],

// When pricing_plans_dynamic is added to the library:
'pricing-plans-dynamic' => [
    'label'            => 'Pricing Plans (Dynamic)',
    'visibility_scope' => 'admin_only',   // ← system-data section
    ...
],
```

Update `SectionPackageGenerator` to pass it through:

```php
$definition = SectionDefinition::create([
    'section_key'      => $defConfig['section_key'],
    ...
    'visibility_scope' => $template['visibility_scope'] ?? SectionDefinition::SCOPE_BOTH,  // ← ADD
]);
```

---

## 7. UI Impact

### 7.1 Admin Add Section Screen

**Before:** All `is_visible=true` sections appear  
**After (Phase 3):** All `visibility_scope IN ('both', 'admin_only')` sections appear

Visual change: Add a badge on `admin_only` cards:

```html
<span class="rounded-full bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 font-semibold uppercase">
    Admin Only
</span>
```

### 7.2 Client Add Section Screen

**Before:** Same as admin (identical query)  
**After (Phase 4):** Only `visibility_scope IN ('both', 'client_only')` sections  
`pricing_plans_dynamic` disappears from the client picker entirely.

### 7.3 Section Templates Picker (`from-template.blade.php`)

Admin-only screen — no client access. Show scope chip on each template card:

```html
@if (($template['visibility_scope'] ?? 'both') === 'admin_only')
    <span class="badge bg-amber-100 text-amber-700">Admin Only</span>
@endif
```

### 7.4 Section Definition CRUD (`form.blade.php`)

Add a `visibility_scope` select field in القسم ١ (Basic Information):

```blade
<div class="col-span-12 md:col-span-6">
    <label class="form-label">{{ t('dashboard.Visibility_Scope', 'Visibility Scope') }}</label>
    <select name="visibility_scope" class="form-select">
        <option value="both"        {{ old('visibility_scope', $def->visibility_scope ?? 'both') === 'both'        ? 'selected' : '' }}>{{ t('dashboard.Scope_Both', 'Both (Admin + Client)') }}</option>
        <option value="admin_only"  {{ old('visibility_scope', $def->visibility_scope ?? 'both') === 'admin_only'  ? 'selected' : '' }}>{{ t('dashboard.Scope_Admin_Only', 'Admin Builder Only') }}</option>
        <option value="client_only" {{ old('visibility_scope', $def->visibility_scope ?? 'both') === 'client_only' ? 'selected' : '' }}>{{ t('dashboard.Scope_Client_Only', 'Client Builder Only') }}</option>
        <option value="hidden"      {{ old('visibility_scope', $def->visibility_scope ?? 'both') === 'hidden'      ? 'selected' : '' }}>{{ t('dashboard.Scope_Hidden', 'Hidden (withdrawn)') }}</option>
    </select>
</div>
```

### 7.5 Section Definition Index (`index.blade.php`)

Add scope badge column (color-coded):

| Scope | Badge Color |
|---|---|
| `both` | Gray — `bg-slate-100 text-slate-600` |
| `admin_only` | Amber — `bg-amber-100 text-amber-700` |
| `client_only` | Blue — `bg-blue-100 text-blue-700` |
| `hidden` | Red — `bg-red-100 text-red-600` |

---

## 8. Future Expansion

### 8.1 Adding `marketplace_only`

When a section marketplace is introduced:

1. Allow `'marketplace_only'` as a valid scope value (application-level validation update only — no schema change if column is VARCHAR)
2. Add `public static function marketplaceVisibleScopes(): array { return [self::SCOPE_BOTH, 'marketplace_only']; }`
3. Marketplace builder controller overrides `sectionLibraryTypes()` with `->whereIn('visibility_scope', SectionDefinition::marketplaceVisibleScopes())`

### 8.2 Adding `tenant_only`

For tenant-exclusive sections (white-label feature where a specific tenant has private sections):

1. `tenant_only` scope value
2. A `tenant_id` column on `section_definitions` (or a separate `section_definition_tenants` pivot)
3. Filter: `->where('visibility_scope', 'tenant_only')->where('tenant_id', $currentTenantId)`

This is the ONLY future scenario that requires an additional column. It does not invalidate the `visibility_scope` approach.

### 8.3 Adding `internal_only`

For sections only used in internal Palgoals.com pages (not client sites):

1. `internal_only` as a scope value
2. Internal page builder filters to include `'both'` and `'internal_only'`

### 8.4 Scope Combination Logic

If a future section needs to be visible in BOTH admin AND marketplace but NOT client:

The recommended approach is to add a `visibility_scopes` JSON column **at that time** and migrate from the string column. This is a clear, bounded future migration with full backward-compat — the string column becomes the default value supplier for the JSON column.

There is no need to build this today.

---

## 9. Implementation Roadmap (Ordered)

```
Phase 1  — Migration + Model constants + fillable    (DB + PHP only, zero behavior change)
Phase 2  — Update pricing_plans_dynamic in migration (single SQL UPDATE)
Phase 3  — Admin builder query enforcement           (SectionController.php change)
Phase 4  — Client builder query enforcement          (requires client controller identified/separated)
Phase 5  — Admin UI for Scope field                  (form.blade.php + index badge + translations)
Phase 6  — SectionTemplateLibrary awareness          (SectionTemplateLibrary + SectionPackageGenerator)
```

Phases 1–3 can be done in a single session with no risk. Phase 4 is gated on client-builder architecture clarity.

---

## 10. Open Questions

| # | Question | Impact |
|---|---|---|
| Q1 | Does a client-facing page builder controller exist today? | Determines Phase 4 scope — if yes, override `sectionLibraryTypes()`. If no, create a subclass first. |
| Q2 | Should `hidden` scope replace `is_visible=false` entirely, or coexist? | Recommendation: coexist — `is_visible=false` hides from the admin definitions list; `hidden` scope hides from builder pickers only. |
| Q3 | Should section placement validation check scope? | i.e., if a client somehow submits `section_definition_id` for an `admin_only` section, should it be rejected? Recommendation: YES — add server-side scope check in `SectionController::store()`. |
| Q4 | Should `SectionTemplateLibrary` blueprints hardcode scope, or should scope be set only at the DB level? | Recommendation: hardcode in library for generator consistency; DB is the source of truth at runtime. |

---

## 11. Summary

| Decision | Choice | Reason |
|---|---|---|
| Design | String Enum `visibility_scope` | Simple, backward-compatible, forward-ready |
| Default value | `'both'` | All existing sections are unaffected |
| Location | `section_definitions` table | It's the library catalog unit |
| Backfill | None required | Default handles existing rows |
| `pricing_plans_dynamic` | `'admin_only'` in migration `up()` | System-data driven, no client config needed |
| Breaking changes | Zero | Phases 1–3 are purely additive |
| Client enforcement | Phase 4 | Gated on client builder controller separation |
