<x-client-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Dashboard</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Welcome, {{ trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) }}</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">Active Subscriptions</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $client->subscriptions_count }}</h3>
                        <span class="w-10 h-10 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center">
                            <i class="ti ti-package text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">Domains</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $client->domains_count }}</h3>
                        <span class="w-10 h-10 rounded-full bg-success-500/10 text-success-500 inline-flex items-center justify-center">
                            <i class="ti ti-world text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">Invoices</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $invoiceCount }}</h3>
                        <span class="w-10 h-10 rounded-full bg-warning-500/10 text-warning-500 inline-flex items-center justify-center">
                            <i class="ti ti-file-invoice text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">Unpaid Invoices</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $unpaidInvoiceCount }}</h3>
                        <span class="w-10 h-10 rounded-full bg-danger-500/10 text-danger-500 inline-flex items-center justify-center">
                            <i class="ti ti-alert-circle text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="card table-card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <h5 class="mb-0">Recent Subscriptions</h5>
                        <a href="{{ route('client.subscriptions') }}" class="btn btn-sm btn-light-primary">View All</a>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Domain</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSubscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->plan->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-primary/10 text-primary rounded-full text-xs px-2 py-1">
                                                {{ ucfirst((string) $subscription->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->domain_name ?: '-' }}</td>
                                        <td>{{ $subscription->next_due_date?->format('Y-m-d') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-500 py-6">No subscriptions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <h5 class="mb-0">Recent Invoices</h5>
                        <a href="{{ route('client.invoices') }}" class="btn btn-sm btn-light-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        @forelse ($recentInvoices as $invoice)
                            <div class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                                <div>
                                    <div class="font-medium">{{ $invoice->number }}</div>
                                    <div class="text-sm text-gray-500">{{ $invoice->due_date?->format('Y-m-d') ?: '-' }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="font-semibold">{{ number_format(($invoice->total_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</div>
                                    <div class="text-sm text-gray-500">{{ ucfirst((string) $invoice->status) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">No invoices yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
