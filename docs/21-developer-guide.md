# Developer Guide

> **Last Updated:** 2026-06-15  
> **Status:** Verified against source code  
> **Purpose:** Practical guide for developers joining the Palgoals project. Covers everything from local setup to code review. Not a duplicate of Architecture docs — links to them instead.

---

## Purpose

This is the **first document** a new developer should read after cloning the repo. It tells you how to run the project, where things are, how to add features correctly, and how to avoid the common mistakes that break this codebase.

---

## First Day Checklist

Work through this in order. Each step has a verification so you know it worked.

- [ ] **Clone repository** — `git clone <repo-url>`
- [ ] **Install PHP dependencies** — `composer install`
- [ ] **Install JS dependencies** — `npm install`
- [ ] **Configure environment** — `cp .env.example .env && php artisan key:generate`
  - Set `DB_DATABASE=palgoalsnewtest1`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty), `DB_HOST=127.0.0.1`, `DB_PORT=3306`
  - Set `APP_URL` to your local URL (e.g. `http://127.0.0.1:8000`)
  - Set `TENANT_DOMAIN` to your local domain for tenant resolution
- [ ] **Run migrations** — `php artisan migrate`
- [ ] **Run seeders** — `php artisan db:seed` *(creates admin user + general settings + languages)*
- [ ] **Run translation seeders** — `php artisan db:seed --class=DashboardTranslationsSeeder && php artisan db:seed --class=SiteTranslationsSeeder`
- [ ] **Build assets** — `npm run dev:vite` (development) or `npm run build:vite` (production)
- [ ] **Start dev server** — `php artisan serve`
- [ ] **Verify admin login** — navigate to `http://127.0.0.1:8000/login`
  - Default development credentials are created by `DatabaseSeeder`. Verify the current credentials in `database/seeders/DatabaseSeeder.php`.
  - ✓ You should land on `/admin/home`
- [ ] **Verify dashboard** — check that the sidebar loads and Quick Actions are visible
- [ ] **Clear caches** after any config change: `php artisan optimize:clear`

---

## Required Knowledge

You need to be comfortable with these before contributing:

**Essential:**
- **Laravel 12** — Routes, Controllers, Middleware, Eloquent, FormRequests, Policies, Jobs
- **Blade** — Templates, components (`<x-dashboard-layout>`), `@push`/`@stack`, slots
- **Tailwind CSS** — Utility classes. The project does not use Sass or component-level CSS files
- **MySQL** — Raw query debugging when Eloquent hides the query

**For admin UI work:**
- **Alpine.js** — Lightweight reactivity used for dropdown toggles, tab switching, and dynamic forms
- **Livewire** — Used selectively (section editor, media picker, appearance panel). Most admin pages are plain Blade + GET/POST
- **GSAP** — Animation library used on the marketing site. Not needed for admin work

**For content/tenant work:**
- **Section Definitions** — The core content authoring system. Read `07-section-definitions.md` before touching any section code
- **Rendering flow** — Read `09-rendering-flow.md` before touching any render/resolver code

**What you do NOT need:**
- Vue, React, or any SPA framework — there is none
- REST API knowledge for most features — most forms are standard HTML POST
- Livewire expertise for most admin pages — only specific components use it

---

## Local Development Setup

### Full Setup Sequence

```bash
# 1. PHP dependencies
composer install

# 2. JS dependencies
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database
php artisan migrate

# 5. Seed essential data (admin user + settings + languages)
php artisan db:seed

# 6. Seed translations (always run both)
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan db:seed --class=SiteTranslationsSeeder
php artisan cache:clear

# 7. Build assets (choose one)
npm run dev           # Vite dev server with HMR
npm run build:vite    # One-time production build

# 8. Serve
php artisan serve
```

### Available NPM Scripts

```bash
npm run dev           # Vite dev mode (HMR, recommended for development)
npm run build:vite    # Vite production build
npm run dev:mix       # Legacy Laravel Mix (kept for compatibility, use Vite instead)
npm run build:mix     # Legacy Mix production build
npm run watch:mix     # Legacy Mix watch mode
```

The project uses **Vite** as the primary bundler (`vite.config.mjs`). Tailwind CSS is processed via `@tailwindcss/vite`.

### After Pulling Changes

```bash
composer install                            # If composer.json changed
npm install                                 # If package.json changed
php artisan migrate                         # If new migrations exist
php artisan db:seed --class=DashboardTranslationsSeeder   # If seeder was updated
php artisan db:seed --class=SiteTranslationsSeeder        # If site seeder was updated
php artisan optimize:clear                  # Always safe to run
```

### Useful Artisan Commands

