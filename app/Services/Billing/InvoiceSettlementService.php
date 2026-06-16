<?php

namespace App\Services\Billing;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InvoiceSettlementService
{
    public function __construct(
        protected OrderActivationService $activationService,
    ) {}

    public function markPaid(Invoice $invoice, ?string $paymentMethod = null): void
    {
        DB::transaction(function () use ($invoice, $paymentMethod) {
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

            $lockedInvoice->update([
                'status' => 'paid',
                'paid_date' => now(),
            ]);

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
