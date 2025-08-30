<x-dashboard-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.invoices.index') }}" class="text-blue-600 hover:underline">&larr; الرجوع لقائمة
                الفواتير</a>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">تفاصيل الفاتورة</h2>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">رقم الفاتورة:</span> <span
                    class="font-mono">{{ $invoice->number }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">الحالة:</span> <span>{{ __($invoice->status) }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">الإجمالي:</span>
                ${{ number_format($invoice->total_cents / 100, 2) }} {{ $invoice->currency }}
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">تاريخ الاستحقاق:</span>
                {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}
            </div>
            <!-- جدول بنود الفاتورة -->
            <div class="mt-8">
                <h3 class="text-lg font-bold mb-3 text-gray-700">بنود الفاتورة</h3>
                @if ($invoice->items && $invoice->items->count())
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الوصف</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الكمية</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">سعر الوحدة</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item->description }}</td>
                                    <td class="px-4 py-2">{{ $item->qty }}</td>
                                    <td class="px-4 py-2">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2">${{ number_format($item->total_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-gray-400">لا توجد بنود لهذه الفاتورة.</div>
                @endif
            </div>
        </div>
    </div>
</x-dashboard-layout>
