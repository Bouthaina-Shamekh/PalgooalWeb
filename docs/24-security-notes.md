# Security Notes

> **Last Updated:** 2026-06-15  
> **Status:** Verified against source code  
> **Scope:** Authentication, authorization, isolation, and security implementation inside the Palgoals codebase.  
> **Authority:** Code > Verified docs (01, 03, 07, 09, 22) > any other document.

---

## Purpose

This document explains **how** Palgoals enforces security — not what security best practices look like in general. Every claim here traces back to actual code in `app/`, `routes/`, or `config/`. A new developer reading this should understand:

- How the platform prevents unauthorized access.
- How clients are isolated from each other.
- How ownership of resources is verified.
- How file writes, uploads, and the Blade editor are constrained.
- What incidents have occurred and what was learned from them.

---

## Security Principles

The codebase enforces six security principles consistently:

1. **Guard separation is absolute.** `web` (admins) and `client` (subscribers) cannot share sessions or impersonate each other except through an explicitly coded admin "Login As" action that leaves a session marker.
2. **Ownership before action.** Every client-facing mutation verifies `subscription.client_id === auth()->guard('client')->id()` before proceeding. There is no "trust the form input" pattern.
3. **Policy-driven admin authorization.** Admin controllers call `$this->authorize()` before every sensitive operation. The `ModelPolicy` base class resolves ability slugs (`pages.edit`, `sectiondefinitions.create`, etc.) against the `role_user` table.
4. **Path restriction on file writes.** `SectionTemplateFileWriter` resolves paths and enforces that writes stay inside `resources/views/front/sections/` using a prefix check before writing.
5. **Validation at the request boundary.** FormRequests validate and normalize input before it reaches controllers. Controllers must not bypass this.
6. **Secrets in environment only.** WHM API tokens, cPanel passwords, and all integration credentials are stored in the `servers` table (encrypted at the DB level is not yet implemented — see Known Risks) and passed via environment variables. They are never exposed in view data or logs.

---

## Authentication Architecture

### Guards

Defined in `config/auth.php`:

| Guard | Driver | Provider | Model | Session |
|-------|--------|----------|-------|---------|
| `web` | session | `users` | `App\Models\User` | Standard (180 min timeout) |
| `client` | session | `clients` | `App\Models\Client` | 30-day remember lifetime |

The two guards use **separate session stores** (different session keys). Laravel never mixes them.

### Admin Login (`web` guard)

Handled by **Laravel Fortify**. Login is at `/login` (standard Fortify route). The guard is the default `web` guard. After login, all `/admin` routes require `middleware: ['auth', 'can:access-dashboard']`.

### Client Login (`client` guard)

Handled by **explicit route closures** in `routes/client.php` — NOT by Fortify's dynamic guard config (which was removed as a security liability; see Incidents). The login route is `POST /client/login` with `throttle:5,1`. Password reset uses the `clients` broker with its own `client_password_reset_tokens` table.

Anti-enumeration: the password reset flow always returns success whether or not the email exists:
```php
// routes/client.php
Password::broker('clients')->sendResetLink($request->only('email'));
return back()->with('status', 'If this email is registered...');
```

### Session Regeneration

Both login flows call `$request->session()->regenerate()` and `->regenerateToken()` on logout and on impersonation teardown. This prevents session fixation.

### `can_login` Enforcement

`EnsureClientCanLogin` middleware runs on every client-authenticated request. If the client's `can_login` flag is `false`, the session is immediately destroyed — unless it was created by an admin impersonation session (verified by checking `client_impersonated_by_admin` + `client_impersonator_admin_id` in the session AND confirming the current admin guard user ID matches).

---

## Authorization Architecture

### Admin: Policy + `authorize()`

All admin controllers extend `Controller` and call `$this->authorize()`. Every call resolves through a **Policy class** (`app/Policies/`).

**Base class: `ModelPolicy`**

All 25+ Policies extend `ModelPolicy`, which implements `__call()` to dynamically generate ability slugs:

