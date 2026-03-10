<x-client-layout>
    <div class="page-header">
        <div class="page-block">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.home') }}">{{ t('frontend.client_nav.home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_domains.search.title', 'Search Domains') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ t('frontend.client_domains.search.title', 'Search Domains') }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_domains.search.subtitle', 'Check domain availability instantly and continue directly to purchase.') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('client.domains.index') }}" class="btn btn-light-secondary">
                    <i class="ti ti-world me-1"></i>
                    {{ t('frontend.client_domains.search.my_domains', 'My Domains') }}
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

    @if (!$has_registrar_setup)
        <div class="alert alert-warning" role="alert">
            {{ t('frontend.client_domains.search.no_provider_setup', 'Domain search is currently unavailable. Please contact support or try again later.') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 xl:col-span-8">
            <div class="card overflow-hidden">
                <div class="card-body !p-0">
                    <div class="bg-primary px-6 py-6 text-white">
                        <span class="badge bg-white/10 text-white mb-3">
                            {{ t('frontend.client_domains.search.live_badge', 'Instant Domain Search') }}
                        </span>
                        <h3 class="mb-2">{{ t('frontend.client_domains.search.hero_title', 'Search with synced pricing and live availability') }}</h3>
                        <p class="mb-0 text-sm text-white/80">
                            {{ t('frontend.client_domains.search.hero_subtitle', 'Search checks live availability and shows the current catalog pricing when it is available.') }}
                        </p>
                    </div>

                    <div class="p-6">
                        <form id="clientDomainSearchForm" class="space-y-6">
                            <div class="grid grid-cols-12 gap-4 items-end">
                                <div class="col-span-12 lg:col-span-8">
                                    <label for="domainSearchInput" class="form-label">
                                        {{ t('frontend.client_domains.search.domain_keyword', 'Domain or keyword') }}
                                    </label>
                                    <input id="domainSearchInput" type="text" class="form-control"
                                        placeholder="{{ t('frontend.client_domains.search.domain_placeholder', 'example or example.com') }}"
                                        autocomplete="off" @disabled(!$has_registrar_setup) />
                                    <p class="mt-2 mb-0 text-xs text-muted">
                                        {{ t('frontend.client_domains.search.domain_hint', 'Type only the keyword to search multiple extensions, or type a full domain to prioritize that exact match.') }}
                                    </p>
                                </div>
                                <div class="col-span-12 lg:col-span-4">
                                    <button id="runDomainSearch" type="submit" class="btn btn-primary w-full"
                                        @disabled(!$has_registrar_setup)>
                                        <i class="ti ti-search me-1"></i>
                                        {{ t('frontend.client_domains.search.search_button', 'Check Availability') }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-secondary-200/50 p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <label class="form-label mb-1">
                                            {{ t('frontend.client_domains.search.extensions_label', 'Extensions to check') }}
                                        </label>
                                        <p class="mb-0 text-xs text-muted">
                                            {{ t('frontend.client_domains.search.extensions_hint', 'Select one or more TLDs to check against the same keyword.') }}
                                        </p>
                                    </div>
                                    <button type="button" id="toggleTldsButton" class="btn btn-sm btn-light-secondary">
                                        {{ t('frontend.client_domains.search.show_all_tlds', 'Show all TLDs') }}
                                    </button>
                                </div>
                                <div id="tldChipWrap" class="mt-4 flex flex-wrap gap-2"></div>
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-4">
                                    <div class="rounded-2xl border border-secondary-200/50 p-4 h-full">
                                        <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                            {{ t('frontend.client_domains.search.provider_count_label', 'Search Mode') }}
                                        </div>
                                        <div class="text-2xl font-semibold text-body">{{ t('frontend.client_domains.search.search_mode_value', 'Live') }}</div>
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="rounded-2xl border border-secondary-200/50 p-4 h-full">
                                        <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                            {{ t('frontend.client_domains.search.tld_count_label', 'Supported TLDs') }}
                                        </div>
                                        <div class="text-2xl font-semibold text-body">{{ $catalog_stats['tld_count'] ?? 0 }}</div>
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="rounded-2xl border border-secondary-200/50 p-4 h-full">
                                        <div class="text-xs uppercase tracking-wider text-muted mb-2">
                                            {{ t('frontend.client_domains.search.search_source_label', 'Purchase Flow') }}
                                        </div>
                                        <div class="text-sm font-medium text-body">{{ t('frontend.client_domains.search.search_source_value', 'Handled automatically after checkout') }}</div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="mb-0">{{ t('frontend.client_domains.search.providers_title', 'What Happens Next') }}</h5>
                        <span class="badge bg-light-success text-success">
                            {{ t('frontend.client_domains.search.live_status', 'Automatic') }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.search.step_one', '1. Search for the domain you want.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.search.step_two', '2. Choose an available result and continue to purchase.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3 text-sm text-muted">
                            {{ t('frontend.client_domains.search.step_three', '3. The registration process is completed automatically in the background.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">{{ t('frontend.client_domains.search.tips_title', 'Quick Tips') }}</h5>
                    <div class="space-y-3 text-sm text-muted">
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3">
                            {{ t('frontend.client_domains.search.tip_one', 'Short brandable names usually give you better results across multiple TLDs.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3">
                            {{ t('frontend.client_domains.search.tip_two', 'If you enter a full domain like example.com, the exact domain is shown first and the selected TLDs are checked as alternatives.') }}
                        </div>
                        <div class="rounded-2xl border border-secondary-200/50 px-4 py-3">
                            {{ t('frontend.client_domains.search.tip_three', 'Standard prices come from the synced catalog. Premium pricing appears automatically when available.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h5 class="mb-1">{{ t('frontend.client_domains.search.results_title', 'Search Results') }}</h5>
                            <p id="resultsSummary" class="mb-0 text-sm text-muted">
                                {{ t('frontend.client_domains.search.results_idle', 'Run a search to load the latest availability results.') }}
                            </p>
                        </div>
                        <span id="resultsBadge" class="badge bg-light-secondary text-secondary px-3 py-2">
                            {{ t('frontend.client_domains.search.results_waiting', 'Waiting for search') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="searchStatus" class="hidden"></div>

                    <div id="resultsPlaceholder" class="rounded-2xl border border-dashed border-secondary-200/70 px-6 py-12 text-center">
                        <span class="w-14 h-14 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center mb-4">
                            <i class="ti ti-world-search text-2xl leading-none"></i>
                        </span>
                        <h6 class="mb-2">{{ t('frontend.client_domains.search.placeholder_title', 'Ready when you are') }}</h6>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_domains.search.placeholder_subtitle', 'Enter a keyword, select the TLDs you want, then run the search.') }}
                        </p>
                    </div>

                    <div id="primaryResult" class="hidden"></div>
                    <div id="resultGrid" class="hidden grid grid-cols-12 gap-4 mt-4"></div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const config = {
                    checkUrl: @json(route('domains.check')),
                    buyUrl: @json(route('client.domains.buy')),
                    hasRegistrarSetup: @json($has_registrar_setup),
                    defaultTlds: @json(array_values($default_tlds ?? [])),
                    allTlds: @json(array_values($all_tlds ?? [])),
                    fallbackPrices: @json($fallback_prices ?? []),
                    searchButtonLabel: @json(t('frontend.client_domains.search.search_button', 'Check Availability')),
                    showAllLabel: @json(t('frontend.client_domains.search.show_all_tlds', 'Show all TLDs')),
                    showLessLabel: @json(t('frontend.client_domains.search.show_less_tlds', 'Show fewer TLDs')),
                    waitingLabel: @json(t('frontend.client_domains.search.results_waiting', 'Waiting for search')),
                    emptyInput: @json(t('frontend.client_domains.search.js.empty_input', 'Enter a domain name or keyword first.')),
                    noTlds: @json(t('frontend.client_domains.search.js.no_tlds', 'Select at least one extension to search.')),
                    searching: @json(t('frontend.client_domains.search.js.searching', 'Checking live availability...')),
                    noProvider: @json(t('frontend.client_domains.search.js.no_provider', 'Domain search is currently unavailable. Please try again later.')),
                    noResults: @json(t('frontend.client_domains.search.js.no_results', 'No results were returned for this search.')),
                    searchReady: @json(t('frontend.client_domains.search.js.search_ready', 'Live search completed successfully.')),
                    available: @json(t('frontend.client_domains.search.js.available', 'Available')),
                    unavailable: @json(t('frontend.client_domains.search.js.unavailable', 'Unavailable')),
                    buyNow: @json(t('frontend.client_domains.search.js.buy_now', 'Review Order')),
                    premium: @json(t('frontend.client_domains.search.js.premium', 'Premium')),
                    noPrice: @json(t('frontend.client_domains.search.js.no_price', 'Pricing from catalog')),
                    resultsFound: @json(t('frontend.client_domains.search.js.results_found', 'results found')),
                    exactMatch: @json(t('frontend.client_domains.search.js.exact_match', 'Exact match')),
                    alternateMatch: @json(t('frontend.client_domains.search.js.alternate_match', 'Alternate option')),
                    poweredBy: @json(t('frontend.client_domains.search.js.powered_by', 'Ready to order')),
                };

                const state = {
                    showAllTlds: false,
                    selectedTlds: new Set((config.defaultTlds || []).slice(0, 6)),
                };

                if (!state.selectedTlds.size && Array.isArray(config.allTlds) && config.allTlds.length) {
                    state.selectedTlds.add(config.allTlds[0]);
                }

                const form = document.getElementById('clientDomainSearchForm');
                const input = document.getElementById('domainSearchInput');
                const searchButton = document.getElementById('runDomainSearch');
                const toggleTldsButton = document.getElementById('toggleTldsButton');
                const tldChipWrap = document.getElementById('tldChipWrap');
                const searchStatus = document.getElementById('searchStatus');
                const resultsSummary = document.getElementById('resultsSummary');
                const resultsBadge = document.getElementById('resultsBadge');
                const resultsPlaceholder = document.getElementById('resultsPlaceholder');
                const primaryResult = document.getElementById('primaryResult');
                const resultGrid = document.getElementById('resultGrid');

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function setStatus(type, message) {
                    if (!message) {
                        searchStatus.className = 'hidden';
                        searchStatus.textContent = '';
                        return;
                    }

                    const classMap = {
                        success: 'alert alert-success',
                        danger: 'alert alert-danger',
                        warning: 'alert alert-warning',
                        info: 'alert alert-info',
                    };

                    searchStatus.className = classMap[type] || classMap.info;
                    searchStatus.textContent = message;
                }

                function setLoading(loading) {
                    searchButton.disabled = loading || !config.hasRegistrarSetup;
                    searchButton.innerHTML = loading
                        ? '<i class="ti ti-loader-2 me-1 animate-spin"></i>' + config.searching
                        : '<i class="ti ti-search me-1"></i>' + config.searchButtonLabel;
                }

                function formatPrice(price, currency) {
                    if (price === null || price === undefined || Number.isNaN(Number(price))) {
                        return config.noPrice;
                    }

                    return '$' + new Intl.NumberFormat('en-US', {
                        maximumFractionDigits: 2,
                    }).format(Number(price)) + ' ' + (currency || 'USD');
                }

                function sanitizeLabel(value) {
                    return String(value || '')
                        .toLowerCase()
                        .replace(/[^a-z0-9-]/g, '')
                        .replace(/^-+/, '')
                        .replace(/-+$/, '');
                }

                function sanitizeTld(value) {
                    return String(value || '')
                        .toLowerCase()
                        .replace(/^\.+/, '')
                        .replace(/[^a-z0-9.-]/g, '');
                }

                function parseSearchValue(rawValue) {
                    const cleaned = String(rawValue || '')
                        .trim()
                        .toLowerCase()
                        .replace(/^https?:\/\//, '')
                        .replace(/^www\./, '')
                        .replace(/\/.*$/, '')
                        .replace(/\s+/g, '');

                    if (!cleaned) {
                        return null;
                    }

                    if (cleaned.includes('.')) {
                        const parts = cleaned.split('.');
                        const label = sanitizeLabel(parts.shift());
                        const exactTld = sanitizeTld(parts.join('.'));

                        if (!label || !exactTld) {
                            return null;
                        }

                        return {
                            label,
                            exactTld,
                            primaryDomain: label + '.' + exactTld,
                        };
                    }

                    const label = sanitizeLabel(cleaned);
                    if (!label) {
                        return null;
                    }

                    return {
                        label,
                        exactTld: null,
                        primaryDomain: null,
                    };
                }

                function buildDomains(rawValue) {
                    const parsed = parseSearchValue(rawValue);
                    if (!parsed) {
                        return null;
                    }

                    const selected = Array.from(state.selectedTlds);
                    const tlds = parsed.exactTld
                        ? [parsed.exactTld].concat(selected.filter(function(tld) {
                            return tld !== parsed.exactTld;
                        }))
                        : selected;

                    if (!tlds.length) {
                        return null;
                    }

                    return {
                        primaryDomain: parsed.primaryDomain || (parsed.label + '.' + tlds[0]),
                        domains: Array.from(new Set(tlds.map(function(tld) {
                            return parsed.label + '.' + tld;
                        }))),
                    };
                }

                function resultTld(domain) {
                    const parts = String(domain || '').toLowerCase().split('.');
                    return parts.length > 1 ? parts.slice(1).join('.') : '';
                }

                function renderTldChips() {
                    const source = state.showAllTlds ? config.allTlds : config.defaultTlds;

                    tldChipWrap.innerHTML = source.map(function(tld) {
                        const selected = state.selectedTlds.has(tld);
                        const classes = selected
                            ? 'bg-primary text-white border-primary'
                            : 'bg-white text-secondary border-secondary-200 hover:border-primary hover:text-primary';
                        const price = config.fallbackPrices[tld];

                        return '<button type="button" class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition ' + classes + '" data-tld="' + escapeHtml(tld) + '">' +
                            '<span>.' + escapeHtml(tld) + '</span>' +
                            (price !== undefined ? '<span class="text-xs opacity-80">$' + escapeHtml(price) + '</span>' : '') +
                            '</button>';
                    }).join('');

                    toggleTldsButton.textContent = state.showAllTlds ? config.showLessLabel : config.showAllLabel;
                }

                function buyLink(domain) {
                    return config.buyUrl + '?domain=' + encodeURIComponent(domain);
                }

                function renderCard(result, featured) {
                    const available = Boolean(result.available);
                    const cardClass = available
                        ? 'border-success-200 bg-success-50/60'
                        : 'border-danger-200 bg-danger-50/60';
                    const badgeClass = available
                        ? 'bg-light-success text-success'
                        : 'bg-light-danger text-danger';
                    const premiumBadge = result.is_premium
                        ? '<span class="badge bg-light-warning text-warning ms-2">' + config.premium + '</span>'
                        : '';
                    const action = available
                        ? '<a href="' + buyLink(result.domain) + '" class="btn btn-primary">' + config.buyNow + '</a>'
                        : '<button type="button" class="btn btn-light-secondary" disabled>' + config.unavailable + '</button>';
                    const wrapperClass = featured ? '' : 'col-span-12 md:col-span-6 xl:col-span-4';

                    return '<div class="' + wrapperClass + '">' +
                        '<div class="rounded-2xl border ' + cardClass + ' p-5 h-full">' +
                            '<div class="flex items-start justify-between gap-3">' +
                                '<div>' +
                                    '<div class="text-xs uppercase tracking-wider text-muted mb-2">' + (featured ? config.exactMatch : config.alternateMatch) + '</div>' +
                                    '<h5 class="mb-1 text-body">' + escapeHtml(result.domain) + premiumBadge + '</h5>' +
                                    '<div class="text-sm text-muted">' + formatPrice(result.price, result.currency) + '</div>' +
                                '</div>' +
                                '<span class="badge ' + badgeClass + '">' + (available ? config.available : config.unavailable) + '</span>' +
                            '</div>' +
                            '<div class="mt-4 flex items-center justify-between gap-3">' +
                                '<div class="text-xs text-muted">' + config.poweredBy + '</div>' +
                                action +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }

                function renderResults(payload, prepared) {
                    const selectedOrder = new Map(Array.from(state.selectedTlds).map(function(tld, index) {
                        return [tld, index];
                    }));
                    const primaryDomain = String(prepared.primaryDomain || '').toLowerCase();
                    const results = (Array.isArray(payload.results) ? payload.results : []).slice().sort(function(left, right) {
                        const leftDomain = String(left.domain || '').toLowerCase();
                        const rightDomain = String(right.domain || '').toLowerCase();

                        if (leftDomain === primaryDomain) return -1;
                        if (rightDomain === primaryDomain) return 1;
                        if (Boolean(left.available) !== Boolean(right.available)) return left.available ? -1 : 1;

                        const leftRank = selectedOrder.get(resultTld(leftDomain)) ?? 999;
                        const rightRank = selectedOrder.get(resultTld(rightDomain)) ?? 999;
                        if (leftRank !== rightRank) return leftRank - rightRank;

                        return leftDomain.localeCompare(rightDomain);
                    });

                    if (!results.length) {
                        resultsPlaceholder.classList.remove('hidden');
                        primaryResult.classList.add('hidden');
                        resultGrid.classList.add('hidden');
                        resultsSummary.textContent = config.noResults;
                        resultsBadge.textContent = config.waitingLabel;
                        setStatus('warning', config.noResults);
                        return;
                    }

                    const first = results[0];
                    const rest = results.slice(1);
                    const availableCount = results.filter(function(item) {
                        return Boolean(item.available);
                    }).length;

                    resultsPlaceholder.classList.add('hidden');
                    primaryResult.classList.remove('hidden');
                    primaryResult.innerHTML = renderCard(first, true);

                    if (rest.length) {
                        resultGrid.classList.remove('hidden');
                        resultGrid.innerHTML = rest.map(function(item) {
                            return renderCard(item, false);
                        }).join('');
                    } else {
                        resultGrid.classList.add('hidden');
                        resultGrid.innerHTML = '';
                    }

                    resultsSummary.textContent = availableCount + ' / ' + results.length + ' ' + config.resultsFound;
                    resultsBadge.textContent = String(payload.duration_ms || 0) + ' ms';
                    setStatus('success', config.searchReady);
                }

                async function runSearch(event) {
                    event.preventDefault();

                    if (!config.hasRegistrarSetup) {
                        setStatus('danger', config.noProvider);
                        return;
                    }

                    if (!state.selectedTlds.size) {
                        setStatus('warning', config.noTlds);
                        return;
                    }

                    const prepared = buildDomains(input.value);
                    if (!prepared) {
                        setStatus('warning', config.emptyInput);
                        return;
                    }

                    setLoading(true);
                    setStatus('info', config.searching);
                    resultsBadge.textContent = config.searching;

                    try {
                        const url = new URL(config.checkUrl, window.location.origin);
                        url.searchParams.set('domains', prepared.domains.join(','));

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const payload = await response.json();

                        if (!response.ok || !payload.ok) {
                            throw new Error(payload.message || config.noResults);
                        }

                        renderResults(payload, prepared);
                    } catch (error) {
                        primaryResult.classList.add('hidden');
                        resultGrid.classList.add('hidden');
                        resultsPlaceholder.classList.remove('hidden');
                        resultsSummary.textContent = error.message || config.noResults;
                        resultsBadge.textContent = config.waitingLabel;
                        setStatus('danger', error.message || config.noResults);
                    } finally {
                        setLoading(false);
                    }
                }

                toggleTldsButton.addEventListener('click', function() {
                    state.showAllTlds = !state.showAllTlds;
                    renderTldChips();
                });

                tldChipWrap.addEventListener('click', function(event) {
                    const chip = event.target.closest('[data-tld]');
                    if (!chip) return;

                    const tld = chip.dataset.tld;
                    if (!tld) return;

                    if (state.selectedTlds.has(tld)) {
                        if (state.selectedTlds.size === 1) return;
                        state.selectedTlds.delete(tld);
                    } else {
                        state.selectedTlds.add(tld);
                    }

                    renderTldChips();
                });

                form.addEventListener('submit', runSearch);
                renderTldChips();
            });
        </script>
    @endpush
</x-client-layout>
