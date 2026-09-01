<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Payments\PaymentManager;
use App\Services\Payments\PaymentSessionStarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Client-facing invoice payment entry point.
 *
 * ADR-007 Phase 6 — Real gateway wiring.
 *
 * IMPORTANT: This controller NEVER calls InvoiceSettlementService::markPaid().
 * It only ever:
 *   1. Validates the invoice is payable (ownership, status, amount, currency, order state).
 *   2. Creates/reuses a PaymentAttempt (our own audit row, never trusting client input for amounts).
 *   3. Calls PaymentManager::gateway()->createSession() and redirects to the REAL gateway checkout URL.
 *
 * Settlement (markPaid()) happens exclusively inside PaymentWebhookController, triggered only
 * by a signature-verified webhook from the gateway — never from a client form submission or
 * a return-URL redirect, both of which are trivially spoofable by the browser.
 */
class InvoiceCheckoutController extends Controller
{
    public function show(Request $request, Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice->load([
            'client',
            'items',
            'order.items',
        ]));

        $state = (string) $request->query('state', '');

        // The gateway's cancel_url landed here — best-effort mark the still-open attempt as
        // cancelled. This is purely informational bookkeeping; it never touches Invoice/Order.
        if ($state === 'cancel' && $invoice->status !== 'paid') {
            $this->cancelLatestPendingAttempt($invoice);
        }

        return view('client.invoices.checkout', [
            'invoice' => $invoice,
            // Map the gateway's "cancel" query value to the view's existing 'cancelled' state key
            // so the current template (untouched) renders its existing amber "cancelled" banner.
            'payment_state' => $state === 'cancel' ? 'cancelled' : $state,
        ]);
    }

    /**
     * Start (or resume) a real payment session for this invoice.
     *
     * This method NEVER marks the invoice paid. It only ever creates a gateway checkout
     * session and redirects the browser to it. The client-submitted `scenario` field is
     * retained only for backward compatibility with the existing demo-labeled view (which
     * is out of scope to redesign here) — it can no longer cause a paid/failed outcome by
     * itself. The only scenario value with real meaning now is `cancel`, which lets the
     * client abandon the flow before ever contacting the gateway.
     */
    public function process(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice = $this->resolveInvoice($invoice);

        $data = $request->validate([
            'scenario' => 'nullable|in:success,failed,cancel',
        ]);
        $scenario = $data['scenario'] ?? 'success';

        // Client-side abandonment — legitimate, and never reaches the gateway or changes
        // any financial state. This is the only scenario value that skips session creation.
        if ($scenario === 'cancel') {
            $this->cancelLatestPendingAttempt($invoice);

            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state'   => 'cancel',
            ])->with('info', 'You cancelled the payment. The invoice remains unpaid.');
        }

        // ADR-007 Phase 1 — Payment gateway feature flag
        if (!app(PaymentManager::class)->isEnabled()) {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state'   => 'disabled',
            ])->with('error', t('site.Payment_Not_Available', 'خدمة الدفع غير متاحة حالياً. يرجى المحاولة لاحقاً.'));
        }

        $result = app(PaymentSessionStarter::class)->start(
            $invoice,
            (int) Auth::guard('client')->id(),
            route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'return']),
            route('client.invoices.checkout', ['invoice' => $invoice, 'state' => 'cancel']),
        );

        if ($result['status'] === 'paid') {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'paid',
            ]);
        }

        if ($result['status'] === 'ready' && is_string($result['checkout_url'])) {
            return redirect()->away($result['checkout_url']);
        }

        if (in_array($result['status'], ['creating', 'indeterminate'], true)) {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'processing',
            ])->with('info', $result['message']);
        }

        return redirect()->route('client.invoices.checkout', [
            'invoice' => $invoice,
            'state' => 'failed',
        ])->with('error', $result['message']);
    }

    protected function resolveInvoice(Invoice $invoice): Invoice
    {
        abort_if((int) $invoice->client_id !== (int) Auth::guard('client')->id(), 404);

        return $invoice;
    }

    protected function cancelLatestPendingAttempt(Invoice $invoice): void
    {
        $attempt = PaymentAttempt::where('invoice_id', $invoice->id)
            ->where('status', PaymentAttempt::STATUS_INITIATED)
            ->latest('id')
            ->first();

        if ($attempt) {
            $attempt->update(['status' => PaymentAttempt::STATUS_CANCELLED]);
        }
    }

}