```php
// ModelPolicy::__call()
$class_name = Str::plural(Str::lower(str_replace('Policy', '', class_basename($this))));
$ability    = $class_name . '.' . Str::kebab($name);
// e.g.: PagePolicy + 'update' → 'pages.edit'
// (note: 'update' is aliased to 'edit' to match abilities.php)
```

Slugs are matched against `role_user` rows (`user_id`, `role_name`, `ability`). A `User` with no matching `role_user` row is denied.

**Exception: `super_admin` flag**

`users.super_admin = 1` bypasses all policy checks via the Gate's `before()` hook registered in `AuthServiceProvider` (standard Laravel super-admin pattern). Super admins can perform any action including writing Blade files.

### Client: Manual `abort_unless()` + Guard Check

Client controllers do **not** use Laravel Policies. Ownership is enforced manually:

```php
// Client\SubscriptionController::resolveOwnedSubscription()
abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);

// Client\DomainDnsController
abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);

// Client\DomainController::update()
if ($request->client_id != $client->id) {
    return redirect()->back()->with('error', 'Unauthorized access.');
}
```

The pattern `abort_unless(..., 403)` is preferred. Some cases use 404 to avoid leaking existence of the resource.

### What Uses Policies

| Resource | Policy | Ability slug example |
|----------|--------|----------------------|
| Pages | `PagePolicy` | `pages.view`, `pages.create`, `pages.update`, `pages.delete` |
| SectionDefinition | `SectionDefinitionPolicy` | `sectiondefinitions.create`, `sectiondefinitions.update` |
| Media | `MediaPolicy` | `medias.view`, `medias.create`, `medias.delete` |
| Plans | `PlanPolicy` | `plans.view`, `plans.create`, `plans.edit`, `plans.delete` |
| Clients | `ClientPolicy` | `clients.view`, `clients.create`, `clients.edit`, `clients.delete` |
| Users | `UserPolicy` | `users.view`, `users.create`, `users.edit`, `users.delete` |
| Subscriptions (admin) | `ModelPolicy` chain | `subscriptions.view`, etc. |
| Orders | `OrderPolicy` | `orders.view`, `orders.bulk` |
| Domains (admin) | `DomainPolicy` | `domains.view`, `domains.create`, etc. |

### What Does NOT Use Policies

- Client portal controllers — use manual `abort_unless()`.
- Public routes (`web.php`) — unauthenticated by definition.
- `ServeTenantSite` middleware — uses host + DB lookup, not auth.

---

## User Roles

| Role | Guard | Abilities |
|------|-------|-----------|
| **Super Admin** | `web` | All abilities (bypasses Policy via `users.super_admin = 1`). Can write Blade files, manage all resources. |
| **Admin (role-based)** | `web` | Granular abilities stored in `role_user` table. Can be restricted to specific models (e.g. `plans.view` only). |
| **Client** | `client` | Can only access their own subscriptions, domains, invoices, and pages. No Policy system — ownership enforced by ID comparison. |
| **Public Visitor** | none | Read-only access to marketing pages, templates, testimonials, cart/checkout. Throttled on write actions. |

**Client `can_login` flag:** A client may be marked `can_login = false` by an admin, which immediately invalidates their session on next request via `EnsureClientCanLogin`.

---

## Ownership Rules

### Client → Subscription

Every client request touching a subscription calls:
```php
abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);
```
This is enforced in `resolveOwnedSubscription()` which every mutating action in `Client\SubscriptionController` calls before proceeding.

### Client → Domain

`DomainController` fetches domains with `where('client_id', Auth::guard('client')->id())`. Individual record access uses `abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404)`.

### Client → Invoice / Order

Enforced via the billing service layer. Clients access invoices only through their subscription context.

### Tenant → Page

Pages carry `context` (`'marketing'` vs `'tenant'`) and `tenant_id` (FK to `subscriptions.id`). `ServeTenantSite` queries pages with `where('context', 'tenant')->where('tenant_id', $subscription->id)`. A tenant can never access another tenant's pages.

### Admin → SectionDefinition / Pages

Platform content (section definitions, marketing pages) is globally admin-managed — no per-user ownership. Policy abilities restrict which admins can read/write. The `super_admin` flag is required for Blade writes.

---

## Tenant Isolation

