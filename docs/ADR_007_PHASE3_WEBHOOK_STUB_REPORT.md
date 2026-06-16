# ADR-007 Phase 3 — Implementation Report
## Webhook Stub Infrastructure

**Date:** 2026-06-17  
**Phase:** 3 of 5 (ADR-007)  
**Scope:** Webhook route + controller stub + logging channel — no gateway integration, no settlement

---

## 1. Objective

Prepare the system to receive payment gateway webhooks in a future phase by:

- Establishing the correct URL contract (`POST /payment/webhook/{gateway}`)
- Wiring the gateway resolution and verification flow (stub)
- Adding a dedicated audit log channel
- Defining the response contract (202 / 401 / 404)

**Phase 3 does not process any payments.** No invoices are settled, no subscriptions are activated, and no `PaymentAttempt` records are created.

---

## 2. Route

### File: `routes/payment.php` *(new)*

```
POST /payment/webhook/{gateway}
Name: payment.webhook
```

### Registration: `bootstrap/app.php`

```php
->withRouting(
    web: ...,
    commands: ...,
    health: '/up',
    then: function () {
        \Illuminate\Support\Facades\Route::middleware('throttle:60,1')
            ->group(base_path('routes/payment.php'));
    }
)
```

The `then:` callback registers the route **outside** the `web` middleware group. This ensures:

| Middleware | Applied? | Reason |
|-----------|----------|--------|
| `VerifyCsrfToken` | ❌ No | Gateway servers don't carry CSRF tokens |
| Session | ❌ No | Webhook handler is stateless |
| Auth | ❌ No | Caller is a gateway server, not a logged-in user |
| `throttle:60,1` | ✅ Yes | Rate-limit: 60 requests per minute per IP |

### URL parameter semantics

The `{gateway}` segment must exactly match `config('payment.default_gateway')` (the `PAYMENT_GATEWAY` env value). Examples:

| `.env` | Valid URL | Invalid URL |
|--------|-----------|-------------|
| `PAYMENT_GATEWAY=mock` | `/payment/webhook/mock` | `/payment/webhook/lahza` → 404 |
| `PAYMENT_GATEWAY=lahza` | `/payment/webhook/lahza` | `/payment/webhook/mock` → 404 |

---

## 3. Controller

### File: `app/Http/Controllers/PaymentWebhookController.php` *(new)*

**Method signature:**

```php
public function handle(Request $request, string $gateway): JsonResponse
```

**Execution flow:**

```
POST /payment/webhook/{gateway}
        │
        ▼
┌────────────────────────────────────────────────────────────┐
│ 1. Gateway key check                                       │
│    if $gateway !== config('payment.default_gateway')       │
│       → log notice → return 404                           │
└────────────────────────┬───────────────────────────────────┘
                         │ match
                         ▼
┌────────────────────────────────────────────────────────────┐
│ 2. Resolve gateway instance                                │
│    app(PaymentManager::class)->gateway()                   │
└────────────────────────┬───────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────────────┐
│ 3. Read raw payload                                        │
│    $rawPayload = $request->getContent()                    │
│    $signatureHeader = $request->header('X-Webhook-...')    │
└────────────────────────┬───────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────────────┐
│ 4. verifyWebhook($rawPayload, $signatureHeader)            │
│                                                            │
│    WebhookVerificationException → log warning → 401       │
│    PaymentException (MockGateway) → log info  → 401       │
└────────────────────────┬───────────────────────────────────┘
                         │ success (Phase 5 only)
                         ▼
┌────────────────────────────────────────────────────────────┐
│ 5. [STUB — Phase 4 will fill this]                        │
│    PaymentAttempt lookup by:                               │
│      - idempotency_key                                     │
│      - gateway_transaction_id                              │
│    Idempotency guard → amount validation → markPaid()      │
│    ← none of this runs in Phase 3                         │
└────────────────────────┬───────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────────────┐
│ 6. Log success                                             │
│    Log::channel('payment-webhook')->info(...)              │
└────────────────────────┬───────────────────────────────────┘
                         │
                         ▼
              return 202 {"status": "accepted"}
```

