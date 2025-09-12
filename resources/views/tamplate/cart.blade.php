@php
    $title = 'سلة الدومينات';
@endphp
<x-template.layouts.index-layouts title="{{ $title }}">
    <section class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6">
            <h1 class="text-2xl font-bold mb-4">سلة الدومينات</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                العناصر التي وضعتها في السلة من صفحة البحث عن الدومينات.
            </p>

            <div id="cartEmpty" class="text-center text-gray-500">جارٍ تحميل محتويات السلة...</div>

            <div id="cartList" class="space-y-3 hidden">
                <!-- سيتم تعبئتها بواسطة JS -->
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button id="btnClear" class="px-4 py-2 rounded-xl bg-red-600 text-white">إفراغ السلة</button>
                <button id="btnProceed" class="px-4 py-2 rounded-xl bg-[#240B36] text-white">
                    اختر قالباً أو ابدأ الدفع
                </button>
            </div>
        </div>
    </section>

    <script>
        (function() {
            const CART_KEY = 'palgoals_cart';
            const elList = document.getElementById('cartList');
            const elEmpty = document.getElementById('cartEmpty');
            const elClear = document.getElementById('btnClear');
            const elGo = document.getElementById('btnProceed');

            const fmtCents = c => Number.isFinite(+c) ? ('$' + (+c / 100).toFixed(2)) : '—';
            const safeParse = (json, fb) => {
                try {
                    const v = JSON.parse(json);
                    return Array.isArray(v) ? v : fb;
                } catch {
                    return fb;
                }
            };

            // قراءة السلة الموحّدة + استيراد القديمة مرة واحدة
            function readUnifiedCart() {
                const legacy = localStorage.getItem('palgoals_cart_domains');
                const unified = localStorage.getItem(CART_KEY);
                let items = safeParse(unified, []);
                if (!unified && legacy) {
                    const old = safeParse(legacy, []);
                    if (old.length) {
                        items = items.concat(old.map(it => ({
                            kind: 'domain',
                            domain: String(it.domain || '').toLowerCase().trim(),
                            item_option: it.item_option ?? it.option ?? null,
                            price_cents: Number(it.price_cents) || 0,
                            meta: it.meta ?? null,
                        })));
                        localStorage.setItem(CART_KEY, JSON.stringify(items));
                    }
                }
                return items;
            }

            function writeUnifiedCart(items) {
                localStorage.setItem(CART_KEY, JSON.stringify(items || []));
            }

            // ————— دومينات —————
            const domainOnly = items => (items || []).filter(it =>
                it && typeof it === 'object' && (
                    it.kind === 'domain' || (it.kind == null && typeof it.domain === 'string' && it.domain
                        .trim() !== '')
                )
            );

            function dedupeDomains(domains) {
                const seen = new Set(),
                    out = [];
                for (const it of domains) {
                    const d = String(it.domain || '').toLowerCase().trim();
                    if (!d || seen.has(d)) continue;
                    seen.add(d);
                    out.push({
                        kind: 'domain',
                        domain: d,
                        item_option: it.item_option ?? it.option ?? null,
                        price_cents: Number(it.price_cents) || 0,
                        meta: it.meta ?? null,
                    });
                }
                return out;
            }

            function replaceDomainsInUnified(newDomains) {
                const unified = readUnifiedCart();
                const others = unified.filter(it => !(it && (it.kind === 'domain' || (it.kind == null && it.domain))));
                writeUnifiedCart([...others, ...newDomains]);
            }

            // ————— قوالب —————
            const templateOnly = items => (items || []).filter(it =>
                it && typeof it === 'object' && it.kind === 'template' && Number(it.template_id)
            );

            function dedupeTemplates(templates) {
                const byId = new Map();
                for (const it of templates) {
                    const id = Number(it.template_id) || 0;
                    if (!id) continue;
                    const cur = byId.get(id) || {
                        kind: 'template',
                        template_id: id,
                        template_name: it.template_name || 'Template',
                        qty: 0,
                        price_cents: Number(it.price_cents) || 0,
                        meta: it.meta ?? null,
                    };
                    cur.qty += Math.max(1, Number(it.qty || 1));
                    // آخر سعر يفوز (أو نفس السعر)
                    cur.price_cents = Number(it.price_cents ?? cur.price_cents) || 0;
                    byId.set(id, cur);
                }
                return [...byId.values()];
            }

            function replaceTemplatesInUnified(newTemplates) {
                const unified = readUnifiedCart();
                const others = unified.filter(it => !(it && it.kind === 'template'));
                writeUnifiedCart([...others, ...newTemplates]);
            }

            async function render() {
                const unified = readUnifiedCart();
                const domains = dedupeDomains(domainOnly(unified));
                const templates = dedupeTemplates(templateOnly(unified));

                // مزامنة التخزين بعد الديدوب
                replaceDomainsInUnified(domains);
                replaceTemplatesInUnified(templates);

                const hasAnything = domains.length || templates.length;
                if (!hasAnything) {
                    elEmpty.textContent = 'السلة فارغة.';
                    elList.classList.add('hidden');
                    elGo.setAttribute('disabled', 'disabled');
                    elGo.classList.add('opacity-50', 'cursor-not-allowed');
                    return;
                }

                elEmpty.style.display = 'none';
                elList.classList.remove('hidden');
                elGo.removeAttribute('disabled');
                elGo.classList.remove('opacity-50', 'cursor-not-allowed');

                elList.innerHTML = '';

                // خريطة index داخل unified لعناصر الدومين فقط (للحذف الدقيق)
                const unifiedNow = readUnifiedCart();
                const idxByDomain = new Map();
                unifiedNow.forEach((it, idx) => {
                    if (it && (it.kind === 'domain' || (it.kind == null && it.domain))) {
                        const d = String(it.domain || '').toLowerCase().trim();
                        if (d && !idxByDomain.has(d)) idxByDomain.set(d, idx);
                    }
                });

                let totalCents = 0;

                // — قِسم القوالب —
                if (templates.length) {
                    const head = document.createElement('div');
                    head.className = 'text-sm font-semibold text-gray-700 dark:text-gray-200 mt-2';
                    head.textContent = 'القوالب المختارة';
                    elList.appendChild(head);

                    templates.forEach(tpl => {
                        const lineTotal = (Number(tpl.price_cents) || 0) * Math.max(1, Number(tpl.qty ||
                            1));
                        totalCents += lineTotal;

                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between p-3 border rounded-xl';
                        row.innerHTML = `
          <div>
            <div class="font-semibold">
              ${tpl.template_name || ('Template #' + tpl.template_id)}
            </div>
            <div class="text-xs text-gray-500">الكمية: ${Math.max(1, Number(tpl.qty || 1))}</div>
          </div>
          <div class="text-right">
            <div class="font-bold">${fmtCents(lineTotal)}</div>
            <div class="mt-2">
              <button data-template-id="${tpl.template_id}" class="removeTpl px-3 py-1 text-sm rounded bg-red-100 text-red-700">حذف</button>
            </div>
          </div>
        `;
                        elList.appendChild(row);
                    });
                }

                // — قِسم الدومينات —
                if (domains.length) {
                    const head = document.createElement('div');
                    head.className = 'text-sm font-semibold text-gray-700 dark:text-gray-200 mt-4';
                    head.textContent = 'الدومينات';
                    elList.appendChild(head);

                    domains.forEach(it => {
                        const cents = Number(it.price_cents) || 0;
                        totalCents += cents;

                        const d = String(it.domain || '').toLowerCase();
                        const idx = idxByDomain.get(d);

                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between p-3 border rounded-xl';
                        row.innerHTML = `
          <div>
            <div class="font-semibold">${d || '—'}</div>
            <div class="text-xs text-gray-500">${it.item_option ?? it.option ?? '—'}</div>
          </div>
          <div class="text-right">
            <div class="font-bold">${fmtCents(cents)}</div>
            <div class="mt-2">
              <button data-unified-idx="${idx ?? -1}" class="removeDom px-3 py-1 text-sm rounded bg-red-100 text-red-700">حذف</button>
            </div>
          </div>
        `;
                        elList.appendChild(row);
                    });
                }

                // إجمالي السلة
                const totalRow = document.createElement('div');
                totalRow.className =
                    'flex items-center justify-between p-3 border rounded-xl bg-gray-50 dark:bg-gray-800 mt-2';
                totalRow.innerHTML = `
      <div class="font-semibold">الإجمالي</div>
      <div class="font-bold">${fmtCents(totalCents)}</div>
    `;
                elList.appendChild(totalRow);

                // حذف قالب
                elList.querySelectorAll('.removeTpl').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = Number(btn.getAttribute('data-template-id')) || 0;
                        if (!id) return;
                        const unified = readUnifiedCart().filter(it => !(it && it.kind ===
                            'template' && Number(it.template_id) === id));
                        writeUnifiedCart(unified);
                        render();
                    });
                });

                // حذف دومين
                elList.querySelectorAll('.removeDom').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const idx = Number(btn.getAttribute('data-unified-idx'));
                        if (!Number.isInteger(idx) || idx < 0) return;
                        const unified = readUnifiedCart();
                        unified.splice(idx, 1);
                        writeUnifiedCart(unified);
                        render();
                    });
                });
            }

            // إفراغ السلة بالكامل (قوالب + دومينات)
            elClear.addEventListener('click', () => {
                writeUnifiedCart([]);
                render();
            });

            // متابعة:
            // - لو في قوالب فقط => نوجّه مباشرة لشراء أول قالب
            // - لو في دومينات => نرسلها للباك إند كما هو (السلوك القديم)
            elGo.addEventListener('click', async () => {
                const unified = readUnifiedCart();
                const domains = dedupeDomains(domainOnly(unified));
                const templates = dedupeTemplates(templateOnly(unified));

                if (!domains.length && templates.length) {
                    const first = templates[0];
                    // غيّر المسار التالي إذا كان عندك اسم Route مختلف للشراء:
                    window.location.href = `/checkout?template_id=${encodeURIComponent(first.template_id)}`;
                    return;
                }

                if (!domains.length) {
                    alert('السلة فارغة.');
                    return;
                }

                const payload = domains.map(it => ({
                    domain: it.domain,
                    // ensure the backend validator always receives a non-null option
                    item_option: it.item_option ?? it.option ?? 'register',
                    price_cents: Number(it.price_cents) || 0,
                    meta: it.meta ?? null,
                }));

                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute(
                        'content') || '';
                    // call the route that actually creates orders + order_items for domains
                    const res = await fetch('{{ route('checkout.domains.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            items: payload
                        }),
                    });
                    const data = await res.json().catch(() => null);
                    if (res.ok && data) {
                        // clear legacy domain-only localStorage key and redirect to unified checkout
                        try {
                            localStorage.removeItem('palgoals_cart_domains');
                        } catch (e) {}
                        replaceDomainsInUnified([]);
                        // If server returns order_id, attach it for the checkout view
                        const orderId = data.order_id || data.id || '';
                        window.location.href = '{{ route('checkout.cart') }}' + (orderId ? ('?order_id=' +
                            orderId) : '');
                    } else {
                        alert((data && data.message) || 'تعذّر إنشاء الطلب على الخادم.');
                        console.error(data);
                    }
                } catch (e) {
                    console.error(e);
                    alert('خطأ في الاتصال بالخادم.');
                }
            });

            render();
        })();
    </script>

</x-template.layouts.index-layouts>
