# ADR-007 Phase 2 — Implementation Report
## PaymentAttempt Infrastructure

**Date:** 2026-06-17  
**Phase:** 2 of 5 (ADR-007)  
**Scope:** Database schema + model + service extension — no gateway integration, no webhook, no checkout change

---

## 1. Objective

Introduce an audit trail (`payment_attempts` table) so every settlement is traceable.
Phase 2 is backward-compatible: existing callers of `markPaid()` pass no `PaymentAttempt` and their behavior is unchanged.

---

## 2. Migrations Created

### Migration 1 — `2026_06_17_000001_create_payment_attempts_table.php`

Creates the `payment_attempts` table with full schema:

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| `id` | bigint PK | no | |
| `invoice_id` | bigint FK → invoices.id | yes | nullOnDelete |
| `order_id` | bigint FK → orders.id | yes | nullOnDelete |
| `client_id` | bigint FK → clients.id | yes | nullOnDelete |
| `gateway` | varchar(50) | no | e.g. `'mock_gateway'`, `'lahza'` |
| `idempotency_key` | varchar(100) UNIQUE | no | UUID, prevents duplicate sessions |
| `gateway_session_id` | varchar(255) | yes | indexed; set by `createSession()` (Phase 4) |
| `gateway_transaction_id` | varchar(255) | yes | indexed; set on payment confirmation |
| `gateway_amount_cents` | unsignedBigInteger | yes | validated against invoice.total_cents on webhook |
| `currency` | char(3) | no default `'USD'` | ISO 4217 |
| `status` | varchar(30) | no default `'initiated'` | indexed; see status machine below |
| `gateway_status_raw` | varchar(100) | yes | raw provider status for debugging |
| `gateway_response` | json | yes | full provider response for audit |
| `webhook_verified_at` | timestamp | yes | when HMAC signature verified (Phase 3) |
| `settled_at` | timestamp | yes | when `markPaid()` completed |
| `refunded_at` | timestamp | yes | when refund confirmed |
| `refund_amount_cents` | unsignedBigInteger | yes | accumulated refund total |
| `created_at` | timestamp | no | |
| `updated_at` | timestamp | no | |

**Status machine:**
```
initiated → pending → succeeded
                  └─→ failed
                  └─→ cancelled
succeeded → refunded
```

**FK strategy — all nullOnDelete:**
Invoice soft-deletes set `deleted_at` — the DB row remains, so the FK from `payment_attempts.invoice_id` is valid. `nullOnDelete` is chosen over `cascadeOnDelete` so audit records survive invoice soft-deletion.

### Migration 2 — `2026_06_17_000002_add_payment_attempt_id_to_invoices_table.php`

Adds `invoices.payment_attempt_id` as a nullable FK → `payment_attempts.id` (nullOnDelete).

**Why a separate migration?**
Circular FK constraint: if both columns were in one migration, MySQL would require `payment_attempts` to exist before `invoices` can reference it, and `invoices` to exist before `payment_attempts` can reference it. Split into two migrations run in sequence solves this.

**Run order:** Migration 1 must complete before Migration 2 runs. Laravel runs migrations in filename-timestamp order, which guarantees this (`000001` before `000002`).

---

## 3. Model Created

### `app/Models/PaymentAttempt.php`

```
Namespace:   App\Models\PaymentAttempt
Location:    app/Models/PaymentAttempt.php (consistent with all other models)
Base class:  Illuminate\Database\Eloquent\Model (no SoftDeletes — audit records must not be soft-deleted)
```

**Status constants:**

| Constant | Value | Meaning |
|----------|-------|---------|
| `STATUS_INITIATED` | `'initiated'` | Session created, client redirected |
| `STATUS_PENDING` | `'pending'` | Webhook received, settlement in progress |
| `STATUS_SUCCEEDED` | `'succeeded'` | `markPaid()` completed |
| `STATUS_FAILED` | `'failed'` | Gateway declined |
| `STATUS_CANCELLED` | `'cancelled'` | Client cancelled on gateway page |
| `STATUS_REFUNDED` | `'refunded'` | Refund confirmed |

**Casts:**

| Column | Cast |
|--------|------|
| `gateway_response` | `array` |
| `gateway_amount_cents` | `integer` |
| `refund_amount_cents` | `integer` |
| `webhook_verified_at` | `datetime` |
| `settled_at` | `datetime` |
| `refunded_at` | `datetime` |

**Relationships:**

| Method | Type | Target |
|--------|------|--------|
| `invoice()` | `BelongsTo` | `Invoice` |
| `order()` | `BelongsTo` | `Order` |
| `client()` | `BelongsTo` | `Client` |

**Helpers:** `isSucceeded()`, `isFailed()`, `isSettled()`  
**Scopes:** `scopeSucceeded()`, `scopeForGateway()`

---

## 4. Relations Added to Invoice Model

| Method | Type | Notes |
|--------|------|-------|
| `paymentAttempt()` | `BelongsTo` | Uses `invoices.payment_attempt_id` — the winning attempt that settled this invoice |
| `paymentAttempts()` | `HasMany` | Uses `payment_attempts.invoice_id` — all attempts (including failed) for this invoice |

`payment_attempt_id` added to `Invoice::$fillable`.

---

## 5. InvoiceSettlementService — Is markPaid Backward-Compatible?

**Yes — 100% backward-compatible.**

Signature change:
```php
// Before Phase 2
public function markPaid(Invoice $invoice, ?string $paymentMethod = null): void

// After Phase 2
public function markPaid(Invoice $invoice, ?string $paymentMethod = null, ?PaymentAttempt $paymentAttempt = null): void
```

The third parameter is optional with a `null` default. All existing callers:

