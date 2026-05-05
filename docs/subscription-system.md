# Subscription System

## Overview

The subscription system manages hosting subscriptions (cPanel/WHM accounts) for clients.
It bridges the billing layer (orders → invoices) with the provider layer (WHM API → cPanel
account). It also drives per-tenant site building, domain verification, and WordPress
provisioning.

---

## Core Files

| File | Role |
|------|------|
| `app/Models/Tenancy/Subscription.php` | Eloquent model — domain logic, helpers |
| `app/Http/Controllers/Admin/Management/SubscriptionController.php` | Admin CRUD + WHM actions |
| `app/Policies/Tenancy/SubscriptionPolicy.php` | Authorization — role-based for admins, ownership for clients |
| `app/Services/Tenancy/SubscriptionSyncService.php` | WHM `createacct` / `removeacct` with retry |
| `app/Jobs/ProvisionSubscription.php` | Queued job → `TenantProvisioningService::provision()` |
| `app/Jobs/SyncSubscriptionToProvider.php` | Queued job for sync |
| `app/Jobs/TerminateSubscriptionOnProvider.php` | Queued job for terminate |
| `app/Services/Tenancy/DomainVerificationService.php` | DNS/SSL verification |
| `database/migrations/*_create_subscriptions_table.php` | Base schema |
| `database/migrations/2026_05_05_000001_add_soft_deletes_to_subscriptions_table.php` | Adds `deleted_at` |
| `resources/views/dashboard/management/subscriptions/` | Admin Blade views |

---

## Data Model

### `subscriptions` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `client_id` | bigint FK | Nullable — retained on client delete |
| `plan_id` | bigint FK | |
| `server_id` | bigint FK nullable | The WHM server hosting this subscription |
| `status` | varchar | `pending`, `active`, `suspended`, `cancelled` |
| `provisioning_status` | varchar | `pending`, `provisioning`, `active`, `failed` |
| `username` | varchar | cPanel account username (max 16 chars, alphanumeric) |
| `domain_name` | varchar nullable | Primary domain |
| `domain_option` | varchar | `new`, `subdomain`, `existing` |
| `subdomain` | varchar nullable | Subdomain slug (used when `domain_option=subdomain`) |
| `server_package` | varchar nullable | WHM package name — always derived from `plan.server_package` |
| `price` | decimal | Subscription price |
| `starts_at` / `ends_at` | date | Billing period |
| `next_due_date` | date nullable | Next renewal date |
| `domain_verification_status` | varchar | `pending`, `dns_pending`, `ssl_pending`, `active`, `failed` |
| `theme_settings` | json nullable | Per-tenant brand settings (colors, fonts) |
| `settings` | json nullable | Arbitrary subscription settings |
| `last_sync_message` | text nullable | Last WHM API response message |
| `deleted_at` | timestamp nullable | Soft-delete column |

---

## Status Machine

### Subscription status

```
pending ──► active
pending ──► suspended
active  ──► suspended
suspended ──► active      (unsuspend)
active  ──► cancelled     (terminate)
```

### Provisioning status

```
pending ──► provisioning ──► active
provisioning ──► failed
```

Status transitions happen via:
- `updateStatus()` — single record from admin UI
- `bulk()` — batch via admin UI
- `ProvisionSubscription` job — background via queue
- `SubscriptionSyncService` — after successful WHM API call

---

## Authorization

### Admin Users

Uses role-slug pattern (same as all other controllers):

| Controller method | Required role slug |
|-------------------|--------------------|
| `index`, `syncLogs` | `subscriptions.view-any` |
| `create`, `store`, `suggestUsername` | `subscriptions.create` |
| `edit`, `update` | `subscriptions.update` |
| `destroy` | `subscriptions.delete` |
| `bulk` | `subscriptions.bulk` |
| `syncWithProvider`, `provision`, `verifyDomain`, `cpanelLogin`, `suspendToProvider`, `unsuspendToProvider`, `terminateToProvider`, `installWordPressManual` | `subscriptions.manage` |

`super_admin` users bypass all role checks.

### Client Users

Clients can only access their **own** subscription — enforced in `SubscriptionPolicy` via
`client_id` ownership check. Bulk and manage actions are admin-only.

---

## WHM API Integration

All WHM calls go through port `2087` with `WHM` token authentication:

```
Authorization: whm {server.username}:{server.api_token}
```

### SSL Verification (P7 fix)

SSL verification is now **configurable** via `config/services.php`:

```php
// config/services.php
'whm' => [
    'ssl_verify' => env('WHM_SSL_VERIFY', true),
],
```

