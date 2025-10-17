<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.invoices.index') }}">الفواتير</a></li>
                <li class="breadcrumb-item" aria-current="page">إنشاء فاتورة</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">إنشاء فاتورة جديدة</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">بيانات الفاتورة</h5>
                </div>
                <div class="card-body">
                    @if(session('ok'))
                        <div class="alert alert-success" role="alert" dir="auto">
                            {{ session('ok') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="list-disc pr-6">
                                @foreach($errors->all() as $error)
                                    <li dir="auto">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('dashboard.invoices.store') }}" method="POST" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        @include('dashboard.management.invoices._form', [
                            'mode' => 'create',
                            'clients' => $clients ?? [],
                            'subscriptions' => $subscriptions ?? [],
                            'domains' => $domains ?? [],
                            'invoice' => $invoice ?? null,
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>


    <script>
    // سكربت إدارة البنود وحساب الإجماليات (بسيط ولا يعتمد على مكتبات)
    (function (win, doc) {
        'use strict';

        const form = doc.querySelector('form[action$="invoices"]');
        if (!form) return;

        // جلب القوائم من الـ dataset التي حقناها في الجزئية
        const dataset = doc.getElementById('invoice-items-config')?.dataset || {};
        const subscriptions = JSON.parse(dataset.subscriptions || '[]');
        const domains       = JSON.parse(dataset.domains || '[]');
        const currency      = dataset.currency || 'USD';

        const tbody       = doc.getElementById('invoice-items-tbody');
        const addBtn      = doc.getElementById('add-invoice-item');
        const tmpl        = doc.getElementById('invoice-item-row-template');
        const subtotalEl  = doc.getElementById('subtotal_cents_view');
        const totalEl     = doc.getElementById('total_cents_view');
        const subtotalInp = doc.getElementById('subtotal_cents_input'); // للعرض فقط (نحدّثه افتراضياً لو حبيت)
        const totalInp    = doc.getElementById('total_cents_input');    // اختياري للعرض

        // تهيئة الخيارات حسب نوع البند
        function buildReferenceOptions(type) {
            const frag = doc.createDocumentFragment();
            const optDefault = doc.createElement('option');
            optDefault.value = '';
            optDefault.textContent = 'اختر المرجع';
            frag.appendChild(optDefault);

            const list = (type === 'subscription') ? subscriptions : (type === 'domain') ? domains : [];
            list.forEach((item) => {
                const opt = doc.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label || item.name || (`#${item.id}`);
                frag.appendChild(opt);
            });
            return frag;
        }

        // حساب الإجماليات
        function recalcTotals() {
            let subtotalCents = 0;

            tbody.querySelectorAll('tr[data-row]').forEach((row) => {
                const qty = parseInt(row.querySelector('[data-qty]')?.value || '0', 10);
                const up  = parseInt(row.querySelector('[data-unit-price]')?.value || '0', 10);
                const line = qty * up;
                row.querySelector('[data-line-total]')!.textContent = formatMoney(line, currency);
                subtotalCents += line;
            });

            // خصم/ضريبة (مكان جاهز للتوسعة لاحقاً)
            const discountCents = 0;
            const taxCents      = 0;
            const totalCents    = Math.max(0, subtotalCents - discountCents + taxCents);

            if (subtotalEl)  subtotalEl.textContent = formatMoney(subtotalCents, currency);
            if (totalEl)     totalEl.textContent    = formatMoney(totalCents, currency);

            // في حال أردت حفظ قيم فرعية مخفية:
            if (subtotalInp) subtotalInp.value = subtotalCents;
            if (totalInp)    totalInp.value    = totalCents;
        }

        function formatMoney(cents, cur) {
            const major = (parseInt(cents || 0, 10) / 100).toFixed(2);
            return `${major} ${cur}`;
        }

        // إضافة سطر بنود
        function addRow(prefill = {}) {
            const clone = tmpl.content.firstElementChild.cloneNode(true);
            const row   = clone;
            row.setAttribute('data-row', '1');

            // تحديث أسماء الحقول بالفهرسة الصحيحة
            const index = tbody.querySelectorAll('tr[data-row]').length;
            row.querySelectorAll('[data-name]').forEach((el) => {
                const base = el.getAttribute('data-name');
                el.setAttribute('name', `items[${index}][${base}]`);
            });

            // مراجع العناصر
            const typeSel = row.querySelector('[data-type]');
            const refSel  = row.querySelector('[data-ref]');
            const qtyInp  = row.querySelector('[data-qty]');
            const upInp   = row.querySelector('[data-unit-price]');
            const delBtn  = row.querySelector('[data-delete]');

            // تعبئة افتراضية عند الإنشاء
            if (prefill.item_type) typeSel.value = prefill.item_type;
            // أبني المراجع لأول نوع
            refSel.innerHTML = '';
            refSel.appendChild(buildReferenceOptions(typeSel.value));

            if (prefill.reference_id) refSel.value = prefill.reference_id;
            if (prefill.description) row.querySelector('[data-desc]').value = prefill.description;
            if (prefill.qty) qtyInp.value = prefill.qty;
            if (prefill.unit_price_cents) upInp.value = prefill.unit_price_cents;

            // تغيّر النوع => إعادة بناء قائمة المراجع
            typeSel.addEventListener('change', () => {
                refSel.innerHTML = '';
                refSel.appendChild(buildReferenceOptions(typeSel.value));
                refSel.value = '';
            });

            // أي تغيير يعيد حساب الإجماليات
            row.addEventListener('input', (e) => {
                if (e.target.matches('[data-qty],[data-unit-price]')) {
                    // منع قيم سالبة
                    if (parseInt(e.target.value || '0', 10) < 0) e.target.value = '0';
                    recalcTotals();
                }
            });

            // حذف السطر
            delBtn.addEventListener('click', (e) => {
                e.preventDefault();
                row.remove();
                // أعد فهرسة الأسماء بعد الحذف
                reindexRows();
                recalcTotals();
            });

            tbody.appendChild(row);
            recalcTotals();
        }

        function reindexRows() {
            tbody.querySelectorAll('tr[data-row]').forEach((row, idx) => {
                row.querySelectorAll('[data-name]').forEach((el) => {
                    const base = el.getAttribute('data-name');
                    el.setAttribute('name', `items[${idx}][${base}]`);
                });
            });
        }

        // زر إضافة
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                addRow();
            });
        }

        // إن لم تكن هناك بنود، أضف واحداً تلقائياً
        if (!tbody.querySelector('tr[data-row]')) {
            addRow();
        }

        // إعادة الحساب الأولية
        recalcTotals();
    })(window, document);
    </script>

