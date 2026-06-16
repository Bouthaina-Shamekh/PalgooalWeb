# ADR-007: Payment Gateway Integration

**Status:** Proposed  
**Date:** 2026-06-16  
**Author:** Architecture Review  
**Relates to:** ADR-003 (Billing Stability Window), docs/25-billing-system.md, docs/24-security-notes.md

---

## 1. Context

The system currently processes all checkout flows without any real payment verification. Every successful form submission immediately triggers `InvoiceSettlementService::markPaid($invoice, 'mock_gateway')`, which activates the subscription and provisions the cPanel account.

### Three Payment Entry Points (all currently mocked)

| Entry Point | File | Trigger |
|-------------|------|---------|
| Public checkout (template/plan) | `CheckoutController::process()` line 423 | AJAX form submit → immediate settlement |
| Client portal invoice | `InvoiceCheckoutController::handleSuccessfulPayment()` line 110 | POST with `scenario=success` → immediate settlement |
| Admin bulk action | `InvoiceController::bulkAction()` | Admin manually marks paid |

### What `mock_gateway` Does Today

```
POST /checkout/process
  └─ Create Order + Invoice (status: draft)
  └─ markPaid($invoice, 'mock_gateway')         ← settlement, NO real payment
       └─ DB::transaction + lockForUpdate()
       └─ invoice.status = 'paid'
       └─ OrderActivationService::activate()
            └─ subscription.status = 'active'
            └─ TenantProvisioningService::provision()  ← cPanel account created
```

`InvoiceCheckoutController` additionally accepts a `scenario` field (`success` / `failed` / `cancel`) — the payment UI is a demo card form with no real gateway connection.

### Current Settlement Layer Strengths

The existing `InvoiceSettlementService` has production-grade internal mechanics:
- `DB::transaction` with `lockForUpdate()` — prevents double-settlement within a single process
- Early-return guard: `if ($lockedInvoice->status === 'paid') { return; }` — idempotent within a request

These strengths must be preserved in the new architecture.

### Current Settlement Layer Weaknesses

- No external idempotency key for webhook retries from a real gateway
- No `gateway_transaction_id` stored — no way to reconcile with gateway dashboard
- No `PaymentAttempt` record — cannot audit failed, retried, or fraudulent payment attempts
- Activation triggered synchronously in the same HTTP request that submits the checkout form

---

## 2. Problem

### 2.1 Critical Risk: Revenue Bypass

Any authenticated client (or unauthenticated user who registers at checkout) can complete a purchase without making a real payment. The system provides:
- A working cPanel hosting account
- An active subscription record
- A paid invoice

with zero financial exchange.

### 2.2 Specific Risks

| Risk | Severity | Current State |
|------|----------|---------------|
| Free hosting for any registrant | CRITICAL | Active in production |
| No webhook signature verification | CRITICAL | No webhook endpoint exists |
| No idempotency on webhook retries | High | Same-process guard only |
| No `gateway_transaction_id` storage | High | Not tracked anywhere |
| Activation from HTTP redirect (no webhook wait) | High | Redirect triggers activation |
| Frontend price trusted without server revalidation | High | `price_cents` from client JS used directly |
| Double-settlement on network retry | Medium | Internal guard only, no external key |
| No failed payment audit trail | Medium | `PaymentAttempt` table does not exist |

### 2.3 The `mock_gateway` String is Hardcoded in 4 Places

```
CheckoutController::process()                       line 423
InvoiceCheckoutController::handleSuccessfulPayment() line 110
InvoiceSettlementService::syncStandaloneInvoiceDomain() line 79 (fallback default)
Domain::query()::update(['payment_method' => $paymentMethod ?: 'mock_gateway'])
```

---

## 3. Current Payment Flow

```
[Client Browser]
     │
     │  POST /checkout/process
     ▼
[CheckoutController::process()]
     │
     ├─ Validate client identity (register or login)
     ├─ DB::transaction {
     │    Create Order (status: pending)
     │    Create Invoice (status: draft)
     │    Create Subscription(s) (status: pending)
     │    Create InvoiceItem(s)
     │  }
     │
     └─ markPaid($invoice, 'mock_gateway')   ◄── NO real payment occurs
          │
          └─ DB::transaction + lockForUpdate {
               invoice.status = 'paid'
               order.status = 'active'
               OrderActivationService::activate()
                    │
                    ├─ subscription.status = 'active'
                    └─ TenantProvisioningService::provision()
                         └─ WHM API → create cPanel account
             }
```

