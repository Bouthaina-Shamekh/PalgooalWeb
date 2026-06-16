# ADR-007 Phase 1 — Implementation Report
## Payment Abstraction Layer (Gateway-Agnostic Architecture)

**Date:** 2026-06-17  
**Phase:** 1 of 5 (ADR-007)  
**Scope:** Architecture only — no real gateway, no migration, no webhook, no PaymentAttempt

---

## 1. Objective

Replace all hardcoded `'mock_gateway'` strings with a proper payment abstraction layer:

- `PaymentGatewayInterface` — contract all gateways must implement
- `MockGateway` — the single source of truth for the mock gateway name
- `PaymentManager` — resolves the active gateway from config/env
- `config/payment.php` — `PAYMENT_GATEWAY` and `PAYMENT_GATEWAY_ENABLED` flags

---

## 2. New Files Created

| File | Purpose |
|------|---------|
| `app/Payments/Contracts/PaymentGatewayInterface.php` | Interface contract for all payment gateways |
| `app/Payments/Gateways/MockGateway.php` | Mock implementation; single source of `'mock_gateway'` string |
| `app/Payments/PaymentManager.php` | Resolver: reads env → returns gateway instance |
| `app/Payments/DTOs/PaymentSession.php` | DTO: return type for `createSession()` |
| `app/Payments/DTOs/WebhookEvent.php` | DTO: return type for `verifyWebhook()` |
| `app/Payments/DTOs/TransactionStatus.php` | DTO: return type for `getTransaction()` |
| `app/Payments/DTOs/RefundResult.php` | DTO: return type for `refund()` |
| `app/Payments/Exceptions/PaymentException.php` | Base exception for all payment errors |
| `app/Payments/Exceptions/WebhookVerificationException.php` | Thrown on invalid webhook signature (Phase 3) |
| `app/Payments/Exceptions/GatewayNotAvailableException.php` | Thrown on misconfigured gateway class |
| `config/payment.php` | Config: `enabled`, `default_gateway`, `gateways` map |

**Total new files: 11**

---

## 3. Modified Files

| File | Change | Location |
|------|--------|----------|
| `app/Http/Controllers/Front/CheckoutController.php` | Replaced `'mock_gateway'` literal + added `isEnabled()` guard | `process()` — line 86 (flag), line 435 (replacement) |
| `app/Http/Controllers/Client/InvoiceCheckoutController.php` | Replaced `'mock_gateway'` literal + added `isEnabled()` guard | `handleSuccessfulPayment()` + `process()` |
| `app/Services/Billing/InvoiceSettlementService.php` | Replaced fallback `'mock_gateway'` default | `syncStandaloneInvoiceDomain()` line 79 |
| `app/Services/Domains/DomainRenewalService.php` | Replaced comparison + `markPaid` call | lines 143, 152 |

**Total modified files: 4**

---

## 4. Hardcoded mock_gateway Strings Removed

| # | File | Type | Before | After |
|---|------|------|--------|-------|
| 1 | `CheckoutController.php` | `markPaid()` arg | `'mock_gateway'` | `app(PaymentManager::class)->gateway()->name()` |
| 2 | `InvoiceCheckoutController.php` | `markPaid()` arg | `'mock_gateway'` | `app(PaymentManager::class)->gateway()->name()` |
| 3 | `InvoiceSettlementService.php` | Fallback default | `$paymentMethod ?: 'mock_gateway'` | `$paymentMethod ?: app(PaymentManager::class)->gateway()->name()` |
| 4 | `DomainRenewalService.php` line 143 | DB comparison | `!== 'mock_gateway'` | `!== MockGateway::GATEWAY_NAME` |
| 5 | `DomainRenewalService.php` line 152 | `markPaid()` arg | `'mock_gateway'` | `app(PaymentManager::class)->gateway()->name()` |

**Total removed: 5 of 5 (100%)**

### Why line 143 uses `MockGateway::GATEWAY_NAME` instead of `gateway()->name()`

`DomainRenewalService` line 143 compares against `domains.payment_method` — a value already **stored in the database** from a previous payment. It answers: "Was this domain originally paid via mock?" This is a historical fact about data, not a runtime gateway question.

Using `gateway()->name()` for this comparison would be incorrect: if the gateway is later changed to `'lahza'`, then `gateway()->name()` returns `'lahza'`, and the comparison `!== 'lahza'` would evaluate to `true` for old mock-paid domains — causing the renewal service to skip auto-renewal for domains that should be auto-renewed.

Using `MockGateway::GATEWAY_NAME` correctly anchors the comparison to the stored historical value regardless of the current gateway configuration.

---

## 5. How Gateway Resolution Works

```
.env:
    PAYMENT_GATEWAY=mock
    PAYMENT_GATEWAY_ENABLED=true

config/payment.php:
    'default_gateway' => env('PAYMENT_GATEWAY', 'mock')   → 'mock'
    'gateways' => ['mock' => MockGateway::class, ...]

PaymentManager::gateway():
    $key   = 'mock'
    $class = MockGateway::class
    return app(MockGateway::class)           → MockGateway instance

MockGateway::name():
    return 'mock_gateway'                   → written to DB as payment_method

PaymentManager::isEnabled():
    return config('payment.enabled', true)  → true (public checkout active)
```

**To disable public checkout** (safest production state before Phase 5):
```
PAYMENT_GATEWAY_ENABLED=false
```
→ `CheckoutController::process()` returns 503 JSON (AJAX) or redirect with error  
→ `InvoiceCheckoutController::process()` redirects with error on `scenario=success`  
→ Admin bulk-mark-paid: **unaffected** (bypasses the flag — admin use only)  
→ `DomainRenewalService`: **unaffected** (internal job — not a public checkout flow)