```bash
php artisan tinker                          # REPL for quick Model queries
php artisan route:list --name=dashboard     # See all /admin routes
php artisan route:list --name=client        # See all /client routes
php artisan view:clear                      # Clear Blade cache
php artisan optimize:clear                  # Clear all caches
php artisan queue:work                      # Process background jobs (provisioning, sync)
php artisan queue:listen --tries=3          # Listen with retry limit
```

### Environment Variables Reference

| Variable | Example | Purpose |
|----------|---------|---------|
| `DB_DATABASE` | `palgoalsnewtest1` | Local database name |
| `APP_URL` | `http://127.0.0.1:8000` | Used in asset and route generation |
| `TENANT_DOMAIN` | `palgoals.test` | Subdomain root for tenant resolution |
| `MAIL_*` | (your mail config) | Email notifications |
| `QUEUE_CONNECTION` | `sync` (local) / `database` (production) | Job queue driver |

---

## Project Structure

Only the directories you need to know:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          ← Admin dashboard controllers
│   │   │   └── Management/ ← Hosting/billing: Servers, Plans, Subscriptions, Orders, Domains
│   │   ├── Client/         ← Client portal controllers
│   │   ├── Front/          ← Marketing site controllers
│   │   └── Tenancy/        ← Tenant site controllers (served via ServeTenantSite middleware)
│   ├── Middleware/          ← Auth guards, tenant resolution, impersonation checks
│   └── Requests/            ← FormRequests (validation + authorization gate)
│       └── Admin/           ← Admin-specific requests
├── Livewire/
│   ├── Admin/               ← Livewire components used in admin UI (sections, media picker, appearance)
│   └── Client/              ← Client portal Livewire components
├── Models/                  ← Eloquent models. See 03-database-architecture.md for schema
│   ├── Concerns/            ← Reusable traits (HasTranslations, etc.)
│   ├── Sections/            ← Section + SectionDefinition + SectionDefinitionField models
│   └── Tenancy/             ← Subscription-related models
├── Policies/                ← Authorization policies. All extend ModelPolicy
├── Services/                ← Business logic classes (not framework-bound)
│   ├── Billing/             ← Invoice, payment, and coupon logic
│   ├── Domains/             ← Domain purchase, verification, DNS management
│   ├── Templates/           ← Template management and duplication
│   └── Tenancy/             ← Tenant provisioning and sync
├── Support/
│   └── Sections/            ← Section rendering engine: Registry, Resolver, Renderer, FileWriter
└── helpers.php              ← t(), current_dir(), available_locales(), tenant_domain()

resources/views/
├── dashboard/               ← Admin panel Blade views
│   ├── layouts/             ← <x-dashboard-layout> component
│   │   └── partials/nav.blade.php  ← Sidebar navigation
│   ├── management/          ← Hosting management pages (servers, plans, subscriptions)
│   └── section_definitions/ ← Section definition CRUD + Monaco Blade editor
├── client/                  ← Client portal Blade views
├── front/                   ← Marketing site Blade views
│   └── sections/            ← Rendered section Blade files (written via Monaco editor)
│       ├── hero/            ← e.g. hero_featured.blade.php
│       ├── faq/
│       └── ...
├── components/              ← Shared Blade components
│   └── dashboard/           ← e.g. <x-dashboard.avatar>, <x-dashboard.media-picker>
├── livewire/                ← Livewire component views
└── tenant/                  ← Tenant site runtime views

database/
├── migrations/              ← All schema migrations
└── seeders/                 ← Essential seeders (run after migrate)

routes/
├── web.php          ← Marketing site + tenant middleware
├── dashboard.php    ← All /admin routes (prefix: admin, name: dashboard.*)
├── client.php       ← All /client routes (prefix: client, name: client.*)
└── lang.php         ← Language switching routes

config/
├── auth.php         ← Two guards: web (admin) + client (subscribers)
├── tenancy.php      ← Tenant domain config
├── sections.php     ← Section registry config
└── front_layouts.php ← Marketing layout config

