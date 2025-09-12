{{-- resources/views/components/template/sections/search-domain.blade.php --}}
@props([
    'fallbackPrices' => [],
    'defaultTlds' => ['com', 'net', 'org', 'info', 'shop', 'xyz', 'rocks', 'news', 'live', 'ninja', 'watches'],
    'currency' => 'USD',
    // optional: current template id when this component is used inside a template checkout page
    'template_id' => null,
])

<section id="search-domain" class="bg-primary py-16 text-white scroll-mt-24">
    <div class="container mx-auto max-w-5xl px-4 text-center">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-4">ابحث عن اسم دومين</h1>
        <p class="text-sm md:text-base text-white/80 mb-8">
            اكتب اسمك فقط مثل <span class="font-mono">palgoals</span> وسنعرض لك .com والبدائل والاقتراحات فورًا
        </p>

        <!-- أزرار ترويجية -->
        <div class="flex flex-col sm:flex-row justify-center items-center gap-3 mb-8">
            <a href="#search-domain"
                class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary/30 text-white font-semibold px-6 py-3 rounded-full shadow-md transition">
                ✨ مولد أسماء الدومينات الذكي
            </a>
            <a href="#search-domain"
                class="inline-flex items-center gap-2 bg-white text-[#4C1D95] hover:bg-gray-100 font-semibold px-6 py-3 rounded-full shadow-md transition">
                البحث عن دومين
            </a>
        </div>

        <!-- شريط البحث -->
        <div class="relative max-w-xl mx-auto">
            <label for="domainInput" class="sr-only">ابحث عن دومين</label>
            <input id="domainInput" type="text" dir="ltr" placeholder="palgoals أو palgoals.com, palgoals.net"
                class="w-full rounded-2xl bg-white/10 text-white placeholder-white/60 border border-white/15
                    focus:border-white/30 focus:outline-none focus:ring-4 focus:ring-white/10
                    ps-12 pe-4 py-3 transition text-start placeholder:text-start"
                aria-describedby="status" />
            <button id="searchButton"
                class="absolute left-2 top-1/2 -translate-y-1/2 px-4 py-2 rounded-xl bg-secondary/90 hover:bg-secondary text-white font-medium transition">
                بحث
            </button>
        </div>

        <div class="mt-3 text-xs text-white/70">
            تلميح: اكتب SLD فقط (بدون .com) وسنقترح الامتدادات الشائعة تلقائيًا.
        </div>

        <div id="status" class="mt-4 text-sm text-white/90" role="status" aria-live="polite"></div>

        {{-- النتائج --}}
        <div id="primaryResult" class="mt-6"></div>

        <div id="altHeader" class="mt-8 text-start hidden">
            <h3 class="text-lg font-semibold">بدائل TLD</h3>
        </div>
        <div id="altResults" class="mt-3"></div>
        <div id="moreWrap" class="mt-6 hidden">
            <button id="loadMoreBtn"
                class="px-5 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white hover:bg-white/20 transition">
                تحميل المزيد
            </button>
        </div>

        {{-- الاقتراحات الذكية (مخفي افتراضيًا ويظهر بعد البحث) --}}
        <div id="suggestionsSection" class="mt-12 text-start hidden">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">اقتراحات أسماء</h3>
                <div class="flex gap-3 text-xs">
                    <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optCommon"
                            class="accent-white" checked> شائعة</label>
                    <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optTech"
                            class="accent-white" checked> تقنية</label>
                    <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optBiz"
                            class="accent-white" checked> أعمال</label>
                    <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optShort"
                            class="accent-white" checked> تقصير</label>
                </div>
            </div>

            <div class="mt-3 flex gap-2">
                <button id="genSuggestBtn"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white hover:bg-white/20 transition">
                    توليد اقتراحات
                </button>
                <button id="checkSuggestBtn"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-secondary text-white hover:bg-secondary/90 transition">
                    فحص الاقتراحات (.com)
                </button>
            </div>

            <div id="suggestionsWrap" class="mt-4"></div>
        </div>
    </div>
</section>

{{-- resources/views/components/template/sections/search-domain.blade.php --}}
@props([
    'fallbackPrices' => [],
    'defaultTlds' => ['com', 'net', 'org', 'info', 'shop', 'xyz', 'rocks', 'news', 'live', 'ninja', 'watches'],
    'currency' => 'USD',
    // optional: current template id when this component is used inside a template checkout page
    'template_id' => null,
])

