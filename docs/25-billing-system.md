# Billing System

> **Last Updated:** 2026-09-09
>
> **Status:** Verified
>
> **Source:** Code-first

## Purpose

This document describes the billing, payment, domain, and tenant-provisioning behavior implemented by the current codebase. It is descriptive, not aspirational: where an administrative path or legacy compatibility path differs from the hosted-payment path, that difference is stated explicitly.

## Billing Principles

- Transactional order, invoice, subscription, and payment amounts are stored as integer cents. `Coupon.discount_value` remains a decimal exception described under Technical Debt.
- The server is authoritative for prices, currency, provider identity, invoice totals, and settlement state. Browser and session values are not financial proof.
- Public hosted payment is confirmed only by a signature-verified gateway webhook whose amount and currency match both the invoice and its owning `PaymentAttempt`.
- Financial persistence and external provisioning are separated. Registrar calls and queued tenant/WHM provisioning run after the surrounding database transaction commits.
- Settlement, checkout creation, domain registration, and WHM account creation each have their own idempotency guard. These guards solve different duplication risks and are not interchangeable.

## Core Records

| Record | Responsibility |
|---|---|
| `Order` | Commercial request and its business status |
| `OrderItem` | One requested domain operation and, for registration, its provisioning state |
| `Invoice` | Amount owed, currency, payment-session claim, and final payment state |
| `InvoiceItem` | Auditable accounting line whose configured type is `subscription`, `domain`, or `service` |
| `PaymentAttempt` | One hosted-gateway interaction and webhook audit trail |
| `Subscription` | Client entitlement plus tenant/WHM provisioning state |
| `Domain` | Resulting domain asset and renewal data |
| `DomainRegistrationClaim` | Global normalized-domain reservation across all orders |
| `DomainProvisioningAttempt` | Durable registrar registration attempt and provider snapshot |

## Orders

### Important fields

| Field | Meaning |
|---|---|
| `order_number` | Immutable generated business identifier |
| `client_id` | Owning client; accounting history can survive client deletion |
| `status` | `pending`, `active`, `cancelled`, or `fraud` |
| `type` | Flow discriminator such as `domains`, `domain`, `subscription`, or `domain_renewal` |
| `checkout_fingerprint` | Nullable 64-character SHA-256 fingerprint used by domain checkout idempotency |
| `notes` | Administrative/context note |

`checkout_fingerprint` has a database `UNIQUE` constraint. It is used by both the domain-only cart checkout and the independently namespaced `Client\DomainController::purchase()` flow. A repeated request owned by the same identity reuses the completed order/invoice. If two requests race, the unique constraint is the final guard; the losing transaction rolls back and resolves the winning order instead of creating duplicate financial records. An incomplete or foreign-identity match is not treated as success.

### Status flow

```text
pending -> active
pending -> cancelled | fraud
```

The real online-payment path changes an order to `active` only during verified invoice settlement. Administrative order actions may set `active` directly and call `OrderActivationService` without a gateway event.

## Order Items

### Important fields

| Field | Meaning |
|---|---|
| `order_id` | Parent order |
| `domain` | Normalized domain name where applicable |
| `item_option` | Requested operation |
| `price_cents` | Trusted item price in integer cents |
| `meta` | Trusted snapshots such as currency, provider identity, TLD, years, and renewal dates |
| `provisioning_status` | Registration idempotency state |
| `provisioning_started_at` | Registration claim time |
| `provisioning_completed_at` | Successful registration completion time |

The public domain cart/checkout accepts only `register`. `renew` is created by the internal domain-renewal flow. Although some lower-level helpers know labels or provider operations for `transfer`, transfer is not a complete public checkout operation in the current flow.

### Registration state machine

For `register` operations only:

```text
not_started -> in_progress -> completed
                         \-> failed
```

- The transition to `in_progress`, global domain claim, local `Domain`, and durable attempt are created atomically under row locks.
- `completed` permanently blocks another registration call for the same order item.
- `failed` represents a confirmed failure and may be claimed for a later explicit retry.
- A timeout or ambiguous provider outcome leaves the item `in_progress` and the durable attempt `indeterminate`; registration is not automatically re-sent.
- The provider request is made only after the claim transaction commits. If activation itself is inside another transaction, `RegistrarProvisioningService` registers an `DB::afterCommit(...)` callback first.

This state machine does not currently govern `renew`.

## Invoices and Invoice Items

### Invoice fields

| Field | Meaning |
|---|---|
| `client_id`, `order_id` | Ownership and optional order link |
| `number` | Unique invoice number |
| `status` | `draft`, `unpaid`, `paid`, or `cancelled` |
| `subtotal_cents` | Sum before discount and tax |
| `discount_cents` | Coupon/invoice-level discount only. A Template/Plan's list-vs-sale price difference is checkout display/merchandising data and is not persisted here — see [Canonical Discount Contract](#canonical-discount-contract). |
| `tax_cents` | Tax in cents; current checkout calculation uses zero |
| `total_cents` | `max(0, subtotal - discount + tax)` |
| `currency` | Three-letter invoice currency |
| `coupon_id` | Coupon snapshot used for settlement-time usage tracking |
| `payment_attempt_id` | Winning attempt that settled the invoice |
| `payment_session_status` | Hosted-session claim: `idle`, `creating`, or `ready` |
| `payment_session_attempt_id` | Attempt that owns the current hosted-session claim |
| `due_date`, `paid_date` | Due and settlement dates |

`scopeUnpaid()` includes both `draft` and `unpaid`.

### Invoice item fields

`InvoiceItem` stores `item_type`, `reference_id`, `description`, `qty`, `unit_price_cents`, and `total_cents`. Admin create/update accepts the keys currently declared by `config('invoices.item_types')`: `subscription`, `domain`, and `service`. All three require an integer `reference_id` in that Admin path, but their type-specific contracts differ:

| `item_type` | `reference_id` validation and model contract | Settlement/activation behavior |
|---|---|---|
| `subscription` | Admin validation requires the referenced `Subscription` to exist. `InvoiceItem` provides a type-guarded `subscription` accessor backed by `subscriptionRelation`. | Settlement uses referenced subscription IDs for coupon attachment. For an order-linked invoice, `OrderActivationService` activates those subscriptions and queues provisioning. |
| `domain` | Admin validation requires the referenced `Domain` to exist. `InvoiceItem` provides a type-guarded `domain` accessor backed by `domainRelation`. Automated domain invoice lines may initially use `null` until registrar provisioning attaches the resulting Domain ID. | A standalone paid invoice can activate the referenced Domain through `syncStandaloneInvoiceDomain()`. Order-linked registrar behavior is driven through order activation and its `OrderItem` operations. |
| `service` | Admin validation requires an integer but performs no existence check for a referenced model. `InvoiceItem` has no service-specific relation or accessor, and no referential meaning is defined by the current code. | No settlement, order-activation, or provisioning branch uses `service.reference_id`. The type is selectable and persistable through the generic Admin Invoice CRUD, but no dedicated automated InvoiceItem creation flow for it exists. |