docs/                ← All project documentation (this folder)
└── adr/             ← Architecture Decision Records
```

---

## Documentation Reading Order

Read in this exact sequence:

| # | Document | What it covers |
|---|----------|----------------|
| 1 | `00-project-overview.md` | What Palgoals does and who uses it |
| 2 | `01-system-architecture.md` | Two-guard design, layers, key services |
| 3 | `03-database-architecture.md` | Schema, ownership rules, billing tables |
| 4 | `07-section-definitions.md` | The section authoring system |
| 5 | `09-rendering-flow.md` | How sections become HTML |
| 6 | `22-coding-standards.md` | Coding conventions, class names, PR rules |
| 7 | `24-security-notes.md` | Authorization, middleware, known risks |

Read at least 1–3 before writing any code. Read 4–5 before touching sections. Read 6–7 before submitting a PR.

---

## Common Workflows

### Adding a Feature

1. Create a migration if the feature needs new columns: `php artisan make:migration add_feature_to_table`
2. Create the Model (if new table). Add `$fillable` and casts.
3. Create a FormRequest: `php artisan make:request StoreFeatureRequest` — implement `authorize()` and `rules()`
4. Create the Policy if needed (extend `ModelPolicy`). Register in `AuthServiceProvider::$policies`
5. Create the Controller. Call `$this->authorize()` before every action
6. Add routes to `routes/dashboard.php` inside the existing middleware group
7. Create Blade views using the patterns in Section 4 of `CLAUDE.md`
8. Add translation keys to `DashboardTranslationsSeeder` and re-seed
9. Add sidebar entry to `resources/views/dashboard/layouts/partials/nav.blade.php` — wrap in `@can`

### Fixing a Bug

1. Reproduce the bug — identify if it's a render error (Blade), logic error (PHP), or JS error
2. Check `storage/logs/laravel.log` for the actual exception
3. Read the stack trace. The first non-vendor file is where to look
4. Fix the code
5. Run `php artisan view:clear` and `php artisan optimize:clear`
6. Verify the fix in the browser

### Refactoring

- Never rename translation keys without updating the Seeder AND every `t('old.Key')` reference
- Never change flash key names (use `session('ok')` for success, `session('error')` for errors)
- Never remove `$this->authorize()` calls
- Always run `php artisan optimize:clear` after changing config or routes

### Adding a Route

All admin routes go in `routes/dashboard.php` inside the existing Route group:

```php
// ✅ Correct — inside the group with auth middleware already applied
Route::group([
    'prefix'     => 'admin',
    'as'         => 'dashboard.',
    'middleware' => ['auth', 'can:access-dashboard'],
], function () {
    Route::get('/my-feature', [MyFeatureController::class, 'index'])->name('my_feature.index');
    Route::post('/my-feature', [MyFeatureController::class, 'store'])->name('my_feature.store');
});
```

Never add raw routes outside this group for admin functionality.

---

## Adding a New Section Definition

Section Definitions are the platform's content authoring units. Read `07-section-definitions.md` and `09-rendering-flow.md` before starting.

**Short version — 7 steps:**

1. Go to `/admin/section-definitions/create`
2. Set `Section Key` (lowercase, underscores only: `hero_campaign`), `Category` (`hero`), and `Label`
3. Save and click "Manage Fields" — add fields (text, media, richtext, etc.)
4. Open the Section Definition and click "Edit Blade" — write the Blade template in the Monaco editor
5. Click "Write File" — this saves to `resources/views/front/sections/{category}/{section_key}.blade.php`
6. The section is now available when editing a page in the admin CMS
7. Test by adding the section to a page and previewing

**In the Blade template**, the runtime variable is `$data` (a flat array of all resolved field values, shared and translatable merged). `$content` is an alias for `$data` available in definition-driven sections. Do not use `$fields`, `$sharedData`, or `$translatableData` — these are not defined at runtime.

```blade
{{-- Text field --}}
@php $title = trim((string) ($data['title'] ?? '')); @endphp
@if ($title)
    <h1>{{ $title }}</h1>
@endif

{{-- Rich text field (unescaped — admin-controlled content only) --}}
@php $description = trim((string) ($data['description'] ?? '')); @endphp
@if ($description)
    {!! $description !!}
@endif

{{-- Media field --}}
@php $image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null); @endphp
@if ($image)
    <img src="{{ $image }}" alt="{{ $data['image_alt'] ?? '' }}">
@endif

{{-- Boolean field --}}
@if (!empty($data['show_cta']))
    <a href="{{ $data['cta_url'] ?? '#' }}">{{ $data['cta_text'] ?? '' }}</a>
@endif

{{-- Repeater field --}}
@foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item)
    <div>{{ $item['title'] ?? '' }}</div>
@endforeach
```

> **Contract:** `$data` is built by `SectionDefinitionFrontendViewDataFactory`. See `docs/07-section-definitions.md § Blade View Contract` for the full variable reference.

**Convention-based Blade resolution:** A section with `category: hero` and `template_key: hero_campaign` resolves to `resources/views/front/sections/hero/hero_campaign.blade.php`. The Monaco editor writes directly to this path.

> See `07-section-definitions.md` for full field type reference and `09-rendering-flow.md` for the render pipeline.

---

## Adding a New Translation

**Rule: always use `t()`. Never use `__()`.**

### Step 1 — Use `t()` in the code

```blade
{{-- In Blade --}}
{{ t('dashboard.My_New_Key', 'My Fallback Text') }}
{{ t('site.New_Site_Key', 'Default English Text') }}
```

```php
// In PHP (Controller, Request, Job)
return redirect()->back()->with('ok', t('dashboard.My_New_Key', 'My Fallback Text'));

