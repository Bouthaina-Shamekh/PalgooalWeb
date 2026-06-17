# ADR-007 Phase 5B — Implementation Report
## LahzaGateway Implementation

**Date:** 2026-06-17  
**Phase:** 5B of 5 (ADR-007)  
**Scope:** Create `LahzaGateway` implementing `PaymentGatewayInterface` — no checkout edits, no webhook settlement, no Phase 5C

---

## 1. New Files

| File | Purpose |
|------|---------|
| `app/Payments/Gateways/LahzaGateway.php` | Full `PaymentGatewayInterface` implementation for Lahza |
| `app/Console/Commands/LahzaHealthCheck.php` | `php artisan lahza:health-check` — 10-point config verification |

---

## 2. Modified Files

| File | Change |
|------|--------|
| `config/payment.php` | Uncommented + activated `'lahza' => LahzaGateway::class` in gateway map |

---

## 3. API Endpoints Used

All requests go to `https://api.lahza.io` (same URL for sandbox and live — key prefix determines environment).

| Method | Endpoint | Used By |
|--------|----------|---------|
| `POST` | `/transaction/initialize` | `createSession()` — initiates hosted checkout |
| `GET`  | `/transaction/verify/{reference}` | `getTransaction()` — fetches transaction state |
| `POST` | `/refund` | `refund()` — issues full or partial refund |

**Authentication:** `Authorization: Bearer {secret_key}` on all requests.  
**Timeout:** 30 seconds for all requests.

---

## 4. How Keys Are Read from the Admin Panel

`LahzaGateway` receives a `PaymentGateway` model instance via constructor injection:

```php
public function __construct(private readonly PaymentGateway $config) {}
```

This injection is performed by `PaymentManager::resolveFromDatabase()` in Phase 5A:

```php
app()->instance(PaymentGateway::class, $row);   // bind the active DB row
return app(LahzaGateway::class);                 // constructor receives it
```

The model uses Laravel's `encrypted` cast:

```php
protected $casts = [
    'secret_key'     => 'encrypted',
    'webhook_secret' => 'encrypted',
    'public_key'     => 'encrypted',
];
```

When `LahzaGateway` accesses `$this->config->secret_key`, Eloquent **automatically decrypts** the ciphertext using `APP_KEY`. The key is never stored in `.env`, never hardcoded, and never appears in any log.

**To configure Lahza keys:**
1. Admin panel → Settings → بوابات الدفع
2. Click **تعديل** on the Lahza row
3. Enter Public Key, Secret Key, Webhook Secret
4. Save — changes take effect on the next request (no restart needed)

---

## 5. How createSession() Works

```
Client browser                LahzaGateway               Lahza API
───────────────                ─────────────              ─────────
      │                              │                         │
      │  (Phase 5C will trigger)     │                         │
      │──────────────────────────────▶                         │
      │                              │  POST /transaction/     │
      │                              │  initialize             │
      │                              │  {email, amount,        │
      │                              │   currency, reference,  │
      │                              │   callback_url,         │
      │                              │   return_url}           │
      │                              │────────────────────────▶│
      │                              │                         │
      │                              │   {status:true,         │
      │                              │    data:{               │
      │                              │      authorization_url, │
      │                              │      reference}}        │
      │                              │◀────────────────────────│
      │                              │                         │
      │  PaymentSession{             │                         │
      │    sessionId: idempotencyKey,│                         │
      │    checkoutUrl: auth_url}    │                         │
      │◀─────────────────────────────│                         │
      │                              │                         │
      │── redirect to checkoutUrl ──▶│                         │
```

**Key design decisions:**

