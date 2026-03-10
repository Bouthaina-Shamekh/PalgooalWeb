<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Domains\DomainRenewalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DomainRenewalController extends Controller
{
    public function store(Domain $domain, DomainRenewalService $renewals): RedirectResponse
    {
        abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);

        $checkout = $renewals->prepareRenewalCheckout($domain);
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
