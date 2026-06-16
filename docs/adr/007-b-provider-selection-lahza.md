# ADR-007-B — Provider Selection: Lahza Readiness Audit

**Date:** 2026-06-17  
**Status:** Research Complete — Conditional Go  
**Scope:** Research-only audit. No code written. No gateway integrated.  
**Prerequisite for:** ADR-007 Phase 5 (Real Gateway Implementation)

---

## 1. Summary

Lahza is a Palestinian payment gateway (founded 2022) built specifically to serve merchants in Palestine. It provides a hosted redirect checkout, webhook notifications with HMAC-SHA256 signatures, and supports ILS / JOD / USD in both transaction and settlement. The technical API is well-documented and maps cleanly onto the ADR-007 architecture.

However, **two critical gaps prevent an unconditional Go decision**:

1. **Refund API is undocumented** — the official refund docs page exists but contains no content. Whether programmatic refunds are supported via API is unknown.
2. **Business eligibility is not publicly documented** — Lahza's website contains no signup eligibility criteria, no list of required onboarding documents, and no explicit statement about whether Gaza-based companies can onboard. This must be confirmed directly with Lahza before `LahzaGateway` is written.

**Recommendation: Conditional Go.** Contact Lahza to resolve both gaps. If answers are satisfactory, Phase 5 can proceed. If Lahza cannot onboard this business, the fallback is **Tap Payments** (see Section 10).

---

## 2. Sources Reviewed

| # | Source | URL | Accessed |
|---|--------|-----|----------|
| 1 | Lahza docs — Accept Payments | `https://docs.lahza.io/payments/accept-payments` | 2026-06-17 |
| 2 | Lahza docs — Webhooks | `https://docs.lahza.io/payments/webhooks` | 2026-06-17 |
| 3 | Lahza docs — Verify Payments | `https://docs.lahza.io/payments/verify-payments` | 2026-06-17 |
| 4 | Lahza docs — Test Payments | `https://docs.lahza.io/payments/test-payments` | 2026-06-17 |
| 5 | Lahza docs — Refunds | `https://docs.lahza.io/payments/refunds` | 2026-06-17 (page is empty) |
| 6 | Lahza docs — Go Live Checklist | `https://docs.lahza.io/guide/go-live-checklist` | 2026-06-17 |
| 7 | PayAtlas — Lahza profile | `https://payatlas.com/company/lahza-2797` | 2026-06-17 |
| 8 | World Bank — E-Payments West Bank/Gaza | `https://www.worldbank.org/en/news/feature/2024/12/16/...` | 2026-06-17 |
| 9 | Lahza docs — sitemap / llms.txt | `https://docs.lahza.io/llms.txt` | 2026-06-17 |

---

## 3. Business Eligibility

### What is confirmed

- Lahza explicitly markets itself as a gateway "designed to empower businesses and individuals in **Palestine**" (PayAtlas profile, company description).
- The gateway is based in Palestine (country: PS, founded 2022).
- MENA is the documented service region.
- **SaaS / Software** is explicitly listed as a supported industry vertical in the PayAtlas feature matrix.
- **Domain Registration & Hosting** and **Streaming & Media Subscriptions** also appear in the supported industries list — matching this project's use case exactly.

### What is NOT confirmed

- Lahza's public documentation contains **no merchant onboarding eligibility page**.
- There is no publicly listed requirement document (business registration, national ID, bank account details).
- There is no explicit statement about whether **Gaza-based companies** specifically can onboard, or whether conflict-related banking restrictions (correspondent banking, OFAC, etc.) affect account opening.
- No self-service signup flow was observed — onboarding appears to require direct contact.

### Risk assessment

The Palestinian banking environment is subject to significant external constraints. Gaza in particular has faced correspondent banking restrictions since 2023. Even though Lahza is Palestinian-built, it operates through **Bank of Palestine** infrastructure — whether Bank of Palestine currently provides merchant accounts to Gaza-based entities is a question that cannot be answered from public documentation.

**This is the single highest-risk unknown in this audit.**

---

## 4. Technical Capability

All claims below are sourced directly from `docs.lahza.io`.

