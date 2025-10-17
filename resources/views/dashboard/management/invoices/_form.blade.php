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

<div class="col-span-12 md:col-span-6">
    <label class="form-label">العميل</label>
    <select name="client_id" class="form-select" required>
        <option value="">-- اختر العميل --</option>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}"
                {{ old('client_id', $invoice->client_id ?? '') == $client->id ? 'selected' : '' }}>
                {{ $client->first_name }} {{ $client->last_name }}
            </option>
        @endforeach
    </select>
    @error('client_id')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

<div class="col-span-12 md:col-span-6">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        <option value="draft" {{ old('status', $invoice->status ?? '') == 'draft' ? 'selected' : '' }}>مسودة</option>
        <option value="unpaid" {{ old('status', $invoice->status ?? '') == 'unpaid' ? 'selected' : '' }}>غير مدفوعة</option>
        <option value="paid" {{ old('status', $invoice->status ?? '') == 'paid' ? 'selected' : '' }}>مدفوعة</option>
        <option value="cancelled" {{ old('status', $invoice->status ?? '') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
    </select>
    @error('status')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

<div class="col-span-12 md:col-span-6">
    <label class="form-label">تاريخ الاستحقاق</label>
    <input type="date" name="due_date" class="form-control"
        value="{{ old('due_date', isset($invoice->due_date) ? $invoice->due_date->format('Y-m-d') : '') }}">
    @error('due_date')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

<div class="col-span-12 md:col-span-6">
    <label class="form-label">تاريخ الدفع</label>
    <input type="date" name="paid_date" class="form-control"
        value="{{ old('paid_date', isset($invoice->paid_date) ? $invoice->paid_date->format('Y-m-d') : '') }}">
    @error('paid_date')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- عناصر الفاتورة --}}
<div class="col-span-12">
    <h3 class="font-bold mb-3">عناصر الفاتورة</h3>

    <div id="invoice-items" class="space-y-4">
        @foreach ($items as $i => $item)
            <div class="p-4 border rounded-md bg-gray-50 invoice-item grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">النوع</label>
                    <select name="items[{{ $i }}][item_type]" class="form-select">
                        @foreach ($itemTypes as $value => $label)
                            <option value="{{ $value }}" {{ ($item['item_type'] ?? $defaultItemType) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">المعرف المرجعي (ID)</label>
                    <input type="text" name="items[{{ $i }}][reference_id]" class="form-control" required
                        value="{{ $item['reference_id'] }}">
                </div>

                <div class="col-span-12 md:col-span-4">
                    <label class="form-label">الوصف</label>
                    <input type="text" name="items[{{ $i }}][description]" class="form-control" required
                        value="{{ $item['description'] }}">
                </div>

                <div class="col-span-6 md:col-span-1">
                    <label class="form-label">الكمية</label>
                    <input type="number" name="items[{{ $i }}][qty]" class="form-control" min="1"
                        required value="{{ $item['qty'] }}">
                </div>

                <div class="col-span-6 md:col-span-2">
                    <label class="form-label">سعر الوحدة (بالسنت)</label>
                    <input type="number" name="items[{{ $i }}][unit_price_cents]" class="form-control"
                        min="0" required value="{{ $item['unit_price_cents'] }}">
                </div>

                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="btn btn-danger remove-item w-full">حذف</button>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-item" class="btn btn-secondary mt-4">+ إضافة عنصر</button>
</div>

<div class="col-span-12 text-right mt-4">
    <button type="submit" class="btn btn-primary">حفظ</button>
    <a href="{{ route('dashboard.invoices.index') }}" class="btn btn-light">إلغاء</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const itemsContainer = document.getElementById('invoice-items');
        const addBtn = document.getElementById('add-item');
        const itemTypes = @json($itemTypes);
        const defaultItemType = @json($defaultItemType);

        const buildTypeOptions = (selectedValue) => {
            return Object.entries(itemTypes)
                .map(([value, label]) => `<option value="${value}" ${value === selectedValue ? 'selected' : ''}>${label}</option>`)
                .join('');
        };

        addBtn.addEventListener('click', () => {
            const index = itemsContainer.querySelectorAll('.invoice-item').length;
            const typeOptions = buildTypeOptions(defaultItemType);
            const html = `
            <div class="p-4 border rounded-md bg-gray-50 invoice-item grid grid-cols-12 gap-4 mt-2">
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">النوع</label>
                    <select name="items[${index}][item_type]" class="form-select">
                        ${typeOptions}
                    </select>
                </div>
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">المعرف المرجعي (ID)</label>
                    <input type="text" name="items[${index}][reference_id]" class="form-control" required>
                </div>
                <div class="col-span-12 md:col-span-4">
                    <label class="form-label">الوصف</label>
                    <input type="text" name="items[${index}][description]" class="form-control" required>
                </div>
                <div class="col-span-6 md:col-span-1">
                    <label class="form-label">الكمية</label>
                    <input type="number" name="items[${index}][qty]" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-span-6 md:col-span-2">
                    <label class="form-label">سعر الوحدة (بالسنت)</label>
                    <input type="number" name="items[${index}][unit_price_cents]" class="form-control" min="0" value="0" required>
                </div>
                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="btn btn-danger remove-item w-full">حذف</button>
                </div>
            </div>`;
            itemsContainer.insertAdjacentHTML('beforeend', html);
        });

        itemsContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-item')) {
                const itemRow = event.target.closest('.invoice-item');
                if (itemRow) {
                    itemRow.remove();
                }
            }
        });
    });
</script>
