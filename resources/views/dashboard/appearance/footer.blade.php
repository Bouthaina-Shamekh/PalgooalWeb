<x-dashboard-layout>
    @php
        $activeFooterKey = $settings->active_footer_variant;
        if (! array_key_exists($activeFooterKey, $footerVariants)) {
            $activeFooterKey = array_key_first($footerVariants);
        }

        $activeVariant = $activeFooterKey ? ($footerVariants[$activeFooterKey] ?? null) : null;
        $sortedFooterVariants = collect($footerVariants)->sortByDesc(
            fn (array $variant, string $key) => $key === $activeFooterKey
        );
    @endphp

    <div class="space-y-6">
        <div class="page-header">
            <div class="page-block">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ t('dashboard.Appearance', 'Appearance') }}</a></li>
                    <li class="breadcrumb-item">{{ t('dashboard.Footer_Layout', 'Footer Layout') }}</li>
                </ul>
                <div class="page-header-title">
                    <h2 class="mb-0">{{ t('dashboard.Footer_Layout', 'Footer Layout') }}</h2>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 md:p-5">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <img
                            src="{{ asset($activeVariant['preview'] ?? 'assets/front-layouts/previews/footers/default.svg') }}"
                            alt="{{ $activeVariant['label'] ?? t('dashboard.Footer_Layout', 'Footer Layout') }}"
                            class="w-28 h-20 object-cover rounded-xl border border-gray-200 bg-slate-100 shrink-0"
                        />
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge bg-light-success text-success">
                                    {{ t('dashboard.Active_Footer', 'Active Footer') }}
                                </span>
                                <span class="text-sm text-muted">{{ t('dashboard.Live_On_Website', 'Live on website') }}</span>
                            </div>
                            <h3 class="text-lg font-semibold mb-0">{{ $activeVariant['label'] ?? ($activeFooterKey ?? '-') }}</h3>
                            <p class="text-sm text-muted mb-0">{{ $activeVariant['description'] ?? '' }}</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="badge {{ $settings->footer_show_contact_banner ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Contact_Banner', 'Contact Banner') }}:
                                    {{ $settings->footer_show_contact_banner ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                                <span class="badge {{ $settings->footer_show_payment_methods ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Payment_Methods', 'Payment Methods') }}:
                                    {{ $settings->footer_show_payment_methods ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('frontend.home') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            {{ t('dashboard.Preview_Homepage', 'Preview Homepage') }}
                        </a>
                        <a href="{{ route('dashboard.general_settings') }}" class="btn btn-primary btn-sm">
                            {{ t('dashboard.Open_General_Settings', 'Open General Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h5 class="mb-1">{{ t('dashboard.Footer_Layouts', 'Footer Layouts') }}</h5>
                                <p class="text-sm text-muted mb-0">{{ t('dashboard.Select_Footer_Layout_Desc', 'Choose and activate the footer style used in your frontend pages.') }}</p>
                            </div>
                            <div class="w-full md:w-72">
                                <input
                                    type="text"
                                    data-footer-search
                                    class="form-control"
                                    placeholder="{{ t('dashboard.Search_Footer_Layouts', 'Search footer layouts...') }}"
                                >
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <button type="button" data-footer-filter="all" class="btn btn-sm btn-primary">
                                {{ t('dashboard.All', 'All') }}
                            </button>
                            <button type="button" data-footer-filter="active" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Active', 'Active') }}
                            </button>
                            <button type="button" data-footer-filter="inactive" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Not_Active', 'Not Active') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($sortedFooterVariants->isEmpty())
                            <div class="text-center py-5 text-muted">
                                {{ t('dashboard.No_Footer_Layouts_Found', 'No footer layouts found.') }}
                            </div>
                        @else
                            <div id="footer-variant-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($sortedFooterVariants as $key => $variant)
                                    @php
                                        $isActive = $activeFooterKey === $key;
                                        $variantLabel = $variant['label'] ?? $key;
                                        $variantDescription = $variant['description'] ?? '';
                                        $variantPreview = $variant['preview'] ?? 'assets/front-layouts/previews/footers/default.svg';
                                    @endphp

                                    <div
                                        data-footer-card
                                        data-state="{{ $isActive ? 'active' : 'inactive' }}"
                                        data-key="{{ strtolower($key) }}"
                                        data-label="{{ strtolower($variantLabel) }}"
                                        data-description="{{ strtolower($variantDescription) }}"
                                        class="rounded-2xl border {{ $isActive ? 'border-primary shadow-lg ring-2 ring-primary/20' : 'border-gray-200' }} bg-white overflow-hidden"
                                    >
                                        <form action="{{ route('dashboard.appearance.footer.variant') }}" method="POST" class="h-full flex flex-col">
                                            @csrf
                                            <input type="hidden" name="active_footer_variant" value="{{ $key }}">

                                            <button type="submit" class="w-full text-start">
                                                <img
                                                    src="{{ asset($variantPreview) }}"
                                                    alt="{{ $variantLabel }}"
                                                    class="w-full h-48 object-cover bg-slate-100"
                                                />
                                            </button>

                                            <div class="p-4 space-y-3 flex-1 flex flex-col">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <h3 class="text-base font-semibold mb-1">{{ $variantLabel }}</h3>
                                                        <p class="text-sm text-muted mb-0">{{ $variantDescription }}</p>
                                                    </div>
                                                    @if ($isActive)
                                                        <span class="badge bg-light-success text-success shrink-0">{{ t('dashboard.Active', 'Active') }}</span>
                                                    @endif
                                                </div>

                                                <div class="mt-auto pt-2">
                                                    <button type="submit" class="btn {{ $isActive ? 'btn-outline-secondary' : 'btn-primary' }} w-full">
                                                        {{ $isActive ? t('dashboard.Active_Now', 'Active Now') : t('dashboard.Activate', 'Activate') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div id="footer-variant-empty" class="hidden text-center py-5 text-muted">
                                {{ t('dashboard.No_Footer_Layout_Match', 'No layout matches your search/filter.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Footer_Settings', 'Footer Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="footer-settings-form" action="{{ route('dashboard.appearance.footer.settings') }}" method="POST" class="space-y-4">
                            @csrf

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="footer_show_contact_banner"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="footer_show_contact_banner"
                                        value="1"
                                        @checked(old('footer_show_contact_banner', $settings->footer_show_contact_banner))
                                    >
                                    <label class="form-check-label" for="footer_show_contact_banner">
                                        {{ t('dashboard.Show_Contact_Banner', 'Show contact banner') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Show_Contact_Banner_Help', 'Show the top contact section in supported footer variants.') }}</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="footer_show_payment_methods"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="footer_show_payment_methods"
                                        value="1"
                                        @checked(old('footer_show_payment_methods', $settings->footer_show_payment_methods))
                                    >
                                    <label class="form-check-label" for="footer_show_payment_methods">
                                        {{ t('dashboard.Show_Payment_Methods', 'Show payment methods') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Show_Payment_Methods_Help', 'Display supported payment icons in the footer.') }}</p>
                            </div>

                            <div id="footer-settings-savehint" class="hidden alert alert-warning py-2 mb-0">
                                {{ t('dashboard.Unsaved_Changes', 'You have unsaved changes.') }}
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="footer-settings-reset" class="btn btn-outline-secondary w-full">
                                    {{ t('dashboard.Reset', 'Reset') }}
                                </button>
                                <button type="submit" class="btn btn-primary w-full">
                                    {{ t('dashboard.Save_Changes', 'Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Footer_Note', 'Footer Note') }}</h5>
                    </div>
                    <div class="card-body space-y-3 text-sm text-muted">
                        <p class="mb-0">{{ t('dashboard.Footer_Note_Desc', 'Click any card to activate a footer layout. Contact and social data remain managed in General Settings.') }}</p>
                        <a href="{{ route('dashboard.general_settings') }}" class="btn btn-outline-primary w-full">
                            {{ t('dashboard.Open_General_Settings', 'Open General Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.querySelector('[data-footer-search]');
                const filterButtons = document.querySelectorAll('[data-footer-filter]');
                const cards = document.querySelectorAll('[data-footer-card]');
                const emptyState = document.getElementById('footer-variant-empty');
                let activeFilter = 'all';

                const normalize = (value) => (value || '').toLowerCase();

                function applyCardFilters() {
                    const query = normalize(searchInput?.value);
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const haystack = [
                            normalize(card.dataset.key),
                            normalize(card.dataset.label),
                            normalize(card.dataset.description),
                        ].join(' ');

                        const matchesQuery = !query || haystack.includes(query);
                        const matchesFilter = activeFilter === 'all' || card.dataset.state === activeFilter;
                        const shouldShow = matchesQuery && matchesFilter;

                        card.classList.toggle('hidden', !shouldShow);
                        if (shouldShow) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', visibleCount > 0);
                    }
                }

                searchInput?.addEventListener('input', applyCardFilters);

                filterButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        activeFilter = this.dataset.footerFilter || 'all';

                        filterButtons.forEach((btn) => {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-secondary');
                        });

                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-primary');

                        applyCardFilters();
                    });
                });

                applyCardFilters();

                const settingsForm = document.getElementById('footer-settings-form');
                const saveHint = document.getElementById('footer-settings-savehint');
                const resetButton = document.getElementById('footer-settings-reset');

                if (!settingsForm) {
                    return;
                }

                const serialize = (formData) => {
                    const values = {};
                    for (const [key, value] of formData.entries()) {
                        if (!Object.prototype.hasOwnProperty.call(values, key)) {
                            values[key] = [];
                        }
                        values[key].push(value);
                    }
                    return JSON.stringify(values);
                };

                const initialState = serialize(new FormData(settingsForm));

                function updateSaveHint() {
                    const currentState = serialize(new FormData(settingsForm));
                    const isDirty = currentState !== initialState;
                    saveHint?.classList.toggle('hidden', !isDirty);
                }

                settingsForm.addEventListener('change', updateSaveHint);
                settingsForm.addEventListener('input', updateSaveHint);

                resetButton?.addEventListener('click', function () {
                    settingsForm.reset();
                    updateSaveHint();
                });

                updateSaveHint();
            });
        </script>
    @endpush
</x-dashboard-layout>