The type guards apply only to the `subscription` and `domain` accessors; they do not establish a polymorphic contract for every configured item type.

For domain-only checkout, every domain produces exactly one `OrderItem` and one `InvoiceItem`. `DomainInvoiceItemBuilder` copies the trusted `OrderItem.price_cents`; checkout verifies both invariants before commit:

```text
count(invoice_items) = count(domain order_items)
SUM(invoice_items.total_cents) = invoice.subtotal_cents
```

Domain invoice lines start with `reference_id = null`. When the matching `Domain` record exists, registrar provisioning attaches its ID to the invoice line whose description contains that normalized domain name. Multi-domain invoices therefore attach each resulting domain to its corresponding line rather than using only the first item.

## Coupons

Coupon codes are resolved and recalculated server-side during checkout. A usable coupon may populate `invoice.coupon_id` and `discount_cents`. Usage is not consumed when a draft invoice is created. During `InvoiceSettlementService::markPaid()`, the coupon row is locked, `used_count` is incremented once, and referenced subscriptions are attached with `syncWithoutDetaching()`. The invoice's paid-state early return prevents a duplicate webhook from consuming the coupon twice.

The settlement step intentionally honors a coupon already attached to an invoice and does not re-check `max_uses` after payment has occurred.

## Canonical Discount Contract

- `Invoice.subtotal_cents` is the sum of its `InvoiceItem.total_cents` — the actual sale-price line totals the client is charged, not a pre-discount list price.
- `Invoice.discount_cents` is the coupon/invoice-level discount only.
- A Template or Plan's list-vs-sale price difference (its own merchandising discount, e.g. a struck-through list price shown on the checkout page) is display/merchandising data only. It is not persisted as `Invoice.discount_cents` and does not participate in the invoice-level discount calculation. `Template.price_cents`/`discount_price_cents` remain the checkout page's own display fields, read live by the checkout view.
- `Invoice.total_cents = max(0, subtotal_cents - discount_cents + tax_cents)`, matching the general Invoice fields contract above.

This keeps `sum(InvoiceItem.total_cents) === Invoice.subtotal_cents` true by construction, which is what [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity)'s invoice-level aggregate check (and the domain-only checkout invariant earlier in this document) both rely on.

## Currency Contract

### Domain checkout source of truth

```text
DomainPricingService quote
-> cart's trusted server-side item
-> OrderItem.meta.currency
-> Invoice.currency
-> PaymentAttempt.currency
-> gateway request
-> verified webhook comparison
```

`DomainPricingService` reads active supported registrar/TLD price rows, selects a positive `sale` price or falls back to positive `cost`, converts it to integer cents, and normalizes the currency to an uppercase three-letter code. It also snapshots `provider_id`, `provider_type`, `provider_mode`, and `domain_tld_id` with the quote.

Rules enforced by current checkout/payment code:

- Hosted-payment currencies are `USD`, `ILS`, and `JOD`.
- Currency is normalized as an uppercase three-letter code.
- A domain cart containing more than one currency is rejected; one invoice cannot mix currencies.
- Competing quotes for one TLD in different currencies are not numerically compared, so that TLD is unavailable to checkout in that ambiguous case.
- There is no FX conversion policy.
- Request/session currency and price are never accepted as authority.
- The webhook currency must equal both `invoice.currency` and `payment_attempt.currency`, while the webhook amount must equal both stored cent amounts.

Template/plan checkout currently writes `USD`; domain checkout carries the trusted catalog currency.

## Registrar Provider Contract

For new registrations the provider is selected by the trusted pricing quote, not by a generic registrar preference:

```text
DomainPricingService quote
-> provider_id/type/mode snapshot
-> availability check using that provider
-> OrderItem.meta provider snapshot
-> RegistrarProvisioningService validates the same active provider
-> DomainProvisioningAttempt provider snapshot
-> reconciliation using the attempt's provider
```

There is no silent provider substitution for a new registration. A missing `provider_id`, inactive/missing provider, or mismatch in the snapshotted type/mode blocks registration. Provider values supplied by the browser are overwritten by the trusted quote.

Renewal is a different internal path: it primarily follows the existing domain registrar and renewal catalog data. The strict quote-snapshot rule above is the registration contract.

## Domain Checkout Flow

### Domain-only cart and client purchase

1. Normalize the requested domain(s); only `register` is accepted by the public cart.
2. Re-price every domain through `DomainPricingService`.
3. Reject missing/changed prices, mixed currency, inactive providers, or inconclusive/unavailable provider checks.
4. Build the namespaced deterministic `checkout_fingerprint` from identity and trusted normalized quote/item data.
5. Reuse a valid prior order/invoice or atomically create the client (for guest cart), pending order, one `OrderItem` per domain, invoice, and one `InvoiceItem` per domain.
6. Redirect to invoice checkout. The request does not mark the invoice paid and does not register the domain.
7. Start/reuse a hosted payment session.
8. On verified payment, settle the invoice, activate the order locally, then execute registrar work after commit.

Every domain item is processed independently by `RegistrarProvisioningService::provisionOrderDomain()`. One item crashing or failing does not prevent the loop from attempting later items. Each registration has its own item state, durable attempt, and global claim.

## Combined and Template Checkout

`Front\CheckoutController::process()` handles template checkout, non-template plan checkout, and either combined with an optional purchased domain. This is the single method that creates financial records for a template-driven checkout, whether or not a domain is bundled with it.

### Template/Plan requirement

A `Template` has a nullable `plan_id` (added to an already-existing table by a later migration, so a plan-less `Template` row is a schema-reachable state even though the Admin `TemplateController` has required `plan_id` on create/update since that migration). Checkout fails closed on this: any checkout path where a real `template_id` was submitted requires that `Template` to resolve to a real linked `Plan` *before* any `Order`, `OrderItem`, `Subscription`, `Invoice`, `InvoiceItem`, or `PaymentAttempt` is created and before any hosted payment session is started. A plan-less `Template` returns a `422` JSON response (`success: false`) for AJAX/JSON requests, or a redirect back with an error for a normal form submit, and creates none of the records above. This guard does not require `Plan.is_active`; no other checkout path currently enforces that column, so requiring it only here would add an asymmetric rule beyond the actual invariant being restored. A checkout with a valid Template+Plan still creates the `Subscription` exactly as before, and the resulting subscription `InvoiceItem.reference_id` equals that `Subscription.id`.