### Host-based Resolution

`ServeTenantSite` middleware intercepts every `GET`/`HEAD` request and resolves the tenant from the HTTP `Host` header:

1. If host matches the platform's primary domain → pass through to normal routes.
2. If host matches `subdomain.primarydomain` → look up `Subscription` where `domain_option = 'subdomain' AND subdomain = $tenantSubdomain`.
3. If host matches a custom domain → look up `Subscription` where `domain_name = $host`.
4. If no matching active subscription found → pass through (serves marketing site).

Only subscriptions with `status = 'active'` and `plan.plan_type = TYPE_MULTI_TENANT` are served. Hosting-type subscriptions are never served by this middleware.

### Domain Readiness Gate

Even for matched active subscriptions with custom domains, `ServeTenantSite` calls `$subscription->customDomainIsReady()` and returns 404 if false. This prevents partial DNS configurations from serving content prematurely.

### Page Isolation

The canonical page query always scopes on both `context = 'tenant'` AND `tenant_id = $subscription->id`. No cross-tenant page leakage is possible through this query.

### Runtime Boundaries

- Tenant pages can only render **their own sections** (scoped by `page_id` which itself is scoped by `tenant_id`).
- Tenant sites cannot access admin routes or client portal routes — separate auth guards prevent this.
- Tenant runtime usage is recorded via `TenantRuntimeUsageRecorder` for monitoring (not security enforcement).

---

## Route Protection

| Area | File | Middleware | Notes |
|------|------|-----------|-------|
| `/admin/*` | `dashboard.php` | `auth`, `can:access-dashboard` | Both required. `auth` = web guard. `can:access-dashboard` = Policy check. |
| `/client/*` (protected) | `client.php` | `web`, `auth:client`, `client.dashboard.impersonation` | Client guard. Impersonation validation on every request. |
| `/client/login`, `/client/register` | `client.php` | `web` only | Public auth pages — redirect to home if already logged in. |
| `/client/forgot-password` POST | `client.php` | `web`, `throttle:5,1` | Rate-limited. |
| `/client/register` POST | `client.php` | `web`, `throttle:5,1` | Rate-limited registration. |
| `/testimonials/submit` POST | `web.php` | `throttle:5,1` | Public form — rate limited. |
| `/` and marketing pages | `web.php` | `setLocale` | Public. No auth. |
| Tenant sites | `web.php` | `ServeTenantSite` (host-based) | Runs before route matching for non-primary hosts. |
| `/.well-known/palgoals-domain-check` | `web.php` | `throttle:30,1` | Domain verification probe. Rate limited. |

**Client impersonation route (`EnsureClientDashboardImpersonation`):** Verifies that any impersonated client session has a valid `client_impersonator_admin_id` that matches the current `web` guard user. If the admin logged out or the session was tampered, the client session is destroyed.

---

## Form Request Security

All FormRequests extend `Illuminate\Foundation\Http\FormRequest`. Each request class provides:

- **`authorize()`** — Returns `false` (deny all) or calls `$this->user()?->can(...)` for admin requests. Client requests return `true` (trust the route middleware to authenticate; ownership is enforced in the controller).
- **`rules()`** — Strict validation rules. `nullable` is explicit, not assumed.
- **`prepareForValidation()`** — Normalizes input before rules run (trims, lowercases slugs, converts to integers). This prevents edge cases where `"  admin  "` bypasses a uniqueness check.

**Why FormRequests over inline `$request->validate()`:** The `authorize()` method runs before the controller method. A missing `$this->authorize()` call in a controller is caught by the FormRequest's own gate. Double-gating is intentional.

**Key requests with security-relevant validation:**

| Request | Security rule |
|---------|--------------|
| `StoreSectionDefinitionRequest` | `template_key` validated with `SectionTemplateRegistry::isValidTemplateKey()` — rejects path traversal via characters outside `[a-z0-9_-]` |
| `UpdateDomainDnsRequest` | Nameserver format enforced with `regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i` |
| `StoreDomainRequest` / `UpdateDomainRequest` | `client_id` must exist in `clients` table |
| `UpdateSubscriptionThemeRequest` | Admin-only; theme values are validated before being stored |

