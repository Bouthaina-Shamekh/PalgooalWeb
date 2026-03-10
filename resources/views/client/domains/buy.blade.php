<x-client-layout>
    @php
        $priceCents = (int) ($domain_data['price_cents'] ?? 0);
        $priceValue = number_format($priceCents / 100, 2);
        $currency = $domain_data['currency'] ?? 'USD';
        $isPremium = (bool) ($quote['is_premium'] ?? false);
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
                            <a href="{{ route('client.domains.search') }}">{{ t('frontend.client_domains.search.title', 'Search Domains') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_domains.buy.review_title', 'Review Order') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ t('frontend.client_domains.buy.review_title', 'Review Order') }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_domains.buy.review_subtitle', 'Confirm the selected domain and continue to the payment step.') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('client.domains.search') }}" class="btn btn-light-secondary">
                    <i class="ti ti-arrow-left me-1"></i>
                    {{ t('frontend.client_domains.buy.back_to_search', 'Back to Search') }}
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

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <h5 class="mb-0">{{ t('frontend.client_domains.buy.cart_title', 'Shopping Cart') }}</h5>
                        <span class="badge bg-light-primary text-primary">
                            {{ t('frontend.client_domains.buy.one_item', '1 item') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="rounded-2xl border border-secondary-200/60 p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="flex items-start gap-4">
                                <span class="w-12 h-12 rounded-2xl bg-primary/10 text-primary inline-flex items-center justify-center shrink-0">
                                    <i class="ti ti-world text-xl leading-none"></i>
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h4 class="mb-0 text-body">{{ $domain }}</h4>
                                        @if ($isPremium)
                                            <span class="badge bg-light-warning text-warning">
                                                {{ t('frontend.client_domains.buy.premium_label', 'Premium') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mb-2 text-sm text-muted">
                                        {{ t('frontend.client_domains.buy.item_subtitle', 'Domain registration for one year.') }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="badge bg-light-secondary text-secondary">
                                            {{ t('frontend.client_domains.buy.term_label', '1 Year Registration') }}
                                        </span>
                                        <span class="badge bg-light-secondary text-secondary">
                                            {{ t('frontend.client_domains.buy.status_label', 'Status') }}:
                                            {{ t('frontend.client_domains.buy.pending_status', 'Pending Activation') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-start md:text-end">
                                <div class="text-sm text-muted mb-1">{{ t('frontend.client_domains.buy.line_total', 'Line Total') }}</div>
                                <div class="text-2xl font-semibold text-body">{{ $priceValue }} {{ $currency }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-4 mt-6">
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_domains.buy.registration_date', 'Registration Date') }}
                                </div>
                                <div class="text-sm font-medium text-body">{{ $domain_data['registration_date'] }}</div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_domains.buy.renewal_date', 'Renewal Date') }}
                                </div>
                                <div class="text-sm font-medium text-body">{{ $domain_data['renewal_date'] }}</div>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="rounded-2xl border border-secondary-200/60 p-4 h-full">
                                <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                    {{ t('frontend.client_domains.buy.delivery_label', 'Order Handling') }}
                                </div>
                                <div class="text-sm font-medium text-body">
                                    {{ t('frontend.client_domains.buy.delivery_value', 'Processed automatically after confirmation') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">{{ t('frontend.client_domains.buy.next_steps_title', 'What Happens After Checkout') }}</h5>
                    <div class="space-y-3">
                        <div class="rounded-2xl border border-secondary-200/60 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.buy.step_one', 'A new order and invoice are created for this domain request.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/60 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.buy.step_two', 'You are redirected to a demo payment gateway to review the checkout experience.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/60 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.buy.step_three', 'After successful payment, the domain appears in your account as an active domain.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-[90px]">
                <div class="card-body">
                    <h5 class="mb-4">{{ t('frontend.client_domains.buy.summary_title', 'Order Summary') }}</h5>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted">{{ t('frontend.client_domains.buy.domain_label', 'Domain') }}</span>
                            <span class="font-medium text-body">{{ $domain }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted">{{ t('frontend.client_domains.buy.plan_label', 'Plan') }}</span>
                            <span class="font-medium text-body">{{ t('frontend.client_domains.buy.term_label', '1 Year Registration') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted">{{ t('frontend.client_domains.buy.subtotal_label', 'Subtotal') }}</span>
                            <span class="font-medium text-body">{{ $priceValue }} {{ $currency }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted">{{ t('frontend.client_domains.buy.fees_label', 'Fees') }}</span>
                            <span class="font-medium text-body">{{ t('frontend.client_domains.buy.fees_value', 'Included') }}</span>
                        </div>
                    </div>

                    <hr class="my-4 border-secondary-200/60">

                    <div class="flex items-center justify-between mb-4">
                        <span class="font-medium text-body">{{ t('frontend.client_domains.buy.total_label', 'Total Due') }}</span>
                        <span class="text-2xl font-semibold text-body">{{ $priceValue }} {{ $currency }}</span>
                    </div>

                    <form id="purchaseForm" method="POST" action="{{ route('client.domains.purchase') }}">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $domain_data['client_id'] }}">
                        <input type="hidden" name="domain_name" value="{{ $domain }}">

                        <button id="purchaseBtn" type="submit" class="btn btn-primary w-full">
                            <i class="ti ti-credit-card me-1"></i>
                            {{ t('frontend.client_domains.buy.complete_order', 'Continue to Payment') }}
                        </button>
                    </form>

                    <a href="{{ route('client.domains.search') }}" class="btn btn-light-secondary w-full mt-2">
                        {{ t('frontend.client_domains.buy.continue_searching', 'Continue Searching') }}
                    </a>

                    <p class="mt-4 mb-0 text-xs text-muted text-center">
                        {{ t('frontend.client_domains.buy.summary_note', 'Continuing will create an invoice and move you to the demo payment page for checkout testing.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('purchaseForm');
                const purchaseBtn = document.getElementById('purchaseBtn');
                const originalLabel = purchaseBtn.innerHTML;
                const processingLabel = @json(t('frontend.client_domains.buy.processing', 'Creating Checkout...'));

                form.addEventListener('submit', function() {
                    purchaseBtn.disabled = true;
                    purchaseBtn.innerHTML = '<i class="ti ti-loader-2 me-1 animate-spin"></i>' + processingLabel;

                    setTimeout(function() {
                        purchaseBtn.disabled = false;
                        purchaseBtn.innerHTML = originalLabel;
                    }, 10000);
                });
            });
        </script>
    @endpush
</x-client-layout>
