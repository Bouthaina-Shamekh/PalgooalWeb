<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">الاشتراكات</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">قائمة الاشتراكات</h2>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-visible">
                @if (session('ok'))
                    <div class="alert alert-success mb-4">{{ session('ok') }}</div>
                @endif
                @if (session('connection_result'))
                    <div class="alert alert-info mb-4">{!! session('connection_result') !!}</div>
                @endif
                <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <h5 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-0">قائمة الاشتراكات
                            </h5>
                            <span class="text-sm text-gray-500">إجمالي:
                                {{ $subscriptions->total() ?? $subscriptions->count() }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="GET" class="flex items-center gap-2">
                                <input type="search" name="q" value="{{ request('q') }}"
                                    class="block w-56 rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200"
                                    placeholder="بحث بالعميل أو الدومين..." />
                                <input type="search" name="domain" value="{{ request('domain') }}"
                                    class="block w-48 rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200"
                                    placeholder="فلتر بالدومين (مثال: example.com)" />
                                <select name="status"
                                    class="rounded-md border border-gray-200 dark:border-gray-700 px-2 py-2 text-sm bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                                    <option value="">الكل</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط
                                    </option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلق
                                    </option>
                                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>
                                        موقوف</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                        ملغي</option>
                                </select>
                                <select name="sort"
                                    class="rounded-md border border-gray-200 px-2 py-2 text-sm bg-white">
                                    <option value="">ترتيب</option>
                                    <option value="domain_name"
                                        {{ request('sort') == 'domain_name' ? 'selected' : '' }}>الدومين</option>
                                    <option value="starts_at" {{ request('sort') == 'starts_at' ? 'selected' : '' }}>
                                        تاريخ البدء</option>
                                </select>
                                <select name="direction"
                                    class="rounded-md border border-gray-200 px-2 py-2 text-sm bg-white">
                                    <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>تصاعدي
                                    </option>
                                    <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>
                                        تنازلي</option>
                                </select>
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">بحث</button>
                            </form>
                            <a href="{{ route('dashboard.subscriptions.create') }}"
                                class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                <svg class="w-4 h-4 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14M5 12h14"></path>
                                </svg>
                                <span>إضافة اشتراك جديد</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <form id="bulk_form" method="POST" action="{{ route('dashboard.subscriptions.bulk') }}"
                            class="flex items-center gap-3">
                            @csrf
                            <input type="hidden" name="ids[]" id="bulk_ids" />
                            <div
                                class="flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md px-3 py-2 shadow-sm">
                                <label for="bulk_action" class="sr-only">إجراء جماعي</label>
                                <select name="action" id="bulk_action"
                                    class="rounded-md border-none px-2 py-1 text-sm bg-transparent">
                                    <option value="">اختر إجراء جماعي</option>
                                    <option value="suspend">تعليق</option>
                                    <option value="unsuspend">إلغاء التعليق</option>
                                    <option value="sync">مزامنة</option>
                                    <option value="terminate">حذف نهائي</option>
                                    <option value="delete">حذف</option>
                                </select>
                                <button type="button" id="bulk_apply"
                                    class="inline-flex items-center px-3 py-2 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-700 ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                    تطبيق
                                </button>
                            </div>
                        </form>
                        <div></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select_all" />
                                    </th>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        العميل</th>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الخطة</th>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الدومين</th>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        الحالة</th>
                                    <th scope="col"
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        خيارات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($subscriptions as $sub)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            <input type="checkbox" class="row_checkbox" name="ids[]"
                                                value="{{ $sub->id }}" />
                                        </td>

                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $sub->client->first_name ?? '' }} {{ $sub->client->last_name ?? '' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $sub->plan->name ?? '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            @if ($sub->domain_name)
                                                <div class="flex items-center gap-2">
                                                    @php
                                                        $link = \Illuminate\Support\Str::startsWith($sub->domain_name, [
                                                            'http://',
                                                            'https://',
                                                        ])
                                                            ? $sub->domain_name
                                                            : 'http://' . $sub->domain_name;
                                                    @endphp
                                                    <a href="{{ $link }}" target="_blank" rel="noopener"
                                                        class="text-blue-600 hover:underline">{{ $sub->domain_name }}</a>
                                                    <button type="button" data-copy-domain="{{ $sub->domain_name }}"
                                                        class="text-sm text-gray-500 hover:text-gray-800">نسخ</button>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td class="px-4 py-2">
                                            @if ($sub->status == 'active')
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">نشط</span>
                                            @elseif($sub->status == 'pending')
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">معلق</span>
                                            @elseif($sub->status == 'suspended')
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">موقوف</span>
                                            @elseif($sub->status == 'cancelled')
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ملغي</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ __($sub->status) }}</span>
                                            @endif
                                        </td>
                                        <td
                                            class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                            {{-- <div class="dropdown drp-show">
                    <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none" href="#" data-pc-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="ti ti-dots-vertical text-lg leading-none"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" data-popper-placement="bottom-end" style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(0px, 32px, 0px);">
                      <a class="dropdown-item" href="#">Today</a>
                      <a class="dropdown-item" href="#">Weekly</a>
                      <a class="dropdown-item" href="#">Monthly</a>
                    </div>
                  </div> --}}
                                            <div class="relative inline-block">
                                                <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none"
                                                    href="#" data-pc-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    <span class="sr-only">خيارات</span>
                                                    <i class="ti ti-dots-vertical text-lg leading-none"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-900 ring-1 ring-black ring-opacity-5 p-2 z-50"
                                                    data-pc-dropdown role="menu" aria-hidden="true">
                                                    <a href="{{ route('dashboard.subscriptions.edit', $sub) }}"
                                                        role="menuitem"
                                                        class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm text-gray-700">
                                                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z">
                                                            </path>
                                                        </svg>
                                                        تعديل
                                                    </a>
                                                    <form
                                                        action="{{ route('dashboard.subscriptions.destroy', $sub) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" role="menuitem"
                                                            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm text-gray-700">
                                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <path
                                                                    d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6M10 6V4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2">
                                                                </path>
                                                            </svg>
                                                            حذف
                                                        </button>
                                                    </form>
                                                    @if ($sub->status == 'active')
                                                        <form
                                                            action="{{ route('dashboard.subscriptions.suspend', $sub) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit" role="menuitem"
                                                                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm text-gray-700">
                                                                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                                    fill="none" stroke="currentColor"
                                                                    stroke-width="2">
                                                                    <path d="M10 6h4v12h-4z"></path>
                                                                </svg>
                                                                تعليق
                                                            </button>
                                                        </form>
                                                    @elseif($sub->status == 'suspended')
                                                        <form
                                                            action="{{ route('dashboard.subscriptions.unsuspend', $sub) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit" role="menuitem"
                                                                class="w-full text-left px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm">
                                                                <svg class="w-4 h-4 text-gray-500 inline-block ml-2"
                                                                    viewBox="0 0 24 24" fill="none"
                                                                    stroke="currentColor" stroke-width="2">
                                                                    <path
                                                                        d="M21 12v.01M3 12v.01M6.2 6.2l.01.01M17.8 17.8l.01.01M6.2 17.8l.01.01M17.8 6.2l.01.01">
                                                                    </path>
                                                                </svg>
                                                                إلغاء التعليق
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('dashboard.subscriptions.sync', $sub) }}"
                                                        method="POST">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm text-gray-700">
                                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <path d="M21 12a9 9 0 1 0-3.2 6.6L21 12z"></path>
                                                                <path d="M21 3v6h-6"></path>
                                                            </svg>
                                                            مزامنة
                                                        </button>
                                                    </form>
                                                    <form
                                                        action="{{ route('dashboard.subscriptions.terminate', $sub) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('سيتم حذف الموقع من السيرفر نهائيًا. هل أنت متأكد؟')">
                                                        @csrf
                                                        <button type="submit" role="menuitem"
                                                            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 text-sm text-red-600">
                                                            <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor"
                                                                stroke-width="2">
                                                                <path
                                                                    d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6">
                                                                </path>
                                                                <path d="M10 11v6M14 11v6"></path>
                                                            </svg>
                                                            حذف نهائي
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-6">لا توجد اشتراكات
                                            لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $subscriptions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