// With variables — use strtr() from OUTSIDE t()
return back()->with('ok', strtr(t('dashboard.Items_Count', ':count items updated.'), [':count' => $n]));
```

### Step 2 — Add the key to the correct Seeder

**`DashboardTranslationsSeeder.php`** — for `dashboard.*` keys (admin panel):

```php
// In the $translations array:
'dashboard.My_New_Key'    => 'النص العربي هنا',
'dashboard.Another_Key'   => 'نص آخر',
```

**`SiteTranslationsSeeder.php`** — for `site.*`, `client.*`, and `common.*` keys:

```php
'site.My_Site_Key'        => 'نص الموقع',
'common.My_Common_Key'    => 'مشترك',
```

### Step 3 — Re-seed

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

### Key naming rules

| Rule | Example |
|------|---------|
| Format: `{section}.{Key_Name}` | `dashboard.Add_New_Client` |
| Section prefix lowercase | `dashboard`, `site`, `client`, `common` |
| Key name: `Snake_Case` (first letter of each word capitalized) | `Plan_Category`, `Server_Package` |
| **Never** use `__()` | `__('text')` ← PROHIBITED |
| **Never** hardcode text in Blade | `<button>حذف</button>` ← PROHIBITED |

---

## Adding a New Template

Templates are pre-configured website designs that clients can activate.

1. Create a Template record via `/admin/templates/create` — set `template_key` (slug), category, plan, and prices
2. The `template_key` is used for Blade view resolution: `resources/views/template/...`
3. Register any custom sections by adding SectionDefinition records linked to this template
4. Add default content via `TemplateSeeder` if the template needs pre-seeded sections
5. Template Blade views go in `resources/views/template/sections/` by convention

The `SectionTemplateRegistry` manages template key → view mapping. Valid template keys match `[a-z0-9_-]+` (validated in `StoreSectionDefinitionRequest`).

---

## Adding a New Admin Page

Here is the full checklist for a new admin CRUD page:

### 1. Route

```php
// routes/dashboard.php — inside the Route::group([...])
Route::resource('my-features', MyFeatureController::class)->names([
    'index'   => 'my_features.index',
    'create'  => 'my_features.create',
    'store'   => 'my_features.store',
    'edit'    => 'my_features.edit',
    'update'  => 'my_features.update',
    'destroy' => 'my_features.destroy',
]);
```

### 2. Controller

```php
class MyFeatureController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MyFeature::class);

        $search  = $request->get('search');
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50]) ? (int) $request->get('per_page') : 20;

        $items = MyFeature::latest()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.my_features.index', compact('items', 'search', 'perPage'));
    }

    public function store(StoreMyFeatureRequest $request)
    {
        // No need to call authorize() — FormRequest::authorize() already did it
        MyFeature::create($request->validated());
        return redirect()->route('dashboard.my_features.index')
            ->with('ok', t('dashboard.My_Feature_Created', 'Created successfully.'));
    }
}
```

### 3. Policy

```php
// app/Policies/MyFeaturePolicy.php
class MyFeaturePolicy extends ModelPolicy
{
    // No methods needed — ModelPolicy::__call() handles everything
    // Ability slugs auto-generated: myfeatures.view, myfeatures.create, myfeatures.edit, myfeatures.delete
}
```

Register in `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    MyFeature::class => MyFeaturePolicy::class,
];
```

### 4. Translation Keys

```php
// DashboardTranslationsSeeder.php
'dashboard.My_Features_List'   => 'قائمة الميزات',
'dashboard.Add_My_Feature'     => 'إضافة ميزة',
'dashboard.My_Feature_Created' => 'تم إنشاء الميزة بنجاح.',
'dashboard.My_Feature_Updated' => 'تم تحديث الميزة بنجاح.',
'dashboard.My_Feature_Deleted' => 'تم حذف الميزة بنجاح.',
```

### 5. Sidebar Entry

```blade
{{-- resources/views/dashboard/layouts/partials/nav.blade.php --}}
@can('viewAny', App\Models\MyFeature::class)
    <li class="pc-item">
        <a href="{{ route('dashboard.my_features.index') }}" class="pc-link">
            <span class="pc-micon"><i class="ti ti-star"></i></span>
            <span class="pc-mtext">{{ t('dashboard.My_Features', 'My Features') }}</span>
        </a>
    </li>
@endcan
```

### 6. Blade View Pattern

Follow the established index view pattern from `CLAUDE.md` Section 4:
- Flash: `@if(session('ok'))` / `@if(session('error'))`
- Toolbar: search form + per_page select + Add button
- Table: `card table-card` + `table table-hover`
- Empty state: `@forelse` with dual-state (empty vs. no search results)
- Pagination: `$items->links()` at the bottom

---

## Working With Media

The media library is managed through `<x-dashboard.media-picker>` (a Livewire component).

**Do not use `<input type="file">` for admin forms.** Use the media picker:

```blade
{{-- In any admin form --}}
<input type="hidden" name="image" value="{{ old('image', $model->image ?? '') }}" id="imageInput">
<button type="button" class="btn btn-light btn-open-media-picker" data-target="imageInput" data-store-value="path">
    {{ t('dashboard.Choose_From_Media', 'Choose from Media') }}
