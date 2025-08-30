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
                        <a href="{{ route('dashboard.domain_providers.create') }}" class="btn btn-primary">إضافة مزود
                            جديد</a>
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
                                <tr>
                                    <td class="px-4 py-2">{{ $provider->name }}</td>
                                    <td class="px-4 py-2">{{ $provider->type }}</td>
                                    <td class="px-4 py-2">
                                        @if ($provider->is_active)
                                            <span class="badge bg-green-100 text-green-700">مفعل</span>
                                        @else
                                            <span class="badge bg-red-100 text-red-700">معطل</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @php
                                            try {
                                                $enomClient = new \App\Services\DomainProviders\EnomClient();
                                                $result = $enomClient->getBalance($provider);
                                                echo $result['ok'] ? $result['balance'] ?? '-' : '—';
                                            } catch (Exception $e) {
                                                echo '—';
                                            }
                                        @endphp
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
                                            onclick="event.preventDefault(); testConnection(this.href, this);">اختبار
                                            الاتصال</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-6">لا يوجد مزودون لعرضهم.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        function testConnection(url, btn) {
            btn.disabled = true;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    let msg = '';
                    if (data.ok) {
                        msg = `✅ تم الاتصال بنجاح.`;
                        if (data.balance !== undefined && data.balance !== null) {
                            msg += `\nالرصيد: ${data.balance}`;
                        }
                    } else {
                        msg = `❌ فشل الاتصال: ${data.message || 'خطأ غير معروف.'}`;
                        msg += "\nاطّلع على السجلات للمزيد.";
                    }
                    alert(msg);
                })
                .catch(() => alert('خطأ في الاتصال'))
                .finally(() => btn.disabled = false);
        }
    </script>
</x-dashboard-layout>
