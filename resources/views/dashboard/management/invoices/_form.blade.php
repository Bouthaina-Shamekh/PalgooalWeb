<div class="col-span-12 md:col-span-6">
    <label class="form-label">العميل</label>
    <select name="client_id" class="form-select" required>
        <option value="">-- اختر عميل --</option>
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
        <option value="unpaid" {{ old('status', $invoice->status ?? '') == 'unpaid' ? 'selected' : '' }}>غير مدفوعة
        </option>
        <option value="paid" {{ old('status', $invoice->status ?? '') == 'paid' ? 'selected' : '' }}>مدفوعة</option>
        <option value="cancelled" {{ old('status', $invoice->status ?? '') == 'cancelled' ? 'selected' : '' }}>ملغاة
        </option>
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

{{-- بنود الفاتورة --}}
<div class="col-span-12">
    <h3 class="font-bold mb-3">بنود الفاتورة</h3>

    <div id="invoice-items" class="space-y-4">
        @php
            $items = old(
                'items',
                isset($invoice)
                    ? $invoice->items->toArray()
                    : [
                        [
                            'item_type' => 'subscription',
                            'reference_id' => '',
                            'description' => '',
                            'qty' => 1,
                            'unit_price_cents' => 0,
                        ],
                    ],
            );
        @endphp

        @foreach ($items as $i => $item)
            <div class="p-4 border rounded-md bg-gray-50 invoice-item grid grid-cols-12 gap-4">
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">النوع</label>
                    <select name="items[{{ $i }}][item_type]" class="form-select">
                        <option value="subscription" {{ $item['item_type'] == 'subscription' ? 'selected' : '' }}>
                            اشتراك
                        </option>
                        <option value="domain" {{ $item['item_type'] == 'domain' ? 'selected' : '' }}>دومين</option>
                    </select>
                </div>

                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">ID المرجع</label>
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
                    <label class="form-label">السعر (سنت)</label>
                    <input type="number" name="items[{{ $i }}][unit_price_cents]" class="form-control"
                        min="0" required value="{{ $item['unit_price_cents'] }}">
                </div>

                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="btn btn-danger remove-item w-full">حذف</button>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-item" class="btn btn-secondary mt-4">+ إضافة بند</button>
</div>

<div class="col-span-12 text-right mt-4">
    <button type="submit" class="btn btn-primary">حفظ</button>
    <a href="{{ route('dashboard.invoices.index') }}" class="btn btn-light">إلغاء</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let itemsContainer = document.getElementById('invoice-items');
        let addBtn = document.getElementById('add-item');

        addBtn.addEventListener('click', () => {
            let index = itemsContainer.querySelectorAll('.invoice-item').length;
            let html = `
            <div class="p-4 border rounded-md bg-gray-50 invoice-item grid grid-cols-12 gap-4 mt-2">
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">النوع</label>
                    <select name="items[${index}][item_type]" class="form-select">
                        <option value="subscription">اشتراك</option>
                        <option value="domain">دومين</option>
                    </select>
                </div>
                <div class="col-span-12 md:col-span-2">
                    <label class="form-label">ID المرجع</label>
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
                    <label class="form-label">السعر (سنت)</label>
                    <input type="number" name="items[${index}][unit_price_cents]" class="form-control" min="0" value="0" required>
                </div>
                <div class="col-span-12 md:col-span-1 flex items-end">
                    <button type="button" class="btn btn-danger remove-item w-full">حذف</button>
                </div>
            </div>`;
            itemsContainer.insertAdjacentHTML('beforeend', html);
        });

        itemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.invoice-item').remove();
            }
        });
    });
</script>
