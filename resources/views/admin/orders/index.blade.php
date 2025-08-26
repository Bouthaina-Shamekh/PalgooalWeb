<x-dashboard-layout>
    <div class="container">
        <h1 class="mb-4">قائمة الطلبات</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>الحالة</th>
                    <th>النوع</th>
                    <th>تاريخ الإنشاء</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->client->first_name ?? '-' }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->type }}</td>
                        <td>{{ $order->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div>
            {{ $orders->links() }}
        </div>
    </div>
</x-dashboard-layout>
