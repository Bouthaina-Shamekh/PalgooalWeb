@extends('dashboard.layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.orders.index') }}" class="text-blue-600 hover:underline">&larr; الرجوع لقائمة
                الطلبات</a>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">تفاصيل الطلب</h2>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">رقم الطلب:</span> <span
                    class="font-mono">{{ $order->order_number }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">الحالة:</span> <span>{{ __($order->status) }}</span>
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">العميل:</span> {{ $order->client->first_name ?? '-' }}
            </div>
            <div class="mb-4">
                <span class="font-semibold text-gray-600">تاريخ الإنشاء:</span>
                {{ $order->created_at->format('Y-m-d H:i') }}
            </div>
            <!-- الفواتير المرتبطة -->
            <div class="mt-8">
                <h3 class="text-lg font-bold mb-3 text-gray-700">الفواتير المرتبطة</h3>
                @if ($order->invoices && $order->invoices->count())
                    @foreach ($order->invoices as $invoice)
                        <div class="mb-6 border rounded p-4 bg-gray-50">
                            <div class="mb-2">
                                <span class="font-semibold text-gray-600">رقم الفاتورة:</span> <span
                                    class="font-mono">{{ $invoice->number }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="font-semibold text-gray-600">الحالة:</span>
                                <span>{{ __($invoice->status) }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="font-semibold text-gray-600">الإجمالي:</span>
                                ${{ number_format($invoice->total_cents / 100, 2) }} {{ $invoice->currency }}
                            </div>
                            <div class="mb-2">
                                <span class="font-semibold text-gray-600">تاريخ الاستحقاق:</span>
                                {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}
                            </div>
                            <!-- جدول بنود الفاتورة -->
                            <div class="mt-4">
                                <h4 class="font-bold text-gray-700 mb-2">بنود الفاتورة:</h4>
                                @if ($invoice->items && $invoice->items->count())
                                    <table class="min-w-full divide-y divide-gray-200 mb-2">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-1 text-right text-xs font-bold text-gray-600">الوصف</th>
                                                <th class="px-2 py-1 text-right text-xs font-bold text-gray-600">الكمية</th>
                                                <th class="px-2 py-1 text-right text-xs font-bold text-gray-600">سعر الوحدة
                                                </th>
                                                <th class="px-2 py-1 text-right text-xs font-bold text-gray-600">الإجمالي
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-100">
                                            @foreach ($invoice->items as $item)
                                                <tr>
                                                    <td class="px-2 py-1">{{ $item->description }}</td>
                                                    <td class="px-2 py-1">{{ $item->qty }}</td>
                                                    <td class="px-2 py-1">
                                                        ${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                                    <td class="px-2 py-1">${{ number_format($item->total_cents / 100, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="text-gray-400">لا توجد بنود لهذه الفاتورة.</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-gray-400">لا توجد فواتير مرتبطة بهذا الطلب.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