<section id="search-domain" class="bg-primary py-16 text-white scroll-mt-24">
  <div class="container mx-auto max-w-5xl px-4 text-center">
    <h1 class="text-2xl md:text-4xl font-extrabold mb-4">ابحث عن اسم دومين</h1>
    <p class="text-sm md:text-base text-white/80 mb-8">
      اكتب اسمك فقط مثل <span class="font-mono">palgoals</span> وسنعرض لك .com والبدائل والاقتراحات فورًا
    </p>

    <!-- أزرار ترويجية -->
    <div class="flex flex-col sm:flex-row justify-center items-center gap-3 mb-8">
      <a href="#search-domain"
         class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary/30 text-white font-semibold px-6 py-3 rounded-full shadow-md transition">
        ✨ مولد أسماء الدومينات الذكي
      </a>
      <a href="#search-domain"
         class="inline-flex items-center gap-2 bg-white text-[#4C1D95] hover:bg-gray-100 font-semibold px-6 py-3 rounded-full shadow-md transition">
        البحث عن دومين
      </a>
    </div>

    <!-- شريط البحث -->
    <div class="relative max-w-xl mx-auto">
      <label for="domainInput" class="sr-only">ابحث عن دومين</label>
      <input id="domainInput" type="text" dir="ltr" placeholder="palgoals أو palgoals.com, palgoals.net"
             class="w-full rounded-2xl bg-white/10 text-white placeholder-white/60 border border-white/15
                    focus:border-white/30 focus:outline-none focus:ring-4 focus:ring-white/10
                    ps-12 pe-4 py-3 transition text-start placeholder:text-start"
             aria-describedby="status" />
      <button id="searchButton"
              class="absolute left-2 top-1/2 -translate-y-1/2 px-4 py-2 rounded-xl bg-secondary/90 hover:bg-secondary text-white font-medium transition">
        بحث
      </button>
    </div>

    <div class="mt-3 text-xs text-white/70">
      تلميح: اكتب SLD فقط (بدون .com) وسنقترح الامتدادات الشائعة تلقائيًا.
    </div>

    <div id="status" class="mt-4 text-sm text-white/90" role="status" aria-live="polite"></div>

    {{-- النتائج --}}
    <div id="primaryResult" class="mt-6"></div>

    <div id="altHeader" class="mt-8 text-start hidden">
      <h3 class="text-lg font-semibold">بدائل TLD</h3>
    </div>
    <div id="altResults" class="mt-3"></div>
    <div id="moreWrap" class="mt-6 hidden">
      <button id="loadMoreBtn"
              class="px-5 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white hover:bg-white/20 transition">
        تحميل المزيد
      </button>
    </div>

    {{-- الاقتراحات الذكية (مخفي افتراضيًا ويظهر بعد البحث) --}}
    <div id="suggestionsSection" class="mt-12 text-start hidden">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">اقتراحات أسماء</h3>
        <div class="flex gap-3 text-xs">
          <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optCommon" class="accent-white" checked> شائعة</label>
          <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optTech" class="accent-white" checked> تقنية</label>
          <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optBiz" class="accent-white" checked> أعمال</label>
          <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" id="optShort" class="accent-white" checked> تقصير</label>
        </div>
      </div>

      <div class="mt-3 flex gap-2">
        <button id="genSuggestBtn"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white hover:bg-white/20 transition">
          توليد اقتراحات
        </button>
        <button id="checkSuggestBtn"
                class="px-4 py-2 rounded-xl text-sm font-semibold bg-secondary text-white hover:bg-secondary/90 transition">
          فحص الاقتراحات (.com)
        </button>
      </div>

      <div id="suggestionsWrap" class="mt-4"></div>
    </div>
  </div>
</section>

<script>
/* ===== بيانات من السيرفر ===== */
const DEFAULT_TLDS  = @json(array_values(array_unique(array_map(fn($t) => strtolower(ltrim($t, '.')), $defaultTlds))));
const FALLBACK_PRICES = @json($fallbackPrices);
const DEFAULT_CCY   = @json($currency);
const CSRF_TOKEN    = @json(csrf_token());

/* ===== إعدادات العرض ===== */
const POPULAR_TLDS = ['com', 'net', 'org'];            // لترتيب التفضيلات
const PRIMARY_TLDS = ['com', 'net', 'org', 'info'];    // شريط النتائج الأساسية
const NEW_TLDS     = new Set(['news','rocks','live','watches','ninja']); // وسم NEW

