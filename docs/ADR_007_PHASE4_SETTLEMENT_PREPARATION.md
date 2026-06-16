# ADR-007 Phase 4 — Settlement Preparation
## Redirect Decoupling & Settlement Architecture Design

**Date:** 2026-06-17
**Phase:** 4 of 5 (ADR-007)
**Type:** Architecture Design Document — No code changes
**Scope:** Analysis of current coupling + design of target architecture for Phase 5 implementation

---

## 1. Current Flow (As-Is)

### 1.1 CheckoutController::process() — Template Checkout

```
Client Browser
    │
    ▼  POST /checkout/client/{template_id}/process
CheckoutController::process()
    │
    ├── 1. Create Client (if guest)
    ├── 2. DB::transaction {
    │       ├── Order::create(status: 'pending')
    │       ├── Subscription::create(status: 'pending')
    │       ├── Invoice::create(status: 'draft')
    │       └── InvoiceItem::create(...)
    │   }
    │
    ├── 3. ──── COUPLING POINT ────────────────────────────────────────────┐
    │       InvoiceSettlementService::markPaid($invoice, $gateway->name()) │
    │       ├── Invoice.status = 'paid'                                    │
    │       ├── Order.status   = 'active'                                  │
    │       └── OrderActivationService::activate()                         │
    │           ├── Subscription.status = 'active'                         │
    │           └── TenantProvisioningService::provision()                 │
    │                                                                       │
    │   PROBLEM: Settlement happens INSIDE the checkout request cycle      │
    │   with no payment verification whatsoever                             │
    └───────────────────────────────────────────────────────────────────────┘
    │
    ▼  Subscription now ACTIVE — before any payment has been verified
    Return JSON / Redirect with success_title / site_url
```

### 1.2 CheckoutController::process() — Hosting Plan / Domain-Only

```
POST /checkout/client/{}/process  (no template_id)
    │
    ├── DB::transaction { Order + Subscription(pending) + Invoice(draft) }
    │
    ├── Does NOT call markPaid() here
    │
    └── ProvisionSubscription::dispatch($subscription->id)   ← Job dispatched
        Subscription stays 'pending'
```

> **Asymmetry:** Template checkout settles immediately. Plan/domain checkout defers.

### 1.3 InvoiceCheckoutController::process() — Client Invoice Checkout

```
POST /client/invoices/{invoice}/checkout/process
    │
    ├── Validate scenario: success | failed | cancel
    │
    ├── scenario === 'success'
    │   ├── Check PAYMENT_GATEWAY_ENABLED
    │   ├── validateDemoCardFields()  ← fictional card UI, no real verification
    │   │
    │   └── handleSuccessfulPayment()
    │       └── InvoiceSettlementService::markPaid($invoice, $gateway->name())
    │           ├── Invoice.status = 'paid'
    │           ├── Order.status   = 'active'
    │           └── OrderActivationService::activate()
    │               └── Subscription → 'active' + provision()
    │
    └── Client controls scenario via POST parameter — no server-side verification
```

### 1.4 InvoiceSettlementService::markPaid() Call Sites

| # | Caller | Method | Trigger |
|---|--------|--------|---------|
| 1 | `CheckoutController::process()` | `markPaid($invoice, $gateway->name())` | Template checkout POST request |
| 2 | `InvoiceCheckoutController::handleSuccessfulPayment()` | `markPaid($invoice, $gateway->name())` | Client sends `scenario=success` in POST |
| 3 | `DomainRenewalService` | `markPaid($invoice, $gateway->name())` | Scheduled auto-renewal job |
| 4 | Admin `InvoiceController::bulkAction()` | `markPaid($invoice, ...)` | Admin manually marks paid |

**Call sites #1 and #2 are the coupling problem** — no payment verification precedes settlement.
Call sites #3 and #4 are legitimate: auto-renewal uses historical DB check, admin uses authority override.

### 1.5 Settlement Coupling Diagram (Current)

