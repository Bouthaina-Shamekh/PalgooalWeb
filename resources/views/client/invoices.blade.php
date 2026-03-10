<x-client-layout>
    @php
        $statusClasses = [
            'paid' => 'bg-success-500/10 text-success-700',
            'unpaid' => 'bg-warning-500/10 text-warning-700',
            'draft' => 'bg-secondary-500/10 text-secondary-700',
            'cancelled' => 'bg-danger-500/10 text-danger-700',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.home') }}">{{ t('frontend.client_nav.home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_nav.invoices', 'Invoices') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ t('frontend.client_invoices.index.title', 'Invoices') }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_invoices.index.subtitle', 'Review invoice history and reopen any unpaid invoice in the demo payment flow.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @if (session('info'))
        <div class="alert alert-info" role="alert">{{ session('info') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h5 class="mb-0">{{ t('frontend.client_invoices.index.list_title', 'Invoice List') }}</h5>
                <span class="text-sm text-muted">
                    {{ t('frontend.client_invoices.index.total_records', ':count invoices', ['count' => $invoices->total()]) }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-hover w-full align-middle">
                    <thead>
                        <tr>
                            <th>{{ t('frontend.client_invoices.index.number', 'Number') }}</th>
                            <th>{{ t('frontend.client_invoices.index.items', 'Items') }}</th>
                            <th>{{ t('frontend.client_invoices.index.total', 'Total') }}</th>
                            <th>{{ t('frontend.client_invoices.index.status', 'Status') }}</th>
                            <th>{{ t('frontend.client_invoices.index.due_date', 'Due Date') }}</th>
                            <th class="text-end">{{ t('frontend.client_invoices.index.actions', 'Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            @php
                                $status = (string) ($invoice->status ?? 'draft');
                                $statusClass = $statusClasses[$status] ?? 'bg-secondary-500/10 text-secondary-700';
                                $itemsSummary = $invoice->items
                                    ->take(2)
                                    ->pluck('description')
                                    ->filter()
                                    ->implode(', ');
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-medium text-body">{{ $invoice->number }}</div>
                                    <div class="text-xs text-muted">{{ $invoice->created_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td>
                                    <div class="text-sm text-body">{{ $itemsSummary ?: t('frontend.client_invoices.index.no_items', 'No items') }}</div>
                                    <div class="text-xs text-muted">
                                        {{ t('frontend.client_invoices.index.item_count', ':count item(s)', ['count' => $invoice->items->count()]) }}
                                    </div>
                                </td>
                                <td class="font-medium text-body">
                                    {{ number_format(($invoice->total_cents ?? 0) / 100, 2) }} {{ $invoice->currency ?? 'USD' }}
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }}">
                                        {{ t('frontend.client_invoices.status.' . $status, ucfirst($status)) }}
                                    </span>
                                </td>
                                <td>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="text-end">
                                    @if (in_array($status, ['draft', 'unpaid'], true))
                                        <a href="{{ route('client.invoices.checkout', $invoice) }}" class="btn btn-sm btn-primary">
                                            {{ t('frontend.client_invoices.index.pay_now', 'Pay Now') }}
                                        </a>
                                    @elseif ($status === 'paid')
                                        <a href="{{ route('client.invoices.checkout', $invoice) }}" class="btn btn-sm btn-light-success">
                                            {{ t('frontend.client_invoices.index.view_result', 'View Result') }}
                                        </a>
                                    @else
                                        <span class="text-sm text-muted">
                                            {{ t('frontend.client_invoices.index.no_action', 'No action') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-muted">
                                    {{ t('frontend.client_invoices.index.empty', 'No invoices found yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-client-layout>