// إذا تم تضمين هذا المكون داخل صفحة قالب، سيمرر الـ template_id هنا
const CURRENT_TEMPLATE_ID = @json($template_id ?? null);

// السلة الموحّدة
const UNIFIED_CART_KEY = 'palgoals_cart';
const LEGACY_CART_KEY  = 'palgoals_cart_domains';

function readUnifiedCart() {
  let items = [];
  try { items = JSON.parse(localStorage.getItem(UNIFIED_CART_KEY) || '[]'); } catch { items = []; }
  if (!Array.isArray(items)) items = [];

  // دمج مرّة واحدة من المفتاح القديم لو موجود ولم تُنشأ السلة الموحّدة بعد
  try {
    const legacyRaw = localStorage.getItem(LEGACY_CART_KEY);
    if (legacyRaw && !localStorage.getItem(UNIFIED_CART_KEY)) {
      const legacy = JSON.parse(legacyRaw);
      if (Array.isArray(legacy)) {
        const mapped = legacy.map(it => ({
          kind: 'domain',
          domain: String(it.domain || '').toLowerCase(),
          item_option: it.item_option ?? it.option ?? 'register',
          price_cents: Number(it.price_cents) || 0,
          meta: it.meta ?? null,
        }));
        items = items.concat(mapped);
        localStorage.setItem(UNIFIED_CART_KEY, JSON.stringify(items));
      }
    }
  } catch {}
  return items;
}

function writeUnifiedCart(items) {
  localStorage.setItem(UNIFIED_CART_KEY, JSON.stringify(items || []));
}

function normalizeDomain(raw) {
  if (!raw) return null;
  try {
    let host = (new URL(raw.includes('://') ? raw : 'http://' + raw)).hostname;
    host = host.toLowerCase().replace(/^www\./,'').replace(/\.$/,'');
    return host || null;
  } catch {
    return String(raw).toLowerCase().replace(/^www\./,'').replace(/\.$/,'') || null;
  }
}

// منع التكرار/التحديث إن كان الدومين موجودًا
function upsertDomain(cartItems, {domain, item_option, price_cents, meta}) {
  const d = normalizeDomain(domain);
  if (!d) return cartItems;

  let exists = false;
  const next = cartItems.map(it => {
    if (it?.kind === 'domain' && normalizeDomain(it.domain) === d) {
      exists = true;
      return {
        ...it,
        item_option: item_option || it.item_option || 'register',
        price_cents: Number(price_cents ?? it.price_cents) || 0,
        meta: meta ?? it.meta ?? null,
      };
    }
    return it;
  });

  if (!exists) {
    next.push({
      kind: 'domain',
      domain: d,
      item_option: item_option || 'register',
      price_cents: Number(price_cents) || 0,
      meta: meta ?? null,
    });
  }
  return next;
}

/* ===== عناصر DOM ===== */
const elInput   = document.getElementById('domainInput');
const elBtn     = document.getElementById('searchButton');
const elStatus  = document.getElementById('status');
const elPrimary = document.getElementById('primaryResult');
const elAltH    = document.getElementById('altHeader');
const elAlt     = document.getElementById('altResults');
const elMoreWrap= document.getElementById('moreWrap');
const elMoreBtn = document.getElementById('loadMoreBtn');
const elSugSection = document.getElementById('suggestionsSection');
const elSugWrap    = document.getElementById('suggestionsWrap');

/* ===== Helpers ===== */
const isEmpty = v => v == null || (typeof v === 'string' && v.trim() === '');
const fmtMoney = n => (n == null || isNaN(n)) ? '—' : (new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(n)));
const getTld = d => d.split('.').pop().toLowerCase();

const tagNew = tld => NEW_TLDS.has(tld)
  ? `<span class="absolute top-0 end-0 bg-green-500 text-xs text-white px-2 py-1 rounded-bl-lg font-bold">NEW</span>`
  : '';

function priceLine(result, tld) {
  let price = null, ccy = DEFAULT_CCY || 'USD';
  if (!isEmpty(result?.price)) {
    price = Number(result.price);
    ccy   = result.currency || ccy;
  } else if (FALLBACK_PRICES && tld in FALLBACK_PRICES) {
    price = Number(FALLBACK_PRICES[tld]);
  }
  if (price == null) return result?.is_premium ? 'سعر بريميوم — تابع الشراء' : '—';
  return `from: <span class="font-medium text-white">$${fmtMoney(price)} ${ccy}</span>`;
}