</button>
@if($model->image ?? false)
    <img src="{{ asset('storage/' . $model->image) }}" class="mt-2 rounded-lg" style="max-height:80px;">
@endif
```

`data-store-value="path"` stores the file path. `data-store-value="id"` stores the media ID (preferred for new features).

**Upload validation** (in `MediaController`): `jpeg,jpg,png,gif,webp,svg` only. Max 10MB. Files stored with hashed names under `storage/app/public/media/`.

> See `24-security-notes.md` — Media Security section for full security constraints.

---

## Working With Billing

The billing system has three layers:

- **Plans** — available hosting packages with prices in `monthly_price` (decimal) and `annual_price` (decimal)
- **Subscriptions** — a client's active/pending/suspended/cancelled plan instance. Has `client_id`, `plan_id`, `domain_name`, `status`, `start_date`, `next_due_date`
- **Invoices** — billing records. Use `price_cents` (integer) for amounts. **Never use floats for money.** See ADR-003 in `03-database-architecture.md`

**Key models:** `Plan`, `Subscription`, `Invoice`, `InvoiceItem`, `Order`, `Coupon`

**Key services:** `app/Services/Billing/`

**Background jobs:** `ProvisionSubscription`, `SyncSubscriptionToProvider`, `TerminateSubscriptionOnProvider` — run via `php artisan queue:work`

---

## Working With Tenant Sites

A tenant site is served when a request comes in on a non-platform domain. The `ServeTenantSite` middleware intercepts the request:

1. Host header matches a `Subscription` by `domain_name` or `subdomain`
2. Subscription must be `status = 'active'` with a `plan.plan_type = TYPE_MULTI_TENANT`
3. The tenant's pages (context = `'tenant'`, `tenant_id = $subscription->id`) are rendered
4. Tenant sections are scoped to the tenant — no cross-tenant data leaks

**Important:** `ServeTenantSite` only accepts `GET` and `HEAD` methods. POST requests from tenant sites must go through the client portal, not the tenant renderer.

**Adding content to tenant sites:** Use the section editor under the tenant's subscription in the client portal. Tenant sections are stored in `sections` table with `page.tenant_id` set.

> See `ADR-001` (Page + Section as Source of Truth) and `01-system-architecture.md` for the full tenant architecture.

---

## Working With Security Rules

The short version for daily development. Full details in `24-security-notes.md`.

**Always:**
- Call `$this->authorize()` at the top of every admin controller method
- Verify `(int) $resource->client_id === (int) Auth::guard('client')->id()` in every client controller that touches a resource
- Use `abort_unless(..., 403)` (or 404 if existence should not leak)
- Use `t()` not `__()`
- Use `session('ok')` for success and `session('error')` for error flash messages

**Never:**
- Remove an `authorize()` call without a documented reason
- Store API tokens or passwords in log messages
- Return early from `authorize()` — let it throw

---

## Working With Policies

The policy system is built on `ModelPolicy` which uses PHP `__call` to generate ability slugs automatically. You rarely need to write methods in a Policy class.

```php
// Ability slugs generated by ModelPolicy:
//   viewAny  → {models}.view     (e.g. pages.view)
//   view     → {models}.view
//   create   → {models}.create
//   update   → {models}.edit     ← note: "update" maps to "edit"
//   delete   → {models}.delete

// In a controller:
$this->authorize('viewAny', Page::class);    // checks pages.view
$this->authorize('update', $page);           // checks pages.edit
$this->authorize('delete', $page);           // checks pages.delete

// In Blade:
@can('create', App\Models\Page::class)
    <a href="{{ route('dashboard.pages.create') }}">Add Page</a>
@endcan
```

Abilities are stored in the `role_user` table as `role_name` strings matching the generated slugs. Super admins (`users.super_admin = 1`) bypass all policy checks.

**The `access-dashboard` gate** is used as a middleware check (`'can:access-dashboard'`) on the entire `/admin` route group. Any admin user who passes this gate can reach the dashboard. Individual resource access is then controlled per-method by `authorize()`.

---

## Working With ADRs

**Architecture Decision Records** live in `docs/adr/`. An ADR documents a significant architectural decision — not implementation details.

**Create an ADR when:**
- You're introducing a new data storage pattern (e.g. "store X as encrypted JSON instead of columns")
- You're changing how a core system works (e.g. "replace WHM API with Cloudflare API")
- You're making a security-relevant choice (e.g. "add at-rest encryption for credentials")
- Future developers would question why something was done this way

**Do NOT create an ADR for:**
- Adding a new feature that follows existing patterns
- Bug fixes
- Refactors that don't change architectural behavior

**ADR format:**

```markdown
# ADR {NUMBER}: {TITLE}

