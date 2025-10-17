<x-dashboard-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.invoices.index') }}" class="text-blue-600 hover:underline">&larr; العودة إلى قائمة الفواتير</a>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">تفاصيل الفاتورة</h2>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">رقم الفاتورة:</span> <span class="font-mono">{{ $invoice->number }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">الحالة:</span> <span>{{ __($invoice->status) }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">إجمالي الفاتورة:</span>
                ${{ number_format($invoice->total_cents / 100, 2) }} {{ $invoice->currency }}
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">تاريخ الاستحقاق:</span>
                {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}
            </div>
            @if ($invoice->client)
                <div class="mb-4">
                    <span class="font-semibold text-gray-600">العميل:</span>
                    <span>{{ $invoice->client->first_name }} {{ $invoice->client->last_name }}</span>
                </div>
            @endif
            @if ($invoice->paid_date)
                <div class="mb-4">
                    <span class="font-semibold text-gray-600">تاريخ الدفع:</span>
                    {{ $invoice->paid_date->format('Y-m-d') }}
                </div>
            @endif
            <!-- تفاصيل عناصر الفاتورة -->
            <div class="mt-8">
                <h3 class="text-lg font-bold mb-3 text-gray-700">عناصر الفاتورة</h3>
                @php
                    $typeLabels = config('invoices.item_types', []);
                @endphp
                @if ($invoice->items && $invoice->items->count())
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الوصف</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">النوع</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">المعرف المرجعي</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">التفاصيل المرتبطة</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الكمية</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">سعر الوحدة</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($invoice->items as $item)
                                @php
                                    $itemTypeLabel = $typeLabels[$item->item_type] ?? ucfirst($item->item_type);
                                    $relatedDetails = null;

                                    if ($item->item_type === 'subscription' && $item->subscription) {
                                        $planName = optional($item->subscription->plan)->name;
                                        $subscriptionDomain = $item->subscription->domain_name;
                                        if ($planName) {
                                            $relatedDetails = 'خطة الاستضافة: ' . $planName;
                                        } elseif ($subscriptionDomain) {
                                            $relatedDetails = 'النطاق المرتبط: ' . $subscriptionDomain;
                                        } else {
                                            $relatedDetails = 'معرف الاشتراك: ' . $item->reference_id;
                                        }
                                    } elseif ($item->item_type === 'domain' && $item->domain) {
                                        $relatedDetails = 'اسم النطاق: ' . $item->domain->domain_name;
                                    }
                                @endphp
                                <tr>
                                    <td class="px-4 py-2">{{ filled($item->description) ? $item->description : '-' }}</td>
                                    <td class="px-4 py-2">{{ $itemTypeLabel }}</td>
                                    <td class="px-4 py-2">{{ $item->reference_id ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        @if ($relatedDetails)
                                            <span class="text-sm text-gray-600">{{ $relatedDetails }}</span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $item->qty }}</td>
                                    <td class="px-4 py-2">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                    <td class="px-4 py-2">${{ number_format($item->total_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-gray-400">لا توجد عناصر متاحة في الفاتورة.</div>
                @endif
                <div class="mt-6 grid gap-2 md:grid-cols-2 lg:grid-cols-4">
                    <div class="p-3 bg-gray-50 rounded border">
                        <div class="text-xs text-gray-500">الإجمالي الفرعي</div>
                        <div class="font-semibold">
                            ${{ number_format(($invoice->subtotal_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded border">
                        <div class="text-xs text-gray-500">الخصم</div>
                        <div class="font-semibold">
                            ${{ number_format(($invoice->discount_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded border">
                        <div class="text-xs text-gray-500">الضريبة</div>
                        <div class="font-semibold">
                            ${{ number_format(($invoice->tax_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded border">
                        <div class="text-xs text-gray-500">الإجمالي المستحق</div>
                        <div class="font-semibold">
                            ${{ number_format(($invoice->total_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
