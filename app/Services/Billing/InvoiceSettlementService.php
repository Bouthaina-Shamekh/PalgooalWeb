<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Tenancy\Subscription;
use Illuminate\Support\Facades\DB;

class InvoiceSettlementService
{
    public function __construct(
        protected OrderActivationService $activationService,
    ) {}

    /**
     * Mark an invoice as paid and activate the associated order/subscription.
     *
     * ADR-007 Phase 2 — Optional PaymentAttempt linkage.
     *
     * The $paymentAttempt parameter is optional and backward-compatible:
     *  - Existing callers (CheckoutController, InvoiceCheckoutController,
     *    DomainRenewalService, admin bulk-mark-paid) pass no PaymentAttempt.
     *    Their behavior is unchanged.
     *  - Phase 3 (Webhook handler) will pass a PaymentAttempt, which gets
     *    linked to the invoice and marked as succeeded inside the transaction.
     *
     * Preserved from Phase 1:
     *  - DB::transaction wrapper
     *  - lockForUpdate() idempotency guard
     *  - Early-return if already paid
     *  - OrderActivationService::activate() call
     *
     * @param  \App\Models\Invoice              $invoice
     * @param  string|null                      $paymentMethod  Gateway name (written to domain.payment_method)
     * @param  \App\Models\PaymentAttempt|null  $paymentAttempt Optional audit record to link and mark succeeded
     */
    public function markPaid(Invoice $invoice, ?string $paymentMethod = null, ?PaymentAttempt $paymentAttempt = null): void
    {
        DB::transaction(function () use ($invoice, $paymentMethod, $paymentAttempt) {
            $lockedInvoice = Invoice::query()
                ->with([
                    'items',
                    'order.items',
                    'order.invoices.items',
                    'coupon',      // ADR-008 Phase 3 — eager-load for settlement tracking
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($lockedInvoice->status === 'paid') {
                // Already settled — idempotency guard prevents double-increment of used_count.
                return;
            }

            $this->assertPaymentSessionOwnsSettlement($lockedInvoice, $paymentAttempt);

            // TLD-3H.3C — Order-backed invoices are a billing projection of their Order/
            // OrderItems (see TLD-3H.3B audit). Before ANY financial mutation — Invoice.status
            // -> paid, coupon consumption, Order activation, provisioning — verify the stored
            // Invoice/InvoiceItem projection still agrees with the Order/OrderItem (and, for
            // subscription lines, Subscription) contract. Fail closed: throwing here rolls back
            // this entire DB::transaction, so no paid state, no coupon increment, no Order
            // activation, and no provisioning can ever be persisted on a mismatch.
            if ($lockedInvoice->order_id !== null && $lockedInvoice->order instanceof Order) {
                $this->assertOrderBackedFinancialIntegrity($lockedInvoice, $lockedInvoice->order);
            }

            // ── Settle the invoice ────────────────────────────────────────
            $invoiceUpdate = [
                'status'    => 'paid',
                'paid_date' => now(),
            ];

            // ADR-007 Phase 2 — Link the winning PaymentAttempt to the invoice
            if ($paymentAttempt !== null) {
                $invoiceUpdate['payment_attempt_id'] = $paymentAttempt->id;
            }

            if (in_array($lockedInvoice->payment_session_status, [
                Invoice::PAYMENT_SESSION_CREATING,
                Invoice::PAYMENT_SESSION_READY,
            ], true)) {
                $invoiceUpdate['payment_session_status'] = Invoice::PAYMENT_SESSION_READY;
            }

            $lockedInvoice->update($invoiceUpdate);

            // ADR-007 Phase 2 — Mark the PaymentAttempt as succeeded
            if ($paymentAttempt !== null && !$paymentAttempt->isSucceeded()) {
                $paymentAttempt->update([
                    'status'     => PaymentAttempt::STATUS_SUCCEEDED,
                    'settled_at' => now(),
                ]);
            }

            // ADR-008 Phase 3 — Coupon usage tracking at settlement time.
            //
            // Design decisions:
            //   1. Tracking happens HERE (after real payment) not in CheckoutController
            //      (which fires before payment), to avoid consuming the coupon on
            //      abandoned invoices.
            //   2. lockForUpdate() on the coupon row prevents race conditions when
            //      two payments settle concurrently (e.g. double-click, webhook retry).
            //   3. We do NOT re-validate max_uses here — once coupon_id is attached to
            //      the invoice, the discount is honored. The lock only prevents the
            //      increment itself from racing; it does not reject payment.
            //   4. Idempotency is guaranteed by the early-return on status==='paid' above.
            if ($lockedInvoice->coupon_id) {
                $coupon = Coupon::query()
                    ->lockForUpdate()
                    ->find($lockedInvoice->coupon_id);

                if ($coupon) {
                    $coupon->increment('used_count');

                    // Attach subscription(s) from invoice items to the coupon pivot.
                    $subscriptionIds = $lockedInvoice->items
                        ->where('item_type', 'subscription')
                        ->pluck('reference_id')
                        ->filter()
                        ->values()
                        ->all();

                    if (!empty($subscriptionIds)) {
                        $coupon->subscriptions()->syncWithoutDetaching($subscriptionIds);
                    }
                }
            }

            // ── Activate the order / provision the subscription ───────────
            $order = $lockedInvoice->order;

            if ($order instanceof Order) {
                // TLD-3H.3A — activate() must fire at most once per genuine
                // non-active -> active transition of this Order. Capture the
                // pre-settlement status BEFORE any mutation: if the Order was
                // already active (e.g. a second/later invoice settling on an
                // Order a prior invoice already activated), OrderActivationService::
                // activate() must NOT be re-invoked — it unconditionally re-extends
                // subscription billing dates and re-dispatches provisioning/registrar
                // calls with no idempotency guard of its own at this layer. The
                // invoice still settles (already marked paid above) either way.
                $wasAlreadyActive = $order->status === Order::STATUS_ACTIVE;

                if (!$wasAlreadyActive) {
                    $order->update(['status' => Order::STATUS_ACTIVE]);
                }

                if ($wasAlreadyActive) {
                    return;
                }

                $order->loadMissing(['invoices.items', 'items']);
                $activationResult = $this->activationService->activate($order, $paymentMethod);
                $domainRegistration = $activationResult['domain_registration'] ?? null;

                if (is_array($domainRegistration) && (($domainRegistration['ok'] ?? true) === false)) {
                    $message = $domainRegistration['message'] ?? 'The registrar rejected the automatic domain request.';
                    $cid = $domainRegistration['cid'] ?? null;

                    if ($cid) {
                        $message .= ' (cid: ' . $cid . ')';
                    }

                    throw new \RuntimeException($message);
                }

                return;
            }

            $this->syncStandaloneInvoiceDomain($lockedInvoice, $paymentMethod);
        });
    }

    /**
     * A hosted-payment claim may only be settled by its linked PaymentAttempt.
     * Legacy settlement without PaymentAttempt remains valid only while the invoice is idle.
     */
    protected function assertPaymentSessionOwnsSettlement(
        Invoice $invoice,
        ?PaymentAttempt $paymentAttempt,
    ): void {
        $sessionStatus = $invoice->payment_session_status ?: Invoice::PAYMENT_SESSION_IDLE;

        if ($sessionStatus === Invoice::PAYMENT_SESSION_IDLE) {
            if ($paymentAttempt !== null) {
                throw new \RuntimeException('Payment attempt does not own an active invoice session claim.');
            }

            return;
        }

        if (!in_array($sessionStatus, [
            Invoice::PAYMENT_SESSION_CREATING,
            Invoice::PAYMENT_SESSION_READY,
        ], true)) {
            throw new \RuntimeException('Unsupported invoice payment session state.');
        }

        if ($paymentAttempt === null
            || (int) $paymentAttempt->id !== (int) $invoice->payment_session_attempt_id
        ) {
            throw new \RuntimeException('Payment attempt does not own the invoice session claim.');
        }

        if ((int) $paymentAttempt->gateway_amount_cents !== (int) $invoice->total_cents
            || (string) $paymentAttempt->currency !== (string) $invoice->currency
        ) {
            throw new \RuntimeException('Payment attempt amount or currency does not match invoice.');
        }
    }

    /**
     * TLD-3H.3C — Fail-closed financial integrity check for Order-backed invoices.
     *
     * Invariant (approved in TLD-3H.3B): an Invoice with order_id !== null is a billing
     * PROJECTION of its Order/OrderItems, never an independent financial record. This method
     * proves the projection has not diverged from the frozen contract before settlement is
     * allowed to proceed. It is type-aware because the two Order-backed InvoiceItem shapes
     * this codebase actually produces (confirmed by fresh-reading every creation path —
     * DomainRenewalService, CheckoutController, Client\DomainController) have different
     * sources of truth:
     *
     *  - domain items: OrderItem has NO qty column (one row = one domain operation) and
     *    carries the frozen price in OrderItem.price_cents. There is no order_item_id FK on
     *    InvoiceItem, and for a fresh domain_registration settlement InvoiceItem.reference_id
     *    is still null (the Domain row doesn't exist until provisioning runs, which happens
     *    AFTER this check) — so a reliable per-row 1:1 pairing is not available. What IS
     *    reliably available on every path: the COUNT of BILLABLE domain-bearing OrderItems
     *    (TLD-3H.3C.5 — item_option in ['register','renew'], the same predicate
     *    OrderActivationService uses for $hasProvisionableDomain; a subdomain/own/transfer
     *    OrderItem is never billable and is excluded before counting, not merely $0-tolerated)
     *    and the COUNT of domain-type InvoiceItems, and their aggregate financial totals. Both
     *    are checked, plus a qty===1 structural check (every domain InvoiceItem-creation call
     *    site hardcodes qty=1 — DomainInvoiceItemBuilder and every inline InvoiceItem::create()).
     *
     *  - subscription items: unlike domain orders, a subscription-type Order-backed invoice
     *    has NO OrderItem row for the subscription line at all (CheckoutController only ever
     *    creates an OrderItem for an optional $0 bundled-domain add-on). The reliable,
     *    already-used-elsewhere source of truth here is Subscription.price_cents, reached via
     *    InvoiceItem.reference_id — a real FK also relied on by OrderActivationService and
     *    InvoiceItem::getSubscriptionAttribute().
     *
     * Explicitly NOT checked: currency. TLD-3H.3B/3H.3C confirmed neither `orders` nor
     * `subscriptions` has ever had a currency column, and OrderItem.meta['currency'] exists
     * only for domain items and is explicitly null on at least one existing domain-adjacent
     * creation path (CheckoutController's bundled-domain OrderItem) — there is no generic
     * Order-level currency invariant to compare against. Inventing one here would be exactly
     * the "weaker than the actual contract" check this phase was told not to add. The existing
     * gateway-path currency check (assertPaymentSessionOwnsSettlement(), comparing
     * PaymentAttempt.currency to Invoice.currency) is untouched and remains the only currency
     * protection. This is a tracked architecture gap, not silently ignored — see the TLD-3H.3C
     * report.
     *
     * Any InvoiceItem whose item_type is neither 'domain' nor 'subscription' fails closed
     * immediately: this codebase never programmatically creates any other item_type on an
     * Order-backed invoice, so an unrecognized shape here is treated as tampering/corruption,
     * never silently skipped.
     */
    protected function assertOrderBackedFinancialIntegrity(Invoice $invoice, Order $order): void
    {
        $invoiceItems = $invoice->items;
        $orderItems = $order->items;

        foreach ($invoiceItems as $item) {
            if (!in_array($item->item_type, ['domain', 'subscription'], true)) {
                throw new \RuntimeException(
                    'Order-backed invoice contains an unrecognized item type and cannot be settled: ' . $item->item_type,
                );
            }

            if ((int) $item->total_cents !== (int) $item->unit_price_cents * (int) $item->qty) {
                throw new \RuntimeException(
                    'Order-backed invoice item total does not match unit price × quantity (invoice item #' . $item->id . ').',
                );
            }
        }

        // ── Domain items: aggregate count + total against OrderItem (no per-row FK exists) ──
        //
        // TLD-3H.3C.5 — filled($orderItem->domain) alone is NOT a billable-domain predicate.
        // Fresh-read across every OrderItem-creation path (CheckoutController combined +
        // domain-only branches, Client\DomainController::registerDomain, DomainRenewalService::
        // prepareRenewalCheckout) plus every downstream consumer of item_option (OrderActivation
        // Service's $hasProvisionableDomain gate, RegistrarProvisioningService::provisionOrderItem's
        // action dispatch, DomainProvisioningAttempt::OPERATION_* constants) confirms 'register'
        // and 'renew' are the ONLY item_option values this codebase ever creates with a real,
        // trusted, non-zero price_cents AND a matching domain InvoiceItem. Every other value
        // (subdomain, own, transfer, or any other client-submitted domain_option string) is only
        // ever created via CheckoutController's scalar-field fallback with price_cents hardcoded
        // to 0 and NO matching InvoiceItem — by construction, not by exemption. 'transfer' in
        // particular is confirmed unimplemented at the billing/provisioning layer today (no
        // OPERATION_TRANSFER constant, RegistrarProvisioningService explicitly returns
        // "Unsupported domain provisioning action." for it) even though the checkout UI fetches a
        // display price for it — that price is never persisted or billed server-side, so treating
        // it as non-billable here reflects actual current behavior, not a guess. This mirrors
        // OrderActivationService::activate()'s existing $hasProvisionableDomain predicate exactly,
        // so "billable" and "provisionable" stay a single, consistent classification project-wide.
        $billableDomainOptions = ['register', 'renew'];
        $domainInvoiceItems = $invoiceItems->where('item_type', 'domain')->values();
        $domainOrderItems = $orderItems->filter(
            fn ($orderItem) => filled($orderItem->domain)
                && in_array(strtolower((string) $orderItem->item_option), $billableDomainOptions, true)
        )->values();

        if ($domainInvoiceItems->count() !== $domainOrderItems->count()) {
            throw new \RuntimeException(
                'Order-backed invoice domain item count does not match the Order contract.',
            );
        }

        foreach ($domainInvoiceItems as $item) {
            if ((int) $item->qty !== 1) {
                throw new \RuntimeException(
                    'Order-backed domain invoice item quantity must be 1 (invoice item #' . $item->id . ').',
                );
            }
        }

        $domainInvoiceTotal = (int) $domainInvoiceItems->sum('total_cents');
        $domainOrderTotal = (int) $domainOrderItems->sum('price_cents');

        if ($domainInvoiceTotal !== $domainOrderTotal) {
            throw new \RuntimeException(
                'Order-backed invoice domain financial total does not match the Order contract.',
            );
        }

        // ── Subscription items: per-row against Subscription.price_cents via reference_id ──
        $subscriptionInvoiceItems = $invoiceItems->where('item_type', 'subscription')->values();

        if ($subscriptionInvoiceItems->isNotEmpty()) {
            $subscriptions = Subscription::query()
                ->whereIn('id', $subscriptionInvoiceItems->pluck('reference_id')->filter()->unique()->values())
                ->get()
                ->keyBy('id');

            foreach ($subscriptionInvoiceItems as $item) {
                if ((int) $item->qty !== 1) {
                    throw new \RuntimeException(
                        'Order-backed subscription invoice item quantity must be 1 (invoice item #' . $item->id . ').',
                    );
                }

                $subscription = $item->reference_id !== null ? $subscriptions->get((int) $item->reference_id) : null;

                if (!$subscription instanceof Subscription) {
                    throw new \RuntimeException(
                        'Order-backed subscription invoice item references a missing Subscription (invoice item #' . $item->id . ').',
                    );
                }

                if ((int) $subscription->client_id !== (int) $invoice->client_id) {
                    throw new \RuntimeException(
                        'Order-backed subscription invoice item references a Subscription belonging to a different client (invoice item #' . $item->id . ').',
                    );
                }

                if ((int) $item->unit_price_cents !== (int) $subscription->price_cents) {
                    throw new \RuntimeException(
                        'Order-backed subscription invoice item price does not match the Subscription contract (invoice item #' . $item->id . ').',
                    );
                }
            }
        }

        // ── Invoice-level aggregate: total_cents must equal sum(items) - discount + tax ──
        $itemsTotal = (int) $invoiceItems->sum('total_cents');
        $expectedSubtotal = $itemsTotal;
        $expectedTotal = max(0, $expectedSubtotal - (int) $invoice->discount_cents + (int) $invoice->tax_cents);

        if ((int) $invoice->subtotal_cents !== $expectedSubtotal) {
            throw new \RuntimeException('Order-backed invoice subtotal does not match the sum of its items.');
        }

        if ((int) $invoice->total_cents !== $expectedTotal) {
            throw new \RuntimeException('Order-backed invoice total does not match subtotal - discount + tax.');
        }
    }

    protected function syncStandaloneInvoiceDomain(Invoice $invoice, ?string $paymentMethod = null): void
    {
        $domainItem = $invoice->items
            ->first(fn ($item) => $item->item_type === 'domain' && $item->reference_id);

        if (!$domainItem) {
            return;
        }

        Domain::query()
            ->whereKey($domainItem->reference_id)
            ->update([
                'status' => 'active',
                'payment_method' => $paymentMethod ?: app(\App\Payments\PaymentManager::class)->gateway()->name(),
            ]);
    }
}