function card(domain, result = {}) {
  const tld = getTld(domain);
  const available = !!result.available;
  return `
    <div class="relative rounded-xl p-4 text-center border ${available ? 'border-green-500/70 bg-green-500/10' : 'border-red-400/70 bg-red-500/10'} shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition">
      ${tagNew(tld)}
      <div class="text-lg font-bold text-white mb-1">${domain}</div>
      <div class="text-sm text-white/70 mb-3">${priceLine(result, tld)}</div>
      ${
        available
        ? `<div class="flex gap-2">
             <button class="flex-1 bg-indigo-900 text-white text-sm font-semibold py-2 rounded-lg hover:bg-indigo-700 transition" onclick="addToCart('${domain}')">أضف للسلة</button>
             <button class="px-2 rounded-lg bg-white/10 hover:bg-white/20 text-white" onclick="copyDomain('${domain}')" title="نسخ">
               <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
             </button>
           </div>`
        : `<div class="flex flex-col gap-2">
             <span class="inline-flex items-center justify-center w-full bg-gray-500/40 text-white/80 text-sm font-semibold py-2 rounded-lg cursor-not-allowed">محجوز</span>
             <button class="w-full bg-white/10 text-white text-xs py-1.5 rounded-lg hover:bg-white/20 transition" onclick="checkMoreTlds('${domain.split('.').slice(0,-1).join('.')}')">جرّب امتدادات أخرى</button>
           </div>`
      }
    </div>
  `;
}

function orderTlds(all) {
  const set = new Set(all);
  const ordered = [];
  for (const t of POPULAR_TLDS) if (set.delete(t)) ordered.push(t);
  const newOnes = Array.from(set).filter(t => NEW_TLDS.has(t)).sort();
  newOnes.forEach(t => { set.delete(t); ordered.push(t); });
  ordered.push(...Array.from(set).sort());
  return ordered;
}

function buildDomains(raw, tlds) {
  raw = raw.trim();
  if (raw.includes(',')) return raw.split(',').map(s => s.trim()).filter(Boolean);
  if (raw.includes('.')) {
    const domain = raw.toLowerCase();
    const sld = domain.split('.')[0] || domain;
    const tld = domain.split('.').pop();
    const others = tlds.filter(x => x !== tld);
    return Array.from(new Set([domain, ...others.map(x => `${sld}.${x}`)]));
  }
  const sld = raw.toLowerCase();
  return [`${sld}.com`, ...tlds.filter(x => x !== 'com').map(x => `${sld}.${x}`)];
}

/* ===== عرض البدائل ===== */
const ALT_BATCH = 8;
let _allResults = [];
let _altPool = [];
let _shownAlt = 0;

function skeleton(rows = 8) {
  return `
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-4">
      ${Array.from({ length: rows }).map(() => `<div class="rounded-xl p-4 text-center bg-white/10 animate-pulse h-28"></div>`).join('')}
    </div>
  `;
}

function renderPrimaryAndAlts() {
  if (!_allResults.length) {
    elPrimary.innerHTML = '';
    elAlt.innerHTML = '';
    elAltH.classList.add('hidden');
    elMoreWrap.classList.add('hidden');
    return;
  }

  // اختر حتى 4 عناصر أساسية بحسب PRIMARY_TLDS
  const byTld = new Map();
  _allResults.forEach(x => {
    const t = getTld(x.domain);
    if (!byTld.has(t)) byTld.set(t, x);
  });

  const prim = [];
  for (const t of PRIMARY_TLDS) if (byTld.has(t)) prim.push(byTld.get(t));
  for (const x of _allResults) {
    if (prim.length >= 4) break;
    if (!prim.includes(x)) prim.push(x);
  }
  const primaryStrip = prim.slice(0, 4);

  // بقية النتائج للبدائل
  const primSet = new Set(primaryStrip.map(x => x.domain.toLowerCase()));
  _altPool = _allResults.filter(x => !primSet.has(x.domain.toLowerCase()));

  // الشريط الأساسي
  elPrimary.innerHTML = `
    <div class="text-start">
      <h3 class="text-lg font-semibold mb-3">نتيجتك الأساسية</h3>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      ${primaryStrip.map(x => card(x.domain, x.result)).join('')}
    </div>
    <div class="mt-6 h-px bg-white/10"></div>
  `;

  // البدائل
  _shownAlt = 0;
  elAlt.innerHTML = '';
  loadMoreAlts(ALT_BATCH);

  elAltH.classList.toggle('hidden', _altPool.length === 0);
  elMoreWrap.classList.toggle('hidden', _shownAlt >= _altPool.length);
}

