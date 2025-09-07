{{-- resources/views/components/template/sections/search-domain.blade.php --}}
@props([
    // يمكنك تمرير هذه من الكنترولر لعرض أسعار افتراضية للـ TLDs (مثلاً من الكتالوج)
    // ['com'=>12.5, 'net'=>13.99, ...]
    'fallbackPrices' => [],
    'defaultTlds'    => ['com','net','org','shop','xyz','rocks','news','live','ninja','watches'],
    'currency'       => 'USD',
])

<section class="bg-primary py-16 text-center text-white" id="search-domain">
  <div class="container mx-auto px-4 max-w-4xl">
    <h1 class="text-2xl md:text-4xl font-extrabold mb-4">ابحث عن اسم دومين</h1>
    <p class="text-sm md:text-base text-white/80 mb-8">
      اكتب اسمك فقط مثل <span class="font-mono">palgoals</span> وسنعرض لك .com والبدائل والاقتراحات فورًا
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-3 mb-8">
      <a href="#search-domain"
         class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary/30 transition-colors text-white font-semibold px-6 py-3 rounded-full">
        ✨ مولد أسماء الدومينات الذكي
      </a>
      <a href="#search-domain"
         class="inline-flex items-center gap-2 bg-white text-[#4C1D95] hover:bg-gray-100 transition-colors font-semibold px-6 py-3 rounded-full">
        البحث عن دومين
      </a>
    </div>

    <!-- حقل البحث -->
    <div class="relative max-w-xl mx-auto will-change-transform">
      <input id="domainInput" type="text" placeholder="palgoals أو palgoals.com, palgoals.net"
             class="w-full text-right py-3 px-4 pr-10 rounded-lg text-white bg-[#2F1A53] placeholder-white/70 border border-white/30 focus:outline-none focus:ring-2 focus:ring-[#7C3AED] transition-shadow duration-200 shadow-inner" />
      <button id="searchButton"
              class="absolute left-2 top-1/2 -translate-y-1/2 bg-secondary hover:bg-secondary/30 text-white px-4 py-2 rounded-lg">
        بحث
      </button>
    </div>

    <div class="mt-3 text-xs text-white/80">تلميح: اكتب SLD فقط (بدون .com) وسنقترح الامتدادات الشائعة تلقائيًا.</div>

    <div id="status" class="mt-6 text-sm text-white/90"></div>

    {{-- النتيجة الأساسية + بدائل TLD --}}
    <div id="primaryResult" class="mt-6"></div>
    <div id="altHeader" class="mt-8 text-left hidden">
      <h3 class="text-lg font-semibold">بدائل TLD</h3>
    </div>
    <div id="altResults" class="mt-3"></div>
    <div class="mt-4 hidden" id="moreWrap">
      <button id="loadMoreBtn" class="btn btn-outline-light">عرض المزيد</button>
    </div>

    {{-- الاقتراحات الذكية --}}
    <div class="mt-12 text-left">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">اقتراحات أسماء</h3>
        <div class="flex gap-2 text-xs">
          <label class="inline-flex items-center gap-1 cursor-pointer">
            <input type="checkbox" id="optCommon" class="accent-white" checked> شائعة
          </label>
          <label class="inline-flex items-center gap-1 cursor-pointer">
            <input type="checkbox" id="optTech" class="accent-white" checked> تقنية
          </label>
          <label class="inline-flex items-center gap-1 cursor-pointer">
            <input type="checkbox" id="optBiz" class="accent-white" checked> أعمال
          </label>
          <label class="inline-flex items-center gap-1 cursor-pointer">
            <input type="checkbox" id="optShort" class="accent-white" checked> تقصير
          </label>
        </div>
      </div>

      <div class="mt-3">
        <button id="genSuggestBtn" class="btn btn-outline-light btn-sm">توليد اقتراحات</button>
        <button id="checkSuggestBtn" class="btn btn-light btn-sm ml-2">فحص الاقتراحات (.com)</button>
      </div>

      <div id="suggestionsWrap" class="mt-4"></div>
    </div>
  </div>
</section>

