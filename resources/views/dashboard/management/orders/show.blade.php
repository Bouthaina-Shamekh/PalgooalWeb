<x-dashboard-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('dashboard.orders.index') }}" class="text-blue-600 hover:underline">&larr; الرجوع لقائمة
                الطلبات</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- كارت بيانات الطلب -->
            <div class="md:col-span-2 bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">تفاصيل الطلب</h2>
                    <span
                        class="px-3 py-1 rounded text-xs font-bold
                        @if ($order->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status == 'active') bg-green-100 text-green-800
                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                        @else bg-gray-200 text-gray-700 @endif">
                        {{ __($order->status) }}
                    </span>
                    <form method="POST" action="{{ route('dashboard.orders.status', $order->id) }}"
                        class="inline-block ms-2 align-middle">
                        @csrf
                        @method('PATCH')
                        <select name="status" onchange="this.form.submit()"
                            class="text-xs rounded border-gray-300 ms-2">
                            <option value="pending" @selected($order->status == 'pending')>معلق</option>
                            <option value="active" @selected($order->status == 'active')>نشط</option>
                            <option value="cancelled" @selected($order->status == 'cancelled')>ملغي</option>
                            <option value="fraud" @selected($order->status == 'fraud')>احتيال</option>
                        </select>
                    </form>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">رقم الطلب:</span> <span
                                class="font-mono">{{ $order->order_number }}</span></div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">النوع:</span> {{ $order->type }}
                        </div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">تاريخ الإنشاء:</span>
                            {{ $order->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div>
                        <div class="mb-2"><span class="font-semibold text-gray-600">ملاحظات:</span>
                            {{ $order->notes ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <!-- كارت بيانات العميل -->
            <div class="bg-gray-50 rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-3 text-gray-700 flex items-center gap-2"><svg
                        xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg> بيانات العميل</h3>
                <div class="mb-2"><span class="font-semibold text-gray-600">الاسم:</span>
                    {{ $order->client->first_name ?? '-' }}</div>
                <div class="mb-2"><span class="font-semibold text-gray-600">البريد الإلكتروني:</span>
                    {{ $order->client->email ?? '-' }}</div>
                <div class="mb-2"><span class="font-semibold text-gray-600">رقم الجوال:</span>
                    {{ $order->client->phone ?? '-' }}</div>
            </div>
        </div>
        <!-- جدول الفواتير المرتبطة -->
        <div class="mt-10 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-700 flex items-center gap-2"><svg
                    xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 14l2-2 4 4m0 0V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5" />
                </svg> الفواتير المرتبطة</h3>
            @if ($order->invoices && $order->invoices->count())
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">رقم الفاتورة</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الحالة</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">الإجمالي</th>
                            <th class="px-4 py-2 text-right text-xs font-bold text-gray-600">تاريخ الاستحقاق</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach ($order->invoices as $invoice)
                            <tr>
                                <td class="px-4 py-2 font-mono text-blue-700">{{ $invoice->number }}</td>
                                <td class="px-4 py-2">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold {{ $invoice->status == 'paid' ? 'bg-green-100 text-green-800' : ($invoice->status == 'unpaid' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-700') }}">
                                        {{ __($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">${{ number_format($invoice->total_cents / 100, 2) }}
                                    {{ $invoice->currency }}</td>
                                <td class="px-4 py-2">
                                    {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-gray-400">لا توجد فواتير مرتبطة بهذا الطلب.</div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