## Status
Proposed | Accepted | Deprecated | Superseded by ADR-XXX

## Context
What problem are we solving? What constraints exist?

## Decision
What did we decide to do?

## Consequences
What are the trade-offs? What gets easier? What gets harder?
```

**Existing ADRs:**
- `ADR-001` — Page + Section as Source of Truth (`docs/adr/001-page-section-as-source-of-truth.md`)
- `ADR-SEC-01` — Encryption At Rest for Sensitive Columns (proposed in `24-security-notes.md`)
- `ADR-SEC-02` — Media ID Instead of Raw Path (proposed in `24-security-notes.md`)
- `ADR-SEC-03` — Admin Action Audit Log (proposed in `24-security-notes.md`)
- `ADR-SEC-04` — Policy Ability Scope for Blade Writes (proposed in `24-security-notes.md`)

---

## Code Review Process

### 1. Self-Review

Before requesting review, go through the PR Checklist below yourself. Fix everything you can find. The reviewer should not have to tell you about `__()` or hardcoded strings.

### 2. Standards Review

Reviewer checks against `22-coding-standards.md`:
- Naming conventions (controllers, methods, variables)
- Translation function compliance
- Flash key compliance
- No hardcoded text in Blade
- Eloquent patterns (no N+1 queries, `withQueryString()` on paginators)

### 3. Security Review

Reviewer checks against `24-security-notes.md`:
- `authorize()` present on every mutating action
- Ownership verification for client resources
- No credentials in logs
- `mimes:` + `mimetypes:` on file uploads
- No `{!! !!}` except on admin-controlled rich text

### 4. Documentation Review

If the PR introduces:
- A new pattern → update `22-coding-standards.md` or `CLAUDE.md`
- A security decision → update `24-security-notes.md`
- A section/render change → update `07-section-definitions.md` or `09-rendering-flow.md`
- An architectural change → create an ADR

---

## Pull Request Checklist

Check every box before submitting:

**Authorization**
- [ ] Every new controller method calls `$this->authorize()` or FormRequest `authorize()` returns the correct gate check
- [ ] Client resource mutations verify `client_id === auth client id`
- [ ] No ability slug was guessed — verified it matches what `ModelPolicy::__call()` generates

**Validation**
- [ ] All new inputs have FormRequest rules with `max:` constraints
- [ ] File uploads use both `mimes:` and `mimetypes:`
- [ ] Sort/status/direction fields use `in:` validation
- [ ] `prepareForValidation()` normalizes input before rules run

**Translation**
- [ ] Zero uses of `__()`
- [ ] Every visible string uses `t('namespace.Key', 'Fallback')`
- [ ] New keys added to the correct Seeder (`DashboardTranslationsSeeder` or `SiteTranslationsSeeder`)
- [ ] Variable replacement uses `strtr()`, not `t()` parameters
- [ ] All Blade string output uses `{{ t(...) }}` (escaped), not `{!! t(...) !!}` unless it's rich text

**Flash Messages**
- [ ] Success: `->with('ok', t(...))`
- [ ] Error: `->with('error', t(...))`
- [ ] Zero uses of `->with('success', ...)`, `->with('toast_success', ...)`, etc.

**Media / Files**
- [ ] File fields use media picker, not `<input type="file">`
- [ ] File paths stored as media ID where possible, not raw path strings

**Database**
- [ ] Migrations are reversible (have `down()` methods)
- [ ] Money amounts stored as integers (cents), not floats
- [ ] New translation columns added with null defaults

**Documentation**
- [ ] Seeder re-seeded and confirmed working
- [ ] CLAUDE.md updated if a new pattern was introduced
- [ ] ADR created if an architectural decision was made

**Testing**
- [ ] Manually tested happy path and error path in the browser
- [ ] Checked `storage/logs/laravel.log` for any warnings
- [ ] Cleared view cache and tested again: `php artisan view:clear`

---

## Common Mistakes

These mistakes have been made before. Do not repeat them.

### 1. Using `__()` instead of `t()`

```php
// ❌ WRONG — prohibited in this project
__('Dashboard')
__('Created successfully.')

// ✅ CORRECT
t('dashboard.Dashboard', 'Dashboard')
t('dashboard.Created', 'Created successfully.')
```

### 2. Wrong flash key

```php
// ❌ WRONG — nothing appears in the view
->with('success', 'Done')
->with('toast_success', 'Done')