function loadMoreAlts(batch = ALT_BATCH) {
  const slice = _altPool.slice(_shownAlt, _shownAlt + batch);
  if (slice.length) {
    elAlt.insertAdjacentHTML('beforeend', `
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        ${slice.map(x => card(x.domain, x.result)).join('')}
      </div>
    `);
    _shownAlt += slice.length;
  }
  elMoreWrap.classList.toggle('hidden', _shownAlt >= _altPool.length);
}
elMoreBtn?.addEventListener('click', () => loadMoreAlts(ALT_BATCH));

const routeCheck = (csv) => `{{ route('domains.check') }}?domains=${encodeURIComponent(csv)}&t=${Date.now()}`;
const routeCheckSingle = (domain) => `{{ route('domains.check') }}?domains=${encodeURIComponent(domain)}&t=${Date.now()}`;

/* ===== البحث ===== */
let inFlight = false, controller;

async function doSearch() {
  const raw = elInput.value.trim();
  if (!raw) {
    elStatus.textContent = 'الرجاء إدخال اسم الدومين.';
    elPrimary.innerHTML = '';
    elAlt.innerHTML = '';
    elAltH.classList.add('hidden');
    elMoreWrap.classList.add('hidden');
    elSugSection?.classList.add('hidden');
    elSugWrap.innerHTML = '';
    return;
  }

  controller?.abort();
  controller = new AbortController();

  const orderedTlds = orderTlds(DEFAULT_TLDS);
  const domains = buildDomains(raw, orderedTlds);

  elStatus.textContent = 'جارٍ الفحص…';
  elPrimary.innerHTML = skeleton(1);
  elAlt.innerHTML = skeleton(ALT_BATCH);
  elAltH.classList.remove('hidden');
  elMoreWrap.classList.add('hidden');

  try {
    inFlight = true;
    const res = await fetch(routeCheck(domains.join(',')), { headers: { 'Accept': 'application/json' }, signal: controller.signal });
    const text = await res.text();
    let data = null;
    try { data = JSON.parse(text); } catch {}

    if (!data || !data.ok) {
      elStatus.textContent = `فشل: ${(data && data.message) || 'تعذّر الفحص.'}`;
      elPrimary.innerHTML = '';
      elAlt.innerHTML = '';
      elAltH.classList.add('hidden');
      elMoreWrap.classList.add('hidden');
      elSugSection?.classList.add('hidden');
      elSugWrap.innerHTML = '';
      return;
    }

    // خريطة نتائج
    const m = new Map();
    (data.results || []).forEach(r => { if (r && r.domain) m.set(r.domain.toLowerCase(), r); });

    // طبّع وترتّب حسب TLDs
    const tldOrder = orderTlds(DEFAULT_TLDS);
    const idxMap   = new Map(tldOrder.map((t, i) => [t, i]));
    const norm = domains.map(d => ({
      domain: d,
      result: m.get(d.toLowerCase()) || { domain: d, available: false }
    }));
    norm.sort((a,b) => {
      const ia = idxMap.has(getTld(a.domain)) ? idxMap.get(getTld(a.domain)) : 999;
      const ib = idxMap.has(getTld(b.domain)) ? idxMap.get(getTld(b.domain)) : 999;
      return ia - ib || a.domain.localeCompare(b.domain);
    });
    _allResults = norm;

    elStatus.textContent = `تم • الزمن: ${data.duration_ms || '?'} ms • ${new Date(data.fetched_at).toLocaleString()}`;
    renderPrimaryAndAlts();

    // الاقتراحات
    const first = raw.split(',')[0].trim();
    const base  = sanitizeSLD(first.includes('.') ? first.split('.')[0] : first);
    if (base && base.length >= 3) {
      elSugSection?.classList.remove('hidden');
      generateAndRenderSuggestions(base); // فحص .com تلقائي لأول 12
    } else {
      elSugSection?.classList.add('hidden');
      elSugWrap.innerHTML = '';
    }
  } catch (e) {
    if (e.name !== 'AbortError') {
      console.error(e);
      elStatus.textContent = 'حدث خطأ في الاتصال.';
      elPrimary.innerHTML = '';
      elAlt.innerHTML = '';
      elAltH.classList.add('hidden');
      elMoreWrap.classList.add('hidden');
      elSugSection?.classList.add('hidden');
      elSugWrap.innerHTML = '';
    }
  } finally {
    inFlight = false;
  }
}