```
┌────────────────────────────────────────────────────────────────┐
│  REQUEST CYCLE (HTTP)                                          │
│                                                                │
│  Client POST ──► CheckoutController ──► markPaid() ──► ACTIVE │
│                                                                │
│  NO gateway involved                                           │
│  NO HMAC verification                                          │
│  NO amount check                                               │
│  NO idempotency guard                                          │
│  Settlement is synchronous with the checkout HTTP request      │
└────────────────────────────────────────────────────────────────┘
```

---

## 2. Target Flow (To-Be, After Phase 5)

### 2.1 Template Checkout — Target

```
Client Browser
    │
    ▼  POST /checkout/client/{template_id}/process
CheckoutController::process()
    │
    ├── 1. Create Client (if guest)
    ├── 2. DB::transaction {
    │       ├── Order::create(status: 'pending')
    │       ├── Subscription::create(status: 'pending')     ← stays pending
    │       ├── Invoice::create(status: 'draft')            ← stays draft
    │       ├── InvoiceItem::create(...)
    │       └── PaymentAttempt::create(status: 'initiated') ← NEW Phase 5
    │   }
    │
    ├── 3. gateway->createSession($invoice, $idempotencyKey, $returnUrl, $cancelUrl)
    │       Returns: PaymentSession { sessionId, checkoutUrl }
    │       PaymentAttempt.gateway_session_id = $sessionId
    │
    └── 4. Redirect client to $checkoutUrl  ← External gateway hosted page
            (NO settlement, NO activation yet)

    ─────────────── CLIENT ON GATEWAY HOSTED PAGE ──────────────────

    ├── CLIENT PAYS on Lahza / Stripe / PayPal hosted page

    ─────────────── GATEWAY SENDS WEBHOOK ─────────────────────────

Gateway Server
    ▼  POST /payment/webhook/{gateway}   ← Phase 3 route (already exists)
PaymentWebhookController::handle()
    │
    ├── 1. Gateway key validation
    ├── 2. verifyWebhook() → HMAC signature check
    ├── 3. Idempotency check (was this webhook already processed?)
    ├── 4. Amount validation ($event->amountCents vs invoice.total_cents)
    ├── 5. PaymentAttempt.status = 'pending'
    └── 6. markPaid($invoice, $gateway, $paymentAttempt) ← ONLY HERE

            Invoice.status = 'paid'
            PaymentAttempt.status = 'succeeded'
            Order.status = 'active'
            Subscription.status = 'active'
            provision()

    ─────────────── CLIENT RETURN REDIRECT ─────────────────────────

Gateway redirects client to $returnUrl
    ▼  GET /checkout/return?session_id=...
CheckoutController::return()  ← NEW method (Phase 5)
    │
    └── Look up PaymentAttempt by session_id
        ├── If succeeded  → show success page (settlement ALREADY done by webhook)
        ├── If pending    → show "processing" page (webhook not yet received)
        └── If failed     → show failure page
```

### 2.2 Key Architecture Principle

```
                    ┌──────────────────────────────────────┐
                    │  PHASE 5 INVARIANT                   │
                    │                                      │
                    │  Settlement authority:               │
                    │    WEBHOOK ONLY                      │
                    │                                      │
                    │  The return redirect:                │
                    │    READS STATE — does not write it   │
                    │                                      │
                    │  The checkout form:                  │
                    │    CREATES ORDER — does not pay it   │
                    └──────────────────────────────────────┘
```

---

## 3. PaymentAttempt Lifecycle

### 3.1 State Machine

```
                    ┌──────────────┐
                    │  initiated   │  createSession() called; client redirected
                    └──────┬───────┘
                           │
              client completes action on gateway
                           │
                    ┌──────▼───────┐
                    │   pending    │  Webhook received; settlement in progress
                    └──────┬───────┘
                           │
              ┌────────────┼────────────────┐
              │            │                │
       ┌──────▼──────┐ ┌──▼──────┐ ┌──────▼──────┐
       │  succeeded  │ │ failed  │ │  cancelled  │
       └──────┬──────┘ └─────────┘ └─────────────┘
              │
    refund confirmed
              │
       ┌──────▼──────┐
       │  refunded   │
       └─────────────┘
```