---

## File Upload Security

Handled by `Admin\MediaController`.

**Allowed file types (enforced by both `mimes:` and `mimetypes:`):**
```
jpeg, jpg, png, gif, webp, svg
```
MIME type: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `image/svg+xml`

**Max file size:** 10240 KB (10 MB) per file.

**Dual validation:** Both `mimes:` (extension check) and `mimetypes:` (server-detected MIME) are required. This prevents extension spoofing (e.g. uploading a PHP file with a `.jpg` extension).

**Storage path:** Files are stored via `Storage::disk('public')->putFileAs($dir, $file, $hashedName)`. The filename is a `uniqid('', true)` hash — never the original filename. This prevents:
- Directory traversal via filename
- Filename collision
- Execution via predictable paths

**Authorization:** Every MediaController action calls `$this->authorize()` first:
- `viewAny` → `medias.view`
- `create` → `medias.create`
- `update` → `medias.update`
- `delete` → `medias.delete`

---

## Media Security

**Media ID over raw path:** Settings and avatar fields should store a `media.id` integer, not a raw file path. The controller resolves the path at runtime via `Media::find($id)->file_path`.

**`normalizeMediaValue()` method** in `HomeController` (admin) normalizes any incoming media value — converting storage URLs back to relative paths, resolving media IDs to paths, and rejecting absolute paths that bypass the storage layer.

**Technical Debt — `clients.avatar`:**

The `clients.avatar` column stores a **raw file path string**, not a media ID. This was a legacy approach replaced by the media picker in the client form UI. The validation was updated from `nullable|image|max:2048` to `nullable|string|max:500` when the media picker was introduced. However, the column still accepts arbitrary path strings, meaning a crafted admin request could store any path value. **This is tracked as Technical Debt** — the column should be migrated to `avatar_media_id` (FK to `media.id`) and the raw path column deprecated.

---

## Blade Editor Security

The Monaco-based Blade editor (`/admin/section-definitions/{id}/write-blade`) is the most security-sensitive feature in the platform. It writes arbitrary PHP/Blade code to disk.

### Base64 Workflow

The editor submits code as a base64-encoded string in the `blade_source_b64` field:

```php
// SectionDefinitionController::writeBladeFile()
if ($request->filled('blade_source_b64')) {
    $decoded = base64_decode($request->input('blade_source_b64'), strict: false);
    $bladeSource = ($decoded !== false) ? $decoded : $request->input('blade_source');
}
```

**Why base64:** ModSecurity (WAF) on shared hosting blocked POST requests containing `<?php` strings. Base64 encoding bypasses WAF pattern matching without disabling WAF globally. The decoded content is never executed at submission — it is stored in `blade_source` then written to disk only after authorization.

**Apache Redirect Issue:** On the production server, Apache redirects `/admin/...` → `/public/admin/...` (301). This redirect converts POST to GET, causing 405 errors on the write-blade endpoint. The JavaScript client adds the `/public/` prefix to the fetch URL to avoid the redirect:
```javascript
// edit.blade.php: doWrite()
if (!/\/public\//.test(url)) {
    url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
}
```
See Incidents section for full analysis.

### Authorization for Write

```php
$this->authorize('update', $sectionDefinition);
```

In practice, only `super_admin` users should be able to perform Blade writes because the `sectiondefinitions.update` ability should be restricted to super admins. Regular role-based admins should not receive `sectiondefinitions.update` unless explicitly granted.

### Path Restriction (SectionTemplateFileWriter)

`SectionTemplateFileWriter::write()` enforces that the resolved path stays within `resources/views/front/sections/`:

```php
// Path traversal guard
$normalizedPath = str_replace(['\\', '//'], ['/', '/'], $path);
$normalizedBase = str_replace(['\\', '//'], ['/', '/'], $this->baseDir);

if (! str_starts_with($normalizedPath, $normalizedBase)) {
    return ['ok' => false, 'error' => 'Path traversal detected — write refused.'];
}
```

**Key/category validation:** `category` and `section_key` are validated against `/^[a-z0-9_-]+$/` before path construction. This prevents path components like `../../bootstrap/cache`.