| Caller | Call site | Changed? |
|--------|-----------|---------|
| `CheckoutController::process()` | `markPaid($invoice, gateway()->name())` | ❌ No change |
| `InvoiceCheckoutController::handleSuccessfulPayment()` | `markPaid($invoice, gateway()->name())` | ❌ No change |
| `DomainRenewalService` | `markPaid($invoice, gateway()->name())` | ❌ No change |
| Admin `InvoiceController::bulkAction()` | `markPaid($invoice, ...)` | ❌ No change |

When `$paymentAttempt` is `null` (all current callers):
- Invoice settlement proceeds identically to Phase 1
- `payment_attempt_id` column on the invoice remains `null`
- No `PaymentAttempt` record is created or modified

When `$paymentAttempt` is provided (Phase 3 webhook handler — future):
- `invoice.payment_attempt_id` set to `$paymentAttempt->id`
- `payment_attempt.status` updated to `'succeeded'`
- `payment_attempt.settled_at` set to `now()`
- All inside the existing `DB::transaction` + `lockForUpdate()` block

**Preserved from Phase 1:**
- `DB::transaction` wrapper ✅
- `lockForUpdate()` on the invoice row ✅
- Early-return guard `if ($lockedInvoice->status === 'paid') { return; }` ✅
- `OrderActivationService::activate()` call ✅
- `syncStandaloneInvoiceDomain()` call ✅

---

## 6. Did Any Checkout Flow Change?

**No.**

- `CheckoutController` — not modified
- `InvoiceCheckoutController` — not modified
- `PaymentManager` — not modified
- `config/payment.php` — not modified
- `MockGateway` — not modified

The public checkout experience is identical to Phase 1. Settlements still happen synchronously; no PaymentAttempt record is created by the checkout flow in Phase 2.

---

## 7. Is MockGateway Still Safe?

**Yes.**

MockGateway was hardened after Phase 1 review:
- `name()` → returns `'mock_gateway'` ✅
- `createSession()` → throws `PaymentException` ✅
- `verifyWebhook()` → throws `PaymentException` ✅
- `getTransaction()` → throws `PaymentException` ✅ (hardened — no synthetic success)
- `refund()` → throws `PaymentException` ✅ (hardened — no synthetic success)

MockGateway does not create `PaymentAttempt` records. Phase 2 does not change this.

---

## 8. Required Commands

Run on the user's machine after deploying the new files:

```bash
# Run both new migrations
php artisan migrate

# Clear compiled config/route/view cache
php artisan optimize:clear
```

**Verification queries** (run in `php artisan tinker` or MySQL client):

```php
// Confirm tables exist
Schema::hasTable('payment_attempts');    // → true
Schema::hasColumn('invoices', 'payment_attempt_id');  // → true

// Confirm markPaid still works without PaymentAttempt
$invoice = Invoice::where('status', 'draft')->first();
// (In a test environment with a real draft invoice)
// app(InvoiceSettlementService::class)->markPaid($invoice, 'mock_gateway');
// $invoice->fresh()->status === 'paid'  → true
```

---

## 9. Is Phase 2 Complete?

**Yes. All 8 steps implemented:**

| Step | Status |
|------|--------|
| Step 1 — Migration: `create_payment_attempts_table` | ✅ |
| Step 2 — Migration: `add_payment_attempt_id_to_invoices` | ✅ |
| Step 3 — `PaymentAttempt` model with fillable, casts, constants, relationships | ✅ |
| Step 4 — `Invoice` model: `paymentAttempt()` + `paymentAttempts()` + fillable | ✅ |
| Step 5 — `InvoiceSettlementService::markPaid()` — optional `PaymentAttempt` param + link on settlement | ✅ |
| Step 6 — MockGateway unchanged; no new PaymentAttempt created in checkout flows | ✅ |
| Step 7 — Syntax verified; artisan commands documented | ✅ |
| Step 8 — This report | ✅ |

---

## 10. Is the Project Ready for Phase 3 (Webhook Stub)?

**Yes.** Phase 3 prerequisites from Phase 2:

| Phase 3 Requirement | Available After Phase 2? |
|--------------------|--------------------------|
| `payment_attempts` table exists | ✅ |
| `PaymentAttempt` model with status constants | ✅ |
| `invoices.payment_attempt_id` FK column exists | ✅ |
| `markPaid()` accepts optional `PaymentAttempt` and links it | ✅ |
| `PaymentGatewayInterface::verifyWebhook()` contract defined | ✅ (Phase 1) |
| `WebhookVerificationException` exists | ✅ (Phase 1) |
| `WebhookEvent` DTO exists | ✅ (Phase 1) |

**Phase 3 will add:**
- Route: `POST /payment/webhook/{gateway}` (exempt from CSRF)
- `PaymentWebhookController::handle()` — verify signature → idempotency check → amount validation → `markPaid()`
- Webhook handler creates or updates `PaymentAttempt` record before calling `markPaid()`
- No new migrations needed (all schema exists after Phase 2)

---

## 11. File Summary

### New files

| File | Purpose |
|------|---------|
| `database/migrations/2026_06_17_000001_create_payment_attempts_table.php` | Creates `payment_attempts` table |
| `database/migrations/2026_06_17_000002_add_payment_attempt_id_to_invoices_table.php` | Adds FK column to `invoices` |
| `app/Models/PaymentAttempt.php` | Eloquent model for payment_attempts |

### Modified files

| File | Change |
|------|--------|
| `app/Models/Invoice.php` | Added `payment_attempt_id` to `$fillable`; added `paymentAttempt()` and `paymentAttempts()` relationships |
| `app/Services/Billing/InvoiceSettlementService.php` | Added `?PaymentAttempt $paymentAttempt = null` to `markPaid()`; links attempt and sets `succeeded` status when provided |
