<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentAttempt;
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

            // ── Settle the invoice ────────────────────────────────────────
            $invoiceUpdate = [
                'status'    => 'paid',
                'paid_date' => now(),
            ];

            // ADR-007 Phase 2 — Link the winning PaymentAttempt to the invoice
            if ($paymentAttempt !== null) {
                $invoiceUpdate['payment_attempt_id'] = $paymentAttempt->id;
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
                if ($order->status !== Order::STATUS_ACTIVE) {
                    $order->update(['status' => Order::STATUS_ACTIVE]);
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
