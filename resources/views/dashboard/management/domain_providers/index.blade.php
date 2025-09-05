{{-- resources/views/dashboard/management/domain_providers/index.blade.php --}}
<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">مزودو الدومينات</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">قائمة مزودي الدومينات</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="bg-white shadow-sm rounded-lg overflow-visible">
                @if (session('ok'))
                    <div class="alert alert-success mb-4">{{ session('ok') }}</div>
                @endif

                <div class="px-4 py-4 border-b border-gray-100">
                    <div class="flex justify-between items-center">
                        <h5 class="text-lg font-semibold">مزودو الدومينات</h5>
                        <a href="{{ route('dashboard.domain_providers.create') }}" class="btn btn-primary">
                            إضافة مزود جديد
                        </a>
                    </div>
                </div>

                <div class="p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">خيارات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($providers as $provider)
                                <tr data-provider-row="{{ $provider->id }}">
                                    <td class="px-4 py-2">
                                        <div class="flex flex-col">
                                            <span>{{ $provider->name }}</span>
                                            <small class="text-gray-500 truncate max-w-[380px]"
                                                title="{{ $provider->endpoint }}">
                                                {{ $provider->endpoint }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 capitalize">
                                        {{ $provider->type }}
                                        @php
                                            $modeHint = null;
                                            if ($provider->endpoint) {
                                                $ep = $provider->endpoint;
                                                $modeHint =
                                                    str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')
                                                        ? 'test'
                                                        : 'live';
                                            } elseif (!empty($provider->mode)) {
                                                $modeHint = $provider->mode;
                                            }
                                        @endphp
                                        @if ($modeHint)
                                            <span
                                                class="badge bg-blue-100 text-blue-700 ms-2">{{ $modeHint }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($provider->is_active)
                                            <span class="badge bg-green-100 text-green-700">مفعل</span>
                                        @else
                                            <span class="badge bg-red-100 text-red-700">معطل</span>
                                        @endif
                                    </td>

                                    {{-- الرصيد (يُعبّأ عبر اختبار الاتصال) --}}
                                    <td class="px-4 py-2">
                                        <span class="balance" data-balance-for="{{ $provider->id }}">—</span>
                                    </td>

                                    <td class="px-4 py-2">
                                        <a href="{{ route('dashboard.domain_providers.edit', $provider) }}"
                                            class="btn btn-sm btn-secondary">تعديل</a>

                                        <form action="{{ route('dashboard.domain_providers.destroy', $provider) }}"
                                            method="POST" style="display:inline-block"
                                            onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                                        </form>

                                        <a href="{{ route('dashboard.domain_providers.test-connection', $provider) }}"
                                            class="btn btn-sm btn-info"
                                            onclick="event.preventDefault(); testConnection(this.href, {{ $provider->id }}, this);">
                                            اختبار الاتصال
                                        </a>

                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="refreshBalance('{{ route('dashboard.domain_providers.test-connection', $provider) }}', {{ $provider->id }}, this);">
                                            تحديث الرصيد
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-6">لا يوجد مزودون لعرضهم.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <script>
        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                },
                ...options
            });
            const text = await res.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch {}
            return {
                ok: res.ok,
                status: res.status,
                data,
                text
            };
        }

        function setBtnLoading(btn, loading) {
            if (!btn) return;
            if (loading) {
                btn.dataset.origText = btn.innerText;
                btn.innerText = '...';
                btn.disabled = true;
            } else {
                btn.innerText = btn.dataset.origText || btn.innerText;
                btn.disabled = false;
            }
        }

        function formatNumber(n) {
            if (typeof n !== 'number') return n;
            try {
                return new Intl.NumberFormat('en-US', {
                    maximumFractionDigits: 2
                }).format(n);
            } catch {
                return n.toFixed(2);
            }
        }

        function qs(url, extra) {
            const sep = url.includes('?') ? '&' : '?';
            return url + sep + extra;
        }

        function applyBalance(providerId, payload) {
            const cell = document.querySelector(`.balance[data-balance-for="${providerId}"]`);
            if (!cell) return;

            const ok = !!(payload && payload.ok);
            const val = (payload && payload.balance != null && payload.balance !== '') ? (typeof payload.balance ===
                'string' ? parseFloat(payload.balance) : payload.balance) : null;
            const cur = (payload && payload.currency) ? ` ${payload.currency}` : '';
            const dur = (payload && payload.duration_ms) ? ` • ${payload.duration_ms}ms` : '';
            const ts = (payload && payload.fetched_at) ? ` • ${new Date(payload.fetched_at).toLocaleString()}` : '';
            const why = (!ok && payload && payload.reason) ? ` [${payload.reason}]` : '';

            if (ok && val !== null) {
                cell.textContent = `${formatNumber(val)}${cur}`;
                cell.title = (payload.message || 'تم الاتصال بنجاح.') + dur + ts;
                cell.classList.add('text-green-700');
                cell.classList.remove('text-red-600');
            } else {
                cell.textContent = '—';
                cell.title = (payload && payload.message ? (payload.message + why) : 'تعذّر جلب الرصيد.') + dur + ts;
                cell.classList.remove('text-green-700');
                cell.classList.add('text-red-600');
            }
        }

        async function testConnection(url, providerId, btn) {
            setBtnLoading(btn, true);
            try {
                const freshUrl = qs(url, `fresh=1&t=${Date.now()}`); // 👈 إجبار جلب حديث
                const {
                    status,
                    data,
                    text
                } = await fetchJSON(freshUrl);
                if (!data) {
                    console.error('Non-JSON response:', text?.slice(0, 400));
                    alert('❌ الاستجابة ليست JSON.');
                    return;
                }
                applyBalance(providerId, data);
                const msg = data.ok ?
                    `✅ تم الاتصال بنجاح.${(data.balance!=null)?`\nالرصيد: ${data.balance} ${data.currency||''}`:''}` :
                    `❌ فشل الاتصال (${status})${data.reason?` [${data.reason}]`:''}: ${data.message || 'تعذّر الاتصال.'}`;
                const extra = data.duration_ms ? `\nالمدة: ${data.duration_ms}ms` : '';
                const when = data.fetched_at ? `\nالتحديث: ${new Date(data.fetched_at).toLocaleString()}` : '';
                alert(msg + extra + when);
            } finally {
                setBtnLoading(btn, false);
            }
        }

        async function refreshBalance(url, providerId, btn) {
            setBtnLoading(btn, true);
            try {
                const freshUrl = qs(url, `fresh=1&t=${Date.now()}`); // 👈 تحديث إجباري
                const {
                    data
                } = await fetchJSON(freshUrl);
                applyBalance(providerId, data || {
                    ok: false,
                    message: 'الاستجابة ليست JSON.'
                });
            } finally {
                setBtnLoading(btn, false);
            }
        }

        async function runWithConcurrency(tasks, limit = 3) {
            const results = [];
            let i = 0;
            const workers = Array.from({
                length: Math.max(1, limit)
            }).map(async () => {
                while (i < tasks.length) {
                    const idx = i++;
                    try {
                        results[idx] = await tasks[idx]();
                    } catch (e) {
                        results[idx] = e;
                    }
                }
            });
            await Promise.all(workers);
            return results;
        }

        // SWR عند التحميل: قيمة سريعة ثم إعادة تحقق بالخلفية
        document.addEventListener('DOMContentLoaded', () => {
            const rows = [...document.querySelectorAll('tr[data-provider-row]')];
            const tasks = rows.map(row => {
                const id = row.getAttribute('data-provider-row');
                const link = row.querySelector('a.btn-info');
                if (!link) return async () => {};

                return async () => {
                    // 1) قراءة سريعة (بدون fresh) لعرض أي قيمة متاحة فورًا
                    const fastUrl = qs(link.href, `t=${Date.now()}`);
                    const fast = await fetchJSON(fastUrl);
                    if (fast.data) applyBalance(id, fast.data);

                    // 2) إعادة تحقق حديثة بالخلفية (fresh=1)
                    const freshUrl = qs(link.href, `fresh=1&t=${Date.now()}`);
                    const fresh = await fetchJSON(freshUrl);
                    if (fresh.data) applyBalance(id, fresh.data);
                };
            });

            runWithConcurrency(tasks, 3);
        });
    </script>
</x-dashboard-layout>
