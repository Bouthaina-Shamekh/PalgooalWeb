<x-client-layout>
    @php
        $status = (string) ($invoice->status ?? 'unpaid');
        $paymentState = $payment_state ?: ($status === 'paid' ? 'paid' : '');
        $totalMajor = number_format(($invoice->total_cents ?? 0) / 100, 2);
        $currency = $invoice->currency ?? 'USD';
        $canPay = in_array($status, ['draft', 'unpaid'], true);
        $hasDomainItem = $invoice->items->contains(fn ($item) => $item->item_type === 'domain');
        $statusClasses = [
            'paid' => 'bg-success-500/10 text-success-700',
            'unpaid' => 'bg-warning-500/10 text-warning-700',
            'draft' => 'bg-secondary-500/10 text-secondary-700',
            'cancelled' => 'bg-danger-500/10 text-danger-700',
        ];
        $statusClass = $statusClasses[$status] ?? 'bg-secondary-500/10 text-secondary-700';
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.home') }}">{{ t('frontend.client_nav.home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.invoices') }}">{{ t('frontend.client_nav.invoices', 'Invoices') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_invoices.checkout.title', 'Demo Payment Checkout') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ t('frontend.client_invoices.checkout.title', 'Demo Payment Checkout') }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_invoices.checkout.subtitle', 'Review your invoice and test the payment experience without charging a real card.') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('client.invoices') }}" class="btn btn-light-secondary">
                    <i class="ti ti-arrow-left me-1"></i>
                    {{ t('frontend.client_invoices.checkout.back', 'Back to Invoices') }}
                </a>
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

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 xl:col-span-7">
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h4 class="mb-0 text-body">{{ $invoice->number }}</h4>
                                <span class="badge {{ $statusClass }}">
                                    {{ t('frontend.client_invoices.status.' . $status, ucfirst($status)) }}
                                </span>
                            </div>
                            <p class="mb-0 text-sm text-muted">
                                {{ t('frontend.client_invoices.checkout.invoice_note', 'This page is connected to a demo gateway for user-experience testing only.') }}
                            </p>
                        </div>
                        <div class="text-start md:text-end">
                            <div class="text-sm text-muted mb-1">{{ t('frontend.client_invoices.checkout.total_due', 'Total Due') }}</div>
                            <div class="text-2xl font-semibold text-body">{{ $totalMajor }} {{ $currency }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-4 mt-6">
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_invoices.checkout.client_label', 'Client') }}
                                </div>
                                <div class="text-sm font-medium text-body">
                                    {{ trim(($invoice->client->first_name ?? '') . ' ' . ($invoice->client->last_name ?? '')) ?: t('frontend.client_nav.account', 'My Account') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_invoices.checkout.due_date', 'Due Date') }}
                                </div>
                                <div class="text-sm font-medium text-body">
                                    {{ $invoice->due_date?->format('Y-m-d') ?? '-' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_invoices.checkout.order_label', 'Order') }}
                                </div>
                                <div class="text-sm font-medium text-body">
                                    {{ $invoice->order->order_number ?? t('frontend.client_invoices.checkout.direct_invoice', 'Direct Invoice') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('frontend.client_invoices.checkout.items_title', 'Invoice Items') }}</h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        @forelse ($invoice->items as $item)
                            <div class="rounded-2xl border border-secondary-200/60 p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="font-medium text-body">{{ $item->description }}</div>
                                        <div class="text-sm text-muted">
                                            {{ t('frontend.client_invoices.checkout.quantity', 'Quantity') }}: {{ $item->qty }}
                                        </div>
                                    </div>
                                    <div class="text-start md:text-end">
                                        <div class="text-sm text-muted">{{ t('frontend.client_invoices.checkout.line_total', 'Line Total') }}</div>
                                        <div class="font-semibold text-body">
                                            {{ number_format(($item->total_cents ?? 0) / 100, 2) }} {{ $currency }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-secondary-300 p-6 text-center text-sm text-muted">
                                {{ t('frontend.client_invoices.checkout.no_items', 'No invoice items are available for this record.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="card sticky top-[90px]">
                <div class="card-body">
                    <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 mb-5">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-primary text-white inline-flex items-center justify-center shrink-0">
                                <i class="ti ti-flask text-lg leading-none"></i>
                            </span>
                            <div>
                                <div class="font-medium text-body">{{ t('frontend.client_invoices.checkout.demo_mode', 'Demo Gateway Enabled') }}</div>
                                <div class="text-sm text-muted">
                                    {{ t('frontend.client_invoices.checkout.demo_note', 'Use the test card below. No real charge will be created.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($paymentState === 'paid')
                        <div class="rounded-2xl border border-success-200 bg-success-500/5 p-5">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="w-12 h-12 rounded-2xl bg-success-500 text-white inline-flex items-center justify-center">
                                    <i class="ti ti-check text-xl leading-none"></i>
                                </span>
                                <div>
                                    <h5 class="mb-1">{{ t('frontend.client_invoices.checkout.paid_title', 'Payment Successful') }}</h5>
                                    <p class="mb-0 text-sm text-muted">
                                        {{ t('frontend.client_invoices.checkout.paid_note', 'The invoice is now marked as paid and the related order has been activated.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-2">
                                @if ($hasDomainItem)
                                    <a href="{{ route('client.domains.index') }}" class="btn btn-primary">
                                        {{ t('frontend.client_invoices.checkout.view_domains', 'View My Domains') }}
                                    </a>
                                @endif
                                <a href="{{ route('client.invoices') }}" class="btn btn-light-secondary">
                                    {{ t('frontend.client_invoices.checkout.view_invoices', 'Back to Invoices') }}
                                </a>
                            </div>
                        </div>
                    @elseif ($canPay)
                        @if ($paymentState === 'failed')
                            <div class="rounded-2xl border border-danger-200 bg-danger-500/5 p-4 mb-4 text-sm text-danger-700">
                                {{ t('frontend.client_invoices.checkout.failed_note', 'The last demo attempt failed. Update the form or retry the payment.') }}
                            </div>
                        @elseif ($paymentState === 'cancelled')
                            <div class="rounded-2xl border border-warning-200 bg-warning-500/5 p-4 mb-4 text-sm text-warning-700">
                                {{ t('frontend.client_invoices.checkout.cancelled_note', 'You left the demo payment without completing it. The invoice is still unpaid.') }}
                            </div>
                        @endif

                        <form id="demoCheckoutForm" method="POST" action="{{ route('client.invoices.checkout.process', $invoice) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="scenario" id="paymentScenario" value="{{ old('scenario', 'success') }}">

                            <div>
                                <label for="card_holder" class="form-label">
                                    {{ t('frontend.client_invoices.checkout.card_holder', 'Card Holder') }}
                                </label>
                                <input
                                    id="card_holder"
                                    type="text"
                                    name="card_holder"
                                    class="form-control"
                                    value="{{ old('card_holder', 'Demo Client') }}"
                                    placeholder="{{ t('frontend.client_invoices.checkout.card_holder_placeholder', 'Demo Client') }}"
                                >
                            </div>

                            <div>
                                <label for="card_number" class="form-label">
                                    {{ t('frontend.client_invoices.checkout.card_number', 'Card Number') }}
                                </label>
                                <input
                                    id="card_number"
                                    type="text"
                                    name="card_number"
                                    class="form-control"
                                    value="{{ old('card_number', '4242 4242 4242 4242') }}"
                                    placeholder="4242 4242 4242 4242"
                                >
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-6">
                                    <label for="expiry_date" class="form-label">
                                        {{ t('frontend.client_invoices.checkout.expiry_date', 'Expiry Date') }}
                                    </label>
                                    <input
                                        id="expiry_date"
                                        type="text"
                                        name="expiry_date"
                                        class="form-control"
                                        value="{{ old('expiry_date', '12/30') }}"
                                        placeholder="12/30"
                                    >
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label for="cvc" class="form-label">
                                        {{ t('frontend.client_invoices.checkout.cvc', 'CVC') }}
                                    </label>
                                    <input
                                        id="cvc"
                                        type="text"
                                        name="cvc"
                                        class="form-control"
                                        value="{{ old('cvc', '123') }}"
                                        placeholder="123"
                                    >
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="rounded-2xl border border-danger-200 bg-danger-500/5 p-4 text-sm text-danger-700">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary w-full" id="payNowBtn" data-scenario="success">
                                <i class="ti ti-lock-check me-1"></i>
                                {{ t('frontend.client_invoices.checkout.pay_now', 'Pay') }} {{ $totalMajor }} {{ $currency }}
                            </button>

                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                <button type="submit" class="btn btn-light-danger w-full" data-scenario="failed">
                                    {{ t('frontend.client_invoices.checkout.simulate_failure', 'Simulate Failure') }}
                                </button>
                                <button type="submit" class="btn btn-light-secondary w-full" data-scenario="cancel">
                                    {{ t('frontend.client_invoices.checkout.cancel_payment', 'Cancel Payment') }}
                                </button>
                            </div>

                            <p class="mb-0 text-xs text-muted text-center">
                                {{ t('frontend.client_invoices.checkout.test_card_help', 'Suggested test card: 4242 4242 4242 4242 with any future expiry date and any 3-digit CVC.') }}
                            </p>
                        </form>
                    @else
                        <div class="rounded-2xl border border-secondary-200/60 p-5">
                            <h5 class="mb-2">{{ t('frontend.client_invoices.checkout.closed_title', 'Payment Is Not Available') }}</h5>
                            <p class="mb-4 text-sm text-muted">
                                {{ t('frontend.client_invoices.checkout.closed_note', 'This invoice is no longer open for demo checkout.') }}
                            </p>
                            <a href="{{ route('client.invoices') }}" class="btn btn-light-secondary w-full">
                                {{ t('frontend.client_invoices.checkout.view_invoices', 'Back to Invoices') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('demoCheckoutForm');
                const payNowBtn = document.getElementById('payNowBtn');
                const scenarioInput = document.getElementById('paymentScenario');

                if (!form || !payNowBtn || !scenarioInput) {
                    return;
                }

                const originalLabel = payNowBtn.innerHTML;
                const processingLabel = @json(t('frontend.client_invoices.checkout.processing', 'Processing Demo Payment...'));
                const scenarioButtons = form.querySelectorAll('button[type="submit"][data-scenario]');

                scenarioButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        scenarioInput.value = button.dataset.scenario || 'success';
                    });
                });

                form.addEventListener('submit', function(event) {
                    const submitter = event.submitter;
                    const selectedScenario = submitter?.dataset?.scenario || scenarioInput.value || 'success';

                    scenarioInput.value = selectedScenario;

                    if (selectedScenario !== 'success') {
                        return;
                    }

                    payNowBtn.disabled = true;
                    payNowBtn.innerHTML = '<i class="ti ti-loader-2 me-1 animate-spin"></i>' + processingLabel;
                });
            });
        </script>
    @endpush
</x-client-layout>