### 3.2 State Transition Table

| From → To | Trigger | Authority |
|-----------|---------|-----------|
| — → `initiated` | `createSession()` called; `PaymentAttempt::create()` | Checkout controller (Phase 5) |
| `initiated` → `pending` | Webhook received and signature verified | `PaymentWebhookController` (Phase 4 wiring, Phase 5 real) |
| `pending` → `succeeded` | `markPaid()` completes inside webhook handler | `InvoiceSettlementService` (called by webhook handler) |
| `pending` → `failed` | Webhook `type = payment.failed` | `PaymentWebhookController` |
| `initiated` → `cancelled` | Client clicks "cancel" on gateway page; `type = payment.cancelled` webhook OR return redirect with cancel state | `PaymentWebhookController` or scheduled cleanup job |
| `succeeded` → `refunded` | Refund webhook confirmed | `PaymentWebhookController` (Phase 5+) |

### 3.3 Who Controls Each Transition

| Status | Owner | Notes |
|--------|-------|-------|
| `initiated` | Checkout controller | Created at `createSession()` call |
| `pending` | Webhook handler | Only after HMAC verification passes |
| `succeeded` | `InvoiceSettlementService` inside webhook handler | Never from redirect callback or checkout controller |
| `failed` | Webhook handler | Frontend cannot set this directly |
| `cancelled` | Webhook handler OR scheduled job | Covers browser-close scenario |
| `refunded` | Webhook handler | After refund confirmed by gateway |

### 3.4 What Frontend Cannot Do

The frontend (client browser or checkout page) **cannot**:
- Set `PaymentAttempt.status = 'succeeded'`
- Call `markPaid()` directly
- Activate a subscription
- Skip the HMAC verification step

---

## 4. Redirect Contract

### 4.1 Outbound — PaymentSession DTO (Phase 1, already exists)

```php
// app/Payments/DTOs/PaymentSession.php  ← already created in Phase 1
class PaymentSession {
    public function __construct(
        public readonly string $sessionId,    // gateway's session/checkout ID
        public readonly string $checkoutUrl,  // URL to redirect client to
    ) {}
}
```

**Needs extension in Phase 5** — should also carry:

```php
// Proposed extension (design only — no code yet):
class PaymentSession {
    public function __construct(
        public readonly string $sessionId,
        public readonly string $checkoutUrl,
        public readonly string $idempotencyKey,  // links to PaymentAttempt
        public readonly \Carbon\Carbon $expiresAt, // gateway session TTL (typ. 30 min)
        public readonly string $gateway,           // gateway key, e.g. 'lahza'
    ) {}
}
```

### 4.2 Inbound — Return Redirect (New in Phase 5)

When the client returns from the gateway page (success or cancel), the gateway appends query params to the `$returnUrl`. The exact params vary by gateway:

| Gateway | Return Params |
|---------|--------------|
| Lahza | `?session_id=...&status=paid` |
| Stripe | `?session_id=...` |
| PayPal | `?token=...&PayerID=...` |

**The return URL handler MUST NOT settle the invoice.** It reads `PaymentAttempt` status and shows the appropriate UI.

```
GET /checkout/return?session_id={session_id}
    │
    └── PaymentAttempt::where('gateway_session_id', $session_id)->first()
        ├── status = 'succeeded' → show success (webhook already settled)
        ├── status = 'pending'   → show "processing, we'll email you"
        ├── status = 'failed'    → show failure with retry option
        ├── status = 'cancelled' → show cancelled
        └── null                 → show error (session unknown)
```

### 4.3 Idempotency Key Contract

- Generated by checkout controller at `createSession()` time: `Str::uuid()->toString()`
- Stored in `payment_attempts.idempotency_key` (UNIQUE index — already in DB after Phase 2)
- Sent to gateway as a session parameter
- Returned by gateway in webhook
- Used to look up the attempt: `PaymentAttempt::where('idempotency_key', ...)->first()`

