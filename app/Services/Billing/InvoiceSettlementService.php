<?php

namespace App\Services\Billing;

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
                ])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($lockedInvoice->status === 'paid') {
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
