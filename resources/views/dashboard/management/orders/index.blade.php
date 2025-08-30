<x-dashboard-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-800">قائمة الطلبات</h1>
            <form method="GET" class="flex items-center gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="بحث برقم الطلب أو اسم العميل"
                    class="rounded border px-3 py-2 text-sm" />
                <select name="status" class="rounded border px-2 py-2 text-sm">
                    <option value="">الكل</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلق</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    <option value="fraud" {{ request('status') == 'fraud' ? 'selected' : '' }}>احتيال</option>
                </select>
                <select name="type" class="rounded border px-2 py-2 text-sm">
                    <option value="">الكل</option>
                    <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>اشتراك
                    </option>
                    <option value="domain" {{ request('type') == 'domain' ? 'selected' : '' }}>دومين</option>
                </select>
                <select name="sort" class="rounded border px-2 py-2 text-sm">
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>الأحدث</option>
                    <option value="order_number" {{ request('sort') == 'order_number' ? 'selected' : '' }}>رقم الطلب
                    </option>
                </select>
                <select name="direction" class="rounded border px-2 py-2 text-sm">
                    <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>تنازلي</option>
                    <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>تصاعدي</option>
                </select>
                <button class="px-3 py-2 bg-blue-600 text-white rounded">تصفية</button>
            </form>
        </div>
        <div class="overflow-x-auto rounded-lg shadow bg-white">
            <div class="p-4">
                <form id="bulk_form" method="POST" action="{{ route('dashboard.orders.bulk') }}"
                    class="flex items-center gap-3">
                    @csrf
                    <select id="bulk_action" name="action" class="rounded border px-2 py-2 text-sm">
                        <option value="">اختر إجراء جماعي</option>
                        <option value="active">تفعيل</option>
                        <option value="pending">وضع معلق</option>
                        <option value="cancelled">إلغاء</option>
                        <option value="fraud">إبلاغ احتيال</option>
                        <option value="delete">حذف</option>
                    </select>
                    <button type="button" id="bulk_apply"
                        class="px-3 py-2 bg-blue-500 text-white rounded">تطبيق</button>
                </form>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">#</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">رقم الطلب</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">العميل</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">الحالة</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">النوع</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><input type="checkbox" class="row_checkbox"
                                    value="{{ $order->id }}" /></td>
                            <td class="px-4 py-2">{{ $order->id }}</td>
                            <td class="px-4 py-2 font-mono text-blue-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-2">{{ $order->client->first_name ?? '-' }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'active' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'fraud' => 'bg-gray-200 text-gray-700',
                                    ];
                                @endphp
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ __($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ $order->type }}</td>
                            <td class="px-4 py-2">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                <div class="relative inline-block">
                                    <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none"
                                        href="#" data-pc-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        <span class="sr-only">خيارات</span>
                                        <i class="ti ti-dots-vertical text-lg leading-none"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-2 z-50"
                                        data-pc-dropdown role="menu" aria-hidden="true">
                                        <a href="{{ route('dashboard.orders.show', $order->id) }}" role="menuitem"
                                            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8S2 12 2 12z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            عرض
                                        </a>
                                        <form action="{{ route('dashboard.orders.status', $order->id) }}"
                                            method="POST" class="ajax-action">
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
                                        <form action="{{ route('dashboard.orders.status', $order->id) }}"
                                            method="POST" class="ajax-action">
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
                                        <form action="{{ route('dashboard.orders.status', $order->id) }}"
                                            method="POST" class="ajax-action">
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
                                        <form action="{{ route('dashboard.orders.status', $order->id) }}"
                                            method="POST" class="ajax-action">
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
                                        <form action="{{ route('dashboard.orders.bulk') }}" method="POST"
                                            class="ajax-action" data-confirm="هل أنت متأكد من حذف هذا الطلب؟">
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-400">لا توجد طلبات حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
</x-dashboard-layout>

<script>
    // select all
    const selectAllOrders = document.getElementById('select_all');
    if (selectAllOrders) {
        selectAllOrders.addEventListener('change', function() {
            document.querySelectorAll('.row_checkbox').forEach(cb => cb.checked = selectAllOrders.checked);
        });
    }

    // bulk apply
    const bulkApplyBtn = document.getElementById('bulk_apply');
    if (bulkApplyBtn) {
        bulkApplyBtn.addEventListener('click', function() {
            const action = document.getElementById('bulk_action').value;
            if (!action) {
                alert('اختر إجراءً أولاً');
                return;
            }
            const checked = Array.from(document.querySelectorAll('.row_checkbox:checked')).map(cb => cb.value);
            if (checked.length === 0) {
                alert('اختر طلباً واحداً على الأقل');
                return;
            }
            const form = document.getElementById('bulk_form');
            // remove any previous ids inputs
            form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
            checked.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                form.appendChild(inp);
            });
            if (confirm('سيتم تنفيذ الإجراء على ' + checked.length + ' طلباً. متابعة؟')) {
                form.submit();
            }
        });
    }
</script>

<script>
    // AJAX handler for inline actions (orders)
    document.querySelectorAll('form.ajax-action').forEach(function(form) {
        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !confirm(confirmMsg)) return;
            const url = form.action;
            const method = (form.querySelector('input[name="_method"]') || {}).value || form.method ||
                'POST';
            const formData = new FormData(form);
            // send fetch
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
                    // try json
                    let data = null;
                    try {
                        data = await res.json();
                    } catch (e) {
                        /* not json */ }
                    // if delete action, remove row
                    if (form.querySelector('input[name="action"]') && form.querySelector(
                            'input[name="action"]').value === 'delete') {
                        const row = form.closest('tr');
                        if (row) row.remove();
                        alert('تم الحذف');
                        return;
                    }
                    // if status change, update badge in same row
                    const statusInput = form.querySelector('input[name="status"]');
                    if (statusInput) {
                        const newStatus = statusInput.value;
                        const row = form.closest('tr');
                        if (row) {
                            const badge = row.querySelector(
                                'span[class*="px-2"][class*="rounded"]');
                            if (badge) {
                                const mapping = {
                                    pending: 'bg-yellow-100 text-yellow-800',
                                    active: 'bg-green-100 text-green-800',
                                    cancelled: 'bg-red-100 text-red-800',
                                    fraud: 'bg-gray-200 text-gray-700'
                                };
                                badge.className = 'px-2 py-1 rounded text-xs font-bold ' + (
                                    mapping[newStatus] || 'bg-gray-100 text-gray-700');
                                badge.innerText = newStatus;
                            }
                        }
                        alert('تم تحديث الحالة إلى: ' + newStatus);
                        return;
                    }
                    // fallback
                    alert('تم تنفيذ الإجراء');
                } else {
                    // non-ok: fallback to full submit
                    form.submit();
                }
            }).catch(function() {
                // on network error, fallback
                form.submit();
            });
        });
    });
</script>

<script>
    // dropdowns: similar behavior to subscriptions index
    document.addEventListener('click', function(e) {
        // close other open dropdowns when clicking outside
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
                // close others
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
                    // focus first actionable element
                    const first = menu.querySelector('[role="menuitem"]');
                    if (first) first.focus();
                } else {
                    menu.classList.add('hidden');
                    menu.setAttribute('aria-hidden', 'true');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });
    // close on escape
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