**Vulnerability:** Steps 4–7 (settlement through provisioning) execute without any external financial confirmation.

---

## 4. Required Payment Flow (Webhook-First)

```
[Client Browser]
     │
     │  POST /checkout/process
     ▼
[CheckoutController::process()]
     │
     ├─ Validate client identity
     ├─ DB::transaction {
     │    Create Order (status: pending)
     │    Create Invoice (status: draft, total_cents from SERVER — not from request)
     │    Create Subscription(s) (status: pending)
     │    Create PaymentAttempt (status: initiated, idempotency_key: uuid)
     │  }
     │
     └─ Call PaymentGatewayInterface::createSession($invoice, $idempotencyKey)
          │
          └─ Returns { checkout_url, session_id }
               │
               └─ Redirect client to gateway hosted checkout page
                         │
                    [Gateway processes payment]
                         │
               ┌─────────┴──────────────────┐
               │                            │
    [Redirect back to site]      [Webhook POST /payment/webhook]
    (informational only —         │
     do NOT activate here)        ├─ Verify signature (HMAC/secret)
                                  ├─ Lookup PaymentAttempt by session_id
                                  ├─ Validate amount matches invoice.total_cents
                                  ├─ Check idempotency_key not already settled
                                  │
                                  └─ markPaid($invoice, $gatewayName)
                                       │
                                       └─ [Existing settlement flow preserved]
                                            subscription.status = 'active'
                                            TenantProvisioningService::provision()
```

**Key invariant:** Subscription activation NEVER occurs from the client redirect. It occurs ONLY after a verified, server-to-server webhook with a confirmed `payment_status: paid` response.

---

## 5. Provider Evaluation Criteria

When selecting a payment gateway, the following criteria must be evaluated. No provider is selected in this ADR because confirmation of regional availability and commercial terms requires direct vendor inquiry.

### 5.1 Mandatory Criteria (Blockers if missing)

| Criterion | Why Mandatory |
|-----------|---------------|
| Palestine / Palestinian clients accepted | Primary customer base location |
| Webhook support (server-to-server event notification) | Required for webhook-first architecture |
| Hosted checkout page (gateway-side, PCI-descoped) | Eliminates PCI DSS scope from this codebase |
| HTTPS webhook endpoint with HMAC signature | Required for webhook authenticity verification |
| USD currency support | `invoices.currency = 'USD'` |
| Test / sandbox mode | Required for development and staging |

### 5.2 Preferred Criteria (Ranked)

| Criterion | Weight | Notes |
|-----------|--------|-------|
| Webhook idempotency (event ID deduplication) | High | Prevents double-settlement on gateway retry |
| Refund API | High | Required for subscription cancellation refunds |
| Settlement to Palestinian or Jordanian bank account | High | Operational requirement |
| Dispute / chargeback webhook events | Medium | For fraud monitoring |
| Subscription / recurring billing API | Medium | Future: auto-renewal |
| Fee structure (% + flat rate) | Medium | Business decision |
| Time to first payout | Medium | Cash flow |
| Arabic-language hosted checkout | Low | UX improvement |

### 5.3 Candidate Providers

The following providers are candidates for evaluation. None is selected in this ADR.

#### Lahza (lahza.io)
- Designed for MENA region including Palestine
- Accepts Palestinian businesses
- Webhook support: requires direct confirmation of event catalog and signature method
- Hosted checkout: requires confirmation
- Settlement: requires confirmation of bank compatibility
- **Action required:** Contact Lahza sales for: sandbox access, webhook docs, Palestinian business onboarding checklist

#### Stripe
- Widely documented, mature webhook infrastructure, strong idempotency support
- **Blocker:** Does not directly support Palestinian-registered businesses as the merchant of record as of 2025. Workaround via intermediary entity requires legal review.
- **Action required:** Legal review of entity structure before pursuing Stripe

#### PayPal
- Widely available, hosted checkout
- Higher fees than alternatives; settlement to Palestinian entities requires verification
- Webhook support exists but historically less reliable than Stripe
- **Action required:** Verify Palestinian business account eligibility and settlement options

#### Manual Bank Transfer
- Zero gateway integration cost
- Eliminates all real-time verification — admin must manually mark invoices paid
- Suitable only as a fallback or secondary payment method for high-value orders
- Does not satisfy the webhook-first architecture requirement
- **Use case:** Can remain as a secondary option for enterprise/large clients while gateway handles standard checkout

