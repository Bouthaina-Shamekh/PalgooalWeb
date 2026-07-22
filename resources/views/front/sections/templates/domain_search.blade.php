@php
    $section_id = trim((string) ($data['section_id'] ?? '')) ?: 'domain-search';
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = (string) ($data['subtitle'] ?? ''); // textarea / trans
    $placeholder = trim((string) ($data['placeholder'] ?? '')); // text / trans
    $button_text = trim((string) ($data['button_text'] ?? '')); // text / trans
    $search_title = trim((string) ($data['search_title'] ?? '')); // text / trans
    $search_description = (string) ($data['search_description'] ?? ''); // textarea / trans

    // امتدادات البحث الافتراضية: تُقرأ من بيانات السكشن إن وُجدت (نص CSV أو مصفوفة)، وإلا com,net,org
    $rawTlds = $data['default_tlds'] ?? $data['tlds'] ?? null;
    if (is_array($rawTlds)) {
        $default_tlds = implode(',', array_filter(array_map('trim', $rawTlds), fn($v) => $v !== ''));
    } else {
        $default_tlds = trim((string) $rawTlds);
    }
    if ($default_tlds === '') {
        $default_tlds = 'com,net,org';
    }
@endphp

<section id="{{ $section_id }}" class="py-20 px-4 md:px-24">
    <div class="text-center mb-8">
        @if ($title)
            <h2 class="text-purple-brand font-extrabold text-3xl md:text-[40px] uppercase">
                {{ $title }}
            </h2>
        @endif
        @if ($subtitle)
            <p class="text-[#555] text-base md:text-lg leading-relaxed">
                {!! nl2br(e($subtitle)) !!}
            </p>
        @endif
    </div>
    <div class="bg-purple-brand rounded-[40px] p-8 md:p-16 text-center text-white max-w-5xl mx-auto shadow-xl">
        @if ($search_title)
            <h3 class="text-2xl md:text-3xl font-bold mb-4">
                {{ $search_title }}
            </h3>
        @endif
        @if ($search_description)
            <p class="text-base md:text-lg font-light mb-6 opacity-80">
                {!! nl2br(e($search_description)) !!}
            </p>
        @endif
        <form data-domain-search-form class="flex flex-col md:flex-row gap-4 max-w-3xl mx-auto">
            <input type="text" name="domain" data-domain-search-input placeholder="{{ $placeholder }}"
                autocomplete="off" spellcheck="false"
                class="flex-1 bg-white rounded-xl px-6 py-4 text-purple-brand text-xl outline-none text-start">
            @if ($button_text)
                <button type="submit" data-domain-search-button
                    class="bg-red-brand text-white px-12 py-4 rounded-xl font-bold text-xl hover:bg-opacity-90">
                    {{ $button_text }}
                </button>
            @endif
        </form>

        <div class="max-w-3xl mx-auto mt-6 text-start" data-domain-search-live aria-live="polite">
            <p class="text-sm hidden" data-domain-search-status></p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3" data-domain-search-results></div>
        </div>
    </div>