<script>
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

    // select all
    const selectAll = document.getElementById('select_all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row_checkbox').forEach(cb => cb.checked = selectAll.checked);
        });
    }

    // copy domain buttons
    document.querySelectorAll('[data-copy-domain]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const val = btn.getAttribute('data-copy-domain');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(val);
            } else {
                const ta = document.createElement('textarea');
                ta.value = val;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            btn.innerText = 'تم النسخ';
            setTimeout(() => btn.innerText = 'نسخ', 1500);
        });
    });

    // bulk apply
    const bulkApply = document.getElementById('bulk_apply');
    if (bulkApply) {
        bulkApply.addEventListener('click', function() {
            const action = document.getElementById('bulk_action').value;
            if (!action) {
                alert('اختر إجراءً أولاً');
                return;
            }
            const checked = Array.from(document.querySelectorAll('.row_checkbox:checked')).map(cb => cb.value);
            if (checked.length === 0) {
                alert('اختر اشتراك واحد على الأقل');
                return;
            }
            // create hidden inputs
            const form = document.getElementById('bulk_form');
            // remove any previous inputs
            form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
            checked.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                form.appendChild(inp);
            });
            if (confirm('سيتم تنفيذ الإجراء على ' + checked.length + ' اشتراكاً. متابعة؟')) {
                form.submit();
            }
        });
    }
</script>