---

## 4. Verification Flow

```php
try {
    $event = $gatewayInstance->verifyWebhook($rawPayload, $signatureHeader);
} catch (WebhookVerificationException $e) {
    // Bad signature — log warning, return 401
} catch (PaymentException $e) {
    // Gateway doesn't support webhooks yet (MockGateway) — log info, return 401
}
```

**Why two catch blocks?**

- `WebhookVerificationException` = signature mismatch on a request that reached the right endpoint. Potentially a spoofed request. Logged at `warning`.
- `PaymentException` = the gateway implementation itself doesn't support webhooks (MockGateway throws this). Logged at `info` — not suspicious, just expected behavior in Phase 3.

**Why raw payload?**

HMAC verification (Phase 5) requires the exact byte sequence of the raw request body. Parsing with `$request->json()` or `$request->all()` first alters whitespace and ordering, breaking signature verification. `$request->getContent()` is called before any parsing.

---

## 5. Logging Strategy

### Channel: `payment-webhook` *(new in `config/logging.php`)*

```php
'payment-webhook' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/payment-webhook.log'),
    'level'  => 'debug',
    'days'   => 90,   // 90 days — longer than default for payment audit trail
],
```

Stored at: `storage/logs/payment-webhook.log` (rotated daily, 90-day retention)

### Log events

| Event | Level | Fields |
|-------|-------|--------|
| Unknown gateway | `notice` | `gateway_requested`, `gateway_configured`, `received_at` |
| Signature verification failed | `warning` | `gateway`, `verified: false`, `reason`, `received_at` |
| Gateway doesn't support webhooks | `info` | `gateway`, `verified: false`, `reason`, `received_at` |
| Webhook received and verified | `info` | `gateway`, `transaction_id`, `verified: true`, `received_at` |

All log entries include `gateway` and `received_at` as required fields.

---

## 6. Response Contract

| Scenario | HTTP Status | Body |
|----------|-------------|------|
| Gateway key mismatch | `404` | `{"status": "not_found"}` |
| `verifyWebhook()` threw `WebhookVerificationException` | `401` | `{"status": "rejected"}` |
| `verifyWebhook()` threw `PaymentException` | `401` | `{"status": "rejected"}` |
| `verifyWebhook()` succeeded | `202` | `{"status": "accepted"}` |

**Why 202 (not 200)?**

202 Accepted semantically means "the request was received, but the action is not yet complete." This is correct because settlement (subscription activation) is deferred — it doesn't happen inside this request in Phase 4+. Payment gateways expect 2xx and will retry on 4xx/5xx; 202 signals unambiguous acceptance without implying the subscription was activated synchronously.

---

## 7. What Was Intentionally Excluded from Phase 3

| Item | Status | Phase |
|------|--------|-------|
| `PaymentAttempt` record creation | ❌ Not implemented | Phase 4 |
| `PaymentAttempt` record lookup | ❌ Not implemented | Phase 4 |
| Idempotency guard (skip duplicate webhooks) | ❌ Not implemented | Phase 4 |
| Amount validation against `gateway_amount_cents` | ❌ Not implemented | Phase 4 |
| `InvoiceSettlementService::markPaid()` call | ❌ Not implemented | Phase 4 |
| Invoice status update | ❌ Not implemented | Phase 4 |
| Order activation | ❌ Not implemented | Phase 4 |
| Real gateway HMAC verification | ❌ Not implemented | Phase 5 |
| `createSession()` redirect flow | ❌ Not implemented | Phase 4 |
| Any real gateway (Lahza / Stripe / PayPal) | ❌ Not implemented | Phase 5 |

---

## 8. Was Any Settlement Logic Created?

**No.**