---

## 6. Decision

### 6.1 Immediate Decision: Gateway-Agnostic Architecture First

**The architecture will be built before the provider is confirmed.** This is the correct sequencing because:
1. Provider evaluation requires business/legal steps outside this codebase
2. The `mock_gateway` risk exists regardless of which provider is chosen
3. A well-designed abstraction layer means provider swap costs near-zero effort

The implementation will build a `PaymentGatewayInterface` contract and a `MockGateway` implementation that replaces the current implicit mocking. When a real provider is confirmed, a concrete implementation (e.g. `LahzaGateway`) is dropped in.

### 6.2 Provider Decision Gate

Provider selection will be decided in a separate ADR-007-B or ADR-007 amendment after:
- [ ] Vendor inquiry completed for top candidate(s)
- [ ] Sandbox credentials obtained and tested
- [ ] Legal entity review completed (if Stripe pursued)
- [ ] Settlement bank account confirmed

### 6.3 mock_gateway in Production

`mock_gateway` **must be disabled for public checkout** before commercial launch. The path to disabling it:
1. Build `PaymentGatewayInterface` and `PaymentAttempt` model (Phase 1 — no provider needed)
2. Add config flag: `PAYMENT_GATEWAY_ENABLED=false` → redirect to "payment coming soon" page
3. On provider confirmation, build concrete gateway class and set `PAYMENT_GATEWAY_ENABLED=true`

Until Phase 1 is complete, `mock_gateway` may remain **for admin-only invoice marking** (the third entry point: admin bulk action). It must be removed from the two public-facing entry points as the first priority.

---

## 7. Architecture Decision

### 7.1 PaymentGatewayInterface Contract

The interface defines what any concrete gateway must provide. It is defined here for clarity; the actual PHP interface will be written in the implementation phase.

**Required methods:**

| Method | Input | Output | Purpose |
|--------|-------|--------|---------|
| `createSession` | Invoice, idempotency_key, return_url, cancel_url | `{session_id, checkout_url}` | Initiate hosted checkout |
| `verifyWebhook` | raw_payload, signature_header | `WebhookEvent` (typed) | Authenticate webhook request |
| `getTransaction` | gateway_transaction_id | `TransactionStatus` | Manual reconciliation / retry check |
| `refund` | gateway_transaction_id, amount_cents | `RefundResult` | Process refund |
| `name` | — | string | Gateway identifier stored in `payment_method` column |

### 7.2 PaymentAttempt Model

A new `payment_attempts` table tracks every interaction with an external gateway.

**Purpose:**
- Audit trail for all payment attempts (initiated, failed, succeeded, refunded)
- Stores `idempotency_key` — webhook handler checks this before settling
- Stores `gateway_transaction_id` — links to gateway dashboard record
- Stores `gateway_session_id` — matches webhook event to the correct invoice
- Prevents duplicate settlement even on webhook retry

**Status machine:**

```
initiated → pending → succeeded
                  └─→ failed
                  └─→ cancelled
succeeded → refunded (partial or full)
```

### 7.3 Webhook Handler Requirements

The webhook endpoint (`POST /payment/webhook/{gateway}`) must:

1. **Authenticate first** — verify HMAC signature before any database operation. Reject immediately if signature invalid (HTTP 401).
2. **Parse event type** — only process `payment.succeeded` (or gateway-equivalent). Ignore all other events (return HTTP 200 to acknowledge).
3. **Lookup by `gateway_session_id`** — find the `PaymentAttempt` record. If not found, return HTTP 200 (unknown session, not our event).
4. **Check idempotency** — if `payment_attempts.status = 'succeeded'` already, return HTTP 200 immediately. Do not re-settle.
5. **Validate amount** — confirm `gateway_amount_cents == invoice.total_cents`. If mismatch, log critical alert and do NOT settle.
6. **Mark PaymentAttempt succeeded** — update `gateway_transaction_id`, `gateway_amount_cents`, `status = 'succeeded'`.
7. **Call `markPaid($invoice, $gateway->name())`** — the existing settlement service handles the rest.
8. **Return HTTP 200** — gateway will retry on any non-2xx response.

### 7.4 Idempotency Key Design

