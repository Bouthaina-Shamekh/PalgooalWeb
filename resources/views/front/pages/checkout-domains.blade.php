@php $title = 'حجز الدومينات'; @endphp
<x-template.layouts.index-layouts title="{{ $title }}">
    <section class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow p-6">
            <h1 class="text-2xl font-bold mb-4">حجز الدومينات</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">راجع الدومينات المحجوزة مسبقاً قبل المتابعة.</p>

            <div id="domainsBox">جارٍ تحميل السلة...</div>

            <div class="mt-6 flex items-center gap-3">
                <button id="btnBack" class="px-4 py-2 rounded-xl bg-gray-200">العودة</button>
                <button id="btnReserve" class="px-4 py-2 rounded-xl bg-[#240B36] text-white">استمرار</button>
            </div>
        </div>
    </section>

    <script>
        (function() {
            const CART_KEY = 'palgoals_cart_domains';
            const box = document.getElementById('domainsBox');

            function safeParse(json, fallback) {
                try {
                    const v = JSON.parse(json);
                    return Array.isArray(v) ? v : fallback;
                } catch {
                    return fallback;
                }
            }
            const raw = localStorage.getItem(CART_KEY);
            const list = safeParse(raw, []);
            if (!list.length) {
                box.innerHTML = '<div class="text-gray-500">السلة فارغة.</div>';
            } else {
                const ul = document.createElement('ul');
                ul.className = 'space-y-2';
                list.forEach(it => {
                    const li = document.createElement('li');
                    li.className = 'flex justify-between p-3 border rounded';
                    const textNode = document.createTextNode(it.domain || '');
                    const span = document.createElement('span');
                    span.textContent = it.price_cents ? ('$' + (Number(it.price_cents) / 100).toFixed(2)) : '—';
                    li.appendChild(textNode);
                    li.appendChild(span);
                    ul.appendChild(li);
                });
                box.innerHTML = '';
                box.appendChild(ul);
            }

            document.getElementById('btnBack').addEventListener('click', () => history.back());
            document.getElementById('btnReserve').addEventListener('click', async () => {
                if (!list.length) return alert('السلة فارغة');
                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute(
                        'content') || '';
                    const res = await fetch('{{ route('cart.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            items: list
                        })
                    });
                    const data = await res.json().catch(() => null);
                    if (data && data.ok) {
                        // now call server process endpoint
                        const proc = await fetch('{{ route('checkout.domains.process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                items: list
                            })
                        });
                        const out = await proc.json().catch(() => null);
                        if (out && out.ok) {
                            // clear local cart and go to success
                            localStorage.removeItem(CART_KEY);
                            window.location.href = '{{ route('checkout.domains.success') }}';
                        } else alert('تعذّر إكمال الحجز');
                    } else alert('تعذّر حفظ السلة على الخادم');
                } catch (e) {
                    console.error(e);
                    alert('خطأ في الاتصال');
                }
            });
        })();
    </script>
</x-template.layouts.index-layouts>