- `amount` is always read from `invoice->total_cents` (authoritative DB value). The frontend cannot inject a different amount.
- `reference` is our `idempotencyKey` (UUID from `payment_attempts.idempotency_key`). This is what Lahza echoes back in the webhook, allowing us to match the event to the correct `PaymentAttempt` without trusting any URL parameter.
- `callback_url` = `route('payment.webhook', ['gateway' => 'lahza'])` — server-to-server delivery.
- `return_url` / `cancel_url` — informational redirects only. **Settlement never happens here** (ADR-007 Principle 2: WEBHOOK-FIRST).
- `sessionId` in the returned `PaymentSession` is set to `$idempotencyKey` (not Lahza's internal reference), so `PaymentAttempt.gateway_session_id` and the webhook `reference` field match.

---

## 6. How verifyWebhook() Works

```
Lahza server                  PaymentWebhookController       LahzaGateway
───────────────                ────────────────────────       ────────────
      │  POST /payment/webhook/lahza                              │
      │  x-lahza-signature: hmac_value                           │
      │  Body: raw JSON                                           │
      │────────────────────────────▶                              │
      │                            │  verifyWebhook(raw, header) │
      │                            │────────────────────────────▶│
      │                            │                             │
      │                            │  1. HMAC-SHA512(raw, secret)│
      │                            │  2. hash_equals(expected,   │
      │                            │       header)               │
      │                            │  3. If mismatch →           │
      │                            │     WebhookVerificationException
      │                            │  4. json_decode(raw)        │
      │                            │  5. Normalize → WebhookEvent│
      │                            │◀────────────────────────────│
```

**Security invariants:**

1. `hash_hmac('sha512', $rawPayload, $webhookSecret)` — computed on raw bytes before any parsing.
2. `hash_equals($expected, strtolower($signatureHeader))` — constant-time comparison, prevents timing attacks.
3. JSON is parsed **only after** signature verification passes.
4. If `WebhookVerificationException` is thrown, the webhook controller returns HTTP 401 (not 500, which would trigger Lahza retry on a forged request).

**Event normalization:**

| Lahza event | Normalized type |
|-------------|-----------------|
| `charge.success` / status=`success` | `WebhookEvent::TYPE_PAYMENT_SUCCEEDED` |
| `charge.failed`  / status=`failed`  | `WebhookEvent::TYPE_PAYMENT_FAILED` |
| `refund.*` | `WebhookEvent::TYPE_REFUND_ISSUED` |
| anything else | `WebhookEvent::TYPE_UNKNOWN` |

**sessionId** in `WebhookEvent` = Lahza `reference` = our `idempotencyKey` — this is what the webhook handler uses to look up the `PaymentAttempt`.

---

## 7. How getTransaction() Works

```
GET https://api.lahza.io/transaction/verify/{reference}
Authorization: Bearer {secret_key}
```

`$gatewayTransactionId` accepts either:
- Our `idempotencyKey` (UUID) stored as Lahza's `reference`
- Lahza's internal `txn_*` ID stored in `payment_attempts.gateway_transaction_id`

Lahza status → `TransactionStatus` constant mapping:

| Lahza status | Our constant |
|--------------|-------------|
| `success`  | `STATUS_SUCCEEDED` |
| `pending`  | `STATUS_PENDING` |
| `failed`   | `STATUS_FAILED` |
| `refunded` | `STATUS_REFUNDED` |
| unknown    | `STATUS_PENDING` (conservative) |

Used for:
- Manual reconciliation when webhook was not delivered
- Admin "check payment status" action
- Automated recovery job

---

## 8. How refund() Works

```
POST https://api.lahza.io/refund
Authorization: Bearer {secret_key}
{
  "transaction_reference": "txn_xxx_or_our_uuid",
  "amount": 1500
}
```

**Notes:**
- Lahza Refund API existence confirmed by account owner.
- The official Lahza documentation page for `/refund` was observed empty at implementation time. The endpoint and body structure follow the standard Lahza integration pattern and should be validated against a real sandbox refund.
- On any non-2xx response → throws `PaymentException` with full HTTP status and body.
- Never returns a fake success — `status=false` in Lahza response → `PaymentException`.
- `refundId` in the returned `RefundResult` is set to Lahza's `data.id`. If absent, a fallback `ref_{uniqid}` is used for traceability.

---

## 9. Checkout Behavior — Was It Changed?

**No.** Checkout behavior is completely unchanged in Phase 5B.

| Component | Changed in Phase 5B? |
|-----------|---------------------|
| `CheckoutController` | ❌ Not touched |
| `InvoiceCheckoutController` | ❌ Not touched |
| `PaymentWebhookController` (settlement logic) | ❌ Not touched |
| `InvoiceSettlementService` | ❌ Not touched |
| Any public-facing URL | ❌ Not touched |

Phase 5B adds only the gateway implementation class. No controller routes to `createSession()` yet. That is Phase 5C.

---

## 10. Is Phase 5C Ready?

**Yes.** All prerequisites are now in place.

| Phase 5C Requirement | Available? |
|---------------------|------------|
| `LahzaGateway::createSession()` — returns `PaymentSession{checkoutUrl}` | ✅ Phase 5B |
| `LahzaGateway::verifyWebhook()` — HMAC-SHA512, returns `WebhookEvent` | ✅ Phase 5B |
| `LahzaGateway::getTransaction()` — verify by reference | ✅ Phase 5B |
| `LahzaGateway::refund()` — issue refund | ✅ Phase 5B |
| `PaymentGateway` DB row with encrypted keys | ✅ Phase 5A |
| Admin UI to enter/rotate keys | ✅ Phase 5A |
| Sandbox/Live mode switch | ✅ Phase 5A |
| `PaymentAttempt` model + table | ✅ Phase 2 |
| `PaymentWebhookController` with route | ✅ Phase 3 |
| `InvoiceSettlementService::markPaid()` | ✅ Phase 1 |

**Phase 5C scope (next):**
1. Decouple `CheckoutController::process()` — create `PaymentAttempt`, call `createSession()`, redirect to `checkoutUrl`
2. Decouple `InvoiceCheckoutController::process()` — same pattern
3. Fill `PaymentWebhookController::handle()` with: lookup `PaymentAttempt` by `event->sessionId`, idempotency guard (check `status !== succeeded`), amount validation, call `markPaid()`
4. Enter Lahza sandbox credentials via admin UI
5. Test with Lahza test cards
6. Switch mode to `live` + activate Lahza row
7. Set `PAYMENT_GATEWAY_ENABLED=true`

---

## 11. Sandbox/Live Note

Lahza uses **the same API base URL** for sandbox and live environments:

```
https://api.lahza.io
```

The environment is controlled entirely by which keys are configured:
- Test keys: `sk_test_*` / `pk_test_*` — sandbox payments only
- Live keys: `sk_live_*` / `pk_live_*` — real money

`$this->config->isSandbox()` and `$this->config->isLive()` are used for **logging and safety warnings only**. The base URL is the same regardless. This is documented in `LahzaGateway` class docblock.

To override the base URL (e.g. staging/proxy):
```php
// In payment_gateways.settings JSON:
{ "base_url": "https://custom-proxy.example.com" }
```

---

## 12. Health Check Command

```bash
php artisan lahza:health-check
```

Runs 10 checks without making API calls or printing keys:

1. `payment_gateways` table accessible
2. Lahza row exists (driver=lahza)
3. `is_active` flag
4. Mode (sandbox/live) with warning if live
5. `secret_key` ciphertext present (value hidden)
6. `webhook_secret` ciphertext present
7. `lahza` in `config/payment.gateways` map
8. `LahzaGateway` class exists
9. `PaymentManager::gateway()` resolves to name=lahza (if active)
10. Webhook route routable

Exit code 0 = all critical checks passed. Exit code 1 = fix required.

---

## 13. Required Commands After Phase 5A + 5B Deployment

```bash
# Run once (Phase 5A commands — if not already run)
php artisan migrate
php artisan db:seed --class=PaymentGatewaySeeder
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan optimize:clear

# Verify configuration (Phase 5B)
php artisan lahza:health-check

# Enter credentials via admin panel, then re-run health check
# Admin → Settings → بوابات الدفع → Lahza → تعديل
```

---

## 14. ADR-007 Status

```
Phase 1  — Payment Abstraction Layer        ✅ Complete
Phase 2  — PaymentAttempt Infrastructure    ✅ Complete
Phase 3  — Webhook Stub                     ✅ Complete
Phase 4  — Redirect Decoupling Design       ✅ Complete (design only)
Phase 5A — Gateway Configuration Management ✅ Complete
Phase 5B — LahzaGateway Implementation     ✅ Complete (this phase)
Phase 5C — Checkout + Webhook Wiring        ⏳ Ready to start
```