---

## 5. Settlement Contract

### 5.1 Proposed PaymentSettlementService (Design Only)

The webhook handler (Phase 4/5) should delegate settlement to a dedicated service rather than calling `InvoiceSettlementService` directly. Proposed responsibility boundaries:

```
PaymentWebhookController::handle()
    │
    ▼
PaymentSettlementService::settle(WebhookEvent $event): SettlementResult
    │
    ├── 1. VERIFY (already done in controller — passed as verified event)
    │
    ├── 2. IDEMPOTENCY
    │   $attempt = PaymentAttempt::where('idempotency_key', $event->sessionId)->lockForUpdate()->first();
    │   if ($attempt->isSucceeded()) return SettlementResult::alreadySettled();
    │
    ├── 3. AMOUNT VALIDATION
    │   if ($event->amountCents !== $attempt->invoice->total_cents) {
    │       throw new AmountMismatchException(...);
    │   }
    │
    ├── 4. ATTEMPT UPDATE
    │   $attempt->update(['status' => 'pending', 'gateway_transaction_id' => $event->transactionId, ...]);
    │
    ├── 5. INVOICE SETTLEMENT (delegates to InvoiceSettlementService)
    │   InvoiceSettlementService::markPaid($invoice, $gateway, $attempt);
    │   ← $attempt is now linked; markPaid() sets it to 'succeeded'
    │
    └── 6. RETURN RESULT
        SettlementResult { settled: true, subscriptionsActivated: [...] }
```

### 5.2 Responsibilities

| Concern | Owner | Notes |
|---------|-------|-------|
| HMAC verification | `PaymentGatewayInterface::verifyWebhook()` | Per-gateway implementation |
| Idempotency | `PaymentSettlementService` | `lockForUpdate()` on PaymentAttempt |
| Amount validation | `PaymentSettlementService` | Server-side only — never trust client |
| Invoice locking | `InvoiceSettlementService::markPaid()` | Already uses `lockForUpdate()` |
| Subscription activation | `OrderActivationService::activate()` | Called inside `markPaid()` |
| Tenant provisioning | `TenantProvisioningService::provision()` | Called inside `activate()` |
| Refund handling | `PaymentSettlementService` (future) | After refund webhook confirmed |

### 5.3 What markPaid() Will Look Like in Phase 5

```php
// In PaymentWebhookController (Phase 5 wiring):
$attempt->update(['status' => PaymentAttempt::STATUS_PENDING, ...]);
app(InvoiceSettlementService::class)->markPaid(
    invoice: $invoice,
    paymentMethod: $gateway,
    paymentAttempt: $attempt,   // ← Phase 2 parameter, already exists
);
// Inside markPaid(): $attempt.status → 'succeeded', $invoice.payment_attempt_id = $attempt->id
```

No changes to `InvoiceSettlementService::markPaid()` signature needed — Phase 2 already added the optional `$paymentAttempt` parameter.

---

## 6. Failure Scenarios

### 6.1 User Closes Browser Before Completing Payment

```
State at browser close:
  PaymentAttempt.status = 'initiated'
  Invoice.status = 'draft'
  Subscription.status = 'pending'

Recovery:
  - Gateway sends 'payment.cancelled' or 'payment.expired' webhook → status = 'cancelled'
  - If no webhook: scheduled cleanup job after TTL (e.g. 30 minutes)
    SELECT * FROM payment_attempts WHERE status = 'initiated' AND created_at < NOW() - INTERVAL 30 MINUTE
    → UPDATE status = 'cancelled'
  - Subscription remains 'pending' until next payment attempt
```

### 6.2 Gateway Timeout / Network Error

```
State: createSession() throws exception before PaymentAttempt was created
  → Checkout shows error, no records created

State: createSession() throws exception AFTER PaymentAttempt was created
  → PaymentAttempt.status = 'initiated' with no session_id
  → Cleanup job will expire it
  → Client can retry (new idempotency key, new attempt)
```