### 4.1 Checkout Flow — ✅ Confirmed

Lahza uses a **redirect (hosted page) model**:

```
POST https://api.lahza.io/transaction/initialize
Authorization: Bearer {SECRET_KEY}
{
  "email": "customer@example.com",
  "amount": 15000,          // amount in smallest currency unit (fils/agorot/cents)
  "currency": "ILS",
  "reference": "INV-2024-001",  // merchant-defined unique reference
  "callback_url": "https://yoursite.com/payment/callback"
}
```

Response contains `authorization_url` — redirect the user there. Card details are entered on Lahza's hosted page (PCI-DSS compliant; merchant never touches card data).

### 4.2 Webhook Notifications — ✅ Confirmed

- **Event:** `charge.success` (payment completed), refund events
- **Signature header:** `x-lahza-signature`
- **Algorithm:** HMAC SHA256 using `SECRET_KEY` over raw request body
- **Retry logic:** Every 3 minutes × 4 attempts, then every hour for 72 hours
- **IP whitelist:** Lahza documents specific IPs for webhook origin — can be used for additional server-side validation

### 4.3 Transaction Verification — ✅ Confirmed

```
GET https://api.lahza.io/transaction/verify/{reference}
Authorization: Bearer {SECRET_KEY}
```

Response includes: `status`, `amount`, `currency`, `reference`, `country_code: "PS"`, `gateway_response`. This allows the webhook handler to independently verify a payment rather than trusting the webhook payload alone.

### 4.4 Test / Sandbox Mode — ✅ Confirmed

- Test keys: `pk_test_*` / `sk_test_*`
- Test Visa card: `4111111111111111`
- Test Mastercard: `5424000000000015`
- Sandbox environment documented and available before production signup

### 4.5 Refund API — ❌ NOT DOCUMENTED

The page `https://docs.lahza.io/payments/refunds` exists but contains **zero content** — only the page title "Refunds" with no API documentation, no endpoint, no parameters. It is impossible to determine from public sources whether:

- Programmatic refunds are supported via API
- Refunds must be issued manually through the Lahza dashboard
- Partial refunds are possible
- Refunds trigger a webhook event

This is a hard gap. The `PaymentGatewayInterface` in ADR-007 includes a `refund()` method. If Lahza has no refund API, `LahzaGateway::refund()` would need to throw `PaymentException('Refunds must be issued manually via Lahza dashboard')`.

### 4.6 Idempotency Keys — ⚠️ Not explicitly documented

The `reference` parameter in `initialize` must be unique and alphanumeric — this functions as a de-facto idempotency key (duplicate reference = rejected transaction). However, Lahza does not use the term "idempotency key" nor document a dedicated header for it.

**Implication for ADR-007:** The `payment_attempts.idempotency_key` column (created in Phase 2) can be populated with the Lahza `reference` value. The webhook handler can look up `PaymentAttempt` by `idempotency_key` (= `reference`) to prevent duplicate settlement.

### 4.7 Recurring / Subscription Billing — ⚠️ Not documented

Lahza does **not** document a recurring billing or tokenization API. This platform manages subscription renewals internally (via `DomainRenewalService` + auto-renewal jobs) and does not rely on the payment gateway for recurring charges — each renewal generates a new invoice and the client pays manually. This is not a blocker.

---

## 5. Currency and Settlement

| Aspect | Detail | Source |
|--------|--------|--------|
| Transaction currencies | ILS, JOD, USD | PayAtlas feature matrix |
| Settlement currencies | ILS, JOD, USD | PayAtlas feature matrix |
| Settlement speed | Less than 24 hours | Real user review (PayAtlas, Nov 2025) |
| Settlement bank | Bank of Palestine (Terminal) | Lahza docs (implicit) |
| Acquiring bank | Bank of Palestine | docs.lahza.io (Terminal section) |

**ILS support is strategically important** — this platform targets Palestinian clients who may wish to pay in Israeli New Shekel, the de-facto currency of the West Bank/Gaza private sector. Lahza supporting ILS natively removes any FX conversion cost for those clients.