Set `WHM_SSL_VERIFY=false` in `.env` for self-signed certificates in development.
**Never disable in production.**

### Sync Flow

`SubscriptionSyncService::sync()` handles account creation on WHM:

1. Validates `server_package` is set (aborts with message if missing)
2. Generates up to 5 username candidates (current → client slug + id → palgoal{id} → suffixed)
3. Attempts `createacct` for each candidate, breaking on success
4. On success: persists the resolved username back to the subscription record
5. Returns a human-readable message stored in `last_sync_message`

### Password Policy (P4 fix)

When `$subscription->password` is null, a **random** password is generated:
```php
\Illuminate\Support\Str::random(14) . '!A9'
```
No hard-coded fallback passwords are used.

### Private `whmCall()` Helper

`SubscriptionController::whmCall()` consolidates the cURL boilerplate for
`suspendacct`, `unsuspendacct`, and `removeacct`:

```php
$result = $this->whmCall($subscription, 'suspendacct', ['user' => $sub->username, 'reason' => '...']);
if ($result['ok']) { ... }
```

---

## WordPress Installation

`installWordPressManual()` installs WordPress via `wp-cli` on the server running the PHP
process. **Security requirements:**

- `$subscription->username` is sanitized to `[a-z0-9]` max 16 chars before being
  used in any shell command.
- All values passed to `exec()` are wrapped in `escapeshellarg()`.
- The WordPress admin password is randomly generated per install.
- The server must have `wp-cli` at `/usr/local/bin/wp`.

**This feature requires the web server process to have write access to `/home/{user}/public_html`.**
Consider running it as a queued job with the correct system user in production.

---

## Domain Verification

`DomainVerificationService::verify()` checks DNS propagation and SSL status for custom
domains. Results are stored on the subscription:

- `domain_verification_status` — current step (`dns_pending`, `ssl_pending`, `active`, `failed`)
- `domain_last_checked_at` — timestamp of last check
- `domain_verified_at` — timestamp when `active` was first reached
- `domain_verification_error` — last error message

`requiresDomainVerification()` returns `false` for subdomains and platform-hosted domains,
so only genuinely custom external domains go through the verification flow.

---

## Soft Deletes

The `Subscription` model now uses `SoftDeletes`. `destroy()` and bulk `delete` mark
records with a `deleted_at` timestamp rather than permanently removing them.

To restore a soft-deleted subscription:
```php
Subscription::withTrashed()->find($id)->restore();
```

To permanently purge (use with caution — all related orders/invoices reference this record):
```php
Subscription::withTrashed()->find($id)->forceDelete();
```

---

## Search & Filtering

All LIKE queries in `index()` and `syncLogs()` escape wildcards with `addcslashes()`:

```php
$qLike = '%' . addcslashes($q, '%_\\') . '%';
```

Supported filters on `index()`: `q` (domain + client name/email), `domain`, `status`, `sort`, `direction`.
Supported filters on `syncLogs()`: `q`, `server_id`, `from` (date), `to` (date).

---

## Known Gaps / Future Work

- **`suggestUsername()` is TOCTOU** — uniqueness is checked in PHP, not enforced by a
  DB `UNIQUE` constraint on `subscriptions.username`. Add a unique index if usernames
  must be globally unique.
- **cPanel password storage** — `$subscription->password` is stored in plaintext if set.
  Consider encrypting via Laravel's `encrypt()` / `decrypt()`.
- **`installWordPressManual()` runs synchronously** — on slow servers this will time out
  the HTTP request. Consider dispatching to a queued job.
- **`SubscriptionSyncService`** does not emit Laravel events after success/failure.
  Add `SubscriptionSynced` / `SubscriptionSyncFailed` events for downstream listeners
  (e.g., notifications, audit logs).
- **`cpanel-login` uses GET** — the login URL endpoint is a GET route, which means the
  WHM session token appears in browser history and server access logs. Consider using
  POST + redirect.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-05 | Authorization added to all 16 controller methods; SubscriptionPolicy repaired for non-super-admin users; `bulk()` policy method added; RCE fixed in `installWordPressManual()` via `escapeshellarg()`; dead code removed; XSS fixed (`connection_result` now escaped); hard-coded `TempPass!123` removed — random password generated instead; SSL verification now configurable via `WHM_SSL_VERIFY` env; `syncWithProvider()` delegates to `SubscriptionSyncService`; cURL boilerplate extracted to `whmCall()` helper; `Client::all()` / `Plan::all()` replaced with column-scoped selects; `SoftDeletes` added to `Subscription` model + migration; LIKE wildcard escaping added to `index()` and `syncLogs()`. |
