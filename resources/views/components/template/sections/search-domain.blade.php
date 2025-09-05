<section class="bg-primary py-16 text-center text-white" id="search-domain">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-4">ابحث عن اسم دومين</h1>
        <p class="text-sm md:text-base text-white/80 mb-8">
            اكتشف دومينك الفريد واشتريه وسجله باستخدام أداة البحث
        </p>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-3 mb-8">
            <a href="#"
               class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary/30 transition-colors text-white font-semibold px-6 py-3 rounded-full">
                ✨ مولد أسماء الدومينات بالـAI
            </a>
            <a href="#search-domain"
               class="inline-flex items-center gap-2 bg-white text-[#4C1D95] hover:bg-gray-100 transition-colors font-semibold px-6 py-3 rounded-full">
                البحث عن دومين
            </a>
        </div>

        <!-- Search input with button -->
        <div class="relative max-w-xl mx-auto will-change-transform">
            <input id="domainInput" type="text" placeholder="اكتب example أو example.com,example.net"
                   class="w-full text-right py-3 px-4 pr-10 rounded-lg text-white bg-[#2F1A53] placeholder-white/70 border border-white/30 focus:outline-none focus:ring-2 focus:ring-[#7C3AED] transition-shadow duration-200 shadow-inner" />
            <button id="searchButton"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-secondary hover:bg-secondary/30 text-white px-4 py-2 rounded-lg">
                بحث
            </button>
        </div>

        <div class="mt-3 text-xs text-white/80">تلميح: يمكنك إدخال قائمة مفصولة بفواصل مثل:
            <span class="font-mono">example.com, example.net</span>
        </div>

        <div id="status" class="mt-6 text-sm text-white/90"></div>
        <div id="searchResults" class="mt-8"></div>
    </div>
</section>

<script>
  // الامتدادات الافتراضية إن كتب المستخدم فقط SLD (بدون .tld)
  const DEFAULT_TLDS = ['com','net','org','shop','xyz','rocks','news','live','ninja','watches'];
  // لتمييز بعض الامتدادات الجديدة بشارة NEW
  const NEW_TLDS = new Set(['news','rocks','live','watches','ninja']);
  // أسعار افتراضية (fallback) عندما لا يزود المزوّد سعراً
  const FALLBACK_PRICES = {
    com: 12.5, net: 13.99, org: 13.5, shop: 9.99, xyz: 1.99,
    rocks: 20.0, news: 30.0, live: 30.0, ninja: 28.0, watches: 266.0
  };

  const elInput  = document.getElementById('domainInput');
  const elBtn    = document.getElementById('searchButton');
  const elStatus = document.getElementById('status');
  const elResults= document.getElementById('searchResults');

  function formatMoney(n) {
    if (n == null || isNaN(n)) return '—';
    try { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(Number(n)); }
    catch { return Number(n).toFixed(2); }
  }

  function isEmpty(v){ return v == null || (typeof v === 'string' && v.trim() === ''); }

  function buildApiUrl(raw) {
    const base = `{{ route('domains.check') }}`;
    const t = Date.now();

    // إن كان المستخدم أدخل نطاقات كاملة أو قائمة بفواصل
    if (raw.includes('.') || raw.includes(',')) {
      const domains = raw.split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .join(',');
      return `${base}?domains=${encodeURIComponent(domains)}&t=${t}`;
    }

    // خلاف ذلك: اعتبره SLD واستخدم القائمة الافتراضية
    const tlds = DEFAULT_TLDS.join(',');
    return `${base}?q=${encodeURIComponent(raw)}&tlds=${encodeURIComponent(tlds)}&t=${t}`;
  }

  function spinner() {
    return `
      <div class="flex justify-center items-center mt-10">
        <div class="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
      </div>
    `;
  }

  function card(result) {
    // result: {domain, available, is_premium?, price?, currency?}
    const domain = result.domain || '';
    const parts = domain.split('.');
    const tld = parts.length > 1 ? parts[parts.length - 1].toLowerCase() : '';
    const isNew = NEW_TLDS.has(tld);

    // السعر: استخدم المزوّد أولاً، وإلا fallback (غير بريميوم)
    let price = null, currency = '';
    if (result.is_premium) {
      price = result.price ?? null;
      currency = result.currency || 'USD';
    } else {
      if (!isEmpty(result.price)) {
        price = result.price;
        currency = result.currency || 'USD';
      } else if (FALLBACK_PRICES[tld] != null) {
        price = FALLBACK_PRICES[tld];
        currency = 'USD';
      }
    }

    const isAvailable = result.available === true;
    const priceLine = price != null
      ? `from: <span class="font-medium text-white">$${formatMoney(price)} ${currency}</span>`
      : (result.is_premium ? 'سعر بريميوم — تابع الشراء' : '—');

    return `
      <div class="border ${isAvailable ? 'border-green-500 bg-green-600/10' : 'border-red-400 bg-red-600/10'}
                  rounded-lg p-4 text-center relative shadow-md hover:scale-[1.02] hover:shadow-xl transition-transform duration-300">
        ${isNew ? `<span class="absolute top-0 end-0 bg-green-500 text-xs text-white px-2 py-1 rounded-bl-lg font-bold">NEW</span>` : ''}
        <div class="text-lg font-bold text-white mb-1">${domain}</div>
        <div class="text-sm text-white/70 mb-3">
          ${priceLine}
        </div>
        ${isAvailable
          ? `<button class="w-full bg-indigo-900 text-white text-sm font-semibold py-2 rounded hover:bg-indigo-700 transition" data-domain="${domain}" onclick="addToCart('${domain}')">أضف للسلة</button>`
          : `<button class="w-full bg-gray-500 text-white/70 text-sm font-semibold py-2 rounded cursor-not-allowed" disabled>محجوز</button>`}
      </div>
    `;
  }

  async function doSearch() {
    const raw = elInput.value.trim();
    if (!raw) {
      elStatus.textContent = 'الرجاء إدخال اسم الدومين.';
      elResults.innerHTML = '';
      return;
    }

    elStatus.textContent = '';
    elResults.innerHTML = spinner();

    try {
      const url = buildApiUrl(raw);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const text = await res.text();
      let data = null;
      try { data = JSON.parse(text); } catch {}

      if (!data) {
        elResults.innerHTML = '';
        elStatus.textContent = 'فشل — الاستجابة ليست JSON.';
        return;
      }

      if (!data.ok) {
        elResults.innerHTML = '';
        elStatus.textContent = `فشل: ${data.message || 'تعذّر الفحص.'}`;
        return;
      }

      const list = Array.isArray(data.results) ? data.results : [];
      const grid = list.map(card).join('');

      elStatus.textContent =
        `المزوّد: ${data.provider || '—'} • الزمن: ${data.duration_ms || '?'}ms • ${new Date(data.fetched_at).toLocaleString()}`;

      elResults.innerHTML = grid
        ? `<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">${grid}</div>`
        : `<div class="mt-6 text-center text-white/80">لا نتائج.</div>`;

    } catch (e) {
      console.error(e);
      elResults.innerHTML = '';
      elStatus.textContent = 'حدث خطأ في الاتصال.';
    }
  }

  // إدخال Enter
  elInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') elBtn.click(); });
  // زر البحث
  elBtn.addEventListener('click', doSearch);

  // API السلة (تجريبي الآن)
  window.addToCart = (domain) => {
    alert(`(Demo) تمت إضافة ${domain} للسلة. سنربطها بعملية الشراء لاحقًا.`);
  };
</script>