- Generated as `Str::uuid()` at Order creation time
- Stored in `payment_attempts.idempotency_key`
- Passed to gateway `createSession()` — gateway must support idempotency key passthrough
- Webhook handler checks `payment_attempts.status` before settling (not the key alone)
- The key prevents creating duplicate sessions on browser back-button / double-submit

### 7.5 Server-Side Amount Validation

The current checkout calculates price in PHP using `$template->resolvedPriceCents()`. This is already correct. However, the amount sent to the gateway must be re-read from the database at webhook time — never from the original request or session.

```
Order creation: invoice.total_cents = computed server-side from resolvedPriceCents()
Gateway session: amount = invoice.total_cents (from DB, not from request)
Webhook validation: gateway_amount_cents MUST equal invoice.total_cents (re-read from DB)
```

The frontend `TEMPLATE_FINAL_CENTS` JavaScript variable (ADR-003 D2) is display-only. It must never be the source of truth for the amount charged.

---

## 8. Required Schema

### 8.1 `payment_attempts` Table

This table does not exist yet. It will be created in Phase 2 of the migration strategy.

**Columns:**

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| `id` | bigint unsigned PK | no | Primary key |
| `invoice_id` | bigint FK → invoices.id | no | Which invoice this attempt is for |
| `order_id` | bigint FK → orders.id | yes | Denormalized for quick lookup |
| `client_id` | bigint FK → clients.id | yes | Denormalized for audit queries |
| `gateway` | varchar(50) | no | Gateway name: `'lahza'`, `'stripe'`, `'mock_gateway'` |
| `idempotency_key` | varchar(100) unique | no | UUID; prevents duplicate sessions |
| `gateway_session_id` | varchar(255) | yes | Hosted checkout session ID from gateway |
| `gateway_transaction_id` | varchar(255) | yes | Final transaction ID after payment |
| `gateway_amount_cents` | int unsigned | yes | Amount the gateway confirmed; validated against `invoice.total_cents` |
| `currency` | char(3) | no default `'USD'` | ISO 4217 |
| `status` | enum | no | `initiated`, `pending`, `succeeded`, `failed`, `cancelled`, `refunded` |
| `gateway_status_raw` | varchar(100) | yes | Raw status string from gateway (for debugging) |
| `gateway_response` | json | yes | Full gateway response payload (for audit/debugging) |
| `webhook_verified_at` | timestamp | yes | When webhook signature was verified |
| `settled_at` | timestamp | yes | When `markPaid()` completed |
| `refunded_at` | timestamp | yes | When refund was confirmed |
| `refund_amount_cents` | int unsigned | yes | Amount refunded |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

**Indexes:**

| Index | Columns | Type | Purpose |
|-------|---------|------|---------|
| PK | `id` | primary | |
| UQ | `idempotency_key` | unique | Prevent duplicate sessions |
| IDX | `gateway_session_id` | index | Webhook lookup by session |
| IDX | `gateway_transaction_id` | index | Reconciliation lookup |
| IDX | `invoice_id` | index | Invoice → attempt(s) |
| IDX | `status` | index | Status filtering |

### 8.2 Columns to Add to Existing Tables

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| `invoices` | `payment_attempt_id` | bigint FK nullable | Link settled invoice to its `PaymentAttempt` |
| `domains` | `payment_method` | varchar (already exists) | Will receive real gateway name instead of `'mock_gateway'` |

No columns are dropped in Phase 1 or Phase 2. The `payment_method` column on `domains` and `invoices` already exists and will naturally receive the real gateway name once the gateway is live.

---

## 9. Security Requirements

### 9.1 Webhook Signature Verification

Every gateway-facing webhook endpoint must verify the request signature before processing:
- Stripe: `Stripe-Signature` header with HMAC-SHA256
- Lahza: confirm exact header name and algorithm with Lahza documentation
- Verification must happen **before any database read/write**
- Reject with HTTP 401 on signature failure; do not log the payload before verification

### 9.2 No Activation from Redirect

The gateway redirect URL (`/checkout/success?session_id=...`) is a client-controlled HTTP request. It must:
- Display a "processing" page only
- NOT call `markPaid()` under any circumstances
- Poll `PaymentAttempt.status` (via AJAX or page reload) to show final state to client
- OR listen for a Laravel Echo / Pusher event dispatched by the webhook handler

The current `CheckoutController::buildSuccessState()` can be adapted to read from `PaymentAttempt` status rather than invoice status.

### 9.3 Amount Validation on Webhook