// ✅ CORRECT
->with('ok', t('dashboard.Created', 'Done'))
->with('error', t('dashboard.Error', 'Something went wrong'))
```

### 3. Storing raw file paths instead of media IDs

```php
// ❌ WRONG — bypasses media library, path can be arbitrary
$client->avatar = $request->input('avatar');  // raw path string

// ✅ CORRECT — store media ID and resolve path at render time
$client->avatar_media_id = (int) $request->input('avatar_media_id');
```

### 4. Forgetting `$this->authorize()`

```php
// ❌ WRONG — any admin can delete anything
public function destroy(Plan $plan)
{
    $plan->delete();
    return redirect()->route('dashboard.plans.index')->with('ok', t('dashboard.Plan_Deleted', 'Deleted.'));
}

// ✅ CORRECT
public function destroy(Plan $plan)
{
    $this->authorize('delete', $plan);
    $plan->delete();
    return redirect()->route('dashboard.plans.index')->with('ok', t('dashboard.Plan_Deleted', 'Deleted.'));
}
```

### 5. Forgetting to add keys to the Seeder

Adding `t('dashboard.My_New_Key', 'Fallback')` in the code without adding the key to `DashboardTranslationsSeeder` means:
- The fallback is shown (OK in dev)
- But if a translator later updates the DB, the key is not found → always shows fallback

Always add keys to the Seeder immediately after using them.

### 6. Relying on the Visual Builder (archived)

The visual drag-and-drop builder was archived. Never reference `builder_type = 'visual'` as an active system. All new content goes through the definition-driven section system.

### 7. Using `custom_preset` without a registered template key

`custom_preset` sections must have a valid `template_key` registered in `SectionTemplateRegistry`. Adding a `custom_preset` section without the registration causes a silent render failure (no output, no error).

### 8. PHP loose comparison on status fields

```blade
{{-- ❌ WRONG: null == 0 is true in PHP — wrong option appears selected --}}
<option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactive</option>

{{-- ✅ CORRECT: use radio buttons with strict comparison --}}
<input type="radio" name="is_active" value="1"
       {{ old('is_active', '1') === '1' ? 'checked' : '' }} />
<input type="radio" name="is_active" value="0"
       {{ old('is_active') === '0' ? 'checked' : '' }} />
```

### 9. Putting `<script>` outside `</x-dashboard-layout>`

Scripts placed outside the layout wrapper are not injected into the layout's `@stack('scripts')`. They are silently dropped or cause parse errors.

```blade
{{-- ❌ WRONG --}}
</x-dashboard-layout>
<script>/* never runs */</script>

