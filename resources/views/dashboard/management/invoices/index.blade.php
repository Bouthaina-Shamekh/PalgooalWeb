<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">الفواتير</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">قائمة الفواتير</h2>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                @if (session('ok'))
                    <div class="alert alert-success mb-4">{{ session('ok') }}</div>
                @endif
                @if (session('connection_result'))
                    <div class="alert alert-info mb-4">{!! session('connection_result') !!}</div>
                @endif
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">قائمة الفواتير</h5>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard.invoices.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus"></i> إضافة فاتورة جديدة
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="mb-4 flex items-center justify-between">
                        <form method="GET" class="flex items-center gap-2">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="بحث برقم الفاتورة أو اسم العميل"
                                class="rounded border px-3 py-2 text-sm" />
                            <select name="status" class="rounded border px-2 py-2 text-sm">
                                <option value="">الكل</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>غير مدفوعة
                                </option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>مدفوعة</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة
                                </option>
                            </select>
                            <button class="px-3 py-2 bg-blue-600 text-white rounded text-sm">تصفية</button>
                        </form>
                        <form id="bulk_form_invoices" method="POST"
                            action="{{ route('dashboard.invoices.bulk') ?? '#' }}" class="flex items-center gap-2">
                            @csrf
                            <select id="bulk_action_invoices" name="action" class="rounded border px-2 py-2 text-sm">
                                <option value="">اختر إجراء جماعي</option>
                                <option value="delete">حذف</option>
                            </select>
                            <button type="button" id="bulk_apply_invoices"
                                class="px-3 py-2 bg-blue-500 text-white rounded text-sm">تطبيق</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select_all_invoices" /></th>
                                    <th>#</th>
                                    <th>رقم</th>
                                    <th>العميل</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="px-2 py-2"><input type="checkbox" class="invoice_checkbox"
                                                value="{{ $invoice->id }}" /></td>
                                        <td>{{ $invoice->id }}</td>
                                        <td><a href="{{ route('dashboard.invoices.show', $invoice->id) }}"
                                                class="font-mono text-blue-700 hover:underline">{{ $invoice->number }}</a>
                                        </td>
                                        <td>{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</td>
                                        <td>{{ number_format($invoice->total_cents / 100, 2) }}
                                            {{ $invoice->currency }}</td>
                                        <td>
                                            @php
                                                $cls = 'bg-gray-100 text-gray-700';
                                                if ($invoice->status == 'paid') {
                                                    $cls = 'bg-green-100 text-green-800';
                                                }
                                                if ($invoice->status == 'unpaid') {
                                                    $cls = 'bg-yellow-100 text-yellow-800';
                                                }
                                            @endphp
                                            <span
                                                class="px-2 py-1 rounded text-xs font-bold {{ $cls }}">{{ __($invoice->status) }}</span>
                                        </td>
                                        <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="relative inline-block">
                                                <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none"
                                                    href="#" data-pc-toggle="dropdown">
                                                    <span class="sr-only">خيارات</span>
                                                    <i class="ti ti-dots-vertical text-lg leading-none"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-2 z-50"
                                                    data-pc-dropdown role="menu" aria-hidden="true">
                                                    <a href="{{ route('dashboard.invoices.show', $invoice->id) }}"
                                                        role="menuitem"
                                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8S2 12 2 12z">
                                                            </path>
                                                            <circle cx="12" cy="12" r="3"></circle>
                                                        </svg>
                                                        عرض
                                                    </a>
                                                    <a href="{{ route('dashboard.invoices.edit', $invoice) }}"
                                                        role="menuitem"
                                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z">
                                                            </path>
                                                        </svg>
                                                        تعديل
                                                    </a>
                                                    <form action="{{ route('dashboard.invoices.destroy', $invoice) }}"
                                                        method="POST" class="inline ajax-action"
                                                        data-confirm="هل أنت متأكد من حذف هذه الفاتورة؟">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" role="menuitem"
                                                            class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 text-sm text-red-600">
                                                            <svg class="w-4 h-4 text-red-600 inline-block ml-2"
                                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <path
                                                                    d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6">
                                                                </path>
                                                                <path d="M10 11v6M14 11v6"></path>
                                                            </svg>
                                                            حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination mt-4">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>

<script>
    // dropdowns
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
            if (!menu) return;
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('[data-pc-dropdown]').forEach(function(el) {
                el.classList.add('hidden');
                el.setAttribute('aria-hidden', 'true');
            });
            if (isHidden) {
                menu.classList.remove('hidden');
                menu.setAttribute('aria-hidden', 'false');
            } else {
                menu.classList.add('hidden');
                menu.setAttribute('aria-hidden', 'true');
            }
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.querySelectorAll('[data-pc-dropdown]').forEach(el => el.classList.add(
            'hidden'));
    });

    // select all
    const selectAllInv = document.getElementById('select_all_invoices');
    if (selectAllInv) selectAllInv.addEventListener('change', function() {
        document.querySelectorAll('.invoice_checkbox').forEach(cb => cb.checked = selectAllInv.checked);
    });

    // bulk apply
    const bulkApplyInv = document.getElementById('bulk_apply_invoices');
    if (bulkApplyInv) bulkApplyInv.addEventListener('click', function() {
        const action = document.getElementById('bulk_action_invoices').value;
        if (!action) {
            alert('اختر إجراءً أولاً');
            return;
        }
        const checked = Array.from(document.querySelectorAll('.invoice_checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('اختر فاتورة واحدة على الأقل');
            return;
        }
        const form = document.getElementById('bulk_form_invoices');
        form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
        checked.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'ids[]';
            inp.value = id;
            form.appendChild(inp);
        });
        if (confirm('سيتم تنفيذ الإجراء على ' + checked.length + ' فاتورة. متابعة؟')) form.submit();
    });

    // AJAX handler for inline actions (delete)
    document.querySelectorAll('form.ajax-action').forEach(function(form) {
        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !confirm(confirmMsg)) return;
            const url = form.action;
            const formData = new FormData(form);
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || ''
                    },
                    body: formData
                })
                .then(res => {
                    if (res.ok) {
                        const row = form.closest('tr');
                        if (row) row.remove();
                        alert('تم الحذف');
                    } else form.submit();
                })
                .catch(() => form.submit());
        });
    });
</script>