Without this guard, a plan-less `Template` checkout would still create an `item_type = subscription` `InvoiceItem` (because that step does not itself check for a `Plan`) while `Subscription::create()` is skipped (because that step does), leaving an `InvoiceItem.reference_id` that can never resolve — exactly the shape [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity) rejects at settlement time. The checkout-time guard closes the gap earlier, before any financial record exists, rather than only reacting to it later.

### Combined domain contract

When a domain is bundled with a template/plan checkout, the same trusted-pricing rule as domain-only checkout applies: every billable (`register`/`renew`) domain gets its own `OrderItem` and matching `InvoiceItem`, priced from the same live `DomainPricingService` quote used by domain-only checkout — never from a client-submitted price. Multiple bundled domains are each represented as their own `OrderItem`/`InvoiceItem` pair, not merged into one row. A non-billable domain selection (subdomain, own, or any other non-`register`/`renew` option) is recorded as a `$0` informational `OrderItem` with no matching `InvoiceItem`, unchanged from the domain-only contract. A client-submitted price that no longer matches the live trusted quote is rejected with the same pre-existing `409` "price changed since added to cart" response domain-only checkout already used — it is never silently substituted with the trusted value.

## Hosted Payment Flow

### End-to-end flow

```text
Invoice
-> PaymentSessionStarter
-> PaymentAttempt
-> active PaymentGateway implementation
-> Lahza hosted checkout
-> signature-verified PaymentWebhookController
-> amount/currency reconciliation
-> InvoiceSettlementService::markPaid()
-> local order/subscription activation
-> commit
-> external provisioning
```

`PaymentManager` resolves an active database-configured gateway first, then the configured gateway map. `LahzaGateway` implements hosted checkout, HMAC webhook verification, transaction lookup, and refunds. `MockGateway` remains a non-settling compatibility/configuration fallback: its hosted-session and webhook methods throw, so it cannot simulate a successful public payment.

### PaymentAttempt schema and role

The implemented payment audit fields are:

- `invoice_id`
- `order_id`
- `client_id`
- `gateway`
- `idempotency_key`
- `gateway_session_id`
- `gateway_transaction_id`
- `gateway_amount_cents`
- `currency`
- `status`
- `gateway_status_raw`
- `gateway_response`
- `webhook_verified_at`
- `gateway_succeeded_at`
- `settled_at`
- `refunded_at`
- `refund_amount_cents`

The principal status constants are `initiated`, `pending`, `succeeded`, `failed`, `cancelled`, and `refunded`:

| Status | Current ingress and permitted later behavior |
|---|---|
| `pending` | Created while session creation is claimed. It may remain pending for an indeterminate provider result, become `initiated` after the provider session is stored, become `failed` after a confirmed pre-session failure, or become `cancelled` if the invoice becomes paid before the provider session can be published. |
| `initiated` | A checkout session exists. Client cancellation can mark it `cancelled`; a verified negative webhook can mark it `failed`; verified success can settle it. A missing reusable checkout URL can move it back to `pending` before another controlled provider call. |
| `failed` | A confirmed negative session/webhook result. It is not terminal against later verified payment proof and may become `succeeded`. |
| `cancelled` | A local client/session cancellation marker, not financial proof. A later verified success is still allowed to settle it; it is not protected as a terminal financial state. |
| `succeeded` | Written by `InvoiceSettlementService` with `settled_at` when local invoice settlement succeeds. It is terminal against stale positive or negative payment webhooks. |
| `refunded` | Reserved by the model/schema for a confirmed refund and protected as terminal against stale payment webhooks. Lahza exposes a refund API, but the current application has no controller/service that persists `refunded`, `refunded_at`, or `refund_amount_cents`; this document does not claim a complete refund workflow. |

The unique `idempotency_key` is sent to the gateway. A hosted-session identity is the pair `(gateway, gateway_session_id)`, enforced by a composite database unique constraint. `gateway_session_id` remains nullable so multiple attempts may be created before any provider response; SQL uniqueness permits multiple `NULL` values. The same non-null session string may exist under different gateway namespaces, but not twice for the same gateway. Webhook lookup uses the same gateway/session pair, and `gateway_transaction_id` records the gateway transaction.

`gateway_succeeded_at` is durable evidence that a signature-verified success matched the attempt/invoice amount and currency and passed any successful secondary transaction reconciliation. On the first verified success, the attempt row lock atomically fixes a first-write snapshot consisting of `gateway_succeeded_at`, `gateway_transaction_id` (which may be `null`), `gateway_status_raw`, `gateway_response`, and `webhook_verified_at`. Later successful delivery cannot rewrite any of those fields, but it may still continue to local settlement when the attempt is not yet settled. It is distinct from both `status = succeeded` plus `settled_at`, which mean local settlement completed, and `invoice.payment_attempt_id`, which identifies the one settlement winner.

If a verified success replay carries a transaction ID different from the immutable first snapshot—including a non-null ID after an initial `null`—the stored snapshot is unchanged and a warning containing identifiers only is logged for operational review. The code does not classify that mismatch automatically as a duplicate charge. The local Lahza adapter distinguishes the webhook `data.id` transaction identifier from the `reference` used for gateway/session lookup, but it documents no global, merchant, or gateway-scoped uniqueness guarantee for `data.id`; `gateway_transaction_id` therefore remains a nullable, non-unique index.

### Invoice payment-session claim

`PaymentSessionStarter` locks the invoice and uses:

```text
idle -> creating -> ready
```

The invoice's `payment_session_attempt_id` owns the claim. This prevents concurrent `PaymentAttempt`/session creation and prevents a new session from being created while the first request is still `creating`.

- `ready` reuses the existing checkout URL when possible.
- `creating` returns a processing result rather than issuing another gateway call.
- A conclusive pre-session failure marks the attempt failed and releases the invoice to `idle`.
- An indeterminate gateway result keeps `creating` and the owner attempt, preventing an unsafe second session.
- External session creation occurs outside the claim transaction.

### Confirmed pre-session failure classification

`PaymentSessionStarter::isConfirmedPreSessionFailure()` decides which branch a thrown `\Throwable` from `$gateway->createSession()` takes. Classification is type-based, not message-based: only `App\Payments\Exceptions\ConfirmedPreSessionFailureException` (a `PaymentException` subclass) is treated as a confirmed pre-session failure. A gateway implementation throws it only from `createSession()`, and only when the failure is provably pre-provider-contact -- i.e. no external checkout session could possibly have been created. Any other `\Throwable`, including a plain `PaymentException` raised during or after an actual network call to the provider, is indeterminate.

