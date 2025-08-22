<x-dashboard-layout>
<div class="container">
    <h2 class="mb-4">قائمة الاشتراكات</h2>
    @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    <a href="{{ route('dashboard.subscriptions.create') }}" class="btn btn-primary mb-3">إضافة اشتراك جديد</a>
    <table class="table table-bordered table-striped align-middle text-center">
        <thead>
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>الخطة</th>
                <th>السعر</th>
                <th>الحالة</th>
                <th>الباقة</th>
                <th>اسم المستخدم</th>
                <th>السيرفر</th>
                <th>الاستحقاق القادم</th>
                <th>تاريخ البداية</th>
                <th>تاريخ النهاية</th>
                <th>الدومين</th>
                <th>خيارات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscriptions as $sub)
            <tr>
                <td>{{ $sub->id }}</td>
                <td>{{ $sub->client->first_name ?? '' }} {{ $sub->client->last_name ?? '' }}</td>
                <td>{{ $sub->plan->name ?? '' }}</td>
                <td>{{ number_format($sub->price, 2) }} $</td>
                <td>
                    <span class="badge bg-{{ $sub->status == 'active' ? 'success' : ($sub->status == 'pending' ? 'warning' : ($sub->status == 'suspended' ? 'secondary' : 'danger')) }}">
                        {{ __($sub->status) }}
                    </span>
                </td>
                <td>{{ $sub->package }}</td>
                <td>{{ $sub->username }}</td>
                <td>{{ $sub->server_id }}</td>
                <td>{{ $sub->next_due_date }}</td>
                <td>{{ $sub->starts_at }}</td>
                <td>{{ $sub->ends_at }}</td>
                <td>{{ $sub->domain_name }}</td>
                <td>
                    <a href="{{ route('dashboard.subscriptions.edit', $sub) }}" class="btn btn-sm btn-info mb-1">تعديل</a>
                    <form action="{{ route('dashboard.subscriptions.destroy', $sub) }}" method="POST" style="display:inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger mb-1">حذف</button>
                    </form>
                    @if($sub->status == 'active')
                        <form action="#" method="POST" style="display:inline-block">
                            <button class="btn btn-sm btn-warning mb-1">تعليق</button>
                        </form>
                    @elseif($sub->status == 'suspended')
                        <form action="#" method="POST" style="display:inline-block">
                            <button class="btn btn-sm btn-success mb-1">إلغاء التعليق</button>
                        </form>
                    @endif
                    <form action="#" method="POST" style="display:inline-block">
                        <button class="btn btn-sm btn-dark mb-1">مزامنة مع المزود</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-3">{{ $subscriptions->links() }}</div>
</div>
</x-dashboard-layout>