/* ===== اقتراحات أسماء (Brandable + Scoring) ===== */
const LIB = {
  common: { pref: ['get','go','my','try','join','we','hey','the'], suff: ['ly','hub','plus','labs','studio','space','world','online','wise','base','nest'] },
  tech:   { pref: ['app','dev','tech','cloud','ai','data'],         suff: ['io','tech','dev','cloud','ai','data','systems','digital','stack','soft'] },
  biz:    { pref: ['pro','pay','shop'],                              suff: ['store','shop','media','group','agency','solutions','works','mart'] }
};
const RESERVED = new Set(['test','admin','root','null','undefined']);
const VOWELS   = /[aeiouy]/g;

function sanitizeSLD(s) {
  return (s || '').toLowerCase().replace(/[^a-z0-9\-]+/g,'').replace(/^\-+|\-+$/g,'').slice(0, 63);
}

function shorten(base) {
  base = sanitizeSLD(base);
  if (base.length <= 4) return [base];
  const noVowels = base[0] + base.slice(1).replace(/[aeiou]/g,'');
  const chunks   = base.split(/[-_]/).filter(Boolean);
  const initials = chunks.map(c => c[0]).join('');
  const uniq = new Set([base, noVowels, initials]);
  return Array.from(uniq).filter(x => x && x.length >= 3);
}

function lev(a,b) {
  a = a.toLowerCase(); b = b.toLowerCase();
  const m = a.length, n = b.length;
  const d = Array.from({length: m+1}, (_,i) => [i].concat(Array(n).fill(0)));
  for (let j=0;j<=n;j++) d[0][j] = j;
  for (let i=1;i<=m;i++) {
    for (let j=1;j<=n;j++) {
      const cost = a[i-1] === b[j-1] ? 0 : 1;
      d[i][j] = Math.min(d[i-1][j]+1, d[i][j-1]+1, d[i-1][j-1]+cost);
    }
  }
  return d[m][n];
}

function scoreCandidate(name, base, weights) {
  let s = 0, len = name.length;
  if (len>=5 && len<=12) s += 30; else if (len>=3 && len<=15) s += 10; else s -= 20;
  const vowels = (name.match(VOWELS) || []).length, ratio = vowels / Math.max(1,len);
  if (ratio>=0.25 && ratio<=0.6) s += 15; else s -= 10;
  if (/(.)\1{2,}/.test(name)) s -= 25;
  if (/--/.test(name)) s -= 20;
  if (/^-|-$/.test(name)) s -= 10;
  const distance = lev(name.replace(/-/g,''), base.replace(/-/g,''));
  if (distance === 0) s -= 30; else if (distance<=3) s += 20; else if (distance<=6) s += 10;
  if (/[aeiouy]$/.test(name)) s += 5;
  if (weights.usedTech)   s += 6;
  if (weights.usedBiz)    s += 6;
  if (weights.usedCommon) s += 4;
  if (RESERVED.has(name)) s -= 50;
  return s;
}

function generateCandidates(base, opts) {
  base = sanitizeSLD(base);
  const variants = new Set();
  const shorties = shorten(base);
  const push = v => { v = sanitizeSLD(v); if (v && v.length>=3 && v.length<=20) variants.add(v); };

  push(base);
  shorties.forEach(push);

  const useCommon = !!opts.common, useTech = !!opts.tech, useBiz = !!opts.biz, useShort = !!opts.short;
  const addAffixes = lib => {
    if (!lib) return;
    lib.pref.forEach(p => push(p + base.charAt(0).toUpperCase() + base.slice(1)));
    lib.suff.forEach(s => {
      const joiner = /[bcdfghjklmnpqrstvwxyz]$/.test(base) && /^[bcdfghjklmnpqrstvwxyz]/.test(s) ? 'a' : '';
      push(base + joiner + s);
    });
    if (useShort) shorties.forEach(sh => lib.suff.slice(0,3).forEach(s => push(sh + s)));
  };

  useCommon && addAffixes(LIB.common);
  useTech   && addAffixes(LIB.tech);
  useBiz    && addAffixes(LIB.biz);
  ['ly','io','ify','ster','verse','stack','flow','grid','kit'].forEach(s => push(base + s));

  return Array.from(variants).filter(v => !/^\d+$/.test(v));
}