Two call sites currently throw it:

- `MockGateway::createSession()` always throws it -- the method never contacts any provider, so its failure can never be ambiguous.
- `LahzaGateway::createSession()` throws it only from its `secret_key` emptiness check, which runs strictly before the HTTP call to Lahza. Every other failure inside that method -- a connection error, a non-2xx response, a malformed response body, or a missing `authorization_url` -- happens during or after the provider contact and still throws a plain `PaymentException`, which remains indeterminate.

This replaced an earlier, brittle implementation that matched `PaymentException` messages containing the literal string `'secret_key is not configured'`. That message match covered only the Lahza secret-key case; `MockGateway::createSession()`'s own failure -- thrown for an unrelated reason and with a different message -- was misclassified as indeterminate, so the attempt stayed `pending` and the invoice's claim stayed `creating` forever, since `MockGateway` can never contact a provider to resolve the ambiguity. The type-based classification fixes this for `MockGateway` while leaving Lahza's genuinely ambiguous provider-contact failures indeterminate, unchanged.

A genuinely indeterminate outcome still has no automatic stale-claim recovery or reconciliation mechanism under this classification -- see [Current Technical Debt and Known Exceptions](#current-technical-debt-and-known-exceptions).

### Checkout controllers

`Client\InvoiceCheckoutController` validates invoice ownership/state and delegates session creation to `PaymentSessionStarter`. It never calls `markPaid()` and a return/cancel URL is not proof of payment.

Template and non-template plan checkout in `Front\CheckoutController` create the order, subscription, invoice, and invoice items locally, commit, start the hosted session, and wait for the webhook. The client request cannot set the invoice to `paid`, activate the order, or dispatch tenant/WHM provisioning. Domain-only checkout continues to the invoice checkout page, which starts the same hosted-session flow.

### Webhook verification and idempotency

`PaymentWebhookController` is the only application-code caller of `InvoiceSettlementService::markPaid()` in the online financial path. It:

1. Resolves and matches the active gateway name.
2. Verifies the raw request signature before trusting the payload.
3. Acknowledges non-success events without settling; explicit failures update only the attempt.
4. Matches a success event by gateway and `gateway_session_id`.
5. Compares webhook, attempt, and invoice amount/currency.
6. Optionally performs a secondary `getTransaction()` reconciliation when a transaction ID is present.
7. Locks the attempt and durably records `gateway_succeeded_at` plus safe success metadata.
8. Calls settlement separately; terminal `succeeded`/`refunded` attempts and already-paid invoices are no-ops.

Every verified negative branch that records `failed`—an explicit payment failure, webhook amount/currency mismatch, or secondary transaction-reconciliation mismatch—performs the mutation in a short transaction, reloads the `PaymentAttempt` with `lockForUpdate()`, and rechecks its current status. `succeeded` and `refunded` are terminal against later payment webhook events. An attempt with `gateway_succeeded_at` is also protected from later negative mutation even when it is not the settlement winner, so a stale event cannot erase verified external success or overwrite its transaction metadata.

A prior negative event is not permanently terminal. If a later signature-verified success event matches the same attempt and passes the authoritative amount/currency and optional transaction checks, it may change `failed` to `succeeded` and settle the still-unpaid invoice. This prevents arrival order from making an earlier negative event win over later confirmed payment proof. It is not a payment-reconciliation job.

One invoice may exceptionally have more than one attempt with verified external success evidence, but it has exactly one local settlement owner. A non-winning externally successful attempt is detected by `gateway_succeeded_at IS NOT NULL`, `settled_at IS NULL`, a paid related invoice, and `invoice.payment_attempt_id != payment_attempts.id`. This represents a potential duplicate charge requiring operational review: it does not reactivate the invoice/order, consume the coupon again, or dispatch provisioning again, and no automatic refund is performed.

### `markPaid()` contract

Signature:

```php
markPaid(Invoice $invoice, ?string $paymentMethod = null, ?PaymentAttempt $paymentAttempt = null): void
```

Inside one transaction it locks the invoice, rejects a non-owning hosted attempt, revalidates an order-linked invoice's stored financial contract (see below), marks the invoice paid, records `paid_date` and winning attempt, finalizes the attempt, consumes coupon usage once, sets the order active, and calls `OrderActivationService` for local activation. A manual call without `PaymentAttempt` is accepted only when the invoice's session claim is `idle`.

Order activation is idempotent per Order: the pre-mutation status is captured before the invoice is marked paid, and `OrderActivationService::activate()` is invoked only on the genuine `pending -> active` transition. If the Order is already `active` — for example a second or later invoice on the same Order settling after a prior invoice already activated it — the invoice still settles normally (paid, coupon consumed, winning attempt recorded), but activation is not re-invoked: subscription billing dates are not extended a second time and provisioning/registrar calls are not redispatched. Gateway webhook settlement and administrative manual settlement share this same rule because both call `markPaid()`.

Administrative invoice create-as-paid, update-to-paid, and bulk mark-paid actions use the same service with payment method `admin_manual` and no `PaymentAttempt`. Create-as-paid first persists the invoice as `unpaid` together with all invoice items, commits that local transaction, and then settles it. Update-to-paid likewise commits item/totals changes before settlement. Bulk settlement calls the service separately for each invoice, so one rejected invoice does not prevent the others from settling and no long controller transaction contains the batch. An invoice with a `creating` or `ready` hosted-session claim is rejected by the existing ownership guard and remains unpaid. Already-paid invoices are not resettled.

Administrative manual settlement reaches this same authoritative `InvoiceSettlementService` path (`payment_method = admin_manual`), so it is bound by the same ownership, financial-integrity, and activation-idempotency rules as gateway settlement. It does not currently persist a dedicated accounting-grade manual-payment record (method, external reference, recording admin, notes) beyond the `Invoice`/`PaymentAttempt`-less state described above — see Technical Debt.

### Order-backed invoice financial integrity

`order_id` is the sole discriminator between two invoice kinds, checked both in `InvoiceController::update()` and inside `InvoiceSettlementService::markPaid()`:

- **Standalone** (`order_id === null`): an independent financial record. Its `InvoiceItem` rows, totals, and status remain admin-editable through the existing CRUD contract exactly as before this invariant existed.
- **Order-backed** (`order_id !== null`): a billing *projection* of its `Order`/`OrderItem` rows, never an independently editable financial record. `InvoiceController::update()` accepts no `items` payload for this kind — any `items` a request submits is ignored entirely, not merely rejected, and the stored `InvoiceItem` rows are preserved unchanged. This is checked once before validation (to select the right rule set) and re-checked against the row locked inside the update transaction, since the locked row is what actually authorizes the write. `due_date` and `status` remain editable on an Order-backed invoice; only its financial line items are frozen.

Before `markPaid()` performs any financial mutation — `Invoice.status -> paid`, coupon consumption, Order activation, provisioning — an Order-backed invoice's stored `Invoice`/`InvoiceItem` projection is revalidated against its `Order`/`OrderItem` (and, for subscription lines, `Subscription`) contract. A mismatch throws and rolls back the entire settlement transaction: no paid state, no coupon increment, no Order activation, and no provisioning are ever persisted. The check is type-aware because domain and subscription Order-backed lines have different sources of truth:

- **Domain lines**: `OrderItem` has no `qty` column and carries its frozen price in `price_cents`; there is no `order_item_id` FK on `InvoiceItem`, and a fresh registration's `InvoiceItem.reference_id` is still `null` at settlement time (the `Domain` row does not exist until provisioning runs, afterward). The check therefore compares aggregates: the *count* of billable (`item_option` in `register`/`renew` — the same predicate `OrderActivationService` uses to decide whether to provision) domain-bearing `OrderItem` rows against the count of `domain`-type `InvoiceItem` rows, plus their summed financial totals, plus a structural `qty === 1` check on each domain `InvoiceItem`. A `subdomain`/`own`/`transfer` `OrderItem`, or any other non-`register`/`renew` value, is excluded from this count by construction — those are created with `price_cents = 0` and no matching `InvoiceItem` on every current creation path, not exempted as a special case.
- **Subscription lines**: a subscription-type Order-backed invoice has no `OrderItem` row for the subscription line itself (`CheckoutController` only ever creates an `OrderItem` for an optional bundled domain add-on). Each subscription `InvoiceItem` is checked per-row against the `Subscription` its `reference_id` points to: the `Subscription` must exist, belong to the same client, and its `price_cents` must equal the `InvoiceItem.unit_price_cents`; `qty` must be `1`.
- Any `InvoiceItem.item_type` other than `domain` or `subscription` on an Order-backed invoice fails closed immediately — this codebase never programmatically creates any other item type on an Order-backed invoice, so an unrecognized shape is treated as tampering/corruption, never silently skipped.
- The invoice-level aggregate is also checked: `subtotal_cents` must equal the sum of its items' `total_cents`, and `total_cents` must equal `subtotal - discount + tax`.

Currency is explicitly **not** part of this check — see Technical Debt.

### Paid invoice immutability

Paid invoices are immutable through the current Admin Invoice CRUD. `InvoiceController` re-reads and locks the persisted invoice before an update or deletion. If its current status is `paid`, the controller does not change the status, `paid_date`, totals, currency, relationships, or invoice items, and it does not delete the invoice. Bulk status changes and deletion lock the selected rows and skip paid invoices while reporting affected and skipped counts. Bulk mark-paid remains idempotent: an already-paid invoice is reported as skipped and does not repeat settlement side effects.

Changing a paid amount or line item, or reverting paid status, requires a future explicit refund, credit-note, or accounting-adjustment workflow. No such reversal workflow is implemented. The same absence applies more broadly to any Order-backed invoice's financial items regardless of paid status: they are immutable through the Admin Invoice CRUD by design (see [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity)), and there is no dedicated correction/credit-note/reissue path for them — a pricing correction currently has to happen through the Order/OrderItem contract itself, not through the invoice.

Invoices whose `payment_session_status` is `creating` or `ready` are also immutable through Admin financial update, individual deletion, bulk status mutation, and bulk deletion while the hosted payment session is active. The controller evaluates this state from the locked database row before computing totals or replacing line items. This prevents local invoice amounts and items from diverging from the amount already sent to the gateway. Reminder remains available because it is read-only. Admin manual mark-paid still passes active sessions to the settlement ownership guard and is rejected; no hosted-session cancellation workflow exists.

Admin invoice duplication creates a new standalone draft invoice for supported non-domain invoices. The duplicate clears `order_id`, `coupon_id`, `payment_attempt_id`, `payment_session_attempt_id`, and `paid_date`, and resets `payment_session_status` to `idle` and `status` to `draft`. Client, currency, totals, and commercial line items are copied according to their existing contract, including supported item `reference_id` values. It does not create or copy a `PaymentAttempt`, even when the original is paid or has an active hosted session. Paying the standalone duplicate therefore cannot reactivate the original order or consume its coupon again.

Invoices containing a `domain` item are not currently duplicable. Their `reference_id` points to an existing `Domain`, and settling a standalone invoice can mutate that domain through `syncStandaloneInvoiceDomain()`. The Admin bulk duplicate action checks the current persisted items before calling `replicate()`, skips each affected domain invoice, and continues processing other selected invoices. Duplication is blocked rather than silently changing reference semantics; this policy does not clone domains or redefine domain item references. These protections are enforced at the Admin Invoice CRUD boundary, not by a model observer or global model event.

## Domain Registration Claims and Attempts

### Global claim

`domain_registration_claims` contains:

| Field | Meaning |
|---|---|
| `domain_name_normalized` | Globally unique normalized domain name |
| `order_item_id` | Item that owns the claim |
| `status` | `claimed`, `completed`, or `released` |
| `claimed_at`, `released_at` | Claim lifecycle timestamps |

This is a global domain-name guard, not merely an order-item guard. A `claimed` or `completed` row blocks another order. A timeout/ambiguous response keeps the claim `claimed`. Only a `confirmed_failed` attempt releases it; a released row can be atomically reassigned to a later item. The unique database index handles concurrent collisions.

### Durable attempt

`domain_provisioning_attempts` records:

- `order_item_id`, nullable `domain_id`
- `provider_id`, `provider_type`, `provider_mode`
- unique `attempt_uuid`
- `operation` (currently `register`)
- `status`: `initiated`, `completed`, `confirmed_failed`, or `indeterminate`
- `provider_reference`, `provider_domain_id`
- `started_at`, `finished_at`, and safe `response_payload`

The provider ID/type/mode are a durable snapshot used for audit and reconciliation. A definitive rejection becomes `confirmed_failed`, sets the item to `failed`, and releases the global claim. Success becomes `completed`, completes both item and claim, and activates the `Domain`. A timeout, transient HTTP result, malformed/ambiguous response, or uncertain duplicate remains `indeterminate` with the item and claim held.

## Domain Reconciliation

Command:

```text
php artisan domains:reconcile-provisioning
```

Relevant options are `--attempt`, `--order-item`, `--limit`, `--older-than`, `--dry-run`, and `--apply`.

- Read-only behavior is the default; `--dry-run` makes that intent explicit.
- It selects old `register` attempts in `initiated`/`indeterminate` whose order items remain `in_progress`.
- It queries the provider snapshotted by the original attempt and never sends another registration request.
- Results include `registered_by_us`, `provider_processing`, `external_unavailable`, `likely_not_sent`, and `indeterminate`.
- `--apply` changes data only for conclusive `registered_by_us`: it completes the attempt/item and activates the domain in a short transaction.
- Processing, unknown, externally unavailable, and likely-not-sent results are not automatically converted to failure and do not release the claim.

## Domain Renewal and Auto-Renew

The scheduler runs `domains:process-auto-renewals` daily at `02:00` with `withoutOverlapping()`.

For an eligible `Domain` with `auto_renew = true`, the service reuses an existing draft/unpaid renewal invoice or creates:

```text
Order(status=pending, type=domain_renewal)
-> OrderItem(item_option=renew)
-> Invoice(status=unpaid)
-> InvoiceItem(item_type=domain, reference_id=domain.id)
-> wait for client payment
```

The scheduler does not call `markPaid()`, does not create a `PaymentAttempt`, and does not call the registrar renewal API. `--dry-run` calculates and reports the plan without database writes or provider renewal calls.

After the client starts a payment session and the verified webhook settles the renewal invoice, `OrderActivationService` passes the `renew` item to `RegistrarProvisioningService`. If settlement is still inside a transaction, the provider renewal is deferred with `DB::afterCommit(...)`.

Renewal pricing first tries the enabled registrar/TLD `renew` price, then a registration-price fallback, and finally a hard-coded amount if neither catalog price exists. This fallback chain is current behavior and is listed as technical debt below.

### Production Validation -- MockGateway Fix and wpgoals.com Renewal (2026-09-09)

**Legacy stuck claim cleanup.** Before the classification fix in [Confirmed pre-session failure classification](#confirmed-pre-session-failure-classification) shipped, the earlier message-based misclassification had already produced one stuck production record: Invoice #3 / PaymentAttempt #1 (`gateway = mock_gateway`), left with attempt `status = pending`, `gateway_status_raw = session_creation_outcome_unknown`, `gateway_session_id = null`, and invoice `payment_session_status = creating` / `payment_session_attempt_id = 1`. A production audit confirmed this was the only matching legacy stuck `mock_gateway` attempt. A guarded transaction corrected both rows to exactly the state the fixed code would have produced on that same failure: `PaymentAttempt #1` -> `status = failed`, `gateway_status_raw = create_session_confirmed_failed`, `gateway_response.reason = confirmed_before_provider_session`, `gateway_session_id` unchanged (`null`); `Invoice #3` -> `payment_session_status = idle`, `payment_session_attempt_id = null`, `payment_attempt_id = null`, `status` unchanged (`unpaid`). No payment, order activation, provisioning, or registrar mutation occurred during this cleanup -- it corrected only the stuck-claim fields the fix itself would have released.

**End-to-end renewal re-validation (wpgoals.com).** With the claim released, the same Invoice #3 / Order #7 (`type = domain_renewal`, `OrderItem #1` renewing `wpgoals.com`, `Domain #1`, frozen `current_renewal_date = 2026-09-29` / target `renewal_date = 2027-09-29`, `provider_id = 1`, Enom, live mode) was carried through the normal admin settlement path (`payment_method = admin_manual`, no `PaymentAttempt`) to confirm the full renewal flow still completes end-to-end after the fix. Production result:

- `Invoice #3`: `status = paid`, `paid_date = 2026-09-09`, `payment_session_status = idle`.
- `Order #7`: `status = active`; `OrderItem #1`: `provisioning_status = completed`.
- `Domain #1`: `status = active`, `renewal_date = 2027-09-29`.
- One `DomainProvisioningAttempt` created: `operation = renew`, `status = completed`, `provider_reference = 314257848`, `started_at = 2026-09-09T18:34:17Z`, `finished_at = 2026-09-09T18:34:29Z`.
- The renewal was independently confirmed visible on the Enom side (matching expiry), and no duplicate `DomainProvisioningAttempt` or duplicate registrar call was observed.

**Verdict.** Invoice settlement -> Order activation -> renewal provisioning -> Enom Extend -> local renewal-date update is confirmed end-to-end **PASS** on real production data. This validates that the `ConfirmedPreSessionFailureException` fix resolves the specific stuck-claim failure mode without weakening indeterminate-outcome safety, order activation idempotency, or registrar provisioning elsewhere in the same flow.

**Known gap, unchanged by this validation.** A genuinely indeterminate `createSession()` outcome still has no automatic stale-claim recovery -- see [Confirmed pre-session failure classification](#confirmed-pre-session-failure-classification) and [Current Technical Debt and Known Exceptions](#current-technical-debt-and-known-exceptions). This production pass did not add, and does not imply, a reconciliation mechanism for that case; it remains operator-driven. It also does not extend to, and does not claim any change in, the separate admin manual/offline payment evidence, generic Order-backed currency invariant, or Order-backed invoice correction-workflow gaps documented elsewhere in this file, nor does it involve enabling or testing Lahza in production -- Lahza remains inactive.

## Subscription and Tenant Provisioning

### Subscription fields and states

Important fields include `client_id`, `plan_id`, `template_id`, `status`, `provisioning_status`, `price_cents`, billing dates/cycle, server/package, username/cPanel credentials, domain data, and provisioning/sync timestamps.

`provisioning_status` is a string with these exact values:

```text
pending -> provisioning -> active
                        \-> failed
                        \-> unknown
failed  -> provisioning  (a later explicit job run may claim it)
```

`status` is the business entitlement state and is separate from `provisioning_status`. Payment activation may set business `status = active` before the asynchronous external work reaches provisioning `active`.

### Paid activation path

```text
verified webhook
-> InvoiceSettlementService transaction
-> invoice paid + order active + subscription locally active
-> OrderActivationService
-> ProvisionSubscription::dispatch(subscription_id)->afterCommit()
-> COMMIT
-> provisioning queue job
-> TenantProvisioningService
```

`OrderActivationService` no longer calls `TenantProvisioningService::provision()` synchronously. It dispatches the existing `ProvisionSubscription` job with `afterCommit()`. If no database transaction is open, Laravel dispatches normally without waiting for a nonexistent commit; if a transaction is open, the job is released only after the outermost successful commit and is discarded on rollback.

`ProvisionSubscription` implements `ShouldQueue`, uses the `provisioning` queue, reloads the subscription, delegates to `TenantProvisioningService`, logs exceptions, and rethrows them for the queue's existing failure/retry behavior. It does not implement `ShouldBeUnique`, and this document does not claim job-level uniqueness.

Provisioning failure after commit cannot roll back the paid invoice or return the order to unpaid/pending. The invoice and local activation persist; the subscription provisioning state and job logs capture the later outcome.

### Hosting / WHM (`plan_type = hosting`)

```text
TenantProvisioningService
-> SubscriptionSyncService::provisionAccount()
-> short transaction + row lock + atomic claim
-> provisioning
-> exactly one WHM createacct request outside transactions
-> active | failed | unknown
```

Idempotency rules:

- `active`, `provisioning`, and `unknown` are blocked states and do not send `createacct`.
- Only `pending` or `failed` can be claimed, after local configuration validation.
- The account username is fixed and persisted during the claim. There is no alternative-username loop or fallback username after an ambiguous response.
- A conclusive WHM rejection becomes `failed`.
- Timeout, connection uncertainty, transient HTTP status, malformed response, missing conclusion, and duplicate/already-exists-style ambiguity become `unknown`.
- `unknown` requires reconciliation before any deliberate retry; automatic job retry cannot issue another `createacct` from that state.
- The WHM HTTP request runs after the claim transaction commits and at `DB::transactionLevel() === 0` on the queued paid-activation path.

### Multi-tenant plans

For non-hosting plans, `TenantProvisioningService` atomically claims local provisioning, ensures the domain, and clones canonical template content through `TemplateCloner`. Existing canonical tenant content prevents a duplicate clone. Success sets provisioning `active`; a thrown failure sets `failed`. Paid order activation reaches this path through the same after-commit job.

## Failure Semantics

| Failure | Persisted behavior |
|---|---|
| Gateway session creation conclusively fails (`ConfirmedPreSessionFailureException`; see [Confirmed pre-session failure classification](#confirmed-pre-session-failure-classification)) | Attempt becomes `failed`; invoice session claim returns to `idle`; invoice remains unpaid |
| Gateway session result is uncertain | Claim stays `creating`; no second session is issued automatically |
| Invalid signature | Request is rejected with no attempt, invoice, or order mutation |
| Amount/currency or transaction-reconciliation mismatch | Settlement is rejected; an unsettled attempt becomes `failed` under a row lock, while an already `succeeded` attempt is never downgraded; invoice/order remain unchanged |
| Negative webhook after success | Attempt success and its settlement identifiers/timestamps remain unchanged |
| Payment webhook after `refunded` | Attempt status, refund/settlement fields, and gateway transaction metadata remain unchanged |
| Additional verified success after another attempt settled the invoice | `gateway_succeeded_at` and success metadata persist on the additional attempt; it remains locally unsettled, the winning pointer and activation side effects remain unchanged, and a warning is logged for operational review |
| Verified success replay with a different transaction ID | The first verified-success snapshot remains unchanged; an identifier-only warning is logged for operational review, with no automatic refund or reconciliation |
| Verified success after a prior failure | The locked attempt may become `succeeded` and settle the still-unpaid invoice after all ownership, amount, currency, and optional transaction checks pass |
| Duplicate successful webhook | Attempt/invoice locks and paid-state guards make it a no-op |
| Registrar definitive registration failure | Attempt `confirmed_failed`, item `failed`, global claim `released`; paid invoice is not rolled back because registrar work is after commit |
| Registrar ambiguous outcome | Attempt `indeterminate`, item `in_progress`, claim `claimed`; no automatic re-registration |
| WHM definitive rejection | Subscription provisioning becomes `failed`; paid invoice remains paid |
| WHM ambiguous outcome | Subscription provisioning becomes `unknown`; paid invoice remains paid; no automatic `createacct` retry |
| Queue exception | Job logs and rethrows according to its existing queue policy; financial rows remain committed |

## Security Controls

- Lahza webhook verification uses the raw request body and signature header; return URLs and browser forms are never payment proof. Signature contract: HMAC-SHA256 over the exact raw request body, using the configured `webhook_secret`, compared against the `x-lahza-signature` header (corrected from SHA512 in P1-13A2; see ADR_007_PHASE5B_LAHZA_GATEWAY_REPORT.md).
- Webhook amount/currency are checked against both the invoice and attempt, and optional transaction lookup provides defense in depth.
- Payment gateway credentials are encrypted casts and hidden from serialization.
- Registrar passwords/tokens/API keys are encrypted casts and hidden from serialization.
- WHM server password and API token are encrypted by `Server` accessors/mutators.
- Domain provider identity and prices are snapshotted from server-side catalog rows, not trusted request values.
- Attempt response payloads are allow-listed/redacted before persistence.
- The composite unique constraint on `(gateway, gateway_session_id)` prevents two valid attempts from sharing one hosted-session identity inside the same gateway namespace.
- Database unique constraints also protect payment idempotency keys, checkout fingerprints, registrar attempt UUIDs, and global normalized-domain claims.

## Current Technical Debt and Known Exceptions

| Area | Verified current issue |
|---|---|
| cPanel credential storage | `subscriptions.cpanel_password` has no encrypted cast/mutator and is stored as plain text. WHM server `api_token` is encrypted and is not part of this debt. |
| Invoice item reference | `invoice_items.reference_id` is type-discriminated rather than a real polymorphic foreign key. Type-guarded accessors reduce phantom matches for `subscription` and `domain`, but database referential integrity remains ambiguous. |
| Service invoice reference | Admin Invoice CRUD accepts an integer `reference_id` for `service`, but there is no service-specific existence validation, model relation/accessor, foreign-key contract, or documented referential meaning. No current settlement or activation behavior consumes that value. |
| Gateway transaction identity | `payment_attempts.gateway_transaction_id` has a non-unique index. The current code does not establish a provider-independent uniqueness contract, so the transaction-identity audit does not add a constraint; repeated transaction IDs across attempts require separate identity analysis. |
| Fixed coupon value/currency | `coupons.discount_value` is still `decimal(10,2)`. For `fixed`, `computeDiscountCents()` multiplies it by 100 and describes it as USD, but checkout can create domain invoices in `USD`, `ILS`, or `JOD`; there is no coupon currency field or FX policy. |
| Renewal pricing | Renewal may fall back from a renewal price to a registration price and ultimately to a hard-coded amount; this is weaker than the strict registration quote contract. |
| Indeterminate payment-session recovery | A genuinely indeterminate `createSession()` outcome (see [Confirmed pre-session failure classification](#confirmed-pre-session-failure-classification)) has no automatic stale-claim recovery or reconciliation mechanism. The invoice's hosted-session claim stays `creating` and the attempt stays `pending` until an operator/admin intervenes; this is an intentional scope boundary, not an oversight. |
| Registrar failure logging | In the exception handler around registration claim creation, the log context references `$domain` before that local variable is assigned, which may obscure the original failure. |
| Legacy code comments | Several PHP docblocks still describe earlier phases (for example old payment-phase wording) even though executable code implements the newer flow. This document follows executable code and migrations. |
| Generic Order-backed currency invariant | Neither `orders` nor `subscriptions` has a currency column, and `OrderItem.meta['currency']` exists only for domain items (and is `null` on at least one existing domain-adjacent creation path). [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity)'s settlement check is therefore deliberately silent on currency rather than inventing a weaker/inconsistent check. The existing gateway-path protection — `PaymentAttempt.currency` compared to `Invoice.currency` in `assertPaymentSessionOwnsSettlement()` and webhook reconciliation — is untouched and remains the only currency protection at settlement time. |
| Admin manual/offline payment evidence | Administrative manual settlement (`payment_method = admin_manual`) reaches the authoritative `InvoiceSettlementService::markPaid()` and is bound by the same ownership/integrity/idempotency rules as gateway settlement, but it does not persist a dedicated accounting-grade manual-payment record — no stored payment method, external reference, recording admin, or notes beyond the `Invoice`/attempt state described above. |
| Order-backed invoice correction workflow | Order-backed invoice financial items are immutable through the Admin Invoice CRUD by design (see [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity)), regardless of paid status. There is no dedicated credit-note/correction/reissue workflow for them; a pricing correction currently has to happen through the Order/OrderItem contract itself. |
| Domain transfer billing/provisioning | `transfer` is not a complete public checkout operation in the current flow: `RegistrarProvisioningService` has no `OPERATION_TRANSFER` handling and returns an "unsupported domain provisioning action" result for it, and [Order-backed invoice financial integrity](#order-backed-invoice-financial-integrity)'s billable-domain predicate (`register`/`renew` only) treats it as non-billable by construction. Any front-end price/display affordance for `transfer` that implies otherwise was not re-verified as part of this audit and remains a separate gap to confirm if still present. |

The following former debt statements are no longer accurate and are intentionally absent: missing payment/webhook infrastructure, lack of payment/checkout idempotency, first-domain-only provisioning, registrar calls inside financial transactions, automatic WHM username retries, and lack of scheduled renewal invoice creation.

## Operational Commands

```text
php artisan domains:process-auto-renewals --dry-run
php artisan domains:process-auto-renewals
php artisan domains:reconcile-provisioning --dry-run
php artisan domains:reconcile-provisioning --apply
php artisan payments:review-duplicate-charges
```

Use reconciliation before making any decision about an `indeterminate` registrar attempt or an `unknown` WHM outcome. Neither state is proof that the external request failed.

`payments:review-duplicate-charges` is a manual, read-only report for non-winning attempts that have verified external success evidence. Its detector is exactly: `payment_attempts.gateway_succeeded_at IS NOT NULL`, `payment_attempts.settled_at IS NULL`, a related paid invoice with a non-null winning `payment_attempt_id`, and a winning attempt different from the reported attempt. The default mode reads only the database, makes no gateway call, and performs no refund, settlement, or other mutation. Filters are available through `--attempt=`, `--invoice=`, `--limit=` (default 50), and `--older-than=` in minutes based on `gateway_succeeded_at`.

The report conservatively classifies rows as `potential_duplicate_charge`, `shared_transaction_id`, `distinct_transaction_ids`, `inconsistent_settlement_state`, or `already_refunded`; none of these labels automatically establishes a double charge. It also reports whether the same gateway and transaction ID occur on another attempt. A shared transaction ID is specifically a warning that both attempts carry the same external identifier, not proof of two charges. Refunded additional attempts remain visible for audit.

Passing `--verify-gateway` permits only a read-only `getTransaction()` lookup when the additional attempt has a transaction ID. The gateway class is resolved from the name stored on that historical attempt; when its constructor requires database configuration, the row with the matching unique `payment_gateways.driver` is used even if inactive. Resolution never falls back to the current default or active gateway. A null transaction ID is reported as `unavailable_no_transaction_id` without an API call.

`verified_succeeded` requires more than a successful provider status. The returned transaction identifier must equal the attempt's stored `gateway_transaction_id`; its non-null cent amount must equal both `payment_attempts.gateway_amount_cents` and the related invoice's `total_cents`; and its non-null currency, normalized with `trim()` and uppercase as in webhook reconciliation, must equal both stored currencies. Known discrepancies are reported as `transaction_identity_mismatch`, `amount_mismatch`, `currency_mismatch`, or `amount_currency_mismatch`. A missing transaction amount or currency is `indeterminate`, because absence is not proof of a mismatch. These results are diagnostic only and are never written back to the invoice, attempt, order, or gateway; the command performs no refund, settlement, or auto-fix.

### Duplicate-charge operational review history

Detection, operational review, and refund are separate contracts. Detection remains the financial predicate documented above and does not disappear after a human review. Operational decisions are stored per additional `PaymentAttempt` in `payment_duplicate_charge_reviews`; no review fields are stored on the attempt or invoice. Refund is not implemented by this history layer.

Review history is append-only. `unreviewed` is not a stored status: it means the candidate has no history rows. Each new decision inserts a row with the reviewer, review time, safe financial-evidence snapshot, detector classification, and optional pre-existing verification snapshot. The current review is the row ordered first by `reviewed_at DESC`, then `id DESC`. Concurrent inserts therefore preserve both decisions without a lost update.

The stored `review_status` values are `needs_follow_up` and `resolved`; the only resolutions are `confirmed_duplicate` and `not_duplicate`. A follow-up row may have no conclusion or may record `confirmed_duplicate` while operational action remains open. A resolved row must have a conclusion. `confirmed_duplicate` is an audit conclusion and never triggers a refund. `not_duplicate` does not erase `gateway_succeeded_at` or other gateway evidence. A refunded attempt remains a separate financial fact derived from `PaymentAttempt.status` or `refunded_at`; `already_refunded` is not a review resolution.

## Related Documents

- [Project Overview](00-project-overview.md)
- [System Architecture](01-system-architecture.md)
- [Database Architecture](03-database-architecture.md)
- [Developer Guide](21-developer-guide.md)
- [Coding Standards](22-coding-standards.md)
- [Security Notes](24-security-notes.md)