The webhook handler must validate:
```
gateway_amount_cents == invoice.total_cents (loaded fresh from DB)
```
If these differ, the payment must NOT be settled. Log a `CRITICAL` error with full context.

This prevents:
- Price manipulation via modified frontend requests
- Partial payment attacks (paying $1 for a $100 invoice)

### 9.4 Idempotency

The webhook handler checks `payment_attempts.status` before calling `markPaid()`. Combined with `InvoiceSettlementService`'s existing `lockForUpdate` + paid-status guard, this provides two layers:
- Layer 1 (outer): `PaymentAttempt.status === 'succeeded'` → return early before `markPaid()`
- Layer 2 (inner): `Invoice.status === 'paid'` inside `DB::transaction` → return early

### 9.5 No Frontend Price Trust

The `TEMPLATE_FINAL_CENTS` JavaScript variable exists for display purposes (ADR-003 D2 fix). It must never be submitted to the server as the payment amount. The server always reads `invoice.total_cents` from the database.

### 9.6 Rate Limiting on Checkout

The public checkout endpoint should have rate limiting applied per IP and per client account to prevent automated abuse. This is outside the scope of this ADR but should be addressed in the same phase.

---

## 10. Migration Strategy

### Phase 1 — Abstract the Mock (no provider needed, no breaking changes)

**Goal:** Replace implicit mocking with explicit, configurable gateway abstraction.

- Define `PaymentGatewayInterface` contract
- Create `MockGateway` implementation that wraps current behavior
- Add `PAYMENT_GATEWAY` config key in `config/payment.php`
- Replace the three hardcoded `'mock_gateway'` strings with `$gateway->name()`
- Add feature flag `PAYMENT_GATEWAY_ENABLED` in `.env`
- When `PAYMENT_GATEWAY_ENABLED=false`: public checkout redirects to "payment coming soon" page; admin bulk-mark-paid remains functional

**Outcome:** `mock_gateway` no longer hardcoded; can be disabled for public routes without code change.

### Phase 2 — Create PaymentAttempt Infrastructure

**Goal:** Database and model layer for tracking gateway interactions.

- Migration: create `payment_attempts` table (schema defined in Section 8)
- `PaymentAttempt` Eloquent model with status enum, relationships, scopes
- Migration: add `invoices.payment_attempt_id` FK nullable
- Update `InvoiceSettlementService::markPaid()` to accept optional `PaymentAttempt $attempt` and link it on settlement
- Update `MockGateway` to create a `PaymentAttempt` record (status: `succeeded`) for admin traceability

**Outcome:** Every settlement is linked to an audit record, even via mock.

### Phase 3 — Webhook Endpoint (provider-agnostic stub)

**Goal:** Build the webhook infrastructure before any real gateway is connected.

- Route: `POST /payment/webhook/{gateway}` → `PaymentWebhookController`
- `PaymentWebhookController::handle()` dispatches to `PaymentGatewayInterface::verifyWebhook()`
- Signature verification, idempotency check, amount validation — all as documented in Section 7.3
- Returns HTTP 200 in all cases after initial auth check (standard gateway requirement)
- Tests: verify that double-POST of same event does not double-settle
- Tests: verify that tampered signature returns 401 and does not settle

**Outcome:** Webhook infrastructure tested and ready before provider credentials arrive.

### Phase 4 — Client Redirect Page (no activation)

**Goal:** Decouple the redirect URL from activation.

- Modify `CheckoutController::process()` to redirect to gateway hosted checkout instead of calling `markPaid()` directly
- Create `/checkout/pending?attempt_id={id}` page that polls `PaymentAttempt.status`
- Confirm template checkout success state is driven by `PaymentAttempt.status` not by direct `markPaid()` call
- Keep existing `buildSuccessState()` but feed it from `PaymentAttempt` after webhook fires

**Outcome:** Public checkout no longer activates subscriptions synchronously. Activation depends solely on webhook.

### Phase 5 — Real Gateway Implementation

**Goal:** Wire in confirmed payment provider.

- Implement concrete gateway class (e.g., `LahzaGateway implements PaymentGatewayInterface`)
- Test in sandbox: create session, simulate webhook, verify settlement, verify idempotency on retry
- Update `PAYMENT_GATEWAY=lahza` and `PAYMENT_GATEWAY_ENABLED=true` in production `.env`
- Monitor first 48 hours: confirm webhook delivery, `payment_attempts` records, cPanel provisioning

**Outcome:** First real payment processed through verified gateway.

