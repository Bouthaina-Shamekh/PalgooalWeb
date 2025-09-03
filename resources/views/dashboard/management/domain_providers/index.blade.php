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
                                            <small class="text-gray-500 truncate max-w-[380px]" title="{{ $provider->endpoint }}">
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
                                                $modeHint = (str_contains($ep, 'sandbox') || str_contains($ep, 'resellertest')) ? 'test' : 'live';
                                            } elseif (!empty($provider->mode)) {
                                                $modeHint = $provider->mode;
                                            }
                                        @endphp
                                        @if ($modeHint)
                                            <span class="badge bg-blue-100 text-blue-700 ms-2">{{ $modeHint }}</span>
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

        function applyBalance(providerId, data) {
            const cell = document.querySelector(`.balance[data-balance-for="${providerId}"]`);
            if (!cell) return;

            if (data.ok) {
                // Namecheap يعيد available/account حسب الكلاينت، Enom يعيد balance
                const value = (typeof data.balance !== 'undefined' && data.balance !== null)
                    ? data.balance
                    : (typeof data.available !== 'undefined' ? data.available : '—');

                cell.textContent = value !== null ? value : '—';
                cell.classList.remove('text-red-600');
                cell.classList.add('text-green-700');
            } else {
                cell.textContent = '—';
                cell.classList.remove('text-green-700');
                cell.classList.add('text-red-600');
            }
        }

        function testConnection(url, providerId, btn) {
            setBtnLoading(btn, true);
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    applyBalance(providerId, data);

                    let msg = '';
                    if (data.ok) {
                        msg = `✅ تم الاتصال بنجاح.`;
                        if (typeof data.balance !== 'undefined' && data.balance !== null) {
                            msg += `\nالرصيد: ${data.balance}`;
                        } else if (typeof data.available !== 'undefined') {
                            msg += `\nالرصيد: ${data.available}`;
                        }
                    } else {
                        msg = `❌ فشل الاتصال: ${data.message || 'خطأ غير معروف.'}\nاطّلع على السجلات للمزيد.`;
                    }
                    alert(msg);
                })
                .catch(() => alert('خطأ في الاتصال'))
                .finally(() => setBtnLoading(btn, false));
        }

        function refreshBalance(url, providerId, btn) {
            setBtnLoading(btn, true);
            fetch(url)
                .then(res => res.json())
                .then(data => applyBalance(providerId, data))
                .catch(() => {})
                .finally(() => setBtnLoading(btn, false));
        }
    </script>
</x-dashboard-layout>