<script>
(function () {
    'use strict';

    var scriptEl = document.currentScript;
    var root = scriptEl ? scriptEl.closest('section') : null;
    if (!root) return;

    var form = root.querySelector('[data-domain-search-form]');
    var input = root.querySelector('[data-domain-search-input]');
    var button = root.querySelector('[data-domain-search-button]');
    var statusEl = root.querySelector('[data-domain-search-status]');
    var resultsEl = root.querySelector('[data-domain-search-results]');

    if (!form || !input || !statusEl || !resultsEl) return;

    var CHECK_URL = @json(route('domains.check'));
    var BOOK_URL = @json(route('client.domains.buy'));
    var DEFAULT_TLDS = @json($default_tlds);

    var MSG_EMPTY = @json(t('site.Domain_Search_Empty_Input', 'يرجى إدخال اسم دومين.'));
    var MSG_LOADING = @json(t('site.Domain_Search_Loading', 'جارٍ البحث...'));
    var MSG_NETWORK_ERROR = @json(t('site.Domain_Search_Network_Error', 'تعذّر الاتصال بالخادم. حاول مرة أخرى.'));
    var MSG_UNEXPECTED = @json(t('site.Domain_Search_Unexpected_Response', 'استجابة غير متوقعة من الخادم.'));
    var MSG_NO_RESULTS = @json(t('site.Domain_Search_No_Results', 'لا توجد نتائج.'));
    var LABEL_AVAILABLE = @json(t('site.Domain_Search_Available', 'متاح'));
    var LABEL_UNAVAILABLE = @json(t('site.Domain_Search_Unavailable', 'غير متاح'));
    var LABEL_PREMIUM = @json(t('site.Domain_Search_Premium', 'بريميوم'));
    var LABEL_BOOK_NOW = @json(t('site.Domain_Search_Book_Now', 'احجز الآن'));
    var LABEL_BOOK_NOW_ARIA_TPL = @json(t('site.Domain_Search_Book_Now_Aria', 'احجز الدومين :domain'));

    var originalButtonText = button ? button.textContent : '';
    var inFlight = false;
    var controller = null;

    function setStatus(text, variant) {
        statusEl.textContent = text || '';
        statusEl.classList.remove('text-white/90', 'text-red-100', 'text-yellow-100');
        if (variant === 'error') {
            statusEl.classList.add('text-red-100');
        } else if (variant === 'loading') {
            statusEl.classList.add('text-yellow-100');
        } else {
            statusEl.classList.add('text-white/90');
        }
        if (text) {
            statusEl.setAttribute('data-state', variant || 'info');
            statusEl.classList.remove('hidden');
        } else {
            statusEl.removeAttribute('data-state');
            statusEl.classList.add('hidden');
        }
    }

    function clearResults() {
        while (resultsEl.firstChild) {
            resultsEl.removeChild(resultsEl.firstChild);
        }
    }

    function setLoading(loading) {
        inFlight = loading;
        if (!button) return;
        button.disabled = loading;
        button.classList.toggle('opacity-60', loading);
        button.classList.toggle('cursor-not-allowed', loading);
        button.textContent = loading ? MSG_LOADING : originalButtonText;
    }

    function buildResultCard(item) {
        var domain = String((item && item.domain) || '');
        var available = !!(item && item.available === true);
        var isPremium = !!(item && item.is_premium === true);
        var price = item ? item.price : null;
        var currency = item ? item.currency : null;

        var card = document.createElement('div');
        card.className = 'rounded-xl p-4 text-start bg-white/10 border ' +
            (available ? 'border-green-400/60' : 'border-red-300/50');

        var nameRow = document.createElement('div');
        nameRow.className = 'flex items-center justify-between gap-2 flex-wrap';

        var nameEl = document.createElement('span');
        nameEl.className = 'font-bold';
        nameEl.textContent = domain;
        nameRow.appendChild(nameEl);

        var badge = document.createElement('span');
        badge.className = 'text-xs font-semibold px-2 py-1 rounded-full ' +
            (available ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100');
        badge.textContent = available ? LABEL_AVAILABLE : LABEL_UNAVAILABLE;
        nameRow.appendChild(badge);

        card.appendChild(nameRow);

        var hasMeta = isPremium || (price !== null && price !== undefined && price !== '');
        if (hasMeta) {
            var metaRow = document.createElement('div');
            metaRow.className = 'mt-2 flex items-center gap-2 flex-wrap text-sm text-white/85';

            if (isPremium) {
                var premiumBadge = document.createElement('span');
                premiumBadge.className = 'text-xs font-semibold px-2 py-1 rounded-full bg-yellow-400/20 text-yellow-100';
                premiumBadge.textContent = LABEL_PREMIUM;
                metaRow.appendChild(premiumBadge);
            }

            if (price !== null && price !== undefined && price !== '') {
                var priceEl = document.createElement('span');
                priceEl.textContent = String(price) + (currency ? (' ' + String(currency)) : '');
                metaRow.appendChild(priceEl);
            }

            card.appendChild(metaRow);
        }

        if (available && domain) {
            var bookLink = document.createElement('a');
            var bookUrl = new URL(BOOK_URL, window.location.origin);
            bookUrl.searchParams.set('domain', domain);
            bookLink.href = bookUrl.toString();
            bookLink.className = 'mt-3 inline-flex items-center justify-center w-full bg-red-brand text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-opacity-90 transition';
            bookLink.textContent = LABEL_BOOK_NOW;
            bookLink.setAttribute('aria-label', LABEL_BOOK_NOW_ARIA_TPL.replace(':domain', domain));
            card.appendChild(bookLink);
        }

        return card;
    }

    function renderResults(results) {
        clearResults();
        if (!Array.isArray(results) || !results.length) {
            setStatus(MSG_NO_RESULTS, 'info');
            return;
        }
        results.forEach(function (item) {
            resultsEl.appendChild(buildResultCard(item));
        });
    }

    async function doSearch(rawValue) {
        var value = String(rawValue || '').trim();
        if (value === '') {
            setStatus(MSG_EMPTY, 'error');
            clearResults();
            return;
        }

        if (controller) {
            controller.abort();
        }
        controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;

        clearResults();
        setStatus(MSG_LOADING, 'loading');
        setLoading(true);

        try {
            var url = new URL(CHECK_URL, window.location.origin);
            url.searchParams.set('q', value);
            url.searchParams.set('tlds', DEFAULT_TLDS);

            var fetchOptions = { headers: { 'Accept': 'application/json' } };
            if (controller) fetchOptions.signal = controller.signal;

            var res = await fetch(url.toString(), fetchOptions);
            var text = await res.text();
            var data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            if (!data || typeof data !== 'object') {
                setStatus(MSG_UNEXPECTED, 'error');
                clearResults();
                return;
            }

            if (!data.ok) {
                setStatus(data.message || MSG_UNEXPECTED, 'error');
                clearResults();
                return;
            }

            setStatus('', 'info');
            renderResults(data.results);
        } catch (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            setStatus(MSG_NETWORK_ERROR, 'error');
            clearResults();
        } finally {
            setLoading(false);
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (inFlight) return;
        doSearch(input.value);
    });
})();
</script>
</section>