{{-- ✅ CORRECT --}}
@push('scripts')
<script>/* runs in layout's @stack('scripts') */</script>
@endpush
</x-dashboard-layout>
```

### 10. `$languages` missing from create/edit controllers

Any view with language tabs requires `$languages` to be passed from the controller. Forgetting it causes a silent 500 error:

```php
// ✅ Always pass $languages to create() and edit()
$languages = Language::where('is_active', true)->orderBy('id')->get();
return view('dashboard.my_resource.create', compact('languages', 'categories'));
```

---

## Troubleshooting

### Apache Redirect: POST requests return 405

**Symptom:** A form POST works locally but returns 405 on the production server.

**Cause:** The production server's Apache document root is `public_html/` (not `public_html/public/`). All requests to `/admin/...` are redirected (301) to `/public/admin/...`. The 301 converts POST to GET. GET on a POST-only route returns 405.

**Fix for AJAX calls:**
```javascript
var url = formElement.action;
if (!/\/public\//.test(url)) {
    url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
}
fetch(url, { method: 'POST', redirect: 'manual', ... });
```

**Fix for HTML forms:** Add `/public` to the form action URL when needed, or configure Apache correctly (see server setup).

---

### ModSecurity Blocks POST with PHP Code

**Symptom:** POST requests containing `<?php` return 403 Forbidden from the WAF.

**Cause:** ModSecurity (WAF) on shared hosting blocks requests with PHP code patterns.

**Fix:** Encode the content as base64 before submitting, and decode server-side:
```javascript
// Client: encode before submitting
formData.set('blade_source_b64', btoa(unescape(encodeURIComponent(codeContent))));
```
```php
// Server: decode
$decoded = base64_decode($request->input('blade_source_b64'), strict: false);
```
This is already implemented in the Blade editor. Apply the same pattern if you need to submit code through other forms.

---

### Blade Editor Write Failures

**Symptom:** "Write File" in the Monaco editor shows success in the UI but the file is not saved to disk.

**Check 1:** Verify the URL includes `/public/` (see Apache Redirect issue above).

**Check 2:** Verify the section key and category contain only `[a-z0-9_-]`. Any other character causes `SectionTemplateFileWriter` to refuse the write with "Path traversal detected".

**Check 3:** Verify the user has `sectiondefinitions.update` ability. Anonymous admin users without this ability get a 403 that may be silently swallowed by the AJAX handler.

**Check 4:** Check `storage/logs/laravel.log` — the FileWriter logs all write attempts.

---

### Missing Translation Keys in Production

**Symptom:** English fallback text is shown instead of Arabic on production.

**Cause:** Keys were added to code but the Seeder was not run on production.

**Fix:**
```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan db:seed --class=SiteTranslationsSeeder
php artisan cache:clear
```

---

### Tenant Site Shows 404

**Symptom:** A client's site shows 404 when accessed via their custom domain.

**Debug sequence:**
1. Check `subscriptions` table: `status = 'active'`?
2. Check `subscriptions.domain_name` matches the exact host header (no `www.` mismatch)
3. Check `$subscription->customDomainIsReady()` — is DNS verified?
4. Check that `plan.plan_type = TYPE_MULTI_TENANT` (not `hosting`)
5. Check `pages` table: is there a page with `context = 'tenant'` and `tenant_id = $subscription->id`?

---

### Queue Jobs Not Running (Provisioning Stuck)

**Symptom:** A new subscription stays in `status = 'pending'` and is never provisioned.

**Cause:** Queue worker is not running, or `QUEUE_CONNECTION` is set to `sync` on a server that needs async.

**Fix:** Start the queue worker:
```bash
php artisan queue:work --tries=3 --sleep=5
```

For production, use a process manager (Supervisor, systemd) to keep this running.

---

### Blade Cache Serving Stale Views

**Symptom:** Code changes in a Blade file are not reflected in the browser.

**Fix:**
```bash
php artisan view:clear        # Clears compiled Blade views
php artisan optimize:clear    # Clears all caches (config, route, view)
```

If `artisan` is not available (shared hosting), delete files in `storage/framework/views/*.php` manually.

---

## FAQ

**Q: Why is there no `__()` usage in the codebase?**

`__()` is the standard Laravel translation function but this project uses a custom `t()` function (defined in `app/helpers.php`) that reads from the `translation_values` database table. This allows admins to edit translation strings from the dashboard without file deployments. The `t()` function is a hard requirement — do not use `__()`.

---

**Q: I added a new translation key to the Seeder but my changes aren't visible. Why?**

You need to re-run the specific Seeder after adding keys:
```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```
The Seeder uses `firstOrCreate()` so re-running it is safe and will not overwrite existing values.

---

**Q: How do I add a new language?**

1. Go to `/admin/languages` and create the language record
2. Add translations for the new language code in the `translation_values` table (via the dashboard or a new Seeder)
3. The language switcher in the front-end will automatically appear

---

**Q: How do I know which Policy ability slug to use?**

`ModelPolicy::__call()` generates the slug automatically from the Policy class name and the method being called:
- Class: `PagePolicy` → model name: `page` → plural: `pages`
- Method: `viewAny` → `view` (aliased)
- Result: `pages.view`

The full alias map is: `viewAny` → `view`, `update` → `edit`. All others are used as-is.

---

**Q: Can I use `localStorage` in the admin JS?**

Yes, for persistent UI preferences (e.g. tab state, filter preferences). Do not store sensitive data in `localStorage`. Do not rely on it for security decisions.

---

**Q: How do I test WHM integration locally?**

You need a real WHM server. Create a record in `/admin/management/servers` with a WHM hostname and API token. Use a reseller account — the `listpkgs` endpoint only returns packages created by that reseller. Root-created packages are not visible. Verify the reseller has the required WHM API privileges (see `24-security-notes.md` — WHM Integration Security).

---

**Q: The visual builder is mentioned in old docs. Should I use it?**

No. The visual builder was archived. All content should use the definition-driven section system. See `ADR-001` and `07-section-definitions.md`.

---

**Q: Should I create a new migration or modify an existing one?**

Always create a new migration. Never modify a migration that has already been run in any environment. Modifying run migrations can leave the schema in an inconsistent state across environments.

---

## Related Documents

- [00-project-overview.md](./00-project-overview.md) — What the platform does
- [01-system-architecture.md](./01-system-architecture.md) — Guards, layers, services
- [03-database-architecture.md](./03-database-architecture.md) — Schema, relationships, money handling
- [07-section-definitions.md](./07-section-definitions.md) — The section authoring system
- [09-rendering-flow.md](./09-rendering-flow.md) — Render pipeline and resolvers
- [22-coding-standards.md](./22-coding-standards.md) — Naming conventions, patterns, PR rules
- [24-security-notes.md](./24-security-notes.md) — Authorization, middleware, known risks
- [CLAUDE.md](../CLAUDE.md) — Project rules, UX patterns, session history
- [docs/adr/001-page-section-as-source-of-truth.md](./adr/001-page-section-as-source-of-truth.md)
