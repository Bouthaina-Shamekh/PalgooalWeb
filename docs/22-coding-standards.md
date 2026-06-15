# Coding Standards — PalgooalWeb

> **Status:** Living Document — update when patterns change, not just when remembered.
> **Scope:** All application code in `app/`, `resources/views/`, `routes/`, `database/`.
> **Authority:** Supersedes any conflicting pattern found in legacy files.

---

## Table of Contents

1. [Architectural Principles](#1-architectural-principles)
2. [Naming Conventions](#2-naming-conventions)
3. [Controller Standards](#3-controller-standards)
4. [Service Layer Standards](#4-service-layer-standards)
5. [Model Standards](#5-model-standards)
6. [Database Standards](#6-database-standards)
7. [Money & Pricing Standards](#7-money--pricing-standards)
8. [Translation Standards](#8-translation-standards)
9. [Section Standards](#9-section-standards)
10. [Blade Standards](#10-blade-standards)
11. [Media Standards](#11-media-standards)
12. [Authorization Standards](#12-authorization-standards)
13. [Validation Standards](#13-validation-standards)
14. [Error Handling Standards](#14-error-handling-standards)
15. [Route Standards](#15-route-standards)
16. [Admin UI Standards](#16-admin-ui-standards)
17. [Technical Debt Rules](#17-technical-debt-rules)
18. [ADR Process](#18-adr-process)
19. [Code Review Checklist](#19-code-review-checklist)
20. [Related Documents](#20-related-documents)

---

## 1. Architectural Principles

### 1.1 Layered Separation

Controllers orchestrate. Services decide. Models persist.

| Layer | Responsibility | Must NOT |
|---|---|---|
| **Controller** | Receive request, authorize, delegate, return response | Contain business logic, query DB directly beyond simple lookups |
| **Service** | Implement business rules, coordinate models | Know about HTTP, sessions, or Blade |
| **Model** | Define schema, relationships, scopes, casts | Contain HTTP logic or service calls |
| **FormRequest** | Validate and normalize input | Contain business logic |
| **Blade view** | Present data | Query the database or call services |

### 1.2 Single Responsibility

Every class does one thing. A class that is hard to name concisely is doing too much.

### 1.3 Explicit Over Implicit

Prefer `compact('users', 'search', 'perPage')` over `compact(...)`. Prefer named route parameters over positional assumptions. Prefer explicit `->with()` flash keys over magic.

### 1.4 Code First, Docs Second

The migration is the source of truth for schema. The model is the source of truth for relationships. Documentation describes what the code does — never invent intent from docs alone.

### 1.5 No Dead Code in Production Views

Bootstrap modals, jQuery CDN includes, inline styles that duplicate Tailwind, and `@push('styles')` blocks for abandoned UI patterns must be removed — not commented out. Dead code is a maintenance liability.

---

## 2. Naming Conventions

### 2.1 PHP Classes

| Type | Convention | Example |
|---|---|---|
| Controller | `PascalCase` + `Controller` | `SubscriptionController` |
| Model | `PascalCase` singular | `Plan`, `Client`, `Subscription` |
| FormRequest | `Store`/`Update` + model + `Request` | `StoreSectionDefinitionRequest` |
| Service — orchestrator | `PascalCase` + `Service` | `ProvisioningService`, `OrderActivationService` |
| Service — decision | `PascalCase` + `Resolver` | `SectionDefinitionRuntimeResolver`, `SectionQueryResolver` |
| Service — data builder | `PascalCase` + `Factory` | `SectionDefinitionFrontendViewDataFactory` |
| Service — file writer | `PascalCase` + `Writer` | `SectionTemplateFileWriter` |
| Service — registry | `PascalCase` + `Registry` | `SectionTemplateRegistry` |
| Job | `PascalCase` + verb phrase | `ProvisionSubscriptionJob`, `SyncSubscriptionJob` |
| Event | Noun or past-tense verb | `ClientCreated`, `SubscriptionActivated` |
| Listener | `Handle` + event name | `HandleClientCreated` |
| Policy | Model name + `Policy` | `SectionDefinitionPolicy`, `PlanPolicy` |
| Seeder | Descriptive + `Seeder` | `SiteTranslationsSeeder`, `DashboardTranslationsSeeder` |

### 2.2 Database

| Type | Convention | Example |
|---|---|---|
| Table names | `snake_case` plural | `plan_categories`, `subscription_pages` |
| Column names | `snake_case` | `is_active`, `created_at`, `monthly_price` |
| Foreign keys | `{model}_id` | `client_id`, `plan_id`, `server_id` |
| Boolean columns | `is_` or `has_` prefix | `is_active`, `is_featured`, `has_ssl` |
| Timestamps | Use `timestamps()` macro | `created_at`, `updated_at` |
| Soft delete | `softDeletes()` macro | `deleted_at` |
| Index names | `{table}_{column}_index` | `subscriptions_client_id_index` |

**Exception — known mismatch (Technical Debt):** `Testimonial` model → `feedbacks` / `feedback_translations` tables. Do not rename until a proper migration plan is in place. See [Section 17](#17-technical-debt-rules).

### 2.3 Translation Keys

```
{section}.{Key_Name}
```

- Section: lowercase — `dashboard`, `site`, `checkout`, `template`, `common`
- Key: `Snake_Case` with initial cap on each word
- Fallback: English string as second argument to `t()`

```blade
{{ t('dashboard.Add_Plan', 'Add Plan') }}
{{ t('site.View_Website', 'View website') }}
```

### 2.4 Route Names

| Guard | Prefix | Name prefix | Example |
|---|---|---|---|
| `auth` (admins) | `/admin` | `dashboard.` | `dashboard.clients`, `dashboard.plans.create` |
| `client` (clients) | `/client` | `client.` | `client.home`, `client.domains.search` |
| Public (unauthenticated) | `/` | *(no prefix)* | `home`, `checkout` |

### 2.5 JavaScript Variables in Blade

PHP-to-JS data injection uses named constants at the top of a `<script>` block IIFE, not inline `{{ __() }}` calls scattered through logic:

```javascript
// ✅ Correct — declare all PHP strings upfront
(function () {
    const i18n = {
        confirmDelete: @json(t('dashboard.Confirm_Delete', 'Confirm delete')),
        saved:         @json(t('dashboard.Saved', 'Saved')),
        errorGeneric:  @json(t('dashboard.Error_Generic', 'An error occurred')),
    };
    // use i18n.confirmDelete throughout the script
})();
```

---

## 3. Controller Standards

### 3.1 Authorize First

Every controller action that touches a protected resource must call `$this->authorize()` before any data access. No exceptions.

```php
public function index(Request $request): View
{
    $this->authorize('viewAny', Plan::class);
    // ...
}

public function update(Request $request, Plan $plan): RedirectResponse
{
    $this->authorize('update', $plan);
    // ...
}
```

### 3.2 No Business Logic in Controllers

Controllers may:
- Resolve route model bindings
- Call `$this->authorize()`
- Delegate to a Service or FormRequest
- Return a response (view, redirect, JSON)

Controllers must NOT:
- Contain multi-step business rules
- Perform calculations (e.g., converting currency, computing dates)
- Make multiple model queries to decide what to do

```php
// ❌ Wrong — business logic in controller
public function store(Request $request): RedirectResponse
{
    $price = (int) round(floatval($request->input('price_ui')) * 100);
    if ($price < 100) { $price = 100; }
    Plan::create(['price' => $price, ...]);
    return redirect()->route('dashboard.plans');
}

// ✅ Correct — delegate to service or helper
public function store(StorePlanRequest $request): RedirectResponse
{
    $this->authorize('create', Plan::class);
    $plan = $this->planService->create($request->validated());
    return redirect()->route('dashboard.plans')
        ->with('ok', t('dashboard.Plan_Created', 'Plan created.'));
}
```

### 3.3 Index Actions — Standard Pattern

```php
public function index(Request $request): View
{
    $this->authorize('viewAny', Model::class);

    $search  = $request->get('search');
    $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
        ? (int) $request->get('per_page') : 20;

    $items = Model::with(['relation1', 'relation2'])
        ->latest()
        ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
        ->paginate($perPage)
        ->withQueryString();

    return view('dashboard.models.index', compact('items', 'search', 'perPage'));
}
```

Rules:
- Always `->withQueryString()` on paginated results — preserves search/filter params in pagination links
- Always pass `$search` and `$perPage` to the view — the view needs them to repopulate the form
- Per-page options must be validated against an allowed list — never trust raw user input

### 3.4 Store/Update Pattern

```php
public function store(StoreModelRequest $request): RedirectResponse
{
    $this->authorize('create', Model::class);

    $model = Model::create($request->validated());

    return redirect()->route('dashboard.models.edit', $model)
        ->with('ok', t('dashboard.Model_Created', 'Record created.'));
}
```

### 3.5 Flash Keys

| Key | Meaning | Usage |
|---|---|---|
| `session('ok')` | Success | After successful create/update/delete |
| `session('error')` | User-facing error | After failed operation (not validation) |

**Never use `session('success')`** — this key is not rendered in the dashboard layout. Only `ok` and `error` are rendered.

```php
// ✅ Correct
return redirect()->back()->with('ok', t('dashboard.Saved', 'Saved.'));
return redirect()->back()->with('error', t('dashboard.Failed', 'Operation failed.'));

// ❌ Wrong — not rendered in layout
return redirect()->back()->with('success', 'Saved.');
```

### 3.6 Avoid Returning JSON from HTML Form Handlers

If an action is reachable from both HTML forms and AJAX clients, branch on `$request->wantsJson()`:

```php
public function store(Request $request)
{
    // ...
    if ($request->wantsJson()) {
        return response()->json(['ok' => true]);
    }
    return redirect()->route('dashboard.models')->with('ok', t('...'));
}
```

Remove `: JsonResponse` return type annotation from methods that can return either.

---

## 4. Service Layer Standards

### 4.1 Naming by Role

The `app/Support/` tree organizes services by domain and role. Naming must be consistent:

| Suffix | Role | Example |
|---|---|---|
| `Service` | Orchestrates multiple models/steps | `ProvisioningService`, `OrderActivationService` |
| `Resolver` | Makes a decision — picks one option from many | `SectionDefinitionRuntimeResolver`, `SectionQueryResolver` |
| `Factory` | Builds a data structure (returns array or DTO) | `SectionDefinitionFrontendViewDataFactory` |
| `Writer` | Writes to disk or external API | `SectionTemplateFileWriter` |
| `Registry` | Manages a collection of known items | `SectionTemplateRegistry` |
| `Client` | Wraps an external API | `EnomClient`, `NamecheapClient` |

### 4.2 Services Know Nothing About HTTP

Services must not reference `Request`, `Session`, `Response`, or any facade that implies an HTTP context. They receive plain PHP values and return plain PHP values.

### 4.3 Logging in Services

Services use `Log::` directly. Log messages must include context arrays for debuggability:

```php
Log::error('Failed to provision domain for order ' . $order->id, [
    'exception' => $e->getMessage(),
    'order_id'  => $order->id,
]);

Log::warning('Enom HTTP error', [
    'status'  => $resp->status(),
    'snippet' => mb_substr($body, 0, 300),
]);
```

### 4.4 Exception Handling in Services

Catch exceptions at the boundary where you can add context. Re-throw or return meaningful values — do not silently swallow errors.

```php
// ✅ Correct — catch at boundary, log with context, re-throw
try {
    $this->registrarClient->registerDomain($order->domain);
} catch (\Throwable $e) {
    Log::error('Failed to provision domain for order ' . $order->id . ': ' . $e->getMessage());
    throw $e; // caller decides whether to abort the whole operation
}
```

Activity listeners that are non-critical must wrap in try/catch so a logging failure does not abort the main operation:

```php
public function handle(ClientCreated $event): void
{
    try {
        $this->logger->clientCreated($event->client);
    } catch (\Throwable $e) {
        Log::warning('Activity logging failed: ' . $e->getMessage());
    }
}
```

---

## 5. Model Standards

### 5.1 Constants for Enum-like Values

Models that have string enum columns must define constants. Do not hardcode string values in controllers or views:

```php
// ✅ Correct — constants on the model
class Plan extends Model
{
    public const TYPE_MULTI_TENANT = 'multi_tenant';
    public const TYPE_HOSTING      = 'hosting';

    public function isHosting(): bool
    {
        return $this->plan_type === self::TYPE_HOSTING;
    }
}

// In controller or view:
if ($plan->isHosting()) { ... }
if ($plan->plan_type === Plan::TYPE_HOSTING) { ... }

// ❌ Wrong
if ($plan->plan_type === 'hosting') { ... }
```

### 5.2 Translation Relationship Pattern

Models with `_translations` tables expose a `translation()` helper that respects the current locale and relation loading:

```php
public function translation(?string $locale = null)
{
    $locale = $locale ?: app()->getLocale();

    if ($this->relationLoaded('translations')) {
        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->first();
    }

    return $this->translations()->where('locale', $locale)->first()
        ?? $this->translations()->first();
}
```

Always eager-load `translations` when rendering a list. Never call `$model->translation()` inside a loop without prior eager loading.

### 5.3 Scopes Must Be Declared Static

PHP 8+ requires Eloquent scopes to be declared `static`:

```php
// ✅ Correct
public static function scopePaid($query): Builder
{
    return $query->where('status', 'paid');
}

// ❌ Wrong — causes "Non-static method cannot be called statically"
public function scopePaid($query): Builder
{
    return $query->where('status', 'paid');
}
```

### 5.4 Soft Deletes

The following models use `SoftDeletes`. Always use `withTrashed()` or `onlyTrashed()` explicitly when you need deleted records — do not add `deleted_at IS NULL` manually.

Models with soft deletes: `Header`, `HeaderItem`, `Invoice`, `InvoiceItem`, `Order`, `OrderItem`, `PlanCategory`, `Portfolio`, `Subscription`, `TemplateReview`, `Testimonial` (via `feedbacks` table).

### 5.5 Money on Models

Money columns that represent cents must use integer type in migrations and cast as `int` in the model:

```php
protected $casts = [
    'monthly_price' => 'integer',   // cents
    'annual_price'  => 'integer',   // cents
    'total_cents'   => 'integer',   // cents
];
```

See [Section 7](#7-money--pricing-standards) for the full money standard.

---

## 6. Database Standards

### 6.1 Migrations

- One concern per migration file
- Migration file names: `{YYYY_MM_DD_HHMMSS}_{verb}_{table_or_description}.php`
- Never modify a migration that has already run in production — create a new migration instead
- Add indexes for all foreign keys and frequently-queried columns
- Use `foreignId()->constrained()->cascadeOnDelete()` for standard FK patterns

### 6.2 Eagerly Load Relationships

N+1 queries are a production performance risk. All index actions must eager-load relationships rendered in the view:

```php
// ✅ Correct
$plans = Plan::with(['translations', 'category.translations', 'server'])->paginate();

// ❌ Wrong — N+1 in the view
$plans = Plan::paginate();
// view calls $plan->translations, $plan->category->name for each row
```

### 6.3 Index the Right Columns

Required indexes:
- All foreign key columns
- `slug` columns (`unique`)
- Columns used in `WHERE` clauses in index actions (e.g., `name`, `status`, `is_active`)
- `(client_id, status)` composite index on `subscriptions`
- `(locale, key)` on translation tables

### 6.4 Never Use Raw DB Credentials in Code

Database connectivity is configured through Laravel environment variables (`DB_*`). No class, config file, or migration should hardcode a hostname, port, database name, username, or password.

---

## 7. Money & Pricing Standards

### 7.1 Canonical Storage: Integer Cents

**All money values are stored as integer cents.** This is the canonical standard for the entire billing domain.

| ✅ Correct | ❌ Wrong |
|---|---|
| `monthly_price` (int, cents) | `monthly_price` (decimal, dollars) |
| `total_cents` (int, cents) | `total` (float, dollars) |
| `Invoice::paid()->sum('total_cents') / 100` | `Invoice::paid()->sum('total')` |

### 7.2 Parsing UI Input to Cents

When a form field accepts a human-readable dollar amount, convert to cents in the controller before persisting:

```php
private function parsePrice(Request $r, string $field): ?int
{
    // First try a hidden _cents field (set by JS)
    $value = $r->input($field . '_cents');
    if ($value === null || $value === '') {
        // Fall back to human-readable _ui field
        $uiValue = $r->input($field . '_ui');
        $value = ($uiValue !== null && $uiValue !== '')
            ? (int) round(floatval($uiValue) * 100)
            : null;
    }
    return ($value !== null) ? (int) $value : null;
}
```

### 7.3 Displaying Money

Display helpers must divide by 100 and format. Never store the divided value:

```php
// In a model accessor or helper:
public function getMonthlyPriceFormattedAttribute(): string
{
    return number_format($this->monthly_price / 100, 2);
}
```

### 7.4 Technical Debt — `subscriptions.price`

`subscriptions.price` uses `decimal(10,2)` (dollar amount), inconsistent with the cents standard. This is **known Technical Debt #1**.

- Do not introduce new `decimal` price columns. All new price columns must use integer cents.
- A migration to convert `subscriptions.price` to integer cents requires an ADR. Reference this as **ADR-003** (pending).
- Until migrated: when reading `subscriptions.price`, be explicit about the unit in comments.

```php
// Technical Debt #1 — subscriptions.price is stored as decimal dollars, not cents
$price = (int) round($subscription->price * 100); // convert to cents for display
```

---

## 8. Translation Standards

### 8.1 `t()` Is the Only Approved Translation API

Palgoals uses database-driven translations exclusively.

The helper `t()` is the only approved translation API for application code.

The Laravel helper `__()` is **prohibited** in project code: Blade views, controllers, services, models, policies, FormRequests, seeders, and any custom packages developed within this project.

If a third-party package internally uses `__()`, that is acceptable and outside the project's coding standards. However, any project-owned code that integrates with that package must continue using `t()` as the public translation interface.

**Never introduce new `__()` calls into the codebase.**

When refactoring legacy code, replace `__()` usages with `t()` whenever the string belongs to the application's translation system.

```php
// ✅ Correct
t('dashboard.Plan_Created', 'Plan created.')
t('site.View_Website', 'View website')

// ❌ Prohibited — reads from lang files, not the translation_values table
__('Plan created.')
__('dashboard.plan_created')
```

### 8.2 Signature of `t()`

```php
t(string $key, ?string $default = null): string
```

- Accepts exactly two arguments
- Does not support parameter replacement (no `:name` substitution)
- For variable replacement, use `strtr()` externally:

```blade
{{ strtr(t('site.Step_Of_Total', 'Step :step of :total'), [':step' => $n, ':total' => $total]) }}
```

### 8.3 Key Format

```
{section}.{Key_Name}
```

Sections: `dashboard`, `site`, `checkout`, `template`, `common`
Key format: `Snake_Case` — each word starts with uppercase, words separated by underscore.

```
dashboard.Add_New_Plan        ✅
dashboard.addNewPlan          ❌ (camelCase)
dashboard.add_new_plan        ❌ (all lowercase)
DASHBOARD.Add_New_Plan        ❌ (uppercase section)
```

### 8.4 Adding New Keys

1. Add the key + English default to the appropriate Seeder (`SiteTranslationsSeeder` or `DashboardTranslationsSeeder`)
2. Run the seeder on the target environment
3. Use `t('section.Key', 'English fallback')` in Blade

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

### 8.5 Strict Comparison on Translated Radio Button Values

PHP has a loose comparison bug where `null == 0` returns `true`. All radio/select comparisons must use `===`:

```blade
{{-- ❌ Wrong — null == 0 is true in PHP --}}
<option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactive</option>

{{-- ✅ Correct — strict comparison --}}
<input type="radio" name="is_active" value="1"
       {{ old('is_active', '1') === '1' ? 'checked' : '' }} />
<input type="radio" name="is_active" value="0"
       {{ old('is_active') === '0' ? 'checked' : '' }} />
```

---

## 9. Section Standards

### 9.1 Only `editor_mode = dynamic` Exists

The `custom_preset` editor mode was permanently removed by migration `2026_04_27_remove_custom_preset_from_section_definitions`. All section definitions use `dynamic`. Any code checking for `custom_preset` is dead code and must be removed.

### 9.2 Actual Blade View Variables

Inside a dynamic section's Blade view, the available variables are:

| Variable | Type | Description |
|---|---|---|
| `$data` | `array` | Merged field values: shared fields + locale-specific translations |
| `$content` | `array` | Alias for `$data` (same reference, both available) |
| `$section` | `Section` | The Eloquent model |

**`$fields` does NOT exist.** This was aspirational naming from older documentation. Do not use `$fields` in any section Blade view.

```blade
{{-- ✅ Correct --}}
<h1>{{ $data['title'] ?? '' }}</h1>
<p>{{ $content['subtitle'] ?? '' }}</p>

{{-- ❌ Wrong — $fields is undefined --}}
<h1>{{ $fields['title'] ?? '' }}</h1>
```

This naming inconsistency requires an ADR before the variable names are changed.

### 9.3 Blade View Path Convention

For a section definition with `template_key = 'hero_main'` and `category = 'hero'`, the resolved Blade view is:

```
resources/views/front/sections/hero/hero_main.blade.php
```

Pattern: `front.sections.{category}.{template_key}`

### 9.4 Security in `SectionTemplateFileWriter`

Section Blade files are written to disk via Monaco Editor + POST endpoint. The Writer enforces two security rules that must never be bypassed:

1. `category` and `section_key` must match `/^[a-z0-9_-]+$/`
2. The resolved file path must remain within `resource_path('views/front/sections/')`

Do not add new write endpoints that skip these checks.

### 9.5 POST URL on Production Server

The production server document root is `public_html/` (not `public_html/public/`). Any `POST` request to `/admin/...` is redirected (301) to `/public/admin/...` by Apache, converting POST to GET, resulting in 405. JavaScript `fetch()` calls to admin endpoints must use the `/public/` prefix:

```javascript
// ✅ Correct — ensure /public/ is in the URL
var url = writeForm.action;
if (!/\/public\//.test(url)) {
    url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
}
```

---

## 10. Blade Standards

### 10.1 No Queries in Blade Views

Blade views must not call Eloquent or any database method. All data must be passed from the controller.

```blade
{{-- ❌ Wrong --}}
@foreach(Plan::active()->get() as $plan)

{{-- ✅ Correct — data passed from controller --}}
@foreach($plans as $plan)
```

### 10.2 No Business Logic in Blade

Blade may only:
- Render data already prepared by the controller
- Apply simple conditionals on passed variables (`@if($plan->isHosting())`)
- Format strings and dates with helpers
- Use `t()` for translated labels

### 10.3 Scripts Inside Layout

All `<script>` blocks must be inside `<x-dashboard-layout>`. Scripts outside the layout closing tag are not loaded through asset compilation and may miss dependencies.

```blade
{{-- ✅ Correct --}}
<x-dashboard-layout>
    ...content...
    @push('scripts')
        <script>...</script>
    @endpush
</x-dashboard-layout>

{{-- ❌ Wrong — outside layout --}}
</x-dashboard-layout>
<script>...</script>
```

### 10.4 `dir="ltr" font-mono` on Technical Fields

All inputs for IP addresses, hostnames, API tokens, slugs, domain names, and keys must include `dir="ltr"` and the `font-mono` class. This ensures correct display in RTL context and readable technical strings:

```blade
<input type="text" name="ip"       class="form-control font-mono" dir="ltr" />
<input type="text" name="hostname" class="form-control font-mono" dir="ltr" />
<input type="text" name="slug"     class="form-control font-mono" dir="ltr" />
<input type="text" name="api_key"  class="form-control font-mono" dir="ltr" />
```

### 10.5 Language Tabs Pattern

Multi-language form sections use the tab pattern. Tabs and panels must use consistent `data-*` attributes for the switcher:

```blade
<div class="lang-switcher">
    @foreach($languages as $lang)
        <button type="button"
                class="lang-tab-btn {{ $loop->first ? 'active' : '' }}"
                data-lang-tab="{{ $lang->code }}">
            {{ $lang->name }}
        </button>
    @endforeach
</div>

@foreach($languages as $lang)
    <div data-lang-panel="{{ $lang->code }}"
         class="{{ $loop->first ? '' : 'hidden' }}">
        {{-- fields for this language --}}
    </div>
@endforeach
```

The controller must always pass `$languages` when the view includes language tabs:

```php
$languages = Language::where('is_active', true)->orderBy('id')->get();
return view('...', compact('model', 'languages'));
```

**Missing `$languages` causes a silent 500 error.**

---

## 11. Media Standards

### 11.1 Store Media IDs, Not Paths

All media references in the database must store the `Media` model's integer ID, not a file path string.

```php
// ✅ Correct — store media ID
'image_id'        => $request->input('image_id'),   // integer FK to media table
'default_image_id' => $request->input('default_image_id'),
```

### 11.2 Use the Media Picker Component

File uploads in admin forms must use the `x-dashboard.media-picker` component, not a raw `<input type="file">`. This connects to the media library and stores an ID.

```blade
{{-- ✅ Correct --}}
<x-dashboard.media-picker name="image_id" :value="old('image_id', $model->image_id)" />

{{-- ❌ Wrong --}}
<input type="file" name="image">
```

When a hidden input is used for the picker value:

```blade
<input type="hidden" name="avatar" id="avatar-value">
<button type="button" class="btn-open-media-picker" data-target="avatar-value" data-store-value="path">
    {{ t('dashboard.Choose_From_Media', 'Choose from media') }}
</button>
```

The corresponding controller validation must accept a string path, not a file upload:

```php
// ✅ Correct — media path from picker
'avatar' => 'nullable|string|max:500',

// ❌ Wrong — expects uploaded file, but picker sends a path
'avatar' => 'nullable|image|max:2048',
```

### 11.3 Technical Debt — `clients.avatar`

`clients.avatar` stores a file path string, inconsistent with the media ID standard. This is **known Technical Debt #4**.

- Do not introduce new path-string media fields.
- Do not fix `clients.avatar` without first planning a migration of existing data.

---

## 12. Authorization Standards

### 12.1 Policy First

Use Policies for all resource-level authorization. Register policies in `AppServiceProvider`:

```php
Gate::policy(SectionDefinition::class, SectionDefinitionPolicy::class);
Gate::policy(Plan::class, PlanPolicy::class);
```

Policies implement `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

### 12.2 Authorize in Every Controller Action

Every action that modifies or reads a protected resource must call `$this->authorize()`:

```php
$this->authorize('viewAny', Plan::class);   // collection
$this->authorize('update', $plan);          // instance
$this->authorize('create', Plan::class);    // new resource
```

### 12.3 Gate Definitions

Custom gates are defined in `AppServiceProvider`:

```php
Gate::define('access-dashboard', fn($user) => (bool) $user->super_admin);
```

The `super_admin` bypass runs via `Gate::before`:

```php
Gate::before(function ($user, $ability) {
    if ($user instanceof User && $user->super_admin) {
        return true;
    }
});
```

This means a `super_admin` bypasses ALL policy checks. Do not rely on this for feature-level access control — use it only for super-admin bypass of security gates.

### 12.4 FormRequest Authorization

FormRequest `authorize()` must use the Policy, not raw `Gate::check()`:

```php
public function authorize(): bool
{
    return $this->user()?->can('create', SectionDefinition::class) ?? false;
}
```

### 12.5 Client Guard

Client-facing routes use the `client` guard. Client users are `App\Models\Client`, not `App\Models\User`. The two models are entirely separate:

```php
// Admin (web guard)
$this->authorize('update', $plan);  // uses User + Policy

// Client (client guard) — use auth('client')
$client = auth('client')->user();
```

---

## 13. Validation Standards

### 13.1 FormRequest for Non-Trivial Validation

Any action with more than two validation rules must use a FormRequest. Inline `$request->validate()` is only acceptable for simple 1-2 field forms.

```php
// ✅ Correct — FormRequest for complex forms
public function store(StoreSectionDefinitionRequest $request): RedirectResponse

// Acceptable — simple single-field action
$request->validate(['email' => 'required|email']);
```

### 13.2 Normalize in `prepareForValidation()`

Input normalization (lowercasing slugs, forcing booleans, setting defaults) belongs in `prepareForValidation()`, not in the controller:

```php
public function prepareForValidation(): void
{
    $this->merge([
        'key'         => strtolower(trim((string) $this->input('key', ''))),
        'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
        'is_active'   => $this->boolean('is_active'),
    ]);
}
```

### 13.3 Conditional Rules

Use `Rule::requiredIf()` and `Rule::when()` for rules that depend on other field values:

```php
public function rules(): array
{
    return [
        'type'    => ['required', Rule::in(['text', 'repeater', 'media'])],
        'options' => ['nullable', 'string', Rule::requiredIf($this->input('type') === 'select')],
    ];
}
```

### 13.4 Validation Messages via `t()`

Custom validation messages in FormRequests must use `t()`:

```php
public function messages(): array
{
    return [
        'name.required' => t('dashboard.Validation_Name_Required', 'Name is required.'),
        'slug.unique'   => t('dashboard.Validation_Slug_Unique', 'This slug is already taken.'),
    ];
}
```

---

## 14. Error Handling Standards

### 14.1 User-Facing Errors — Flash, Not Exception

Operations that fail due to external systems (API errors, file write failures, WHM errors) must catch the exception and flash a user-friendly error message instead of letting the exception propagate to a 500 page:

```php
try {
    $this->writer->write($definition, $content);
} catch (\Throwable $e) {
    Log::error('Blade write failed: ' . $e->getMessage(), ['definition_id' => $definition->id]);
    return redirect()->back()
        ->with('error', t('dashboard.Write_Failed', 'Failed to write template file.'));
}
```

### 14.2 Log Before Flash

Always `Log::error()` or `Log::warning()` before flashing a user-facing error. The log is the forensic record; the flash message is the user experience.

### 14.3 Validation Errors — `$errors` Bag

Validation errors from FormRequests are automatically placed in the `$errors` bag. Blade views must display them per-field:

```blade
<input name="name" class="form-control @error('name') is-invalid @enderror">
@error('name')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
```

### 14.4 Never Expose Stack Traces to Users

All exceptions must be handled at the controller boundary. Users see only friendly messages. Stack traces go to `storage/logs/laravel.log` only.

---

## 15. Route Standards

### 15.1 Route Files

| File | Covers |
|---|---|
| `routes/web.php` | Public unauthenticated pages |
| `routes/dashboard.php` | Admin (auth guard, `dashboard.*` names) |
| `routes/client.php` | Client portal (client guard, `client.*` names) |
| `routes/api.php` | JSON API endpoints |

### 15.2 Admin Route Middleware

All admin routes are wrapped with:

```php
Route::middleware(['auth', 'can:access-dashboard'])->prefix('admin')->name('dashboard.')->group(function () {
    // ...
});
```

Do not add individual routes outside this group unless explicitly needed.

### 15.3 Resource Routes vs Manual Routes

Prefer explicit manual route declarations over `Route::resource()` when only a subset of CRUD operations exist. This avoids registering routes that have no corresponding controller action.

### 15.4 Route Model Binding

Use route model binding for all single-model show/edit/update/destroy actions. The binding resolves automatically from the route parameter name matching the variable name:

```php
Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');

// In controller:
public function edit(Plan $plan): View  // $plan automatically resolved
```

---

## 16. Admin UI Standards

### 16.1 Layout Component

All admin views use `<x-dashboard-layout>`. Never create a standalone HTML page for admin.

### 16.2 Page Header & Breadcrumb

Every admin page must start with:

```blade
<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ t('dashboard.Plans', 'الباقات') }}</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">{{ t('dashboard.Plans', 'الباقات') }}</h2>
        </div>
    </div>
</div>
```

### 16.3 Flash Messages

Flash messages immediately follow the page header:

```blade
@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

### 16.4 Card Structure

Tables and list views use:

```blade
<div class="card table-card">
    <div class="card-header">
        {{-- toolbar: search, per_page, add button --}}
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            ...
        </table>
    </div>
    <div class="card-footer">
        {{ $items->links() }}
    </div>
</div>
```

### 16.5 Create/Edit Layout

All create and edit forms use a two-column grid:

```blade
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 xl:col-span-8">
        {{-- main form sections --}}
    </div>
    <div class="col-span-12 xl:col-span-4">
        {{-- help sidebar, sticky --}}
        <div class="card sticky top-6">...</div>
    </div>
</div>
```

### 16.6 Form Sections — Numbered

Forms must be organized into numbered sections:

```blade
<div class="card mb-4">
    <div class="card-header">
        <span class="badge bg-primary rounded-pill me-2">١</span>
        <h5 class="mb-0">{{ t('dashboard.Basic_Info', 'المعلومات الأساسية') }}</h5>
    </div>
    <div class="card-body">
        ...fields...
    </div>
</div>
```

### 16.7 Buttons

| Purpose | Class |
|---|---|
| Primary action (save, create) | `btn btn-primary` |
| Secondary / cancel | `btn btn-light` |
| Danger (delete) | `btn btn-danger` |
| Small action icons in tables | Icon only with hover color |

Do not use `btn btn-secondary`, `btn btn-outline-*`, or Bootstrap utility buttons — these do not match the project's design system.

### 16.8 Icons

Use Tabler Icons: `<i class="ti ti-{name}"></i>`. Do not use Font Awesome or Bootstrap Icons.

Common icons:
- Edit: `ti-pencil`
- Delete: `ti-trash`
- Add: `ti-plus`
- Save: `ti-device-floppy`
- View: `ti-eye`
- Back: `ti-arrow-right` (RTL)
- Search: `ti-search`
- Users: `ti-users`

### 16.9 Empty State

Every table must have a `@forelse / @empty` block with a professional empty state:

```blade
@forelse($items as $item)
    <tr>...</tr>
@empty
    <tr>
        <td colspan="X">
            <div class="flex flex-col items-center py-16 text-gray-400">
                <i class="ti ti-inbox" style="font-size:48px;"></i>
                <p class="mt-3 font-medium text-gray-600">
                    @if($search)
                        {{ t('dashboard.No_Results', 'لا توجد نتائج') }}
                    @else
                        {{ t('dashboard.No_Records', 'لا توجد سجلات بعد') }}
                    @endif
                </p>
            </div>
        </td>
    </tr>
@endforelse
```

### 16.10 Per-Page Selector

All index pages include a per-page selector:

```blade
<select name="per_page" class="form-select w-auto" onchange="this.form.submit()">
    @foreach([10, 25, 50] as $size)
        <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
    @endforeach
</select>
```

### 16.11 KPI Card Icons — Avoid Bootstrap `bg-opacity-*`

Bootstrap `bg-opacity-*` utility classes conflict with Tailwind and produce incorrect results. Use inline styles for KPI icon backgrounds:

```blade
{{-- ✅ Correct --}}
<div class="flex items-center justify-center rounded-xl text-indigo-600"
     style="width:48px;height:48px;background:#eef2ff;">
    <i class="ti ti-users" style="font-size:22px;"></i>
</div>

{{-- ❌ Wrong --}}
<div class="bg-indigo-600 bg-opacity-10 rounded-xl">
```

### 16.12 Status Badges — Tailwind, Not Bootstrap `badge`

Status badges must use Tailwind utility classes:

```blade
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
    {{ t('dashboard.Status_Active', 'نشط') }}
</span>
```

---

## 17. Technical Debt Rules

The following known inconsistencies exist in the codebase. Each has a rule for how to handle it until resolved.

| # | Debt | Location | Rule |
|---|---|---|---|
| **TD-1** | `subscriptions.price` is `decimal(10,2)` (dollars), not integer cents | `subscriptions` table | Never read it without a comment noting the unit. All new price columns use integer cents. Pending ADR-003 for migration. |
| **TD-2** | `Testimonial` model uses `feedbacks` / `feedback_translations` tables | `app/Models/Testimonial.php` | Always use the `Testimonial` model; never query `feedbacks` directly. Do not rename without a planned migration. |
| **TD-3** | `sites` table is legacy — superseded by `subscriptions` | `sites` table | Do not add new columns or logic to `sites`. New features use `subscriptions`. |
| **TD-4** | `clients.avatar` stores a file path string, not a `media` ID | `clients` table | Never use `clients.avatar` as a pattern for new media fields. All new fields use media IDs. |
| **TD-5** | `$fields` vs `$data` variable naming in Section Blade views | Section Blade files | Use `$data` (canonical). `$content` is an alias. `$fields` does not exist. Requires ADR before renaming. |
| **TD-6** | `page_builder_structures` table contains archived GrapesJS data | `page_builder_structures` | Do not query or extend. Archived. |
| **TD-7** | `subscription_pages`, `subscription_sections`, `subscription_section_translations` were dropped | Dropped in `2026_03_27` | Do not reference these tables anywhere. |
| **TD-8** | Legacy Livewire components exist in the repository | `app/Http/Livewire/` (if present) | Do not introduce new Livewire-based features or dependencies until an architecture review formally approves Livewire as a supported platform component. Status: **Needs Verification**. |

**Rule for all Technical Debt:** Do not work around the debt silently. Comment the debt reference at the point of contact:

```php
// Technical Debt #1 — subscriptions.price is stored as decimal dollars (see docs/22-coding-standards.md TD-1)
$priceCents = (int) round($subscription->price * 100);
```

---

## 18. ADR Process

Architectural Decision Records live in `docs/adr/`. An ADR is required before changing any of the following:

- Canonical data type for money storage (e.g., migrating TD-1)
- Section Blade variable naming (`$data` → `$fields` or similar)
- Auth guard strategy (adding a new guard, merging guards)
- Translation storage strategy (database vs file-based)
- Switching any standard library (Tailwind, Alpine.js, GSAP, etc.)

### 18.1 ADR Format

```markdown
# ADR-{number}: {Title}

**Status:** Proposed | Accepted | Rejected | Superseded by ADR-{n}
**Date:** YYYY-MM-DD
**Deciders:** {names or roles}

## Context

Why is this decision needed? What is the current state?

## Decision

What are we choosing to do?

## Consequences

What changes as a result? What gets easier? What gets harder?

## Alternatives Considered

What else was evaluated and why was it rejected?
```

### 18.2 Pending ADRs

| ADR | Topic | Status |
|---|---|---|
| ADR-003 | Migrate `subscriptions.price` from decimal dollars to integer cents | Proposed |
| ADR-004 | Canonicalize Section Blade variable naming (`$data` vs `$fields`) | Proposed |

---

## 19. Code Review Checklist

Use this checklist when reviewing any pull request touching admin UI, controllers, or models.

### Controller
- [ ] `$this->authorize()` called before any data access?
- [ ] No business logic in the controller?
- [ ] `->withQueryString()` on paginated queries?
- [ ] Flash key is `ok` or `error` — not `success`?
- [ ] All variables passed via `compact()` and received by the view?

### Model
- [ ] Money columns cast as `integer` (cents)?
- [ ] Enum-like columns have constants?
- [ ] Scopes declared `static`?
- [ ] Translation relationship uses the standard `translation()` helper?

### Blade
- [ ] No Eloquent calls in the view?
- [ ] `t()` used for all user-visible text — no `__()` or hardcoded strings?
- [ ] Technical fields have `dir="ltr" font-mono`?
- [ ] `is_active` and similar boolean fields use radio buttons with strict comparison?
- [ ] `<script>` blocks are inside `<x-dashboard-layout>`?
- [ ] Empty state handles both "no records" and "no search results" cases?

### Database
- [ ] New price columns use integer cents?
- [ ] Migration adds indexes for FK columns?
- [ ] No hardcoded DB credentials?

### Translation
- [ ] All new keys added to the appropriate Seeder?
- [ ] Key format follows `section.Snake_Case`?
- [ ] `t()` used — not `__()`?

### Authorization
- [ ] Policy registered in `AppServiceProvider`?
- [ ] FormRequest `authorize()` uses Policy, not raw Gate?

### Technical Debt
- [ ] No new `decimal` price columns?
- [ ] No new path-string media fields?
- [ ] No `$fields` in Section Blade views?
- [ ] No `session('success')` flash keys?

---

## 20. Related Documents

| Document | Contents |
|---|---|
| [00-project-overview.md](./00-project-overview.md) | Project purpose, tech stack, team context |
| [01-system-architecture.md](./01-system-architecture.md) | Domain boundaries, request flows, platform layers |
| [03-database-architecture.md](./03-database-architecture.md) | Full schema reference, ER diagram, migration timeline |
| [07-section-definitions.md](./07-section-definitions.md) | Section Definition system deep dive |
| [09-rendering-flow.md](./09-rendering-flow.md) | Frontend rendering pipeline |
| [docs/adr/](./adr/) | Architectural Decision Records |
| [CLAUDE.md](../CLAUDE.md) | Session-by-session change log (project instructions) |
