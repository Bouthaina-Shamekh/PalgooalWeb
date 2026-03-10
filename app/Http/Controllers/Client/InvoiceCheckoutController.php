<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\InvoiceSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceCheckoutController extends Controller
{
    public function show(Request $request, Invoice $invoice)
    {
        $invoice = $this->resolveInvoice($invoice->load([
            'client',
            'items',
            'order.items',
        ]));

        return view('client.invoices.checkout', [
            'invoice' => $invoice,
            'payment_state' => (string) $request->query('state', ''),
        ]);
    }

    public function process(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice = $this->resolveInvoice($invoice);

        $data = $request->validate([
            'scenario' => 'required|in:success,failed,cancel',
            'card_holder' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:32',
            'expiry_date' => 'nullable|string|max:10',
            'cvc' => 'nullable|string|max:4',
        ], [
            'scenario.required' => 'Please choose a payment action before submitting the form.',
            'scenario.in' => 'The selected payment action is invalid.',
        ]);

        if ($invoice->status === 'paid') {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'paid',
            ]);
        }

        if ($invoice->status === 'cancelled') {
            return redirect()->route('client.invoices')
                ->with('error', 'This invoice has already been cancelled.');
        }

        if ($data['scenario'] === 'success') {
            $this->validateDemoCardFields($data);

            try {
                $this->handleSuccessfulPayment($invoice);
            } catch (\Throwable $e) {
                return redirect()->route('client.invoices.checkout', [
                    'invoice' => $invoice,
                    'state' => 'failed',
                ])->with('error', $e->getMessage());
            }

            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'paid',
            ])->with('success', 'Demo payment completed successfully.');
        }

        if ($data['scenario'] === 'failed') {
            return redirect()->route('client.invoices.checkout', [
                'invoice' => $invoice,
                'state' => 'failed',
            ])->with('error', 'The demo payment was declined. You can try again immediately.');
        }

        return redirect()->route('client.invoices.checkout', [
            'invoice' => $invoice,
            'state' => 'cancelled',
        ])->with('info', 'You cancelled the demo payment. The invoice remains unpaid.');
    }

    protected function resolveInvoice(Invoice $invoice): Invoice
    {
        abort_if((int) $invoice->client_id !== (int) Auth::guard('client')->id(), 404);

        return $invoice;
    }

    protected function validateDemoCardFields(array $data): void
    {
        validator($data, [
            'card_holder' => 'required|string|max:255',
            'card_number' => 'required|string|max:32',
            'expiry_date' => 'required|string|max:10',
            'cvc' => 'required|string|max:4',
        ], [
            'card_holder.required' => 'Card holder is required for the demo payment.',
            'card_number.required' => 'Card number is required for the demo payment.',
            'expiry_date.required' => 'Expiry date is required for the demo payment.',
            'cvc.required' => 'CVC is required for the demo payment.',
        ])->validate();
    }

    protected function handleSuccessfulPayment(Invoice $invoice): void
    {
        app(InvoiceSettlementService::class)->markPaid($invoice, 'mock_gateway');
    }
}