### 6.3 Duplicate Webhook Delivery

```
Gateway sends same webhook twice (normal behavior for all gateways).

First delivery:
  PaymentAttempt.status = 'initiated' → 'pending' → 'succeeded'
  Invoice.status = 'draft' → 'paid'
  Subscription.status = 'pending' → 'active'

Second delivery:
  PaymentWebhookController checks:
    $attempt = PaymentAttempt::where('idempotency_key', ...)->lockForUpdate()->first();
    if ($attempt->isSucceeded()) return 202; // ← early return, no double-settlement

InvoiceSettlementService also has its own guard:
    if ($lockedInvoice->status === 'paid') return; // ← double protection
```

### 6.4 Duplicate Return Redirect (Client Refreshes Return Page)

```
Client refreshes /checkout/return?session_id=...

Handler reads PaymentAttempt.status (read-only):
  → If 'succeeded': show success page (idempotent, no harm)
  → Settlement does NOT happen again (return redirect never calls markPaid())
```

### 6.5 Webhook Arrives Before Client Return Redirect

```
Timeline:
  T+0s:  Client pays on gateway page
  T+1s:  Gateway sends webhook → PaymentAttempt settled → Subscription active
  T+3s:  Client browser redirects to /checkout/return

Return handler reads:
  PaymentAttempt.status = 'succeeded' → show full success page with site_url

This is the HAPPY PATH — webhook-first settlement means return page always
reads a complete state.
```

### 6.6 Client Return Redirect Arrives Before Webhook

```
Timeline:
  T+0s:  Client pays on gateway page
  T+1s:  Client browser redirects to /checkout/return  (webhook not yet received)
  T+5s:  Gateway sends webhook

Return handler reads:
  PaymentAttempt.status = 'initiated' or 'pending'
  → Show "Processing payment — we'll email you when ready"
  → Client does NOT wait; subscription activates when webhook arrives

UI: JavaScript polling or WebSocket to update page when status changes.
    OR: email notification when PaymentAttempt → 'succeeded'.
```

### 6.7 Summary of Failure Handling Responsibilities

| Scenario | Immediate Response | Recovery |
|----------|--------------------|----------|
| Browser closed | Show "processing" on return | Cleanup job expires attempt |
| Gateway timeout before session | 503 to client, no DB records | Client retries |
| Gateway timeout after session | Client gets error, attempt in 'initiated' | Cleanup job expires |
| Duplicate webhook | 202, skip settlement | Idempotency guard |
| Duplicate return redirect | Read state only | No action needed |
| Webhook before return | State already complete | Show success |
| Return before webhook | Show "processing" | Webhook arrives and settles |
| Amount mismatch | Log alert, return 422 to gateway | Manual admin review |
| HMAC failure | Return 401 to gateway | Gateway retries (bad sig = reject all) |

---

## 7. Security Requirements

### 7.1 What the Frontend Cannot Trigger

| Attempt | Can Activate Subscription? | Reason |
|---------|---------------------------|--------|
| POST checkout form with valid data | ❌ No (Phase 5) | Form creates order only; no settlement |
| POST checkout form with `success=1` in body | ❌ No | No server reads client success flag |
| Direct POST to `/client/invoices/{id}/checkout/process` with `scenario=success` | ❌ No (Phase 5) | demo card flow removed; real gateway required |
| Forged `amount` in POST body | ❌ No | Amount is computed server-side from DB |
| Forged `invoice_id` in POST body | ❌ No | Invoice loaded from DB, not from client |
| POST to `/payment/webhook/mock` with fake payload | ❌ No | HMAC verification fails → 401 |
| Replay of a real webhook with wrong amount | ❌ No | Amount validation in PaymentSettlementService |
| Duplicate valid webhook | ❌ Settlement skipped | Idempotency guard in `markPaid()` |

### 7.2 Amount Authority