<script>
  // ===== بيانات من السيرفر =====
  const DEFAULT_TLDS    = @json(array_values(array_unique(array_map(fn($t)=>strtolower(ltrim($t,'.')), $defaultTlds))));
  const FALLBACK_PRICES = @json($fallbackPrices);
  const DEFAULT_CCY     = @json($currency);

  // ===== إعدادات العرض =====
  const POPULAR_TLDS = ['com','net','org'];
  const NEW_TLDS     = new Set(['news','rocks','live','watches','ninja']); // وسم "NEW"

  // ===== عناصر DOM =====
  const elInput    = document.getElementById('domainInput');
  const elBtn      = document.getElementById('searchButton');
  const elStatus   = document.getElementById('status');
  const elPrimary  = document.getElementById('primaryResult');
  const elAltH     = document.getElementById('altHeader');
  const elAlt      = document.getElementById('altResults');
  const elMoreWrap = document.getElementById('moreWrap');
  const elMoreBtn  = document.getElementById('loadMoreBtn');
  const elSugWrap  = document.getElementById('suggestionsWrap');
  const elGenSug   = document.getElementById('genSuggestBtn');
  const elChkSug   = document.getElementById('checkSuggestBtn');

  const elOptCommon = document.getElementById('optCommon');
  const elOptTech   = document.getElementById('optTech');
  const elOptBiz    = document.getElementById('optBiz');
  const elOptShort  = document.getElementById('optShort');

  // ===== أدوات مساعدة =====
  const isEmpty  = v => v == null || (typeof v === 'string' && v.trim() === '');
  const fmtMoney = n => (n==null||isNaN(n)) ? '—' : (new Intl.NumberFormat('en-US',{maximumFractionDigits:2}).format(Number(n)));

  function orderTlds(all) {
    const set = new Set(all);
    const ordered = [];
    for (const t of POPULAR_TLDS) if (set.delete(t)) ordered.push(t);
    const newOnes = Array.from(set).filter(t=>NEW_TLDS.has(t)).sort();
    newOnes.forEach(t => { set.delete(t); ordered.push(t); });
    ordered.push(...Array.from(set).sort());
    return ordered;
  }

  function buildDomains(raw, tlds) {
    raw = raw.trim();
    if (raw.includes(',')) {
      return raw.split(',').map(s=>s.trim()).filter(Boolean);
    }
    if (raw.includes('.')) {
      const domain = raw.toLowerCase();
      const sld = domain.split('.')[0] || domain;
      const tld = domain.split('.').pop();
      const others = tlds.filter(x => x !== tld);
      return Array.from(new Set([domain, ...others.map(x => `${sld}.${x}`)]));
    }
    const sld = raw.toLowerCase();
    return [ `${sld}.com`, ...tlds.filter(x=>x!=='com').map(x => `${sld}.${x}`) ];
  }

  function priceLine(result, tld) {
    let price = null, ccy = DEFAULT_CCY || 'USD';
    if (!isEmpty(result?.price)) {
      price = Number(result.price);
      ccy   = result.currency || ccy;
    } else if (FALLBACK_PRICES && tld in FALLBACK_PRICES) {
      price = Number(FALLBACK_PRICES[tld]);
    }
    if (price==null) return result?.is_premium ? 'سعر بريميوم — تابع الشراء' : '—';
    return `from: <span class="font-medium text-white">$${fmtMoney(price)} ${ccy}</span>`;
  }

  const tagNew = tld => NEW_TLDS.has(tld) ? `<span class="absolute top-0 end-0 bg-green-500 text-xs text-white px-2 py-1 rounded-bl-lg font-bold">NEW</span>` : '';

  function card(domain, result = {}) {
    const tld = domain.split('.').pop().toLowerCase();
    const available = !!result.available;
    return `
      <div class="border ${available?'border-green-500 bg-green-600/10':'border-red-400 bg-red-600/10'}
                  rounded-lg p-4 text-center relative shadow-md hover:scale-[1.02] hover:shadow-xl transition-transform duration-300">
        ${tagNew(tld)}
        <div class="text-lg font-bold text-white mb-1">${domain}</div>
        <div class="text-sm text-white/70 mb-3">${priceLine(result,tld)}</div>
        ${available
          ? `<button class="w-full bg-indigo-900 text-white text-sm font-semibold py-2 rounded hover:bg-indigo-700 transition" onclick="addToCart('${domain}')">أضف للسلة</button>`
          : `<div class="flex flex-col gap-2">
               <button class="w-full bg-gray-500 text-white/70 text-sm font-semibold py-2 rounded cursor-not-allowed" disabled>محجوز</button>
               <button class="w-full bg-white/10 text-white text-xs py-1 rounded hover:bg-white/20 transition"
                       onclick="checkMoreTlds('${domain.split('.').slice(0,-1).join('.')}')">جرّب امتدادات أخرى</button>
             </div>`}
      </div>
    `;
  }

  function skeleton(rows=8){
    return `
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-4">
        ${Array.from({length:rows}).map(()=>`<div class="rounded-lg p-4 text-center bg-white/10 animate-pulse h-28"></div>`).join('')}
      </div>
    `;
  }

  // ===== البحث الأساسي + البدائل =====
  let lastResults = [];  // [{domain, result}]
  let shownCount  = 0;

  function renderPrimaryAndAlts() {
    if (!lastResults.length) {
      elPrimary.innerHTML = '';
      elAlt.innerHTML = '';
      elAltH.classList.add('hidden');
      elMoreWrap.classList.add('hidden');
      return;
    }
    const idxCom = lastResults.findIndex(x => x.domain.toLowerCase().endsWith('.com'));
    const primaryIdx = idxCom >= 0 ? idxCom : 0;
    const primary = lastResults[primaryIdx];
    const rest = lastResults.filter((_,i)=>i!==primaryIdx);

    elPrimary.innerHTML = `
      <div class="text-left">
        <h3 class="text-lg font-semibold mb-2">نتيجتك الأساسية</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">${card(primary.domain, primary.result)}</div>
    `;

    const BATCH = 8;
    shownCount = 0;
    elAlt.innerHTML = '';
    lastResults = rest;
    loadMore(BATCH);

    elAltH.classList.toggle('hidden', lastResults.length === 0);
    elMoreWrap.classList.toggle('hidden', shownCount >= lastResults.length);
  }

  function loadMore(batch=12) {
    const slice = lastResults.slice(shownCount, shownCount + batch);
    if (slice.length) {
      elAlt.insertAdjacentHTML('beforeend', `
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          ${slice.map(x => card(x.domain, x.result)).join('')}
        </div>
      `);
      shownCount += slice.length;
    }
    elMoreWrap.classList.toggle('hidden', shownCount >= lastResults.length);
  }

  elMoreBtn?.addEventListener('click', () => loadMore(12));

  async function doSearch() {
    const raw = elInput.value.trim();
    if (!raw) {
      elStatus.textContent = 'الرجاء إدخال اسم الدومين.';
      elPrimary.innerHTML = '';
      elAlt.innerHTML = '';
      elAltH.classList.add('hidden');
      elMoreWrap.classList.add('hidden');
      return;
    }

    const orderedTlds = orderTlds(DEFAULT_TLDS);
    const domains = buildDomains(raw, orderedTlds);

    elStatus.textContent = '';
    elPrimary.innerHTML = skeleton(1);
    elAlt.innerHTML     = skeleton(8);
    elAltH.classList.remove('hidden');
    elMoreWrap.classList.add('hidden');

    try {
      const url  = `{{ route('domains.check') }}?domains=${encodeURIComponent(domains.join(','))}&t=${Date.now()}`;
      const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const text = await res.text();
      let data   = null; try { data = JSON.parse(text); } catch {}

      if (!data || !data.ok) {
        elStatus.textContent = `فشل: ${(data && data.message) || 'تعذّر الفحص.'}`;
        elPrimary.innerHTML = '';
        elAlt.innerHTML = '';
        elAltH.classList.add('hidden');
        return;
      }

      const m = new Map();
      (data.results||[]).forEach(r => { if (r && r.domain) m.set(r.domain.toLowerCase(), r); });
      const normalized = domains.map(d => ({ domain: d, result: m.get(d.toLowerCase()) || {domain:d,available:false} }));

      // اجعل .com أولاً إن وُجد
      normalized.sort((a,b) => (b.domain.endsWith('.com') - a.domain.endsWith('.com')) || a.domain.localeCompare(b.domain));
      lastResults = normalized;

      elStatus.textContent = `التحقُّق: تم • الزمن: ${data.duration_ms || '?'}ms • ${new Date(data.fetched_at).toLocaleString()}`;
      renderPrimaryAndAlts();

      // لو المستخدم كتب SLD فقط، ولّد اقتراحات وتحقق تلقائياً
      if (!raw.includes('.')) generateAndRenderSuggestions(raw.trim().toLowerCase());

    } catch (e) {
      console.error(e);
      elStatus.textContent = 'حدث خطأ في الاتصال.';
      elPrimary.innerHTML = '';
      elAlt.innerHTML = '';
      elAltH.classList.add('hidden');
      elMoreWrap.classList.add('hidden');
    }
  }

  // فحص مزيد من TLDs لاسم معين
  window.checkMoreTlds = async (sld) => {
    const tlds = orderTlds(DEFAULT_TLDS).slice(0, 6);
    const domains = tlds.map(t => `${sld}.${t}`);
    try {
      const url  = `{{ route('domains.check') }}?domains=${encodeURIComponent(domains.join(','))}&t=${Date.now()}`;
      const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const text = await res.text();
      let data   = null; try { data = JSON.parse(text); } catch {}
      if (!data || !data.ok) { alert('تعذّر الفحص'); return; }

      const avail = (data.results||[]).filter(r => r.available && r.domain.startsWith(`${sld}.`)).map(r => r.domain);
      if (avail.length) {
        alert(`متاح:\n${avail.join('\n')}`);
      } else {
        alert('لا توجد امتدادات متاحة ضمن المجموعة المختارة.');
      }
    } catch { alert('خطأ في الاتصال'); }
  };

  // ===== مولِّد اقتراحات محسَّن (Brandable + Scoring) =====
  const LIB = {
    common: {
      pref: ['get','go','my','try','join','we','hey','the'],
      suff: ['ly','hub','plus','labs','studio','space','world','online','wise','base','nest']
    },
    tech: {
      pref: ['app','dev','tech','cloud','ai','data'],
      suff: ['io','tech','dev','cloud','ai','data','systems','digital','stack','soft']
    },
    biz: {
      pref: ['pro','pay','shop'],
      suff: ['store','shop','media','group','agency','solutions','works','mart']
    }
  };
  const RESERVED = new Set(['test','admin','root','null','undefined']);
  const VOWELS   = /[aeiouy]/g;

  function sanitizeSLD(s) {
    return (s||'').toLowerCase()
      .replace(/[^a-z0-9\-]+/g,'')
      .replace(/^\-+|\-+$/g,'')
      .slice(0,63);
  }

  function shorten(base) {
    base = sanitizeSLD(base);
    if (base.length <= 4) return [base];
    const noVowels = base[0] + base.slice(1).replace(/[aeiou]/g,'');
    const chunks = base.split(/[-_]/).filter(Boolean);
    const initials = chunks.map(c=>c[0]).join('');
    const uniq = new Set([base, noVowels, initials]);
    return Array.from(uniq).filter(x => x && x.length >= 3);
  }

  function lev(a,b){
    a=a.toLowerCase(); b=b.toLowerCase();
    const m=a.length,n=b.length; const d=Array.from({length:m+1},(_,i)=>[i].concat(Array(n).fill(0)));
    for(let j=0;j<=n;j++) d[0][j]=j;
    for(let i=1;i<=m;i++){
      for(let j=1;j<=n;j++){
        const cost = a[i-1]===b[j-1]?0:1;
        d[i][j]=Math.min(d[i-1][j]+1,d[i][j-1]+1,d[i-1][j-1]+cost);
      }
    }
    return d[m][n];
  }

  function scoreCandidate(name, base, weights){
    let s = 0;
    const len = name.length;

    if (len>=5 && len<=12) s += 30;
    else if (len>=3 && len<=15) s += 10;
    else s -= 20;

    const vowels = (name.match(VOWELS)||[]).length;
    const ratio = vowels/Math.max(1,len);
    if (ratio>=0.25 && ratio<=0.6) s += 15; else s -= 10;

    if (/(.)\1{2,}/.test(name)) s -= 25;
    if (/--/.test(name)) s -= 20;
    if (/^-|-$/.test(name)) s -= 10;

    const distance = lev(name.replace(/-/g,''), base.replace(/-/g,''));
    if (distance === 0) s -= 30;
    else if (distance <= 3) s += 20;
    else if (distance <= 6) s += 10;

    if (/[aeiouy]$/.test(name)) s += 5;

    if (weights.usedTech)   s += 6;
    if (weights.usedBiz)    s += 6;
    if (weights.usedCommon) s += 4;

    RESERVED.has(name) && (s -= 50);

    return s;
  }

  function generateCandidates(base, opts){
    base = sanitizeSLD(base);
    const variants = new Set();
    const shorties = shorten(base);
    const push = (v)=>{ v=sanitizeSLD(v); if (v && v.length>=3 && v.length<=20) variants.add(v); };

    push(base);
    shorties.forEach(push);

    const useCommon = !!opts.common, useTech=!!opts.tech, useBiz=!!opts.biz, useShort=!!opts.short;

    const addAffixes = (lib) => {
      if (!lib) return;
      lib.pref.forEach(p => push(p + base.charAt(0).toUpperCase() + base.slice(1)));
      lib.suff.forEach(s => {
        const joiner = /[bcdfghjklmnpqrstvwxyz]$/.test(base) && /^[bcdfghjklmnpqrstvwxyz]/.test(s) ? 'a' : '';
        push(base + joiner + s);
      });
      if (useShort) {
        shorties.forEach(sh => {
          lib.suff.slice(0,3).forEach(s => push(sh + s));
        });
      }
    };

    useCommon && addAffixes(LIB.common);
    useTech   && addAffixes(LIB.tech);
    useBiz    && addAffixes(LIB.biz);

    ['ly','io','ify','ster','verse','stack','flow','grid','kit'].forEach(s=> push(base + s));

    return Array.from(variants).filter(v => !/^\d+$/.test(v));
  }

  function generateSuggestions(base, opts){
    const cand = generateCandidates(base, opts);
    const weights = {
      usedCommon: !!opts.common,
      usedTech:   !!opts.tech,
      usedBiz:    !!opts.biz
    };
    const scored = cand.map(name => ({
      name,
      score: scoreCandidate(name, base, weights)
    }));
    scored.sort((a,b)=> b.score - a.score || a.name.localeCompare(b.name));
    return scored.slice(0,12).map(x=>x.name);
  }

  function renderSuggestions(sld, names) {
    if (!names.length) {
      elSugWrap.innerHTML = '<div class="text-white/80 text-sm">لا اقتراحات مناسبة.</div>';
      return;
    }
    elSugWrap.innerHTML = `
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        ${names.map(n => `
          <div class="bg-white/10 rounded-lg p-3 text-center sug-item" data-sld="${n}">
            <div class="font-semibold">${n}.com</div>
            <div class="sug-status text-xs text-white/70 mt-1">جارٍ التحضير للفحص…</div>
            <div class="mt-2 flex gap-2">
              <button class="flex-1 bg-indigo-900 text-white text-xs font-semibold py-1.5 rounded hover:bg-indigo-700 transition"
                      onclick="checkOneSuggestion('${n}')">فحص .com</button>
              <button class="flex-1 bg-white/10 text-white text-xs py-1.5 rounded hover:bg-white/20 transition"
                      onclick="checkMoreTlds('${n}')">امتدادات أخرى</button>
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
      card.classList.add('ring-2','ring-green-400');
    } else {
      st.textContent = '❌ محجوز';
      card.classList.add('opacity-80');
    }
  }

  async function autoCheckSuggestions(limit = 12) {
    const items = Array.from(elSugWrap.querySelectorAll('.sug-item')).slice(0, limit);
    if (!items.length) return;
    const names = items.map(x => x.dataset.sld);
    const domains = names.map(n => `${n}.com`);
    const url = `{{ route('domains.check') }}?domains=${encodeURIComponent(domains.join(','))}&t=${Date.now()}`;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => null);
      if (!data || !data.ok) return;
      const map = new Map((data.results || []).map(r => [r.domain.toLowerCase(), r]));
      names.forEach(n => {
        const r = map.get(`${n}.com`);
        annotateSuggestion(n, !!r?.available, r?.price ?? null, r?.currency ?? null);
      });
    } catch { /* ignore */ }
  }

  function generateAndRenderSuggestions(raw) {
    const base = sanitizeSLD(raw);
    if (!base || base.length < 3) { elSugWrap.innerHTML = ''; return; }
    const names = generateSuggestions(base, {
      common: elOptCommon?.checked ?? true,
      tech:   elOptTech?.checked ?? true,
      biz:    elOptBiz?.checked ?? true,
      short:  elOptShort?.checked ?? true,
    });
    renderSuggestions(base, names);
    autoCheckSuggestions(12); // فحص تلقائي لأول 12 .com
  }

  // فحص اقتراح واحد (.com فقط)
  window.checkOneSuggestion = async (sld) => {
    const domain = `${sld}.com`;
    const url  = `{{ route('domains.check') }}?domains=${encodeURIComponent(domain)}&t=${Date.now()}`;
    try {
      const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(()=>null);
      if (!data || !data.ok) { alert('تعذّر الفحص'); return; }
      const r = (data.results||[]).find(x => x.domain?.toLowerCase() === domain.toLowerCase());
      if (!r) { alert('تعذّر الفحص'); return; }
      if (r.available) {
        addToCart(domain);
      } else {
        checkMoreTlds(sld);
      }
    } catch { alert('خطأ في الاتصال'); }
  };

  // فحص مجموعة الاقتراحات (.com) دفعة واحدة
  async function checkSuggestionsBatch() {
    const items = Array.from(elSugWrap.querySelectorAll('.sug-item'));
    if (!items.length) return alert('لا توجد اقتراحات لفحصها.');
    const names = items.map(x => x.dataset.sld).slice(0, 12);
    const domains = names.map(n => `${n}.com`);
    const url  = `{{ route('domains.check') }}?domains=${encodeURIComponent(domains.join(','))}&t=${Date.now()}`;
    try {
      const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => null);
      if (!data || !data.ok) { alert('تعذّر الفحص'); return; }
      const map = new Map((data.results||[]).map(r => [r.domain.toLowerCase(), r]));
      names.forEach(n => {
        const r = map.get(`${n}.com`);
        annotateSuggestion(n, !!r?.available, r?.price ?? null, r?.currency ?? null);
      });
      const available = names.map(n => `${n}.com`).filter(d => map.get(d)?.available);
      if (available.length) {
        alert(`متاح الآن:\n${available.slice(0,20).join('\n')}${available.length>20?'\n...':''}`);
      } else {
        alert('كل الاقتراحات .com في هذه الدفعة محجوزة.');
      }
    } catch { alert('خطأ في الاتصال'); }
  }

  // أحداث الاقتراحات
  elGenSug?.addEventListener('click', () => {
    const raw = elInput.value.trim();
    if (!raw) return;
    generateAndRenderSuggestions(raw);
  });
  elChkSug?.addEventListener('click', () => checkSuggestionsBatch());

  // أحداث البحث
  elInput.addEventListener('keydown', (e)=>{ if (e.key === 'Enter') elBtn.click(); });
  elBtn.addEventListener('click', doSearch);

  // بحث تلقائي لو كان فيه q= في URL
  (function autoSearchFromQuery(){
    const u = new URL(window.location.href);
    const q = u.searchParams.get('q');
    if (q) { elInput.value = q; doSearch(); }
  })();

  // Placeholder للسلة
  window.addToCart = (domain) => {
    alert(`(Demo) تمت إضافة ${domain} للسلة. سنربطها بعملية الشراء لاحقًا.`);
  };
</script>