---

## 6. Provider-Specific Code

**Zero provider-specific code exists in the codebase after Phase 1.**

- No Lahza SDK references
- No Stripe SDK references
- No PayPal references
- No API keys, webhook secrets, or provider configuration outside `config/payment.php`

The only provider in `config/payment.php` is `MockGateway`, which is 100% internal.

---

## 7. Behavior Parity — Settlement Flows

MockGateway is a **legacy settlement name provider only** in Phase 1.
It must not fake successful transactions or refunds.

The following table covers the settlement flows where Phase 1 must preserve parity:

| Scenario | Before Phase 1 | After Phase 1 | Parity? |
|----------|---------------|---------------|---------|
| Public template checkout | `markPaid($invoice, 'mock_gateway')` | `markPaid($invoice, app(PM)->gateway()->name())` where `name()` → `'mock_gateway'` | ✅ Identical |
| Client invoice portal payment | `markPaid($invoice, 'mock_gateway')` | Same resolution path | ✅ Identical |
| Domain standalone settlement | `'mock_gateway'` fallback | `gateway()->name()` → `'mock_gateway'` | ✅ Identical |
| Domain auto-renewal eligibility check | `!== 'mock_gateway'` | `!== MockGateway::GATEWAY_NAME` → same value | ✅ Identical |
| Domain auto-renewal settlement | `markPaid($invoice, 'mock_gateway')` | `markPaid($invoice, gateway()->name())` | ✅ Identical |
| Admin bulk-mark-paid | Unchanged — does not use the hardcoded string | Unchanged | ✅ Identical |

**Hardened (Phase 1 review, 2026-06-17):**
`getTransaction()` and `refund()` now throw `PaymentException` instead of returning synthetic success.
MockGateway no longer fakes transaction status or refund results — it is strictly a name provider.
These methods are not called by any Phase 1 code path; the hardening is a safety measure only.

---

## 8. Required Runtime Commands

**None required for Phase 1.**

Phase 1 is architecture-only:
- No migration created → no `php artisan migrate`
- No seeder added → no `php artisan db:seed`
- No cache invalidation needed → no `php artisan cache:clear`

The `config/payment.php` file is auto-discovered by Laravel's config loader. Run `php artisan config:clear` if you have a cached config in production:

```bash
php artisan config:clear
```

**Recommended `.env` additions (optional but recommended now):**

```env
# ADR-007 Phase 1 — Payment Gateway
PAYMENT_GATEWAY=mock
PAYMENT_GATEWAY_ENABLED=true
```

---

## 9. Is Phase 1 Complete?

**Yes. All 8 steps are implemented:**

| Step | Status |
|------|--------|
| Step 1 — Create `app/Payments/` namespace | ✅ |
| Step 2 — `PaymentGatewayInterface` with 5 methods + PHPDoc | ✅ |
| Step 3 — `MockGateway` implements interface | ✅ |
| Step 4 — `PaymentManager::gateway()` + `isEnabled()` | ✅ |
| Step 5 — `config/payment.php` with `PAYMENT_GATEWAY` + `PAYMENT_GATEWAY_ENABLED` | ✅ |
| Step 6 — All 5 hardcoded `'mock_gateway'` strings replaced | ✅ |
| Step 7 — Feature flag in `CheckoutController` + `InvoiceCheckoutController` | ✅ |
| Step 8 — This report | ✅ |

---

## 10. Is the Project Ready for Phase 2 (PaymentAttempt)?

**Yes.** Phase 1 establishes the prerequisites Phase 2 depends on:

| Phase 2 Requirement | Available After Phase 1? |
|--------------------|--------------------------|
| `PaymentGatewayInterface` contract exists | ✅ |
| `MockGateway::name()` as single source of gateway name | ✅ |
| `PaymentManager::gateway()` resolvable via service container | ✅ |
| Checkout and settlement callers are gateway-agnostic | ✅ |
| `config/payment.php` with gateway map | ✅ |
| No hardcoded gateway name in business logic | ✅ |

**Phase 2 will add:**
- Migration: `payment_attempts` table (schema in ADR-007 Section 8)
- `PaymentAttempt` Eloquent model
- Migration: `invoices.payment_attempt_id` FK nullable
- `InvoiceSettlementService::markPaid()` updated to accept optional `PaymentAttempt` and link it on settlement
- `MockGateway` creates a `PaymentAttempt` record for admin audit trail

---

## 11. File Structure After Phase 1

```
app/
└── Payments/
    ├── Contracts/
    │   └── PaymentGatewayInterface.php    ← Interface (5 methods, full PHPDoc)
    ├── DTOs/
    │   ├── PaymentSession.php             ← createSession() return type
    │   ├── WebhookEvent.php               ← verifyWebhook() return type
    │   ├── TransactionStatus.php          ← getTransaction() return type
    │   └── RefundResult.php               ← refund() return type
    ├── Exceptions/
    │   ├── PaymentException.php           ← Base exception
    │   ├── WebhookVerificationException.php ← Phase 3: invalid signature
    │   └── GatewayNotAvailableException.php ← Misconfigured gateway class
    ├── Gateways/
    │   └── MockGateway.php               ← Single source of 'mock_gateway'
    └── PaymentManager.php                ← Resolver: env → gateway instance

config/
└── payment.php                           ← PAYMENT_GATEWAY + PAYMENT_GATEWAY_ENABLED
```