```
Client submits:    price from UI (not trusted)
Server validates:  invoice.total_cents from DB  ← authoritative
Gateway reports:   event.amountCents from webhook
Settlement guard:  event.amountCents === invoice.total_cents  (required)
```

The client **never** influences the settlement amount. The DB amount is the only authority.

### 7.3 Activation Path — Minimum Required Conditions

A subscription may only become `status = 'active'` if ALL of the following are true:

1. `PaymentAttempt.idempotency_key` matches a record created during a valid `createSession()` call
2. `verifyWebhook()` returned a valid `WebhookEvent` (HMAC passed)
3. `WebhookEvent.type === 'payment.succeeded'`
4. `WebhookEvent.amountCents === invoice.total_cents` (from DB)
5. `PaymentAttempt.status !== 'succeeded'` (idempotency — not already settled)
6. Settlement runs inside `DB::transaction` with `lockForUpdate()` on the invoice row

If any condition fails, settlement is aborted and the subscription remains `pending`.

### 7.4 InvoiceCheckoutController Demo Flow — Phase 5 Disposition

Currently `InvoiceCheckoutController::process()` allows settlement via `scenario=success` POST parameter. This flow:

- Does zero HMAC verification
- Does zero amount validation
- Is controlled entirely by the client
- Is labeled "demo" but currently processes real settlement

**In Phase 5**, this controller's `scenario=success` path must be removed or guarded behind a flag. The production path will be: invoice checkout page → `createSession()` → gateway redirect → webhook settlement.

The `scenario=failed` and `scenario=cancel` paths are safe (no settlement occurs) and may remain for UX testing.

---

## 8. Readiness Gates for Phase 5

### Gate P4-G1 — Payment Provider Selected

- [ ] A real payment provider (Lahza, Stripe, etc.) has been chosen
- [ ] Provider's webhook documentation reviewed
- [ ] Provider's SDK or API credentials obtained
- [ ] Test environment credentials available

### Gate P4-G2 — Gateway Implementation Ready

- [ ] `LahzaGateway` (or equivalent) implements `PaymentGatewayInterface`
- [ ] `createSession()` returns a real `PaymentSession` with `checkoutUrl`
- [ ] `verifyWebhook()` performs real HMAC verification using provider's secret
- [ ] `getTransaction()` returns real `TransactionStatus`
- [ ] `refund()` initiates real refund

### Gate P4-G3 — PaymentSettlementService Created

- [ ] `PaymentSettlementService::settle(WebhookEvent $event): SettlementResult` exists
- [ ] Idempotency guard using `lockForUpdate()` on `PaymentAttempt`
- [ ] Amount validation: `$event->amountCents === $invoice->total_cents`
- [ ] Delegates to `InvoiceSettlementService::markPaid()` with `$paymentAttempt`

### Gate P4-G4 — Webhook Route Wired

- [ ] `PaymentWebhookController::handle()` calls `verifyWebhook()` with real gateway
- [ ] MockGateway is replaced or `PAYMENT_GATEWAY=lahza` is set in production `.env`
- [ ] Webhook endpoint registered with the payment provider dashboard
- [ ] Webhook secret stored in `.env` (not in code)

### Gate P4-G5 — Checkout Decoupled

- [ ] `CheckoutController::process()` no longer calls `markPaid()` directly
- [ ] Template checkout creates `PaymentAttempt` and redirects to gateway
- [ ] Return URL handler reads `PaymentAttempt` state (does not write it)
- [ ] `InvoiceCheckoutController::process()` demo flow removed or feature-flagged

### Gate P4-G6 — Failure Handling Implemented

- [ ] Cleanup job for expired `'initiated'` attempts exists
- [ ] Return redirect shows "processing" when webhook not yet received
- [ ] Admin notification/alert on `AmountMismatchException`

### Gate P4-G7 — Test Coverage

- [ ] Webhook handler tested with valid payload (succeeds → 202)
- [ ] Webhook handler tested with invalid HMAC (fails → 401)
- [ ] Duplicate webhook tested (second delivery → 202, no double-settlement)
- [ ] Amount mismatch tested (wrong amount → 422, no settlement)
- [ ] Cleanup job tested (expired `initiated` → `cancelled`)

