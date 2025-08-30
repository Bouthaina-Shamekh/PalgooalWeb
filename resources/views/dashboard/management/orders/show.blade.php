<x-dashboard-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.orders.index') }}" class="text-blue-600 hover:underline">&larr; الرجوع لقائمة
                الطلبات</a>
        </div>
        @if (session('sync_result'))
            <div class="mb-4">
                <div class="rounded p-3 bg-blue-50 border border-blue-100 text-blue-800">
                    {!! session('sync_result') !!}
                </div>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- كارت بيانات الطلب -->
            <div class="md:col-span-2 bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-bold text-gray-800">تفاصيل الطلب</h2>
                        <div class="text-sm text-gray-600">#<span class="font-mono">{{ $order->order_number }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @php
                            $statusClass = 'bg-gray-200 text-gray-700';
                            if ($order->status === 'pending') {
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                            } elseif ($order->status === 'active') {
                                $statusClass = 'bg-green-100 text-green-800';
                            } elseif ($order->status === 'cancelled') {
                                $statusClass = 'bg-red-100 text-red-800';
                            }
                        @endphp
                        <span
                            class="px-3 py-1 rounded text-xs font-bold {{ $statusClass }}">{{ __($order->status) }}</span>

                        <form method="POST" action="{{ route('dashboard.orders.status', $order->id) }}"
                            class="inline-block">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                class="text-xs rounded border-gray-300">
                                <option value="pending" @selected($order->status == 'pending')>معلق</option>
                                <option value="active" @selected($order->status == 'active')>نشط</option>
                                <option value="cancelled" @selected($order->status == 'cancelled')>ملغي</option>
                                <option value="fraud" @selected($order->status == 'fraud')>احتيال</option>
                            </select>
                        </form>

                        <div class="relative inline-block">
                            <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none"
                                href="#" data-pc-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="sr-only">خيارات</span>
                                <i class="ti ti-dots-vertical text-lg leading-none"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end hidden origin-top-right absolute right-0 mt-2 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-2 z-50"
                                data-pc-dropdown role="menu" aria-hidden="true">
                                <form action="{{ route('dashboard.orders.status', $order->id) }}" method="POST"
                                    class="ajax-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M5 12l5 5L20 7"></path>
                                        </svg>
                                        تفعيل
                                    </button>
                                </form>
                                <form action="{{ route('dashboard.orders.status', $order->id) }}" method="POST"
                                    class="ajax-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M12 6v6l4 2"></path>
                                        </svg>
                                        وضع معلق
                                    </button>
                                </form>
                                <form action="{{ route('dashboard.orders.status', $order->id) }}" method="POST"
                                    class="ajax-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        إلغاء
                                    </button>
                                </form>
                                <form action="{{ route('dashboard.orders.status', $order->id) }}" method="POST"
                                    class="ajax-action">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="fraud">
                                    <button type="submit" role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3 7h7l-5.5 4L20 22l-8-5-8 5 2.5-9L1 9h7z"></path>
                                        </svg>
                                        إبلاغ احتيال
                                    </button>
                                </form>
                                <form action="{{ route('dashboard.orders.bulk') }}" method="POST" class="ajax-action"
                                    data-confirm="هل أنت متأكد من حذف هذا الطلب؟">
                                    @csrf
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="ids[]" value="{{ $order->id }}">
                                    <button type="submit" role="menuitem"
                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-red-600">
                                        <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"></path>
                                            <path d="M10 11v6M14 11v6"></path>
                                        </svg>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">رقم الطلب:</span> <span
                                class="font-mono">{{ $order->order_number }}</span></div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">النوع:</span>
                            {{ $order->type }}
                        </div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">تاريخ الإنشاء:</span>
                            {{ $order->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">ملاحظات:</span>
                            {{ $order->notes ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <!-- كارت بيانات العميل -->
            <div class="bg-gray-50 rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-3 text-gray-700 flex items-center gap-2"><svg
                        xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg> بيانات العميل</h3>
                <div class="mb-2"><span class="font-semibold text-gray-600">الاسم:</span>
                    {{ $order->client->first_name ?? '-' }}</div>
                <div class="mb-2"><span class="font-semibold text-gray-600">البريد الإلكتروني:</span>
                    {{ $order->client->email ?? '-' }}</div>
                <div class="mb-2"><span class="font-semibold text-gray-600">رقم الجوال:</span>
                    {{ $order->client->phone ?? '-' }}</div>
            </div>
        </div>
        <!-- جدول الفواتير المرتبطة -->
        <div class="mt-10 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-700 flex items-center gap-2"><svg
                    xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 14l2-2 4 4m0 0V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5" />
                </svg> الفواتير المرتبطة</h3>
            @if ($order->invoices && $order->invoices->count())
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">رقم الفاتورة</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الحالة</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الإجمالي</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">تاريخ الاستحقاق</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach ($order->invoices as $invoice)
                            <tr>
                                <td class="px-4 py-2 font-mono text-blue-700">
                                    <a href="{{ route('dashboard.invoices.show', $invoice->id) }}"
                                        class="hover:underline">{{ $invoice->number }}</a>
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold {{ $invoice->status == 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status == 'unpaid' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-700') }}">
                                        {{ __($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">${{ number_format($invoice->total_cents / 100, 2) }}
                                    {{ $invoice->currency }}</td>
                                <td class="px-4 py-2">
                                    {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="bg-gray-50 px-4 py-2">
                                    <div class="mt-2 mb-2">
                                        <h4 class="font-bold text-gray-700 mb-2">بنود الفاتورة:</h4>
                                        @if ($invoice->items && $invoice->items->count())
                                            <table class="min-w-full divide-y divide-gray-200 mb-2">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th
                                                            class="px-2 py-1 text-right text-xs font-bold text-gray-600">
                                                            الوصف</th>
                                                        <th
                                                            class="px-2 py-1 text-right text-xs font-bold text-gray-600">
                                                            الكمية</th>
                                                        <th
                                                            class="px-2 py-1 text-right text-xs font-bold text-gray-600">
                                                            سعر الوحدة</th>
                                                        <th
                                                            class="px-2 py-1 text-right text-xs font-bold text-gray-600">
                                                            الإجمالي</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    @foreach ($invoice->items as $item)
                                                        <tr>
                                                            <td class="px-2 py-1">{{ $item->description }}</td>
                                                            <td class="px-2 py-1">{{ $item->qty }}</td>
                                                            <td class="px-2 py-1">
                                                                ${{ number_format($item->unit_price_cents / 100, 2) }}
                                                            </td>
                                                            <td class="px-2 py-1">
                                                                ${{ number_format($item->total_cents / 100, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="text-gray-400">لا توجد بنود لهذه الفاتورة.</div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-gray-400">لا توجد فواتير مرتبطة بهذا الطلب.</div>
            @endif
        </div>
    </div>
</x-dashboard-layout>

<script>
    // dropdowns for order show page
    document.addEventListener('click', function(e) {
        document.querySelectorAll('[data-pc-dropdown]').forEach(function(el) {
            if (!el.contains(e.target) && !el.previousElementSibling?.contains(e.target)) {
                el.classList.add('hidden');
            }
        });
    });
    document.querySelectorAll('[data-pc-toggle="dropdown"]').forEach(function(btn) {
        btn.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            const menu = btn.parentElement.querySelector('[data-pc-dropdown]');
            if (menu) {
                const isHidden = menu.classList.contains('hidden');
                document.querySelectorAll('[data-pc-dropdown]').forEach(function(el) {
                    el.classList.add('hidden');
                    el.setAttribute('aria-hidden', 'true');
                    const tb = el.parentElement.querySelector('[data-pc-toggle="dropdown"]');
                    if (tb) tb.setAttribute('aria-expanded', 'false');
                });
                if (isHidden) {
                    menu.classList.remove('hidden');
                    menu.setAttribute('aria-hidden', 'false');
                    btn.setAttribute('aria-expanded', 'true');
                } else {
                    menu.classList.add('hidden');
                    menu.setAttribute('aria-hidden', 'true');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-pc-dropdown]').forEach(function(el) {
                el.classList.add('hidden');
                el.setAttribute('aria-hidden', 'true');
                const tb = el.parentElement.querySelector('[data-pc-toggle="dropdown"]');
                if (tb) tb.setAttribute('aria-expanded', 'false');
            });
        }
    });
</script>

<script>
    // AJAX handler for header actions on order show page
    document.querySelectorAll('form.ajax-action').forEach(function(form) {
        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !confirm(confirmMsg)) return;
            const url = form.action;
            const method = (form.querySelector('input[name="_method"]') || {}).value || form.method ||
                'POST';
            const formData = new FormData(form);
            fetch(url, {
                method: method.toUpperCase(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || ''
                },
                body: formData
            }).then(async function(res) {
                if (res.ok) {
                    // if delete: redirect to index
                    if (form.querySelector('input[name="action"]') && form.querySelector(
                            'input[name="action"]').value === 'delete') {
                        alert('تم الحذف');
                        window.location = '{{ route('dashboard.orders.index') }}';
                        return;
                    }
                    // if status change: update badge and show message
                    const statusInput = form.querySelector('input[name="status"]');
                    if (statusInput) {
                        const newStatus = statusInput.value;
                        const badge = document.querySelector(
                            '.px-3.py-1.rounded.text-xs.font-bold');
                        if (badge) {
                            const mapping = {
                                pending: 'bg-yellow-100 text-yellow-800',
                                active: 'bg-green-100 text-green-800',
                                cancelled: 'bg-red-100 text-red-800',
                                fraud: 'bg-gray-200 text-gray-700'
                            };
                            badge.className = 'px-3 py-1 rounded text-xs font-bold ' + (
                                mapping[newStatus] || 'bg-gray-200 text-gray-700');
                            badge.innerText = newStatus;
                        }
                        alert('تم تحديث الحالة إلى: ' + newStatus);
                        return;
                    }
                    alert('تم تنفيذ الإجراء');
                } else {
                    form.submit();
                }
            }).catch(function() {
                form.submit();
            });
        });
    });
</script>