USD support is relevant for clients who purchase hosting plans priced in USD.

---

## 6. Fit with ADR-007 Architecture

The ADR-007 `PaymentGatewayInterface` requires five methods. Here is the mapping:

| Interface Method | Lahza Capability | Fit |
|-----------------|-----------------|-----|
| `name(): string` | Return `'lahza'` | ✅ Trivial |
| `createSession(Invoice, PaymentAttempt): SessionResult` | `POST /transaction/initialize` → `authorization_url` | ✅ Direct map |
| `verifyWebhook(string $payload, string $signature): WebhookEvent` | HMAC SHA256 with `x-lahza-signature` over raw body | ✅ Standard pattern |
| `getTransaction(string $transactionId): TransactionResult` | `GET /transaction/verify/{reference}` | ✅ Direct map |
| `refund(PaymentAttempt, int $amountCents): RefundResult` | ❌ API undocumented | ⚠️ May need to throw or stub |

### Checkout flow mapping

```
Client clicks "Pay" (Phase 4 decoupled checkout)
    ↓
CheckoutController creates PaymentAttempt (status=initiated)
    ↓
LahzaGateway::createSession()
  → POST /transaction/initialize
  → reference = $attempt->idempotency_key
  → callback_url = route('payment.webhook', ['gateway' => 'lahza'])
    ↓
Redirect client to $sessionResult->redirectUrl (authorization_url)
    ↓
Client enters card on Lahza hosted page
    ↓
Lahza fires webhook: POST /payment/webhook/lahza
    ↓
PaymentWebhookController::handle()
  → verifyWebhook() — HMAC check
  → PaymentAttempt::where('idempotency_key', $event->reference)->firstOrFail()
  → idempotency guard (already succeeded? return 202)
  → amount validation (event amount === attempt.gateway_amount_cents)
  → markPaid($invoice, 'lahza', $attempt)
  → return 202
```

This flow aligns exactly with the Phase 4 settlement design. No architectural changes are needed.

### WebhookEvent DTO mapping (Phase 5)

```php
// From Lahza charge.success webhook payload:
return new WebhookEvent(
    transactionId: $payload['data']['reference'],      // merchant reference
    sessionId:     $payload['data']['id'],             // Lahza transaction ID
    amountCents:   $payload['data']['amount'],         // already in smallest unit
    currency:      $payload['data']['currency'],
    status:        'succeeded',
    rawPayload:    $rawJson,
);
```

---

## 7. Risks

| Risk | Severity | Likelihood | Mitigation |
|------|----------|------------|------------|
| **Business cannot onboard** — Gaza banking restrictions prevent Lahza from issuing a merchant account | 🔴 Critical | Medium | Confirm eligibility before writing any code. Have Tap Payments as fallback. |
| **Refund API absent** — programmatic refunds impossible | 🟡 Medium | High (docs are empty) | Implement `refund()` as manual-only with dashboard link. Confirm with Lahza support. |
| **Card acceptance rate** — real review notes "not all cards work" and payment failures for some clients | 🟡 Medium | Confirmed | Acceptable for initial launch; monitor and address post-launch. |
| **Small provider risk** — Lahza founded 2022, limited third-party reviews (2 on PayAtlas), no independent benchmarks | 🟡 Medium | Low-Medium | Acceptable given the Palestinian market context. No alternative serves ILS natively. |
| **No idempotency header** — reference-based de-duplication is implicit | 🟢 Low | Low | Use `idempotency_key` as the Lahza `reference`. The uniqueness constraint on `payment_attempts.idempotency_key` prevents doubles. |
| **IP whitelist not enforced** — Lahza publishes IPs but enforcement is optional | 🟢 Low | Low | Implement IP whitelist in `verifyWebhook()` as defense-in-depth. |
| **Webhook retry storm** — retries every 3 min × 4 then hourly × 72h | 🟢 Low | Low | Idempotency guard in `PaymentWebhookController::handle()` (Phase 4 design) handles this correctly. |

---

## 8. Decision

**Conditional Go — pending eligibility confirmation.**

