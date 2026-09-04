<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Domains\DomainRenewalService;
use App\Services\Domains\Exceptions\MissingRenewalPriceException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DomainRenewalController extends Controller
{
    public function store(Domain $domain, DomainRenewalService $renewals): RedirectResponse
    {
        abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);

        // TLD-3B — Strict Sale-Only Renewal Pricing: buildRenewalQuote() throws
        // before any Order/Invoice write when no trusted renew.sale exists.
        // Never invent a price and never show internal cost to the customer.
        try {
            $checkout = $renewals->prepareRenewalCheckout($domain);
        } catch (MissingRenewalPriceException $e) {
            Log::warning('Domain renewal blocked: no trusted renewal sale price.', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain_name,
                'registrar' => $domain->registrar,
                'reason' => 'missing_renewal_sale',
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', t(
                'client.domains.renewal_price_unavailable',
                'سعر التجديد غير متوفر حالياً لهذا النطاق.'
            ));
        }

        $invoice = $checkout['invoice'];

        return redirect()
            ->route('client.invoices.checkout', $invoice)
            ->with(
                'success',
                $checkout['created']
                    ? 'Renewal invoice created. Continue to payment to renew the domain.'
                    : 'An existing unpaid renewal invoice was found. Continue to payment.'
            );
    }
}
