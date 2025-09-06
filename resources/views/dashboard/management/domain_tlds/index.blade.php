<x-dashboard-layout>
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">أسعار الدومينات (TLD)</h2>
            <p class="mt-1 text-xs text-gray-500">إدارة المزامنة والتسعير والكتالوج للأجزاء الظاهرة فقط لتحسين الأداء.
            </p>
        </div>
        <nav class="text-xs text-gray-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1">
                <li><a class="text-indigo-600 hover:underline" href="#">الرئيسية</a></li>
                <li>/</li>
                <li class="text-gray-400">إدارة TLD</li>
            </ol>
        </nav>
    </div>

    <div class="space-y-8">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            {{-- رسائل --}}
            @if (session('ok'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700">
                    {{ session('ok') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            {{-- فلترة بالمزوّد (GET) --}}
            <form method="get" class="mb-6 flex flex-wrap items-end gap-4">
                <div class="w-48">
                    <label class="mb-1 block text-xs font-medium text-gray-600">المزوّد</label>
                    <select name="provider_id"
                        class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- الكل --</option>
                        @foreach ($providers as $p)
                            <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                ({{ $p->type }})</option>
                        @endforeach
                    </select>
                </div>
                <button
                    class="inline-flex items-center justify-center rounded-md bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">تصفية</button>
            </form>

            {{-- مزامنة من المزوّد (POST) --}}
            <form action="{{ route('dashboard.domain_tlds.sync') }}" method="post"
                class="mb-8 rounded-md border border-dashed border-gray-300 bg-gray-50/60 p-4">
                @csrf
                <div class="flex flex-wrap items-end gap-6">
                    <div class="w-52">
                        <label class="mb-1 block text-xs font-medium text-gray-600">مزامنة من المزوّد</label>
                        <select name="provider_id" required
                            class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @foreach ($providers as $p)
                                <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                    ({{ $p->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-64">
                        <label class="mb-1 block text-xs font-medium text-gray-600">TLDs (اختياري)</label>
                        <input name="tlds" placeholder="com,net,org" value="{{ old('tlds') }}"
                            class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-[11px] leading-relaxed text-gray-500">اتركه فارغًا لمزامنة العناصر المعلّمة
                            في الكتالوج فقط.</p>
                    </div>
                    <button
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">سحب
                        الأسعار</button>
                </div>
            </form>

            {{-- فورم تسعير تلقائي --}}
            <form action="{{ route('dashboard.domain_tlds.apply-pricing') }}" method="post"
                class="mb-10 rounded-lg bg-gradient-to-br from-indigo-50 to-white p-4 ring-1 ring-inset ring-indigo-100/60">
                @csrf
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">النطاق</label>
                        <select name="scope"
                            class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="page">الصفحة الحالية فقط</option>
                            <option value="provider" @selected($providerId)>كل صفوف المزود المصفّى</option>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">المزوّد (عند اختيار نطاق
                            المزود)</label>
                        <select name="provider_id"
                            class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">— اختر —</option>
                            @foreach ($providers as $p)
                                <option value="{{ $p->id }}" @selected($providerId == $p->id)>{{ $p->name }}
                                    ({{ $p->type }})</option>
                            @endforeach
                        </select>
                        <label class="mt-2 inline-flex items-center gap-2 text-[11px] font-medium text-gray-600">
                            <input type="checkbox" name="only_in_catalog" value="1"
                                class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>فقط المعلّمة في الكتالوج</span>
                        </label>
                    </div>
                    <div class="col-span-12 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">الأكشن</label>
                        <div class="flex flex-wrap gap-x-3 gap-y-2 rounded-md bg-white p-2 ring-1 ring-gray-200">
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                <input type="checkbox" name="actions[]" value="register" checked
                                    class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Register
                            </label>
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                <input type="checkbox" name="actions[]" value="renew" checked
                                    class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Renew
                            </label>
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700">
                                <input type="checkbox" name="actions[]" value="transfer" checked
                                    class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Transfer
                            </label>
                        </div>
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">المدة (سنوات)</label>
                        <input type="number" name="years" value="1" min="1" max="10"
                            class="block w-24 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="col-span-12 sm:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-gray-600">نمط التسعير</label>
                        <div class="flex flex-wrap items-center gap-4 rounded-md bg-white p-2 ring-1 ring-gray-200">
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                    type="radio" name="mode" value="percent" checked
                                    class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"> نسبة
                                %</label>
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                    type="radio" name="mode" value="fixed_margin"
                                    class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"> هامش ثابت
                                +</label>
                            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700"><input
                                    type="radio" name="mode" value="fixed_final"
                                    class="h-3.5 w-3.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"> سعر
                                نهائي =</label>
                            <input type="number" step="0.01" name="value" placeholder="القيمة" required
                                class="w-28 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">طريقة التقريب</label>
                        <select name="rounding"
                            class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="2dp">رقمين عشريين (2dp)</option>
                            <option value="99">إنهاء بـ .99</option>
                        </select>
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">خيارات</label>
                        <label
                            class="inline-flex items-center gap-2 rounded-md bg-white px-2 py-1 text-[11px] font-medium text-gray-700 ring-1 ring-gray-200">
                            <input type="checkbox" name="overwrite" value="1"
                                class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            الكتابة فوق أسعار البيع الموجودة
                        </label>
                    </div>
                    <div class="col-span-12 sm:col-span-2 flex items-end">
                        <button
                            class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">تطبيق
                            التسعير</button>
                    </div>
                </div>
                @foreach ($rows as $row)
                    <input type="hidden" name="visible_ids[]" value="{{ $row->id }}">
                @endforeach
            </form>

            {{-- حذف جماعي (اختياري) --}}
            <form id="bulkDeleteForm" action="{{ route('dashboard.domain_tlds.bulk-destroy') }}" method="POST"
                onsubmit="return confirm('هل تريد حذف العناصر المحددة؟');" class="mb-5">
                @csrf
                <button type="submit"
                    class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">حذف
                    المحدد</button>
            </form>

            {{-- حفظ الكتالوج + أسعار البيع (POST) للصفحة الحالية --}}
            <form action="{{ route('dashboard.domain_tlds.save-all') }}" method="post" class="mt-2">
                @csrf
                <input type="hidden" name="provider_id" value="{{ $providerId }}">

                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <button type="button" id="checkAll"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">تحديد
                        الكل</button>
                    <button type="button" id="uncheckAll"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">إلغاء
                        تحديد الكل</button>
                    <span class="text-[11px] text-gray-500">— يطبَّق على الكتالوج في الصفحة الحالية فقط.</span>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table id="tldsTable" class="min-w-full divide-y divide-gray-200 text-right text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold text-gray-600">
                            <tr>
                                <th class="px-2 py-2">
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" id="selectAllRows"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>تحديد</span>
                                    </label>
                                </th>
                                <th class="px-2 py-2">كتالوج</th>
                                <th class="px-2 py-2">TLD</th>
                                <th class="px-2 py-2">عملة</th>
                                <th class="px-2 py-2">تفعيل</th>
                                <th class="px-2 py-2">Register 1y (Cost)</th>
                                <th class="px-2 py-2">Register 1y (Sale)</th>
                                <th class="px-2 py-2">Renew 1y (Cost)</th>
                                <th class="px-2 py-2">Renew 1y (Sale)</th>
                                <th class="px-2 py-2">Transfer 1y (Cost)</th>
                                <th class="px-2 py-2">Transfer 1y (Sale)</th>
                                <th class="px-2 py-2">حذف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($rows as $row)
                                @php
                                    $reg = $row->prices->firstWhere('action', 'register');
                                    $ren = $row->prices->firstWhere('action', 'renew');
                                    $tra = $row->prices->firstWhere('action', 'transfer');
                                @endphp
                                <tr class="hover:bg-indigo-50/30" data-row-id="{{ $row->id }}">
                                    <td class="px-2 py-2">
                                        <input type="checkbox"
                                            class="row-check h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            value="{{ $row->id }}">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="checkbox"
                                            class="catalog-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            name="catalog[{{ $row->id }}]" value="1"
                                            @checked($row->in_catalog)>
                                        <input type="hidden" name="visible_ids[]" value="{{ $row->id }}">
                                    </td>
                                    <td class="whitespace-nowrap px-2 py-2 text-gray-800">.{{ $row->tld }}</td>
                                    <td class="px-2 py-2 text-xs text-gray-500">{{ $row->currency }}</td>
                                    <td class="px-2 py-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $row->enabled ? 'bg-green-100 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $row->enabled ? 'مفعل' : 'معطل' }}</span>
                                    </td>
                                    <td class="px-2 py-2 text-xs text-gray-700">{{ optional($reg)->cost ?? '—' }}</td>
                                    <td class="px-2 py-2">
                                        @if ($reg)
                                            <input name="items[{{ $reg->id }}][id]" type="hidden"
                                                value="{{ $reg->id }}">
                                            <input name="items[{{ $reg->id }}][sale]" type="number"
                                                step="0.01" min="0" value="{{ $reg->sale }}"
                                                class="w-24 rounded-md border border-gray-300 bg-white px-1.5 py-1 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-xs text-gray-700">{{ optional($ren)->cost ?? '—' }}</td>
                                    <td class="px-2 py-2">
                                        @if ($ren)
                                            <input name="items[{{ $ren->id }}][id]" type="hidden"
                                                value="{{ $ren->id }}">
                                            <input name="items[{{ $ren->id }}][sale]" type="number"
                                                step="0.01" min="0" value="{{ $ren->sale }}"
                                                class="w-24 rounded-md border border-gray-300 bg-white px-1.5 py-1 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-xs text-gray-700">{{ optional($tra)->cost ?? '—' }}</td>
                                    <td class="px-2 py-2">
                                        @if ($tra)
                                            <input name="items[{{ $tra->id }}][id]" type="hidden"
                                                value="{{ $tra->id }}">
                                            <input name="items[{{ $tra->id }}][sale]" type="number"
                                                step="0.01" min="0" value="{{ $tra->sale }}"
                                                class="w-24 rounded-md border border-gray-300 bg-white px-1.5 py-1 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2">
                                        <form action="{{ route('dashboard.domain_tlds.destroy', $row) }}"
                                            method="POST"
                                            onsubmit="return confirm('حذف .{{ $row->tld }} وجميع أسعاره؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-md bg-red-600 px-2.5 py-1 text-[11px] font-medium text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <button
                        class="inline-flex items-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">حفظ
                        الكتالوج وأسعار البيع</button>
                    <p class="text-[11px] text-gray-500">يحفظ (in_catalog) وأسعار البيع للصفحة الحالية فقط.</p>
                </div>

                <div class="mt-4">{{ $rows->withQueryString()->links() }}</div>
            </form>

            <div class="mt-6 rounded-md bg-gray-50 px-4 py-3 text-[11px] leading-relaxed text-gray-600">
                ملاحظة: يتم سحب الأسعار فقط لـ TLDs المختارة في الكتالوج أو المحددة يدويًا في حقل <strong
                    class="font-semibold text-gray-700">TLDs</strong> عند المزامنة.
            </div>
        </div>
    </div>

    {{-- أدوات بسيطة للواجهة --}}
    <script>
        document.getElementById('checkAll')?.addEventListener('click', () => {
            document.querySelectorAll('.catalog-checkbox').forEach(cb => cb.checked = true);
        });
        document.getElementById('uncheckAll')?.addEventListener('click', () => {
            document.querySelectorAll('.catalog-checkbox').forEach(cb => cb.checked = false);
        });

        const selectAllRows = document.getElementById('selectAllRows');
        selectAllRows?.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = selectAllRows.checked);
        });

        const bulkForm = document.getElementById('bulkDeleteForm');
        bulkForm?.addEventListener('submit', e => {
            bulkForm.querySelectorAll('input[name="delete_ids[]"]').forEach(n => n.remove());
            document.querySelectorAll('.row-check:checked').forEach(cb => {
                const h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'delete_ids[]';
                h.value = cb.value;
                bulkForm.appendChild(h);
            });
            if (!bulkForm.querySelectorAll('input[name="delete_ids[]"]').length) {
                e.preventDefault();
                alert('اختر صفوفًا للحذف أولاً.');
            }
        });
    </script>
</x-dashboard-layout>
