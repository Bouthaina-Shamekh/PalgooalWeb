<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDomainDnsRequest;
use App\Models\Domain;
use App\Services\Domains\DomainDnsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DomainDnsController extends Controller
{
    public function edit(Domain $domain, DomainDnsService $dns): mixed
    {
        $domain = $this->ownedDomain($domain);

        return view('client.domains.dns', [
            'domain' => $domain,
            ...$dns->buildEditorData($domain),
        ]);
    }

    public function update(UpdateDomainDnsRequest $request, Domain $domain, DomainDnsService $dns): RedirectResponse
    {
        $domain = $this->ownedDomain($domain);
        $result = $dns->updateDomainDns($domain, $request->validated());

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['nameservers' => $result['message'] ?? 'Unable to update nameservers.']);
        }

        return redirect()
            ->route('client.domains.dns.edit', $domain)
            ->with('success', $result['message'] ?? 'Nameservers updated successfully.');
    }

    protected function ownedDomain(Domain $domain): Domain
    {
        abort_if((int) $domain->client_id !== (int) Auth::guard('client')->id(), 404);

        return $domain;
    }
}