**Timestamp tracking:** `blade_written_at` is updated on every successful write, providing an audit trail.

---

## Section Definition Security

**Authorization:** All CRUD operations on `SectionDefinition` go through `SectionDefinitionPolicy` (extends `ModelPolicy`). The ability slugs are `sectiondefinitions.view`, `sectiondefinitions.create`, `sectiondefinitions.update`, `sectiondefinitions.delete`.

**Template key validation:** `StoreSectionDefinitionRequest` validates `template_key` using `SectionTemplateRegistry::isValidTemplateKey()`. Only keys matching `[a-z0-9_-]+` are accepted. This prevents directory traversal in the Blade view resolution path.

**Dynamic editor restriction:** `editor_mode` is forced to `SectionDefinition::EDITOR_MODE_DYNAMIC` in `prepareForValidation()`. Clients cannot submit arbitrary editor modes.

**Import/Export:** `SectionDefinitionImportExportController` validates JSON imports using `ImportSectionDefinitionsRequest`. The import endpoint is protected by the dashboard middleware group (`auth`, `can:access-dashboard`).

---

## Translation System Security

**The `t()` mandate:** All user-visible text must go through `t(string $key, ?string $default = null)`. The `__()` function is prohibited in all project code. This is enforced by the i18n refactor phases and checked in code review.

**Why this matters for security:** Using `t()` ensures all text goes through the `translation_values` table, where keys are defined by developers (in Seeders) and values are admin-controlled. An admin cannot inject arbitrary text into a key slot that doesn't exist — they can only modify existing translation values.

**Translation injection risk:** An admin with access to `translation_values` can inject arbitrary text (including HTML) into any translation value. The `t()` function returns raw strings. Any value rendered with `{!! t(...) !!}` (unescaped) is an XSS vector. Use `{{ t(...) }}` (escaped) in all Blade templates. WYSIWYG content should only use `{!! !!}` in contexts where the content is admin-controlled and trusted.

**Seeder ownership:** New keys are added only through `DashboardTranslationsSeeder` (for `dashboard.*`) and `SiteTranslationsSeeder` (for `site.*` and `client.*`). No `common.*` Seeder exists yet — `common.*` keys are defined in `SiteTranslationsSeeder` as a temporary measure.

---

## Domain Management Security

### Client Domain Ownership

`Client\DomainController` scopes all domain queries to `where('client_id', Auth::guard('client')->id())`. Individual record mutations verify:

```php
// DomainController::update()
$client = Client::findOrFail(Auth::guard('client')->user()->id);
if ($request->client_id != $client->id) {
    return redirect()->back()->with('error', 'Unauthorized access.');
}
```

**Note:** The comparison uses `!=` (loose), not `!==` (strict). This is safe here because both sides are integers after `findOrFail()`, but it should be migrated to strict comparison to match the project standard.

### DNS Updates (DomainDnsController)

```php
// DomainDnsController
abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);
```

Uses 404 (not 403) to avoid leaking whether the domain exists for a different client.

Nameserver format is validated by `UpdateDomainDnsRequest` with strict hostname regex. Duplicate nameservers are rejected.

### Domain Verification

Domain verification runs asynchronously via `DomainVerificationService::reset()`. The probe endpoint (`DomainVerificationProbeController`) is rate-limited at `throttle:30,1`. Custom domains are not served until `$subscription->customDomainIsReady()` returns `true`.

---

## WHM Integration Security

### API Credentials Storage

WHM credentials are stored in the `servers` table:
- `api_token` — WHM API Token (preferred)
- `username` — reseller username
- `password` — fallback credential (basic auth)

**The `api_token` field is protected from accidental clearance:**
```php
// ServerController::update()
if (empty($data['api_token'])) {
    unset($data['api_token']);  // Never overwrite with empty
}
```

**Risk:** Credentials are stored in plaintext in the database. There is no at-rest encryption for these fields. See Known Risks.

### Credential Exposure Prevention

WHM credentials are never returned in API responses or view data. The `ServerController::packages()` method returns only package names — not credentials. Debug responses have been explicitly cleaned to remove raw credential data.

