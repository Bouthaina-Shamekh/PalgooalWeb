<x-dashboard-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">قائمة الطلبات</h1>
            {{-- يمكنك إضافة زر إضافة طلب جديد هنا إذا أردت --}}
        </div>
        <div class="overflow-x-auto rounded-lg shadow bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">#</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">رقم الطلب</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">العميل</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">الحالة</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">النوع</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $order->id }}</td>
                            <td class="px-4 py-2 font-mono text-blue-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-2">{{ $order->client->first_name ?? '-' }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'active' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'fraud' => 'bg-gray-200 text-gray-700',
                                    ];
                                @endphp
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ __($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ $order->type }}</td>
                            <td class="px-4 py-2">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('dashboard.orders.show', $order->id) }}"
                                    class="inline-block px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">عرض
                                    التفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-400">لا توجد طلبات حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
</x-dashboard-layout>
