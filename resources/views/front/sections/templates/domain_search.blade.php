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

    // معرّف فريد لكل ظهور للسكشن (وليس $section_id، الذي قد يتكرر إن لم يُخصَّص من Builder) —
    // يُستخدم فقط لربط <label for> بالحقل (a11y)، وليس كمعتمد من JS لتحديد العناصر.
    $inputId = 'domain-search-input-' . substr(md5(uniqid((string) $section_id, true)), 0, 10);
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
            <label for="{{ $inputId }}" class="sr-only">
                {{ t('site.Domain_Search_Input_Label', 'ابحث عن اسم النطاق') }}
            </label>
            <input type="text" id="{{ $inputId }}" name="domain" data-domain-search-input
                placeholder="{{ $placeholder }}"
                autocomplete="off" spellcheck="false" inputmode="text"
                class="flex-1 bg-white rounded-xl px-6 py-4 text-purple-brand text-xl outline-none text-start focus-visible:ring-4 focus-visible:ring-white/60">
            @if ($button_text)
                <button type="submit" data-domain-search-button
                    class="bg-red-brand text-white px-12 py-4 rounded-xl font-bold text-xl hover:bg-opacity-90 transition disabled:opacity-60 disabled:cursor-not-allowed focus-visible:ring-4 focus-visible:ring-white/60">
                    {{ $button_text }}
                </button>
            @endif
        </form>

        <div class="max-w-3xl mx-auto mt-6 text-start" data-domain-search-live aria-live="polite">
            <p class="text-sm hidden" role="status" data-domain-search-status></p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 items-stretch" data-domain-search-results
                aria-busy="false"></div>
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
    var CART_STORE_URL = @json(route('cart.store'));
    var CHECKOUT_CART_URL = @json(route('checkout.cart'));
    var CSRF_TOKEN = @json(csrf_token());
    var DEFAULT_TLDS = @json($default_tlds);
    var PAGE_LANG = @json(app()->getLocale());

    var MSG_EMPTY = @json(t('site.Domain_Search_Empty_Input', 'يرجى إدخال اسم دومين.'));
    var MSG_LOADING = @json(t('site.Domain_Search_Loading', 'جارٍ البحث...'));
    var MSG_LOADING_RESULTS = @json(t('site.Domain_Search_Loading_Results', 'جارٍ البحث عن أفضل النطاقات المتاحة...'));
    var MSG_NETWORK_ERROR = @json(t('site.Domain_Search_Network_Error', 'تعذّر الاتصال بالخادم. حاول مرة أخرى.'));
    var MSG_UNEXPECTED = @json(t('site.Domain_Search_Unexpected_Response', 'استجابة غير متوقعة من الخادم.'));
    var MSG_NO_RESULTS = @json(t('site.Domain_Search_No_Results', 'لا توجد نتائج.'));
    var LABEL_AVAILABLE = @json(t('site.Domain_Search_Available', 'متاح'));
    var LABEL_UNAVAILABLE = @json(t('site.Domain_Search_Unavailable', 'غير متاح'));
    var LABEL_UNKNOWN = @json(t('site.Domain_Search_Unknown', 'تعذّر التحقق الآن'));
    var MSG_UNKNOWN_DETAIL = @json(t('site.Domain_Search_Unknown_Detail', 'تعذّر التحقق من توفر هذا النطاق حالياً.'));
    var LABEL_RETRY = @json(t('site.Domain_Search_Retry', 'إعادة المحاولة'));
    var LABEL_PREMIUM = @json(t('site.Domain_Search_Premium', 'بريميوم'));
    var LABEL_PER_YEAR = @json(t('site.Domain_Search_Per_Year', '/ سنة'));
    var LABEL_BOOK_NOW = @json(t('site.Domain_Search_Book_Now', 'احجز الآن'));
    var LABEL_BOOK_NOW_ARIA_TPL = @json(t('site.Domain_Search_Book_Now_Aria', 'احجز الدومين :domain'));
    var LABEL_RETRY_ARIA_TPL = @json(t('site.Domain_Search_Retry_Aria', 'إعادة التحقق من :domain'));
    var MSG_BOOK_ADDING = @json(t('site.Domain_Search_Booking_Adding', 'جارٍ الإضافة...'));
    var MSG_BOOK_ADDED = @json(t('site.Domain_Search_Booking_Added', 'تمت الإضافة، جارٍ التحويل للسلة...'));
    var MSG_BOOK_ERROR = @json(t('site.Domain_Search_Booking_Error', 'تعذّر إضافة الدومين للسلة. حاول مرة أخرى.'));

    var originalButtonText = button ? button.textContent : '';
    var inFlight = false;
    var controller = null;
    var requestSeq = 0;
    var lastQueryValue = '';

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

    function setLoading(loading, seq) {
        // Guard against a stale (already-aborted) request's `finally` block
        // flipping the button back to its idle state after a newer search
        // has already taken over.
        if (typeof seq === 'number' && seq !== requestSeq) {
            return;
        }
        inFlight = loading;
        resultsEl.setAttribute('aria-busy', loading ? 'true' : 'false');
        if (!button) return;
        button.disabled = loading;
        button.classList.toggle('opacity-60', loading);
        button.classList.toggle('cursor-not-allowed', loading);
        button.textContent = loading ? MSG_LOADING : originalButtonText;
    }

    function formatPrice(price, currency) {
        var amount = Number(price);
        if (isNaN(amount)) return null;
        var code = currency ? String(currency) : null;

        if (code && typeof Intl !== 'undefined' && Intl.NumberFormat) {
            try {
                return new Intl.NumberFormat(PAGE_LANG || undefined, {
                    style: 'currency',
                    currency: code
                }).format(amount);
            } catch (e) {
                // Unsupported/unknown currency code from the backend — fall back below.
            }
        }

        return code ? (amount.toFixed(2) + ' ' + code) : amount.toFixed(2);
    }

    function buildLoadingPlaceholder() {
        var wrap = document.createElement('div');
        wrap.className = 'sm:col-span-2 flex flex-col items-center justify-center gap-3 py-8 text-white/90';

        var spinner = document.createElement('span');
        spinner.className = 'h-8 w-8 rounded-full border-2 border-white/30 border-t-white animate-spin';
        spinner.setAttribute('aria-hidden', 'true');
        wrap.appendChild(spinner);

        var text = document.createElement('p');
        text.className = 'text-sm font-medium';
        text.textContent = MSG_LOADING_RESULTS;
        wrap.appendChild(text);

        return wrap;
    }

    function statusBadge(label, tone) {
        var badge = document.createElement('span');
        var toneClasses = {
            available: 'bg-green-500/20 text-green-100',
            unavailable: 'bg-red-500/20 text-red-100',
            unknown: 'bg-yellow-400/20 text-yellow-100'
        };
        badge.className = 'inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full ' +
            (toneClasses[tone] || toneClasses.unknown);
        badge.textContent = label;
        return badge;
    }

    function buildResultCard(item) {
        var domain = String((item && item.domain) || '');

        // العقد الصريح المفضَّل هو status ('available'|'unavailable'|'unknown').
        // إن غاب (توافق خلفي)، يُشتق من available الثلاثي القيم (true|false|null).
        var status = item && item.status;
        if (status !== 'available' && status !== 'unavailable' && status !== 'unknown') {
            if (item && item.available === true) status = 'available';
            else if (item && item.available === false) status = 'unavailable';
            else status = 'unknown';
        }

        var isPremium = !!(item && item.is_premium === true);
        var price = item ? item.price : null;
        var currency = item ? item.currency : null;

        var borderTone = status === 'available'
            ? 'border-green-400/60'
            : (status === 'unavailable' ? 'border-red-300/50' : 'border-yellow-300/50');

        var card = document.createElement('div');
        card.className = 'rounded-2xl p-4 sm:p-5 text-start bg-white/10 border flex flex-col justify-between gap-3 h-full min-w-0 ' + borderTone;

        var top = document.createElement('div');

        var nameRow = document.createElement('div');
        nameRow.className = 'flex items-center justify-between gap-2 flex-wrap';

        var nameEl = document.createElement('span');
        nameEl.className = 'font-bold break-all';
        nameEl.textContent = domain;
        nameRow.appendChild(nameEl);

        var badgeLabel = status === 'available' ? LABEL_AVAILABLE
            : (status === 'unavailable' ? LABEL_UNAVAILABLE : LABEL_UNKNOWN);
        nameRow.appendChild(statusBadge(badgeLabel, status));

        top.appendChild(nameRow);

        if (status === 'unknown') {
            var detail = document.createElement('p');
            detail.className = 'mt-2 text-sm text-white/80';
            detail.textContent = MSG_UNKNOWN_DETAIL;
            top.appendChild(detail);
        }

        if (status === 'available') {
            var hasMeta = isPremium || (price !== null && price !== undefined && price !== '');
            if (hasMeta) {
                var metaRow = document.createElement('div');
                metaRow.className = 'mt-2 flex items-center gap-2 flex-wrap';

                if (isPremium) {
                    metaRow.appendChild(statusBadge(LABEL_PREMIUM, 'unknown'));
                }

                if (price !== null && price !== undefined && price !== '' && !isNaN(Number(price))) {
                    var priceWrap = document.createElement('div');
                    priceWrap.className = 'flex items-baseline gap-1';

                    var priceEl = document.createElement('span');
                    priceEl.className = 'text-lg font-extrabold';
                    var formatted = formatPrice(price, currency);
                    priceEl.textContent = formatted !== null ? formatted : String(price);
                    priceWrap.appendChild(priceEl);

                    var perYearEl = document.createElement('span');
                    perYearEl.className = 'text-xs text-white/70';
                    perYearEl.textContent = LABEL_PER_YEAR;
                    priceWrap.appendChild(perYearEl);

                    metaRow.appendChild(priceWrap);
                }

                top.appendChild(metaRow);
            }
        }

        card.appendChild(top);

        if (status === 'available' && domain) {
            var bookBtn = document.createElement('button');
            bookBtn.type = 'button';
            bookBtn.className = 'inline-flex items-center justify-center w-full bg-red-brand text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-opacity-90 transition disabled:opacity-60 disabled:cursor-not-allowed focus-visible:ring-4 focus-visible:ring-white/60';
            bookBtn.textContent = LABEL_BOOK_NOW;
            bookBtn.setAttribute('aria-label', LABEL_BOOK_NOW_ARIA_TPL.replace(':domain', domain));

            var priceCents = (price !== null && price !== undefined && price !== '' && !isNaN(Number(price)))
                ? Math.round(Number(price) * 100)
                : 0;

            bookBtn.addEventListener('click', function () {
                addDomainToCart(domain, priceCents, bookBtn);
            });

            card.appendChild(bookBtn);
        } else if (status === 'unknown' && domain) {
            var retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'inline-flex items-center justify-center w-full bg-white/15 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-white/25 transition focus-visible:ring-4 focus-visible:ring-white/60';
            retryBtn.textContent = LABEL_RETRY;
            retryBtn.setAttribute('aria-label', LABEL_RETRY_ARIA_TPL.replace(':domain', domain));
            retryBtn.addEventListener('click', function () {
                if (inFlight) return;
                doSearch(lastQueryValue);
            });
            card.appendChild(retryBtn);
        }

        return card;
    }

    async function addDomainToCart(domain, priceCents, btn) {
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = MSG_BOOK_ADDING;

        try {
            var res = await fetch(CART_STORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    items: [{
                        domain: domain,
                        item_option: 'register',
                        price_cents: priceCents,
                        meta: null
                    }]
                })
            });

            var text = await res.text();
            var data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            if (!res.ok || !data || !data.ok) {
                setStatus((data && data.message) || MSG_BOOK_ERROR, 'error');
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            btn.textContent = MSG_BOOK_ADDED;
            window.location.href = CHECKOUT_CART_URL;
        } catch (err) {
            setStatus(MSG_BOOK_ERROR, 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        }
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
        lastQueryValue = value;

        if (controller) {
            controller.abort();
        }
        controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;

        var seq = ++requestSeq;

        clearResults();
        resultsEl.appendChild(buildLoadingPlaceholder());
        setStatus(MSG_LOADING, 'loading');
        setLoading(true, seq);

        try {
            var url = new URL(CHECK_URL, window.location.origin);
            url.searchParams.set('q', value);
            url.searchParams.set('tlds', DEFAULT_TLDS);

            var fetchOptions = { headers: { 'Accept': 'application/json' } };
            if (controller) fetchOptions.signal = controller.signal;

            var res = await fetch(url.toString(), fetchOptions);

            if (seq !== requestSeq) {
                // A newer search has already started; discard this response.
                return;
            }

            var text = await res.text();
            var data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            if (seq !== requestSeq) {
                return;
            }

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
            if (seq === requestSeq) {
                setStatus(MSG_NETWORK_ERROR, 'error');
                clearResults();
            }
        } finally {
            setLoading(false, seq);
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