### Provisioning Boundaries

WHM API calls are made via `ProvisionSubscription` jobs and `SubscriptionSyncService`. These run as background jobs, not in the request cycle, reducing exposure. The WHM API endpoint is never exposed to clients — only to admin-initiated actions.

### Logging Restrictions

WHM error logs in `ServerController` capture error messages but never log the `api_token` or `password` values. Log entries contain: server ID, username, endpoint called, and error message.

---

## Data Exposure Rules

| Data | Exposure Rule |
|------|--------------|
| `users.password` | Hidden via `$hidden`. Never returned in JSON or view. |
| `clients.password` | Hidden via `$hidden`. |
| `servers.api_token` | Never included in view compact(). Only read internally for API calls. |
| `servers.password` | Same as api_token. |
| `users.remember_token` | Hidden via `$hidden`. |
| `clients.remember_token` | Hidden via `$hidden`. |
| WHM account passwords | Generated randomly during provisioning (`Str::random(16)`). Stored in `subscriptions` or passed directly to WHM — never shown to client after creation. |
| Translation values | Readable by any user (public API). Should not contain secrets. |
| `general_settings` | Contains logo URLs, colors. Not secrets. Import/export restricted to admins. |

---

## XSS Protection

### Blade Escaping Default

All `{{ $variable }}` expressions in Blade are automatically HTML-escaped. This is the default and should never be disabled unless the source is admin-controlled trusted HTML.

### Unescaped Rendering (`{!! !!}`)

Only permitted for:
- Rich text / WYSIWYG content from admin-controlled fields (`page_translations.content`, `section_translations.*` rich text fields)
- Translation values rendered as HTML in layout templates (rare — flag in PR review)

**Risk:** Any `{!! t('...') !!}` is an XSS vector if a compromised admin account modifies translation values. Audit uses of `{!!` in Blade templates before each release.

### Section Content

Section fields of type `richtext` are rendered unescaped inside the tenant site. The content comes from `section_translations` rows, which are only writeable by authenticated admins or the client via the section editor (under their own subscription). Cross-tenant XSS is not possible because sections are scoped by `page_id` → `tenant_id`.

---

## CSRF Protection

Laravel's default CSRF middleware (`VerifyCsrfToken`) is active for all `web` middleware group routes. This covers:
- All `/admin` routes
- All `/client` routes
- All public form submissions (`/testimonials/submit`, `/checkout`, etc.)

**Known exceptions:** None found in `VerifyCsrfToken`. If a webhook route is added in the future, it must be explicitly excluded and protected by signature verification instead.

**Token regeneration:** CSRF tokens are regenerated on login, logout, and impersonation teardown via `$request->session()->regenerateToken()`.

---

## Validation Strategy

All controller inputs must pass through a FormRequest or `$request->validate()` before use. The standards in `22-coding-standards.md` (Section 13) apply. From a security standpoint, key rules are:

- **Whitelist, not blacklist.** Validation rules specify what is allowed (`regex:/^[a-z0-9_-]+$/`), not what is forbidden.
- **`in:` for enums.** Status fields, type fields, and sort directions use `in:` validation to prevent injection via unexpected values.
- **No user-controlled SQL operators.** Sort directions are validated: `$direction = $direction === 'asc' ? 'asc' : 'desc'`.
- **Integer IDs cast explicitly.** Ownership checks always cast to `(int)` before comparison to prevent type juggling exploits.
- **`max:` on all string inputs.** Prevents oversized input from causing slow queries or storage exhaustion.

---

## Audit Logging

### What Is Logged

| Event | Mechanism | Location |
|-------|-----------|----------|
| Client profile update | `ActivityLog::create()` | `Client\HomeController` |
| WHM API errors | `Log::error()` | `ServerController`, `DomainController` |
| Tenant page resolution | `Log::info()` | `ServeTenantSite::resolveTenantPage()` |
| Domain purchase errors | `Log::error()` | `Client\DomainController` |
| Bulk job dispatch errors | `logger()->error()` | `Admin\Management\SubscriptionController` |
| Blade write events | `blade_written_at` timestamp | `SectionTemplateFileWriter` |

