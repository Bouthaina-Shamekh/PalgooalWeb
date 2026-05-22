<x-client-layout>

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-sm text-slate-400 mb-6 font-cairo" dir="rtl">
    <a href="{{ route('client.home') }}" class="hover:text-slate-700 transition">الرئيسية</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <a href="{{ route('client.domains.index') }}" class="hover:text-slate-700 transition">النطاقات</a>
    <i class="ti ti-chevron-left text-xs"></i>
    <span class="text-slate-700 font-medium">البحث عن نطاق</span>
</nav>

{{-- Flash --}}
@if (session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700" dir="rtl">
        <i class="ti ti-circle-check flex-shrink-0"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700" dir="rtl">
        <i class="ti ti-alert-circle flex-shrink-0"></i> {{ session('error') }}
    </div>
@endif

{{-- No registrar warning --}}
@if (!$has_registrar_setup)
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700" dir="rtl">
        <i class="ti ti-alert-triangle flex-shrink-0"></i>
        البحث عن النطاقات غير متاح حالياً. يرجى التواصل مع الدعم.
    </div>
@endif

{{-- Hero Search Card --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 lg:p-8 mb-6 shadow-xl" dir="rtl">
    <div class="pointer-events-none absolute -top-10 -left-10 h-48 w-48 rounded-full bg-white/5"></div>
    <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-white/5"></div>

    <div class="relative">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white">
                <i class="ti ti-world-search text-xl leading-none"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">البحث عن نطاق</h1>
                <p class="text-sm text-slate-400">تحقق من التوفر الفوري واشترِ مباشرةً</p>
            </div>
        </div>

        {{-- Search Form --}}
        <form id="clientDomainSearchForm">
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <input
                    id="domainSearchInput"
                    type="text"
                    placeholder="مثال: mystore أو mystore.com"
                    autocomplete="off"
                    @disabled(!$has_registrar_setup)
                    class="flex-1 rounded-xl bg-white/10 border border-white/20 px-4 py-3 text-sm text-white placeholder-slate-400 outline-none transition focus:bg-white/15 focus:border-white/40 disabled:opacity-50"
                    dir="ltr"
                >
                <button id="runDomainSearch" type="submit"
                        @disabled(!$has_registrar_setup)
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                    <i class="ti ti-search text-base leading-none"></i>
                    تحقق من التوفر
                </button>
            </div>

            {{-- TLD Chips --}}
            <div class="rounded-xl bg-white/5 border border-white/10 p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-slate-400">اختر اللاحقات للبحث</p>
                    <button type="button" id="toggleTldsButton"
                            class="text-xs font-semibold text-slate-300 hover:text-white transition">
                        عرض الكل
                    </button>
                </div>
                <div id="tldChipWrap" class="flex flex-wrap gap-2"></div>
            </div>
        </form>

        {{-- Mini Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
            <div class="rounded-xl bg-white/10 px-4 py-3">
                <p class="text-xs text-slate-400 mb-1">وضع البحث</p>
                <p class="text-sm font-bold text-white">مباشر</p>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-3">
                <p class="text-xs text-slate-400 mb-1">اللاحقات المدعومة</p>
                <p class="text-sm font-bold text-white">{{ $catalog_stats['tld_count'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-3 sm:col-span-1 col-span-2">
                <p class="text-xs text-slate-400 mb-1">الشراء</p>
                <p class="text-sm font-bold text-white">فوري بعد الدفع</p>
            </div>
        </div>
    </div>
</div>

{{-- Results Section --}}
<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm" dir="rtl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i class="ti ti-list-search text-slate-500"></i>
            <h2 class="font-semibold text-slate-800">نتائج البحث</h2>
        </div>
        <div class="flex items-center gap-3">
            <p id="resultsSummary" class="text-xs text-slate-400">في انتظار البحث...</p>
            <span id="resultsBadge"
                  class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                —
            </span>
        </div>
    </div>

    <div class="p-6">
        {{-- Status Message --}}
        <div id="searchStatus" class="hidden mb-4"></div>

        {{-- Placeholder --}}
        <div id="resultsPlaceholder" class="flex flex-col items-center justify-center py-16 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 mb-3">
                <i class="ti ti-world-search text-2xl leading-none text-slate-300"></i>
            </div>
            <p class="font-semibold text-slate-600 text-sm">جاهز للبحث</p>
            <p class="text-xs text-slate-400 mt-1">أدخل اسماً، اختر اللاحقات، ثم اضغط تحقق</p>
        </div>

        {{-- Primary Result --}}
        <div id="primaryResult" class="hidden"></div>

        {{-- Grid Results --}}
        <div id="resultGrid" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4"></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const config = {
        checkUrl: @json(route('domains.check')),
        buyUrl: @json(route('client.domains.buy')),
        hasRegistrarSetup: @json($has_registrar_setup),
        defaultTlds: @json(array_values($default_tlds ?? [])),
        allTlds: @json(array_values($all_tlds ?? [])),
        fallbackPrices: @json($fallback_prices ?? []),
        searchButtonLabel: 'تحقق من التوفر',
        showAllLabel: 'عرض الكل',
        showLessLabel: 'عرض أقل',
        waitingLabel: '—',
        emptyInput: 'أدخل اسم النطاق أو كلمة بحث أولاً.',
        noTlds: 'اختر لاحقة واحدة على الأقل.',
        searching: 'جاري التحقق...',
        noProvider: 'البحث غير متاح حالياً. حاول لاحقاً.',
        noResults: 'لم تُرجَع أي نتائج لهذا البحث.',
        searchReady: 'اكتمل البحث بنجاح.',
        available: 'متاح',
        unavailable: 'غير متاح',
        buyNow: 'اشتراء',
        premium: 'بريميوم',
        noPrice: 'السعر من الكتالوج',
        resultsFound: 'نتائج',
        exactMatch: 'النطاق المطلوب',
        alternateMatch: 'بديل',
        poweredBy: 'جاهز للطلب',
    };

    const state = {
        showAllTlds: false,
        selectedTlds: new Set((config.defaultTlds || []).slice(0, 6)),
    };

    if (!state.selectedTlds.size && Array.isArray(config.allTlds) && config.allTlds.length) {
        state.selectedTlds.add(config.allTlds[0]);
    }

    const form              = document.getElementById('clientDomainSearchForm');
    const input             = document.getElementById('domainSearchInput');
    const searchButton      = document.getElementById('runDomainSearch');
    const toggleTldsButton  = document.getElementById('toggleTldsButton');
    const tldChipWrap       = document.getElementById('tldChipWrap');
    const searchStatus      = document.getElementById('searchStatus');
    const resultsSummary    = document.getElementById('resultsSummary');
    const resultsBadge      = document.getElementById('resultsBadge');
    const resultsPlaceholder = document.getElementById('resultsPlaceholder');
    const primaryResult     = document.getElementById('primaryResult');
    const resultGrid        = document.getElementById('resultGrid');

    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function setStatus(type, message) {
        if (!message) { searchStatus.className = 'hidden'; searchStatus.innerHTML = ''; return; }
        const map = {
            success: 'flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 mb-4',
            danger:  'flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-4',
            warning: 'flex items-center gap-2 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700 mb-4',
            info:    'flex items-center gap-2 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-700 mb-4',
        };
        const icons = { success:'ti-circle-check', danger:'ti-alert-circle', warning:'ti-alert-triangle', info:'ti-info-circle' };
        searchStatus.className = map[type] || map.info;
        searchStatus.innerHTML = '<i class="ti ' + (icons[type]||'ti-info-circle') + ' flex-shrink-0"></i>' + escapeHtml(message);
    }

    function setLoading(loading) {
        searchButton.disabled = loading || !config.hasRegistrarSetup;
        searchButton.innerHTML = loading
            ? '<i class="ti ti-loader-2 animate-spin text-base leading-none"></i>' + config.searching
            : '<i class="ti ti-search text-base leading-none"></i>' + config.searchButtonLabel;
    }

    function formatPrice(price, currency) {
        if (price === null || price === undefined || Number.isNaN(Number(price))) return config.noPrice;
        return '$' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(price)) + ' ' + (currency || 'USD');
    }

    function sanitizeLabel(v) {
        return String(v||'').toLowerCase().replace(/[^a-z0-9-]/g,'').replace(/^-+/,'').replace(/-+$/,'');
    }
    function sanitizeTld(v) {
        return String(v||'').toLowerCase().replace(/^\.+/,'').replace(/[^a-z0-9.-]/g,'');
    }
    function parseSearchValue(raw) {
        const cleaned = String(raw||'').trim().toLowerCase()
            .replace(/^https?:\/\//,'').replace(/^www\./,'').replace(/\/.*$/,'').replace(/\s+/g,'');
        if (!cleaned) return null;
        if (cleaned.includes('.')) {
            const parts = cleaned.split('.');
            const label = sanitizeLabel(parts.shift());
            const exactTld = sanitizeTld(parts.join('.'));
            if (!label || !exactTld) return null;
            return { label, exactTld, primaryDomain: label + '.' + exactTld };
        }
        const label = sanitizeLabel(cleaned);
        if (!label) return null;
        return { label, exactTld: null, primaryDomain: null };
    }
    function buildDomains(raw) {
        const parsed = parseSearchValue(raw);
        if (!parsed) return null;
        const selected = Array.from(state.selectedTlds);
        const tlds = parsed.exactTld
            ? [parsed.exactTld].concat(selected.filter(t => t !== parsed.exactTld))
            : selected;
        if (!tlds.length) return null;
        return {
            primaryDomain: parsed.primaryDomain || (parsed.label + '.' + tlds[0]),
            domains: Array.from(new Set(tlds.map(t => parsed.label + '.' + t))),
        };
    }
    function resultTld(domain) {
        const parts = String(domain||'').toLowerCase().split('.');
        return parts.length > 1 ? parts.slice(1).join('.') : '';
    }

    function renderTldChips() {
        const source = state.showAllTlds ? config.allTlds : config.defaultTlds;
        tldChipWrap.innerHTML = source.map(tld => {
            const sel = state.selectedTlds.has(tld);
            const cls = sel
                ? 'bg-white text-slate-900 border-white'
                : 'bg-white/10 text-slate-300 border-white/20 hover:bg-white/20 hover:text-white';
            const price = config.fallbackPrices[tld];
            return '<button type="button" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition ' + cls + '" data-tld="' + escapeHtml(tld) + '">' +
                '<span>.' + escapeHtml(tld) + '</span>' +
                (price !== undefined ? '<span class="opacity-70">$' + escapeHtml(price) + '</span>' : '') +
                '</button>';
        }).join('');
        toggleTldsButton.textContent = state.showAllTlds ? config.showLessLabel : config.showAllLabel;
    }

    function buyLink(domain) {
        return config.buyUrl + '?domain=' + encodeURIComponent(domain);
    }

    function renderCard(result, featured) {
        const available = Boolean(result.available);
        const cardBg = available ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200';
        const badge  = available
            ? '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>' + config.available + '</span>'
            : '<span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-500">' + config.unavailable + '</span>';
        const premium = result.is_premium
            ? '<span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">' + config.premium + '</span>'
            : '';
        const action = available
            ? '<a href="' + buyLink(result.domain) + '" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700"><i class="ti ti-shopping-cart text-xs leading-none"></i>' + config.buyNow + '</a>'
            : '<button type="button" disabled class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400 cursor-not-allowed">' + config.unavailable + '</button>';
        const labelTag = featured ? config.exactMatch : config.alternateMatch;
        const wrapClass = featured ? '' : '';

        return '<div class="' + wrapClass + '">' +
            '<div class="rounded-xl border ' + cardBg + ' p-4 flex items-center justify-between gap-3">' +
                '<div class="min-w-0">' +
                    '<p class="text-[10px] uppercase tracking-wide text-slate-400 mb-1">' + labelTag + '</p>' +
                    '<p class="font-semibold text-slate-800 text-sm break-all">' + escapeHtml(result.domain) + premium + '</p>' +
                    '<p class="text-xs text-slate-400 mt-0.5">' + formatPrice(result.price, result.currency) + '</p>' +
                '</div>' +
                '<div class="flex flex-col items-end gap-2 flex-shrink-0">' +
                    badge +
                    action +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function renderResults(payload, prepared) {
        const selectedOrder = new Map(Array.from(state.selectedTlds).map((tld, i) => [tld, i]));
        const primaryDomain = String(prepared.primaryDomain || '').toLowerCase();
        const results = (Array.isArray(payload.results) ? payload.results : []).slice().sort((l, r) => {
            const ld = String(l.domain||'').toLowerCase(), rd = String(r.domain||'').toLowerCase();
            if (ld === primaryDomain) return -1;
            if (rd === primaryDomain) return 1;
            if (Boolean(l.available) !== Boolean(r.available)) return l.available ? -1 : 1;
            const lr = selectedOrder.get(resultTld(ld)) ?? 999;
            const rr = selectedOrder.get(resultTld(rd)) ?? 999;
            if (lr !== rr) return lr - rr;
            return ld.localeCompare(rd);
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
        const availableCount = results.filter(item => Boolean(item.available)).length;

        resultsPlaceholder.classList.add('hidden');
        primaryResult.classList.remove('hidden');
        primaryResult.innerHTML = renderCard(first, true);

        if (rest.length) {
            resultGrid.classList.remove('hidden');
            resultGrid.innerHTML = rest.map(item => renderCard(item, false)).join('');
        } else {
            resultGrid.classList.add('hidden');
            resultGrid.innerHTML = '';
        }

        resultsSummary.textContent = availableCount + ' / ' + results.length + ' ' + config.resultsFound;
        resultsBadge.textContent = (payload.duration_ms || 0) + ' ms';
        setStatus('success', config.searchReady);
    }

    async function runSearch(event) {
        event.preventDefault();
        if (!config.hasRegistrarSetup) { setStatus('danger', config.noProvider); return; }
        if (!state.selectedTlds.size)  { setStatus('warning', config.noTlds); return; }
        const prepared = buildDomains(input.value);
        if (!prepared) { setStatus('warning', config.emptyInput); return; }

        setLoading(true);
        setStatus('info', config.searching);
        resultsBadge.textContent = config.searching;

        try {
            const url = new URL(config.checkUrl, window.location.origin);
            url.searchParams.set('domains', prepared.domains.join(','));
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || config.noResults);
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

    toggleTldsButton.addEventListener('click', function () {
        state.showAllTlds = !state.showAllTlds;
        renderTldChips();
    });

    tldChipWrap.addEventListener('click', function (event) {
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
    if (input) input.focus();
});
</script>
@endpush
</x-client-layout>