Verification:
- `markPaid(` — zero active occurrences in `PaymentWebhookController.php`
- `InvoiceSettlementService` — zero active occurrences (mentioned in comment block only)
- `PaymentAttempt::create(` — zero occurrences
- No `Invoice` model access of any kind

The controller reads only: gateway key (from config), raw payload (from request), signature header (from request). It writes only: log entries. No database writes occur.

---

## 9. Modified Files

| File | Change |
|------|--------|
| `bootstrap/app.php` | Added `then:` callback to register `routes/payment.php` outside `web` middleware group |
| `config/logging.php` | Added `payment-webhook` daily log channel (90-day retention) |

---

## 10. New Files

| File | Purpose |
|------|---------|
| `routes/payment.php` | Defines `POST /payment/webhook/{gateway}` route |
| `app/Http/Controllers/PaymentWebhookController.php` | Webhook stub controller |
| `docs/adr007-phase3-validate.php` | Tinker validation script (7 checks) |
| `docs/ADR_007_PHASE3_WEBHOOK_STUB_REPORT.md` | This report |

---

## 11. Required Commands

```bash
# Clear compiled config/route cache to pick up the new route and logging channel
php artisan optimize:clear

# Optional: verify the route exists
php artisan route:list --name=payment.webhook

# Optional: run the validation script in Tinker
php artisan tinker --execute="require base_path('docs/adr007-phase3-validate.php');"
```

---

## 12. Is Phase 3 Complete?

**Yes. All 9 steps implemented:**

| Step | Status |
|------|--------|
| Step 1 — Route: `POST /payment/webhook/{gateway}` | ✅ |
| Step 2 — `PaymentWebhookController` with `handle()` | ✅ |
| Step 3 — Gateway resolution + 404 on mismatch | ✅ |
| Step 4 — `verifyWebhook()` call + 401 on `WebhookVerificationException` | ✅ |
| Step 5 — PaymentAttempt lookup fields designed (stub, no writes) | ✅ |
| Step 6 — `payment-webhook` log channel with required fields | ✅ |
| Step 7 — Response contract: 202 / 401 / 404 | ✅ |
| Step 8 — Validation script (`docs/adr007-phase3-validate.php`) | ✅ |
| Step 9 — This report | ✅ |

---

## 13. Is the Project Ready for Phase 4?

**Yes.** Phase 4 prerequisites from Phase 3:

| Phase 4 Requirement | Available? |
|--------------------|------------|
| Webhook route at `/payment/webhook/{gateway}` | ✅ Phase 3 |
| Controller with `handle()` method | ✅ Phase 3 |
| Gateway resolution + mismatch guard | ✅ Phase 3 |
| `verifyWebhook()` call point | ✅ Phase 3 |
| `payment_attempts` table exists | ✅ Phase 2 |
| `PaymentAttempt` model with status constants | ✅ Phase 2 |
| `invoices.payment_attempt_id` FK column | ✅ Phase 2 |
| `markPaid()` accepts optional `PaymentAttempt` | ✅ Phase 2 |
| `WebhookEvent` DTO (carries `transactionId`, `sessionId`, `amountCents`) | ✅ Phase 1 |

**Phase 4 will add inside `PaymentWebhookController::handle()`:**
- `PaymentAttempt` record creation / lookup by `idempotency_key` or `gateway_transaction_id`
- Duplicate webhook guard (idempotency)
- Amount validation against `payment_attempts.gateway_amount_cents`
- `markPaid()` call with the resolved `PaymentAttempt`
- No new migrations required (all schema exists after Phase 2)

---

## 14. ADR-007 Status

```
Phase 1 — Payment Abstraction Layer       ✅ Complete + Hardened
Phase 2 — PaymentAttempt Infrastructure   ✅ Complete
Phase 3 — Webhook Stub                    ✅ Complete
Phase 4 — Redirect Decoupling & Settlement  ⏳ Awaiting request
Phase 5 — Real Gateway (Lahza / Stripe)   ⏳ Awaiting provider confirmation
```