function generateSuggestions(base, opts) {
  const cand = generateCandidates(base, opts);
  const weights = { usedCommon: !!opts.common, usedTech: !!opts.tech, usedBiz: !!opts.biz };
  const scored = cand.map(name => ({ name, score: scoreCandidate(name, base, weights) }));
  scored.sort((a,b) => b.score - a.score || a.name.localeCompare(b.name));
  return scored.slice(0, 12).map(x => x.name);
}

function renderSuggestions(sld, names) {
  if (!names.length) {
    elSugWrap.innerHTML = '<div class="text-white/80 text-sm">لا اقتراحات مناسبة.</div>';
    return;
  }
  elSugWrap.innerHTML = `
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      ${names.map(n => `
        <div class="bg-white/10 rounded-xl p-3 text-center sug-item ring-0 hover:ring-2 hover:ring-white/30 transition" data-sld="${n}">
          <div class="font-semibold text-white">${n}.com</div>
          <div class="sug-status text-xs text-white/70 mt-1">جارٍ التحضير للفحص…</div>
          <div class="mt-2 flex gap-2">
            <button class="flex-1 bg-indigo-900 text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-indigo-700 transition" onclick="checkOneSuggestion('${n}')">فحص .com</button>
            <button class="flex-1 bg-white/10 text-white text-xs py-1.5 rounded-lg hover:bg-white/20 transition" onclick="checkMoreTlds('${n}')">امتدادات أخرى</button>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

function annotateSuggestion(sld, available, price, currency) {
  const card = elSugWrap.querySelector(`.sug-item[data-sld="${sld}"]`);
  if (!card) return;
  const st = card.querySelector('.sug-status');
  if (available) {
    const ccy = currency || DEFAULT_CCY || 'USD';
    const priceTxt = (price != null) ? ` — $${fmtMoney(price)} ${ccy}` : '';
    st.innerHTML = `✅ متاح${priceTxt}`;
    card.classList.add('ring-2', 'ring-green-400');
  } else {
    st.textContent = '❌ محجوز';
    card.classList.add('opacity-80');
  }
}

function generateAndRenderSuggestions(raw) {
  const base = sanitizeSLD(raw);
  if (!base || base.length < 3) { elSugWrap.innerHTML = ''; return; }
  const names = generateSuggestions(base, {
    common: document.getElementById('optCommon')?.checked ?? true,
    tech:   document.getElementById('optTech')?.checked ?? true,
    biz:    document.getElementById('optBiz')?.checked ?? true,
    short:  document.getElementById('optShort')?.checked ?? true,
  });
  renderSuggestions(base, names);
  autoCheckSuggestions(12);
}

window.checkOneSuggestion = async (sld) => {
  const domain = `${sld}.com`;
  try {
    const res = await fetch(routeCheckSingle(domain), { headers: { 'Accept': 'application/json' }});
    const data = await res.json().catch(() => null);
    if (!data || !data.ok) return alert('تعذّر الفحص');
    const r = (data.results || []).find(x => x.domain?.toLowerCase() === domain.toLowerCase());
    if (!r) return alert('تعذّر الفحص');
    if (r.available) addToCart(domain);
    else checkMoreTlds(sld);
  } catch {
    alert('خطأ في الاتصال');
  }
};

async function autoCheckSuggestions(limit = 12) {
  const items = Array.from(elSugWrap.querySelectorAll('.sug-item')).slice(0, limit);
  if (!items.length) return;
  const names   = items.map(x => x.dataset.sld);
  const domains = names.map(n => `${n}.com`);
  try {
    const res = await fetch(routeCheck(domains.join(',')), { headers: { 'Accept': 'application/json' }});
    const data = await res.json().catch(() => null);
    if (!data || !data.ok) return;
    const map = new Map((data.results || []).map(r => [r.domain.toLowerCase(), r]));
    names.forEach(n => {
      const r = map.get(`${n}.com`);
      annotateSuggestion(n, !!r?.available, r?.price ?? null, r?.currency ?? null);
    });
  } catch {}
}

window.checkMoreTlds = async (sld) => {
  const tlds = orderTlds(DEFAULT_TLDS).slice(0, 6);
  const domains = tlds.map(t => `${sld}.${t}`);
  try {
    const res = await fetch(routeCheck(domains.join(',')), { headers: { 'Accept': 'application/json' }});
    const data = await res.json().catch(() => null);
    if (!data || !data.ok) return alert('تعذّر الفحص');
    const avail = (data.results || []).filter(r => r.available && r.domain.startsWith(`${sld}.`)).map(r => r.domain);
    alert(avail.length ? `متاح:\n${avail.join('\n')}` : 'لا توجد امتدادات متاحة ضمن المجموعة المختارة.');
  } catch {
    alert('خطأ في الاتصال');
  }
};

/* ===== الأحداث ===== */
let typingTimer;
elInput.addEventListener('input', () => {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(() => {
    if (elInput.value.trim().length >= 3 && !inFlight) doSearch();
  }, 450);
});
elInput.addEventListener('keydown', e => { if (e.key === 'Enter') elBtn.click(); });
elBtn.addEventListener('click', () => { if (!inFlight) doSearch(); });

document.getElementById('genSuggestBtn')?.addEventListener('click', () => {
  const raw = elInput.value.trim();
  if (!raw) return;
  elSugSection?.classList.remove('hidden');
  const base = (raw.includes(',') ? raw.split(',')[0] : raw).trim();
  generateAndRenderSuggestions(base);
});
document.getElementById('checkSuggestBtn')?.addEventListener('click', () => autoCheckSuggestions(12));

(function autoSearchFromQuery() {
  const u = new URL(window.location.href);
  const q = u.searchParams.get('q');
  if (q) { elInput.value = q; doSearch(); }
})();

/* ===== السلة + نسخ ===== */
// ربط زر "أضف للسلة": يدعم حالتين: داخل صفحة checkout للقالب أو استخدام عام
window.addToCart = function(domain){
  try {
    // داخل checkout الخاص بالقالب: لا نغيّر منطقك الحالي
    if (window.location.pathname.includes('/checkout/client/')) {
      const finalForm = document.getElementById('checkoutForm');
      if (finalForm){
        let inputOption = finalForm.querySelector('input[name="domain_option"]');
        if (!inputOption){
          inputOption = document.createElement('input');
          inputOption.type='hidden';
          inputOption.name='domain_option';
          finalForm.appendChild(inputOption);
        }
        inputOption.value = 'register';

        let inputDomain = finalForm.querySelector('input[name="domain"]');
        if (!inputDomain){
          inputDomain = document.createElement('input');
          inputDomain.type='hidden';
          inputDomain.name='domain';
          finalForm.appendChild(inputDomain);
        }
        inputDomain.value = domain;

        let cents = 0;
        try{
          const tld = '.' + domain.split('.').pop().toLowerCase();
          if (window.priceMap && (tld in window.priceMap)) cents = Number(window.priceMap[tld]);
          else {
            const shortTld = tld.slice(1);
            if (FALLBACK_PRICES && (shortTld in FALLBACK_PRICES)) cents = Math.round(Number(FALLBACK_PRICES[shortTld]) * 100);
          }
        } catch {}

        if (typeof window.setReview === 'function') window.setReview(domain, cents);
        if (typeof window.goto === 'function')      window.goto(1);
        if (typeof window.updateDomainFields === 'function') window.updateDomainFields();
        return;
      }
    }
  } catch (_) {}

  try {
    // لو المكوّن مستخدم داخل صفحة قالب قبل الانتقال للـ checkout
    if (CURRENT_TEMPLATE_ID) {
      const url = `/checkout/client/${encodeURIComponent(CURRENT_TEMPLATE_ID)}?domain=${encodeURIComponent(domain)}&review=1`;
      window.location.href = url;
      return;
    }

    // استخدام عام: خزّن في السلة الموحّدة ثم اذهب لصفحة السلة
    let cents = 0;
    try{
      const tld = '.' + domain.split('.').pop().toLowerCase();
      if (window.priceMap && (tld in window.priceMap)) cents = Number(window.priceMap[tld]);
      else {
        const shortTld = tld.slice(1);
        if (FALLBACK_PRICES && (shortTld in FALLBACK_PRICES)) cents = Math.round(Number(FALLBACK_PRICES[shortTld]) * 100);
      }
    } catch {}

    const cart = readUnifiedCart();
    const updated = upsertDomain(cart, { domain, item_option: 'register', price_cents: cents });
    writeUnifiedCart(updated);

    // ثم الانتقال للسلة
    window.location.href = '/cart';
  } catch (e){
    console.error(e);
    alert('تمت إضافة ' + domain + ' للسلة.');
    window.location.href = '/cart';
  }
};

window.copyDomain = async (domain) => {
  try {
    await navigator.clipboard.writeText(domain);
    elStatus.textContent = `تم نسخ ${domain}`;
  } catch {
    elStatus.textContent = 'تعذّر النسخ.';
  }
};
</script>

