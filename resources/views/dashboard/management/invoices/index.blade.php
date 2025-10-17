{{-- resources/views/dashboard/management/invoices/index.blade.php --}}
<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.invoices.index') }}">{{ __('Invoices') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Manage Invoices') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Manage Invoices') }}</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                @if (session('ok'))
                    <x-dashboard.alert type="success" class="mb-4">
                        {{ session('ok') }}
                    </x-dashboard.alert>
                @endif

                @if (session('connection_result'))
                    @php $connMsg = session('connection_result'); @endphp
                    <x-dashboard.alert type="info" class="mb-4">
                        <span class="whitespace-pre-line">{{ $connMsg }}</span>
                    </x-dashboard.alert>
                @endif

                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">{{ __('Invoices') }}</h5>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard.invoices.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> {{ __('Create Invoice') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    {{-- Filters --}}
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <form id="invoiceFilterForm" method="GET" action="{{ route('dashboard.invoices.index') }}"
                              class="flex flex-col sm:flex-row gap-2 sm:items-center w-full">
                            <div class="flex items-center gap-2 sm:min-w-[16rem] w-full">
                                <label for="invoiceSearch" class="sr-only">{{ __('Search') }}</label>
                                <input id="invoiceSearch" type="search" name="q" value="{{ request('q') }}"
                                       placeholder="{{ __('Search by invoice number or client name') }}"
                                       class="rounded border px-3 py-2 text-sm w-full"
                                       autocomplete="off" />
                            </div>

                            <div class="flex items-center gap-2 sm:w-48">
                                <label for="invoiceStatus" class="sr-only">{{ __('Status') }}</label>
                                <select id="invoiceStatus" name="status"
                                        class="rounded border px-2 py-2 text-sm w-full">
                                    <option value="">{{ __('All statuses') }}</option>
                                    @foreach (['draft','unpaid','paid','cancelled'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ __(ucfirst($st)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <button class="px-3 py-2 bg-blue-600 text-white rounded text-sm">
                                    {{ __('Apply') }}
                                </button>
                                @if (request()->hasAny(['q','status']) && (filled(request('q')) || filled(request('status'))))
                                    <a href="{{ route('dashboard.invoices.index') }}"
                                       class="px-3 py-2 border rounded text-sm text-gray-600 hover:bg-gray-100">
                                        {{ __('Reset') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table text-end align-middle">
                            <thead>
                                <tr>
                                    <th class="w-10">
                                        <input type="checkbox" id="select_all_invoices" class="form-check-input" aria-label="{{ __('Select all') }}">
                                    </th>
                                    <th>{{ __('Invoice #') }}</th>
                                    <th>{{ __('Client') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Total') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    @php
                                        // تنسيق المبلغ: cents -> major units، وإظهار كود العملة فقط لتجنب ازدواج $ مع الكود
                                        $totalMajor = number_format(($invoice->total_cents ?? 0) / 100, 2);
                                        $currency   = $invoice->currency ?? 'USD';
                                        $clientF    = optional($invoice->client)->first_name;
                                        $clientL    = optional($invoice->client)->last_name;
                                        $clientName = trim(($clientF ?? '') . ' ' . ($clientL ?? '')) ?: __('Unknown client');
                                        $statusBadges = [
                                            'draft'     => 'badge-secondary',
                                            'unpaid'    => 'badge-warning',
                                            'paid'      => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                        ];
                                        $badgeClass = $statusBadges[$invoice->status] ?? 'badge-secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   class="form-check-input invoice_checkbox"
                                                   name="ids[]"
                                                   value="{{ $invoice->id }}"
                                                   form="bulk_form_invoices"
                                                   aria-label="{{ __('Select invoice :num', ['num' => $invoice->number]) }}">
                                        </td>
                                        <td class="font-mono text-sm">{{ $invoice->number }}</td>
                                        <td class="truncate max-w-[220px]" title="{{ $clientName }}">{{ $clientName }}</td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ __(ucfirst($invoice->status)) }}</span>
                                        </td>
                                        <td>
                                            <span class="font-medium">{{ $totalMajor }}</span>
                                            <span class="text-xs text-gray-500">{{ $currency }}</span>
                                        </td>
                                        <td>
                                            {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}
                                        </td>
                                        <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('dashboard.invoices.show', $invoice->id) }}"
                                                   class="btn btn-icon btn-link-secondary text-gray-600 hover:text-primary-600"
                                                   title="{{ __('View invoice') }}" aria-label="{{ __('View invoice') }}">
                                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8S2 12 2 12z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('dashboard.invoices.edit', $invoice->id) }}"
                                                   class="btn btn-icon btn-link-secondary text-gray-600 hover:text-primary-600"
                                                   title="{{ __('Edit invoice') }}" aria-label="{{ __('Edit invoice') }}">
                                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"></path>
                                                    </svg>
                                                </a>
                                                <form action="{{ route('dashboard.invoices.destroy', $invoice->id) }}"
                                                      method="POST" class="inline ajax-action"
                                                      data-confirm="{{ __('Are you sure you want to delete this invoice?') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-icon btn-link-danger text-red-600 hover:text-red-700"
                                                            title="{{ __('Delete invoice') }}" aria-label="{{ __('Delete invoice') }}">
                                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"></path>
                                                            <path d="M10 11v6M14 11v6"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                            {{ __('No invoices found at the moment.') }}
                                            <a href="{{ route('dashboard.invoices.create') }}"
                                               class="text-blue-600 hover:underline font-medium">
                                                {{ __('Create a new invoice') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Bulk actions --}}
                    <form id="bulk_form_invoices" method="POST" action="{{ route('dashboard.invoices.bulk') }}"
                          class="mt-6 border-t pt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        @csrf
                        <input type="hidden" name="action" id="bulk_action_invoices">
                        <div class="flex flex-col gap-1">
                            <div class="text-sm font-medium text-gray-600">{{ __('With selected:') }}</div>
                            <div id="bulk_selection_helper" class="text-xs text-gray-500">
                                {{ __('Select invoices to enable the actions.') }}
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="paid" disabled>
                                {{ __('Mark Paid') }}
                            </button>
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="unpaid" disabled>
                                {{ __('Mark Unpaid') }}
                            </button>
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="cancelled" disabled>
                                {{ __('Mark Cancelled') }}
                            </button>
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="duplicate" disabled
                                    data-confirm="{{ __('Create a duplicate of :count invoice(s)?', ['count' => ':count']) }}">
                                {{ __('Duplicate Invoice') }}
                            </button>
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="reminder" disabled
                                    data-confirm="{{ __('Send a reminder email for :count invoice(s)?', ['count' => ':count']) }}">
                                {{ __('Send Reminder') }}
                            </button>
                            <button type="button" class="bulk-action-btn px-3 py-2 border rounded text-sm text-red-600 border-red-200 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    data-bulk-action="delete" disabled
                                    data-confirm="{{ __('Delete :count selected invoice(s)? This action cannot be undone.', ['count' => ':count']) }}">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </form>

                    <div class="pagination mt-4">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
<script src="{{ asset('assets/dashboard/js/invoices-index.js') }}" defer></script>
@push('scripts')
    {{-- إن كان لديك الملف جاهزاً فسيعمل مباشرة، وإلا انسخ المحتوى أدناه لملف invoices-index.js --}}
    <script src="{{ asset('assets/dashboard/js/invoices-index.js') }}" defer></script>
@endpush