---

## 11. Consequences

### Positive

- Eliminates the critical revenue bypass (any registrant gets free hosting)
- Provides complete audit trail of all payment interactions
- Enables future refund processing, dispute handling, subscription auto-renewal
- Architecture is provider-agnostic — switching gateways requires only one new class
- `InvoiceSettlementService`'s existing transaction safety is preserved and enhanced

### Negative / Trade-offs

- Checkout flow introduces async gap (redirect → webhook → activation) — client must wait or poll for provisioning status
- Webhook delivery is not guaranteed by all providers (especially in MENA region) — must handle delayed or missing webhooks with a fallback manual-confirm admin action
- Phase 1–4 add code complexity before any real revenue improvement; must be completed correctly to avoid introducing new bugs
- Test mode credentials must be kept strictly separated from production credentials (`.env` discipline required)

### Risks Introduced

| Risk | Mitigation |
|------|-----------|
| Webhook delivery failure (gateway issue) | Admin "retry settlement" action in invoice UI + monitoring dashboard |
| Webhook arrives before redirect (race condition) | Idempotency handles this correctly — settled once regardless of order |
| Gateway session expires before client completes checkout | Set session expiry to ≥ 30 minutes; show clear "session expired" UX |
| Provider blocks Palestinian clients at onboarding | Confirmed before Phase 5; fallback to Manual Bank Transfer for affected clients |

---

## 12. Open Questions

| # | Question | Owner | Target Resolution |
|---|----------|-------|------------------|
| OQ-1 | Which gateway can onboard a Palestinian-registered business with USD settlement? | Business | Before Phase 5 |
| OQ-2 | Does Lahza provide webhook event IDs for idempotency, or must we deduplicate by session_id only? | Technical (after sandbox access) | Phase 3 prep |
| OQ-3 | Should subscription auto-renewal be in scope for ADR-007 or a separate ADR-013? | Architecture | Before Phase 5 |
| OQ-4 | What is the fallback for a client whose payment succeeds at the gateway but webhook is never delivered? | Product + Technical | Phase 3 |
| OQ-5 | Should `PAYMENT_GATEWAY_ENABLED=false` show a waiting page, disable checkout routes entirely, or allow Bank Transfer only? | Product | Phase 1 |
| OQ-6 | Is the admin "bulk mark paid" entry point kept as-is for Manual Bank Transfer after real gateway launches? | Product | Phase 5 |

---

## 13. References

### Internal

- `app/Services/Billing/InvoiceSettlementService.php` — settlement entry point; `markPaid()`, `lockForUpdate()`, `syncStandaloneInvoiceDomain()`
- `app/Services/Billing/OrderActivationService.php` — post-settlement activation; `activate()`, `TenantProvisioningService::provision()`
- `app/Http/Controllers/Front/CheckoutController.php` — public checkout; `process()` line 423 (`markPaid('mock_gateway')`)
- `app/Http/Controllers/Client/InvoiceCheckoutController.php` — client portal invoice payment; `handleSuccessfulPayment()` line 110
- `docs/25-billing-system.md` — billing system documentation
- `docs/24-security-notes.md` — security risk register
- `docs/ARCHITECTURE_HEALTH_REVIEW_2026-06.md` — originated ADR-007 recommendation (Risk #1)
- `docs/ADR_003_STABILITY_WINDOW_PLAN.md` — `subscriptions.price` decimal column drop gated on G4/G5; must not regress

### External (for provider evaluation)

- Lahza documentation: https://lahza.io/docs
- Stripe webhook security: https://stripe.com/docs/webhooks/signatures
- PayPal webhooks: https://developer.paypal.com/docs/api-basics/notifications/webhooks/
- PCI DSS SAQ A (for hosted checkout, out-of-scope card data): https://www.pcisecuritystandards.org

---

## 14. Appendix: Minimum Safe Implementation

If only a subset of phases can be completed before commercial launch, the minimum safe state is:

**Phase 1 only (disable public mock):**
- Add `PAYMENT_GATEWAY_ENABLED` feature flag
- When `false`: public checkout (`CheckoutController::process()` and `InvoiceCheckoutController::process()`) returns "payment not yet available" — no settlement occurs
- Admin entry point (bulk-mark-paid) remains functional for Manual Bank Transfer processing
- No new revenue possible from website, but no free hosting either

This is the minimum change that eliminates the critical risk without requiring a real gateway integration.
