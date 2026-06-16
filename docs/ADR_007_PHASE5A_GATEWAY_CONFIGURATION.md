# ADR-007 Phase 5A — Implementation Report
## Gateway Configuration Management

**Date:** 2026-06-17  
**Phase:** 5A of 5 (ADR-007)  
**Scope:** Database-driven gateway config, encrypted key storage, admin UI, PaymentManager upgrade — no gateway code, no checkout edits, no webhook edits, no settlement

---

## 1. Objective

Move payment gateway configuration from `.env` / hardcoded config to the database so that:

- API keys can be rotated from the admin panel without a deployment.
- Sandbox ↔ Live switching is a one-click operation in the UI.
- Adding a new gateway in Phase 5B requires zero application code changes for the config layer.
- API keys are never stored in plaintext anywhere.

---

## 2. New Files

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_17_100001_create_payment_gateways_table.php` | Creates `payment_gateways` table |
| `app/Models/PaymentGateway.php` | Eloquent model with encrypted casts + helpers |
| `app/Http/Controllers/Admin/PaymentGatewayController.php` | Admin CRUD + activate/deactivate actions |
| `resources/views/dashboard/settings/payments/index.blade.php` | Gateway list view |
| `resources/views/dashboard/settings/payments/edit.blade.php` | Gateway edit form |
| `database/seeders/PaymentGatewaySeeder.php` | Seeds `mock` (active) + `lahza` (inactive) rows |

---

## 3. Modified Files

| File | Change |
|------|--------|
| `app/Payments/PaymentManager.php` | Added DB-first resolution layer with config fallback |
| `routes/dashboard.php` | Added `settings/payments/*` route group |
| `resources/views/dashboard/layouts/partials/nav.blade.php` | Added "بوابات الدفع" link under Settings |
| `database/seeders/DashboardTranslationsSeeder.php` | Added 22 translation keys (`dashboard.Payment_*`, `dashboard.Keys_*`, etc.) |

---

## 4. How API Keys Are Managed

### Storage: encrypted at rest

The columns `public_key`, `secret_key`, and `webhook_secret` use Laravel's `encrypted` cast:

```php
protected $casts = [
    'public_key'     => 'encrypted',
    'secret_key'     => 'encrypted',
    'webhook_secret' => 'encrypted',
];
```

This encrypts with `AES-256-CBC` using `APP_KEY` before writing to the database, and decrypts transparently on read. The raw ciphertext in MySQL is unreadable without `APP_KEY`.

### Protection layers

| Layer | Mechanism |
|-------|-----------|
| At-rest encryption | `encrypted` cast — `AES-256-CBC` using `APP_KEY` |
| Hidden from serialization | `$hidden = ['public_key', 'secret_key', 'webhook_secret']` — never appears in JSON output |
| Never logged | Keys are not included in any log statements |
| Admin UI masking | Input fields are `type="password"`, placeholder shows `••••••••` if key is set |
| Keep-on-empty | Update controller only overwrites a key if the new value is non-empty |

### Key rotation workflow

1. Admin navigates to **Settings → بوابات الدفع**.
2. Clicks **تعديل** on the Lahza row.
3. Types the new key into the Secret Key field (old value stays if left blank).
4. Saves. New ciphertext is written; old plaintext was never stored.

---

## 5. How Sandbox ↔ Live Switching Works

The `mode` column holds `'sandbox'` or `'live'`. The `PaymentGateway` model exposes:

```php
$gateway->isSandbox();  // mode === 'sandbox'
$gateway->isLive();     // mode === 'live'
```

`LahzaGateway` (Phase 5B) will read `$this->config->mode` to choose which Lahza API endpoint / key prefix to use (`pk_test_*` vs `pk_live_*`).

**Switching flow (admin UI):**
1. Open **Settings → بوابات الدفع → تعديل (Lahza)**.
2. Change Mode from **اختبار** to **إنتاج**.
3. Confirm the live-mode warning.
4. Save — takes effect on the next checkout request (no restart needed).

**Switching flow (programmatic):**
```php
PaymentGateway::where('driver', 'lahza')->update(['mode' => 'live']);
```

---

## 6. How to Add a New Gateway in the Future

Adding a third gateway (e.g. Stripe) requires exactly **three steps**:

**Step 1 — Add the class to the config map** (`config/payment.php`):
```php
'gateways' => [
    'mock'   => \App\Payments\Gateways\MockGateway::class,
    'lahza'  => \App\Payments\Gateways\LahzaGateway::class,
    'stripe' => \App\Payments\Gateways\StripeGateway::class,  // ← add
],
```

**Step 2 — Seed a DB row** (or create via admin UI):
```php
PaymentGateway::create([
    'name'      => 'Stripe',
    'driver'    => 'stripe',
    'is_active' => false,
    'mode'      => 'sandbox',
]);
```

**Step 3 — Implement the class** (`app/Payments/Gateways/StripeGateway.php`) implementing `PaymentGatewayInterface`.

**Nothing else changes.** `PaymentManager`, routes, controllers, webhook handler, and `InvoiceSettlementService` are all gateway-agnostic.

---

## 7. PaymentManager Resolution Order

```
PaymentManager::gateway()
    │
    ▼
1. payment_gateways table — is_active = true row
    │  found → resolve class from config('payment.gateways')[$row->driver]
    │           bind PaymentGateway model in container
    │           return app($class)
    │
    │  not found / table missing / driver not in map
    ▼
2. config('payment.default_gateway') → config('payment.gateways')[$key]
    │  found → return app($class)
    │
    │  class not found
    ▼
3. throw GatewayNotAvailableException
```

The DB row binding step is important for Phase 5B:

```php
// Inside PaymentManager::resolveFromDatabase():
app()->instance(PaymentGateway::class, $row);
return app($class);  // LahzaGateway's constructor receives the model automatically
```

```php
// LahzaGateway (Phase 5B):
class LahzaGateway implements PaymentGatewayInterface {
    public function __construct(private PaymentGateway $config) {}

    public function createSession(...): SessionResult {
        $secretKey = $this->config->secret_key;  // decrypted automatically
        $isSandbox = $this->config->isSandbox();
        // ...
    }
}
```

### isEnabled() resolution

```
PaymentManager::isEnabled()
    │
    ▼
1. payment_gateways table — any row with is_active = true?
    │  yes → return true
    │
    │  no / table missing
    ▼
2. config('payment.enabled') → env('PAYMENT_GATEWAY_ENABLED', true)
```

---

## 8. Admin Routes

All routes are under the `dashboard.*` prefix group (`/admin`, middleware: `auth`, `can:access-dashboard`):

| Method | URL | Name | Action |
|--------|-----|------|--------|
| GET | `/admin/settings/payments` | `dashboard.settings.payments.index` | List all gateways |
| GET | `/admin/settings/payments/{id}/edit` | `dashboard.settings.payments.edit` | Edit form |
| POST | `/admin/settings/payments/{id}` | `dashboard.settings.payments.update` | Save credentials + mode |
| POST | `/admin/settings/payments/{id}/activate` | `dashboard.settings.payments.activate` | Activate (deactivates others) |
| POST | `/admin/settings/payments/{id}/deactivate` | `dashboard.settings.payments.deactivate` | Deactivate all |

---

## 9. Required Commands

```bash
# 1. Run the new migration
php artisan migrate

# 2. Seed the default gateway rows (mock=active, lahza=inactive)
php artisan db:seed --class=PaymentGatewaySeeder

# 3. Seed Arabic translation strings
php artisan db:seed --class=DashboardTranslationsSeeder

# 4. Clear config/route cache
php artisan optimize:clear
```

---

## 10. Is Phase 5B Ready to Start?

**Yes.** All prerequisites for `LahzaGateway` implementation are now in place:

| Phase 5B Requirement | Available? |
|---------------------|------------|
| `payment_gateways` table with `lahza` row | ✅ Phase 5A |
| `PaymentGateway` model with decrypted key accessors | ✅ Phase 5A |
| `PaymentManager` binds `PaymentGateway` model in container before class resolution | ✅ Phase 5A |
| Admin UI to enter Lahza credentials | ✅ Phase 5A |
| Sandbox / Live mode switch | ✅ Phase 5A |
| Webhook route at `/payment/webhook/lahza` | ✅ Phase 3 |
| `PaymentAttempt` model + table | ✅ Phase 2 |
| `InvoiceSettlementService::markPaid()` accepts `PaymentAttempt` | ✅ Phase 2 |
| `PaymentGatewayInterface` contract | ✅ Phase 1 |
| `WebhookEvent` / `SessionResult` DTOs | ✅ Phase 1 |

**Phase 5B scope (next):**
1. Create `app/Payments/Gateways/LahzaGateway.php` implementing `PaymentGatewayInterface`
2. Add `'lahza' => LahzaGateway::class` to `config/payment.gateways`
3. Decouple `CheckoutController::process()` — redirect to Lahza instead of `markPaid()` (Phase 4 design)
4. Fill `PaymentWebhookController::handle()` with lookup + idempotency + `markPaid()` (Phase 4 design)
5. Enter Lahza sandbox keys via admin UI
6. Test with Lahza test cards
7. Switch mode to `live` + activate Lahza row
8. Set `PAYMENT_GATEWAY_ENABLED=true`

---

## 11. ADR-007 Status

```
Phase 1  — Payment Abstraction Layer        ✅ Complete
Phase 2  — PaymentAttempt Infrastructure    ✅ Complete
Phase 3  — Webhook Stub                     ✅ Complete
Phase 4  — Redirect Decoupling Design       ✅ Complete (design only)
Phase 5A — Gateway Configuration Management ✅ Complete (this phase)
Phase 5B — LahzaGateway Implementation     ⏳ Ready to start
```