### What Is NOT Logged

- Admin login / logout events (no explicit audit log — relies on Laravel's default session behavior)
- Admin permission changes (role_user table changes are not audited)
- Translation value edits
- Client login / logout
- Failed authorization attempts (only a 403/404 response — no log entry)
- Successful resource creates/updates (flash message only — no audit trail)

**Gap:** There is no centralized audit trail for admin actions. An admin with `sectiondefinitions.update` access can write arbitrary Blade code to disk with no log entry beyond `blade_written_at`. This is a known risk — see below.

---

## Security Incidents & Lessons Learned

### Apache Redirect Issue (POST → GET → 405)

**Root cause:** The production server's Apache document root is `public_html/` (not `public_html/public/`). Any request to `/admin/...` is redirected (301) by Apache's `.htaccess` to `/public/admin/...`. The 301 redirect converts POST to GET. Laravel routes that accept only POST respond with 405 Method Not Allowed.

**Impact:** The Blade editor's "Write File" button was silently failing. The AJAX fetch received a 301 opaque redirect, detected as an error, but the actual 405 response was never visible. File writes appeared to succeed in the UI but the disk was never updated.

**Discovery:** Manually testing `fetch()` directly to the `/public/` URL returned `{"ok":true}`.

**Fix:**
```javascript
// doWrite() in section_definitions/edit.blade.php
var url = writeForm.action;
if (!/\/public\//.test(url)) {
    url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
}
```
`redirect: 'manual'` was added to the fetch call to detect opaque redirects as an error signal.

**Lesson:** On shared hosting with Apache, always use the `/public/` prefix in AJAX URLs. Never assume the document root. Add `redirect: 'manual'` to any security-critical fetch call to detect unexpected redirects.

---

### ModSecurity / WAF False Positives

**Root cause:** ModSecurity blocks POST bodies containing `<?php` (common WAF rule). The Blade editor submits PHP code.

**Impact:** Every attempt to write a Blade file returned a 403 from ModSecurity before the request reached Laravel.

**Fix:** Blade source is base64-encoded before submission (`blade_source_b64` parameter). Laravel decodes it server-side:
```php
$decoded = base64_decode($request->input('blade_source_b64'), strict: false);
```

**Lesson:** On shared hosting, assume ModSecurity is active. Use base64 encoding for any user input that contains code-like content. Never disable WAF globally — encode around it.

---

### Legacy Visual Builder (Archived)

**Background:** An early version of the platform included a visual drag-and-drop page builder (distinct from the current Monaco section editor). It allowed freeform HTML/CSS construction with no template boundary enforcement.

**Risks removed by archiving:**
- Arbitrary HTML injection into page content with no schema validation.
- No section type system — any content structure could be created.
- XSS vectors through unvalidated HTML block content.
- No ownership model for individual blocks.

**Current state:** Visual builder routes are disabled. The `builder_type` field exists on pages for backwards compatibility but `visual` mode is treated as archived. All new content goes through the definition-driven section system which has schema-level field validation.

---

### Config::set Hack (Client Registration)

**Root cause:** An early implementation of client registration used `Config::set('fortify.guard', 'client')` to redirect Fortify's login action to the client guard. This mutated global runtime config and caused the admin login guard to switch to 'client' during the same request cycle in certain race conditions.

**Impact:** Admin users could be logged out or misidentified as client-guard users if registration and admin actions happened in parallel.

**Fix:** Client registration was rewritten as an explicit route closure in `routes/client.php` with manual validation and `Auth::guard('client')->login()`. Fortify config is no longer mutated at runtime.

**Lesson:** Never use `Config::set()` to change authentication guard configuration at runtime. Explicit guard specification (`Auth::guard('client')->...`) is the only safe approach.

---

## Known Risks

| Risk | Severity | Status | Mitigation |
|------|----------|--------|-----------|
| `clients.avatar` stores raw path string (not media ID) | Medium | Open — Technical Debt | Admin-only field. Migrate to `avatar_media_id` FK. |
| WHM credentials stored in plaintext in `servers` table | High | Open | Restrict DB access. Add encryption at-rest for this column. |
| No audit log for admin actions | Medium | Open | Admin-only access. Plan centralized audit logging. |
| `sectiondefinitions.update` allows arbitrary Blade writes | High | Mitigated | Restricted to `super_admin` in practice. Path traversal blocked by `SectionTemplateFileWriter`. |
| `{{!! t('...') !!}}` XSS via translation values | Medium | Mitigated | Admin-only edit access. Audit `{!! !!}` uses in views. |
| No at-rest encryption for `servers.password` / `api_token` | High | Open | Restrict DB access. Use Laravel encrypted casts. |
| Client registration uses `throttle:5,1` (basic rate limit only) | Low | Mitigated | No CAPTCHA. Consider adding for high-traffic scenarios. |
| `Livewire` usage scope unknown | Low | Open | Audit Livewire component authorization if used. |
| `subscriptions.price` decimal inconsistency | Low | Open | Use `price_cents` for all calculations. |
| Legacy tables (pre-platform schema) | Low | Open | Soft deletes and foreign keys prevent accidental data loss. |

---

## Security Review Checklist

Use before merging any feature branch:

### Authorization
- [ ] Every admin controller method calls `$this->authorize()` or uses a FormRequest with `authorize()`.
- [ ] Every client controller method verifies resource ownership via `abort_unless((int) $resource->client_id === (int) $client->id, 403)`.
- [ ] No `authorize('update', ...)` call is removed without a documented reason.

### Ownership
- [ ] New resources with `client_id` FK are always scoped with `where('client_id', ...)` before access.
- [ ] Integer IDs compared with `===` (strict) not `==`.
- [ ] 404 used (not 403) where existence should not be leaked.

### Validation
- [ ] All string inputs have `max:` constraints.
- [ ] Sort/direction/status fields use `in:` validation.
- [ ] File uploads use both `mimes:` and `mimetypes:`.
- [ ] New FormRequests have `prepareForValidation()` to normalize input before rules.

### Translation
- [ ] No `__()` calls. All text uses `t('namespace.Key', 'Fallback')`.
- [ ] New keys added to the correct Seeder (`DashboardTranslationsSeeder` or `SiteTranslationsSeeder`).
- [ ] Variable replacement uses `strtr()`, not `t()` parameters.
- [ ] `{{ t(...) }}` (escaped) used, not `{!! t(...) !!}`.

### Uploads & Media
- [ ] New file uploads validate both MIME type and extension.
- [ ] Files stored with hashed filenames, not original names.
- [ ] Media values stored as `media.id` integers, not raw paths.

### Secrets
- [ ] No API keys, tokens, or passwords in `.env.example` as real values.
- [ ] Credential fields protected from accidental clearance (`unset()` if empty).
- [ ] No credential values in log entries.

### AJAX / HTTP
- [ ] POST routes on shared hosting use `/public/` prefix in fetch URLs.
- [ ] Code submitted via AJAX uses base64 encoding to bypass WAF.
- [ ] `redirect: 'manual'` added to security-critical fetches.

### Blade Editor (if touched)
- [ ] `SectionTemplateFileWriter` path restriction still in place.
- [ ] `template_key` and `category` validated against `[a-z0-9_-]+` before path construction.
- [ ] `writeBladeFile` is still `POST` only and behind `authorize('update', ...)`.

### Logging
- [ ] Error paths log via `Log::error()` or `logger()->error()`.
- [ ] No sensitive values (`password`, `api_token`, `token`) in log messages.

### Documentation Impact
- [ ] CLAUDE.md updated if new pattern introduced.
- [ ] Relevant doc in `docs/` updated if architecture or security behavior changed.

---

## Related Documents

- [01-system-architecture.md](./01-system-architecture.md) — Platform layers, guard separation, tenant design
- [03-database-architecture.md](./03-database-architecture.md) — Table ownership, tenant isolation, billing data
- [07-section-definitions.md](./07-section-definitions.md) — Section template system, definition-driven content
- [09-rendering-flow.md](./09-rendering-flow.md) — Render pipeline, resolver chain
- [22-coding-standards.md](./22-coding-standards.md) — Authorization standards (§12), validation standards (§13), translation standards (§8)