Lahza is the correct technical choice for this platform. The API design matches ADR-007 without modification. ILS/JOD/USD currency support is unique in the Palestinian market. The go-live checklist requirements (SSL, refund policy page, purchase receipt email) are standard and implementable.

The two mandatory pre-conditions before writing `LahzaGateway`:

1. **Eligibility confirmed in writing** — Lahza confirms this business can open a merchant account and receive settlements.
2. **Refund capability clarified** — Lahza confirms whether programmatic refunds are available via API, or whether the `refund()` method must be implemented as manual-only.

If both conditions are met → `LahzaGateway` can be implemented in Phase 5.  
If eligibility fails → proceed with Tap Payments (see Section 10).

---

## 9. Required Follow-Up Questions to Lahza

Send these to Lahza support / sales before proceeding:

**Q1 — Eligibility:**
> We are a SaaS company operating a web hosting platform. Can a Gaza-based business entity open a Lahza merchant account? What documents are required for onboarding?

**Q2 — Refund API:**
> Your documentation page for refunds (`docs.lahza.io/payments/refunds`) appears to be empty. Is there a programmatic refund API endpoint? If yes, what is the endpoint and what parameters does it accept? If no, must refunds be issued manually through the Lahza dashboard?

**Q3 — Settlement for Gaza merchants:**
> We understand Lahza settles via Bank of Palestine. Does settlement work for businesses whose bank account is at a Gaza branch, or only West Bank branches?

**Q4 — Idempotency:**
> If we submit a `POST /transaction/initialize` with a `reference` that was already used for a successful transaction, does the API reject it with an error, or does it create a duplicate charge?

**Q5 — Webhook IP range:**
> What are the production IP addresses Lahza uses to send webhook notifications? We want to implement IP allowlisting on our server for security.

---

## 10. Go / No-Go Recommendation

### If Lahza confirms eligibility + refund API exists:
**→ GO. Begin Phase 5.**

Phase 5 scope:
- Create `app/Payments/Gateways/LahzaGateway.php` implementing `PaymentGatewayInterface`
- Set `PAYMENT_GATEWAY=lahza` in `.env`
- Decouple `CheckoutController::process()` from direct `markPaid()` call (Phase 4 design)
- Update `PaymentWebhookController::handle()` to add PaymentAttempt lookup + idempotency guard + markPaid
- Test against Lahza sandbox with test cards before enabling `PAYMENT_GATEWAY_ENABLED=true`

### If Lahza cannot onboard Gaza businesses:

**→ Fallback: Tap Payments (`tap.company`)**

| | Lahza | Tap Payments |
|-|-------|-------------|
| Region | Palestine (MENA) | Gulf + MENA |
| ILS support | ✅ Yes | ❌ No (USD/SAR/KWD/BHD) |
| Webhook (HMAC) | ✅ Yes | ✅ Yes |
| Redirect checkout | ✅ Yes | ✅ Yes |
| Refund API | ⚠️ Undocumented | ✅ Documented |
| Palestinian merchant onboarding | Unknown | Unknown |
| ADR-007 API fit | Excellent | Excellent |

**Note:** Tap Payments does not support ILS. If ILS is a business requirement, Tap is not a complete substitute. In that case, a dual-gateway approach (Lahza for ILS, Tap for USD/JOD) would be required — but this significantly increases Phase 5 complexity. Resolve the Lahza eligibility question first.

---

## 11. ADR-007 Status After This Audit

```
Phase 1 — Payment Abstraction Layer        ✅ Complete + Hardened
Phase 2 — PaymentAttempt Infrastructure    ✅ Complete
Phase 3 — Webhook Stub                     ✅ Complete
Phase 4 — Redirect Decoupling Design       ✅ Complete (design only)
Phase 5 — Real Gateway (Lahza)             🔶 Conditional — awaiting eligibility confirmation
ADR-007-B — Provider Selection             ✅ Complete (this document)
```

---

*Document prepared by: automated audit session*  
*Based on: docs.lahza.io (2026-06-17), payatlas.com/company/lahza-2797 (2026-06-17)*  
*No gateway code was written as part of this audit.*