### Gate P4-G8 — Security Review Passed

- [ ] No frontend parameter can trigger settlement
- [ ] No checkout form field influences the settled amount
- [ ] `InvoiceCheckoutController` demo `scenario=success` path disabled in production
- [ ] Webhook URL is not guessable (uses `{gateway}` key that matches env config)
- [ ] Webhook secret rotatable without code change (env-only)

---

## 9. Files to Create / Modify in Phase 5

> This section is a design contract only — no files have been created.

| File | Action | Purpose |
|------|--------|---------|
| `app/Payments/Gateways/LahzaGateway.php` | Create | Real gateway implementation |
| `app/Payments/Services/PaymentSettlementService.php` | Create | Idempotency + amount validation + settlement orchestration |
| `app/Http/Controllers/Front/CheckoutController.php` | Modify | Remove `markPaid()` call; add `createSession()` + redirect |
| `app/Http/Controllers/Front/CheckoutController.php` | Modify | Add `return()` method for redirect-back handling |
| `app/Http/Controllers/Client/InvoiceCheckoutController.php` | Modify | Remove `scenario=success` path; add `createSession()` flow |
| `app/Http/Controllers/PaymentWebhookController.php` | Modify | Add `PaymentSettlementService::settle()` call |
| `app/Console/Commands/ExpireInitiatedPaymentAttempts.php` | Create | Scheduled cleanup for timed-out attempts |
| `routes/payment.php` | Exists | Route already in place (Phase 3) |
| `config/payment.php` | Modify | Add `lahza` → `LahzaGateway::class` mapping |
| `.env` | Modify | `PAYMENT_GATEWAY=lahza`, `LAHZA_SECRET_KEY=...`, `LAHZA_WEBHOOK_SECRET=...` |
| `database/seeders/DashboardTranslationsSeeder.php` | Modify | Add payment UI strings |

---

## 10. ADR-007 Status After Phase 4

```
Phase 1 — Payment Abstraction Layer                 ✅ Complete + Hardened
Phase 2 — PaymentAttempt Infrastructure             ✅ Complete
Phase 3 — Webhook Stub Infrastructure               ✅ Complete
Phase 4 — Redirect Decoupling & Settlement Design   ✅ Complete (this document)
Phase 5 — Real Gateway Integration                  ⏳ Awaiting P4-G1 (provider selection)
```

---

## Appendix A — Current Coupling Summary

```
┌─────────────────────────────────────────────────────────────────────┐
│  BIGGEST COUPLING POINT                                             │
│                                                                     │
│  CheckoutController::process() line 434:                           │
│    app(InvoiceSettlementService::class)                             │
│        ->markPaid($invoice, $gateway->name());                      │
│                                                                     │
│  This single line:                                                  │
│   1. Marks the invoice paid                                         │
│   2. Activates the order                                            │
│   3. Activates the subscription                                     │
│   4. Provisions the tenant site                                     │
│   5. Optionally registers a domain                                  │
│                                                                     │
│  All without any payment verification.                              │
│  All inside the same HTTP request cycle as the form POST.           │
│  Possible for a user to trigger by submitting the checkout form     │
│  with no money changing hands.                                      │
└─────────────────────────────────────────────────────────────────────┘
```

## Appendix B — Quick Reference: Phase 5 Constraint

```
┌───────────────────────────────────────────────────────────┐
│  PHASE 5 INVARIANT (must hold after all changes)         │
│                                                           │
│  Checkout form POST   → creates Order, Attempt, returns  │
│                          redirect URL to gateway          │
│                                                           │
│  Gateway return URL   → reads Attempt status, shows UI   │
│                          NEVER writes settlement          │
│                                                           │
│  Webhook POST         → ONLY path that calls markPaid()  │
│                          after HMAC + amount verified     │
└───────────────────────────────────────────────────────────┘
```
