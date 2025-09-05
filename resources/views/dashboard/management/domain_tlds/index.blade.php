<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">إدارة TLD</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">أسعار الدومينات (TLD)</h2>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded p-4">
        {{-- رسائل --}}
        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ implode(' | ', $errors->all()) }}</div>
        @endif

        {{-- فلترة بالمزوّد (GET) --}}
        <form class="flex gap-2 items-end mb-4" method="get">
            <div>
                <label class="block mb-1 text-sm">المزوّد</label>
                <select name="provider_id" class="form-control">
                    <option value="">-- الكل --</option>
                    @foreach ($providers as $p)
                        <option value="{{ $p->id }}" @selected($providerId == $p->id)>
                            {{ $p->name }} ({{ $p->type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary">تصفية</button>
        </form>

        {{-- مزامنة من المزوّد (POST) --}}
        <form action="{{ route('dashboard.domain_tlds.sync') }}" method="post" class="mb-4">
            @csrf
            <div class="flex flex-wrap gap-2 items-end">
                <div>
                    <label class="block mb-1 text-sm">مزامنة من المزوّد</label>
                    <select name="provider_id" class="form-control" required>
                        @foreach ($providers as $p)
                            <option value="{{ $p->id }}" @selected($providerId == $p->id)>
                                {{ $p->name }} ({{ $p->type }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-sm">TLDs (اختياري)</label>
                    <input name="tlds" class="form-control w-64" placeholder="com,net,org" value="{{ old('tlds') }}">
                    <div class="text-xs text-muted mt-1">
                        اتركه فارغًا لمزامنة العناصر المُعلّمة في الكتالوج فقط.
                    </div>
                </div>
                <button class="btn btn-primary">سحب الأسعار</button>
            </div>
        </form>

        {{-- حفظ الكتالوج + أسعار البيع (POST) للصفحة الحالية --}}
        <form action="{{ route('dashboard.domain_tlds.save-all') }}" method="post" class="mt-4">
            @csrf
            {{-- نمرّر المزوّد الحالي كي نعود لنفس الفلتر --}}
            <input type="hidden" name="provider_id" value="{{ $providerId }}">

            <div class="flex items-center gap-2 mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="checkAll">تحديد الكل</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="uncheckAll">إلغاء تحديد الكل</button>
                <span class="text-xs text-muted">— يطبَّق على الكتالوج في الصفحة الحالية فقط.</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>كتالوج</th>
                            <th>TLD</th>
                            <th>عملة</th>
                            <th>تفعيل</th>
                            <th>Register 1y (Cost)</th>
                            <th>Register 1y (Sale)</th>
                            <th>Renew 1y (Cost)</th>
                            <th>Renew 1y (Sale)</th>
                            <th>Transfer 1y (Cost)</th>
                            <th>Transfer 1y (Sale)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $reg = $row->prices->firstWhere('action', 'register');
                                $ren = $row->prices->firstWhere('action', 'renew');
                                $tra = $row->prices->firstWhere('action', 'transfer');
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           class="catalog-checkbox"
                                           name="catalog[{{ $row->id }}]"
                                           value="1"
                                           @checked($row->in_catalog)>
                                    {{-- IDs الظاهرة في الصفحة الحالية حتى نحفظ فقط ما تراه --}}
                                    <input type="hidden" name="visible_ids[]" value="{{ $row->id }}">
                                </td>
                                <td>.{{ $row->tld }}</td>
                                <td>{{ $row->currency }}</td>
                                <td>
                                    <span class="badge {{ $row->enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $row->enabled ? 'مفعل' : 'معطل' }}
                                    </span>
                                </td>

                                <td>{{ optional($reg)->cost ?? '—' }}</td>
                                <td>
                                    @if ($reg)
                                        <input name="items[{{ $reg->id }}][id]" type="hidden" value="{{ $reg->id }}">
                                        <input name="items[{{ $reg->id }}][sale]"
                                               type="number" step="0.01" min="0"
                                               class="form-control w-28"
                                               value="{{ $reg->sale }}">
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>{{ optional($ren)->cost ?? '—' }}</td>
                                <td>
                                    @if ($ren)
                                        <input name="items[{{ $ren->id }}][id]" type="hidden" value="{{ $ren->id }}">
                                        <input name="items[{{ $ren->id }}][sale]"
                                               type="number" step="0.01" min="0"
                                               class="form-control w-28"
                                               value="{{ $ren->sale }}">
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>{{ optional($tra)->cost ?? '—' }}</td>
                                <td>
                                    @if ($tra)
                                        <input name="items[{{ $tra->id }}][id]" type="hidden" value="{{ $tra->id }}">
                                        <input name="items[{{ $tra->id }}][sale]"
                                               type="number" step="0.01" min="0"
                                               class="form-control w-28"
                                               value="{{ $tra->sale }}">
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3 mt-3">
                <button class="btn btn-success">حفظ الكتالوج وأسعار البيع</button>
                <div class="text-xs text-muted">يحفظ (in_catalog) وأسعار البيع للصفحة الحالية فقط.</div>
            </div>

            <div class="mt-2">
                {{ $rows->withQueryString()->links() }}
            </div>
        </form>

        <div class="mt-4 text-muted text-sm">
            ملاحظة: يتم سحب الأسعار فقط لـ TLDs المختارة في الكتالوج أو المحددة يدويًا في حقل <strong>TLDs</strong> عند المزامنة.
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
    </script>
</x-dashboard-layout>
