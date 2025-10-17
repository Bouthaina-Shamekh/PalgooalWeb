@php
    $itemTypes = config('invoices.item_types', [
        'subscription' => 'اشتراك استضافة',
        'domain' => 'نطاق',
    ]);

    if (empty($itemTypes)) {
        $itemTypes = [
            'subscription' => 'Subscription',
            'domain' => 'Domain',
        ];
    }

    $defaultItemType = array_key_first($itemTypes);
    $prefilledItems = (isset($invoice) && $invoice->exists)
        ? $invoice->items->map(fn ($item) => $item->only([
            'item_type',
            'reference_id',
            'description',
            'qty',
            'unit_price_cents',
        ]))->toArray()
        : null;

    $items = old('items', $prefilledItems ?? [
        [
            'item_type' => $defaultItemType,
            'reference_id' => '',
            'description' => '',
            'qty' => 1,
            'unit_price_cents' => 0,
        ],
    ]);
@endphp

{{-- Primary invoice fields --}}
<div class="col-span-12 grid grid-cols-12 gap-6">
    <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">العميل</label>
        <select
            name="client_id"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white/80 py-2 px-3 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            required
        >
            <option value="">-- اختر العميل --</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}"
                    {{ old('client_id', $invoice->client_id ?? '') == $client->id ? 'selected' : '' }}>
                    {{ $client->first_name }} {{ $client->last_name }}
                </option>
            @endforeach
        </select>
        @error('client_id')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">الحالة</label>
        <select
            name="status"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white/80 py-2 px-3 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            required
        >
            <option value="draft" {{ old('status', $invoice->status ?? '') == 'draft' ? 'selected' : '' }}>مسودة</option>
            <option value="unpaid" {{ old('status', $invoice->status ?? '') == 'unpaid' ? 'selected' : '' }}>غير مدفوعة</option>
            <option value="paid" {{ old('status', $invoice->status ?? '') == 'paid' ? 'selected' : '' }}>مدفوعة</option>
            <option value="cancelled" {{ old('status', $invoice->status ?? '') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
        </select>
        @error('status')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">تاريخ الاستحقاق</label>
        <input
            type="date"
            name="due_date"
            value="{{ old('due_date', isset($invoice->due_date) ? $invoice->due_date->format('Y-m-d') : '') }}"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white/80 py-2 px-3 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
        >
        @error('due_date')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label class="block text-sm font-medium text-gray-700">تاريخ الدفع</label>
        <input
            type="date"
            name="paid_date"
            value="{{ old('paid_date', isset($invoice->paid_date) ? $invoice->paid_date->format('Y-m-d') : '') }}"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white/80 py-2 px-3 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500"
        >
        @error('paid_date')
            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>
</div>

{{-- Items section --}}
<div class="col-span-12">
    <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">تفاصيل البنود</h3>
                <p class="text-sm text-gray-500">أضف بنود الفاتورة مع النوع والوصف والسعر.</p>
            </div>
            <button
                type="button"
                id="add-item"
                class="inline-flex items-center justify-center rounded-lg border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-100 transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            >
                + إضافة بند
            </button>
        </div>

        <div id="invoice-items" class="space-y-4">
            @foreach ($items as $i => $item)
                <div class="invoice-item rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">نوع البند</label>
                        <select
                            name="items[{{ $i }}][item_type]"
                            data-field="item_type"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500"
                        >
                            @foreach ($itemTypes as $value => $label)
                                <option value="{{ $value }}" {{ ($item['item_type'] ?? $defaultItemType) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">المعرف المرجعي (ID)</label>
                        <input
                            type="text"
                            name="items[{{ $i }}][reference_id]"
                            data-field="reference_id"
                            value="{{ $item['reference_id'] }}"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">الوصف</label>
                        <input
                            type="text"
                            name="items[{{ $i }}][description]"
                            data-field="description"
                            value="{{ $item['description'] }}"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">الكمية</label>
                        <input
                            type="number"
                            name="items[{{ $i }}][qty]"
                            data-field="qty"
                            value="{{ $item['qty'] }}"
                            min="1"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">سعر الوحدة (بالسنت)</label>
                        <input
                            type="number"
                            name="items[{{ $i }}][unit_price_cents]"
                            data-field="unit_price_cents"
                            value="{{ $item['unit_price_cents'] }}"
                            min="0"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                    </div>

                    <div class="md:col-span-12 flex justify-end">
                        <button
                            type="button"
                            class="remove-item inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        >
                            حذف البند
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Form actions --}}
<div class="col-span-12 mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
    <button
        type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        حفظ الفاتورة
    </button>
    <a
        href="{{ route('dashboard.invoices.index') }}"
        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        إلغاء
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const itemsContainer = document.getElementById('invoice-items');
        const addBtn = document.getElementById('add-item');
        const itemTypes = @json($itemTypes);
        const defaultItemType = @json($defaultItemType);

        const buildTypeOptions = (selectedValue = defaultItemType) => {
            return Object.entries(itemTypes)
                .map(([value, label]) => `<option value="${value}" ${value === selectedValue ? 'selected' : ''}>${label}</option>`)
                .join('');
        };

        const buildItemRow = (index) => `
            <div class="invoice-item rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">نوع البند</label>
                    <select name="items[${index}][item_type]" data-field="item_type"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500">
                        ${buildTypeOptions()}
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">المعرف المرجعي (ID)</label>
                    <input type="text" name="items[${index}][reference_id]" data-field="reference_id"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500" required>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">الوصف</label>
                    <input type="text" name="items[${index}][description]" data-field="description"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">الكمية</label>
                    <input type="number" name="items[${index}][qty]" data-field="qty" value="1" min="1"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">سعر الوحدة (بالسنت)</label>
                    <input type="number" name="items[${index}][unit_price_cents]" data-field="unit_price_cents" value="0" min="0"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-700 focus:border-primary-500 focus:ring-primary-500" required>
                </div>
                <div class="md:col-span-12 flex justify-end">
                    <button type="button"
                        class="remove-item inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        حذف البند
                    </button>
                </div>
            </div>
        `;

        const reindexItems = () => {
            itemsContainer.querySelectorAll('.invoice-item').forEach((row, idx) => {
                row.querySelectorAll('[data-field]').forEach((field) => {
                    const name = field.dataset.field;
                    field.setAttribute('name', `items[${idx}][${name}]`);
                });
            });
        };

        addBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            const index = itemsContainer.querySelectorAll('.invoice-item').length;
            itemsContainer.insertAdjacentHTML('beforeend', buildItemRow(index));
        });

        itemsContainer.addEventListener('click', (event) => {
            const trigger = event.target.closest('.remove-item');
            if (!trigger) return;
            event.preventDefault();
            const row = trigger.closest('.invoice-item');
            if (row) {
                row.remove();
                reindexItems();
            }
        });
    });
</script>
