<x-dashboard-layout>
<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
            <li class="breadcrumb-item" aria-current="page">الاشتراكات</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">قائمة الاشتراكات</h2>
        </div>
    </div>
</div>
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card table-card">
            @if(session('ok'))
                <div class="alert alert-success mb-4">{{ session('ok') }}</div>
            @endif
            @if(session('connection_result'))
                <div class="alert alert-info mb-4">{!! session('connection_result') !!}</div>
            @endif
            <div class="card-header">
                <div class="sm:flex items-center justify-between">
                    <h5 class="mb-3 sm:mb-0">قائمة الاشتراكات</h5>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard.subscriptions.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus"></i> إضافة اشتراك جديد
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover w-full">
                        <thead>
                            <tr>
                                <th class="text-right">#</th>
                                <th class="text-right">العميل</th>
                                <th class="text-right">الخطة</th>
                                <th class="text-right">السعر</th>
                                <th class="text-right">الحالة</th>
                                <th class="text-right">اسم المستخدم</th>
                                <th class="text-right">السيرفر</th>
                                <th class="text-right">الاستحقاق القادم</th>
                                <th class="text-right">الدومين</th>
                                <th class="text-right">خيارات</th>
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
                                        @if($sub->status == 'active')
                                            <span class="badge bg-emerald-500/10 text-emerald-600 rounded-full text-xs px-2 py-0.5">نشط</span>
                                        @elseif($sub->status == 'pending')
                                            <span class="badge bg-yellow-100 text-yellow-800 rounded-full text-xs px-2 py-0.5">معلق</span>
                                        @elseif($sub->status == 'suspended')
                                            <span class="badge bg-gray-500/10 text-gray-600 rounded-full text-xs px-2 py-0.5">موقوف</span>
                                        @elseif($sub->status == 'cancelled')
                                            <span class="badge bg-red-100 text-red-800 rounded-full text-xs px-2 py-0.5">ملغي</span>
                                        @else
                                            <span class="badge bg-gray-200 text-gray-700 rounded-full text-xs px-2 py-0.5">{{ __($sub->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $sub->username }}</td>
                                    <td>{{ $sub->server ? $sub->server->name : '-' }}</td>
                                    <td>{{ $sub->next_due_date }}</td>
                                    <td>{{ $sub->domain_name }}</td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1 justify-center">
                                            <a href="{{ route('dashboard.subscriptions.edit', $sub) }}" class="btn btn-xs btn-info" title="تعديل">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <form action="{{ route('dashboard.subscriptions.destroy', $sub) }}" method="POST" style="display:inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-xs btn-danger" title="حذف"><i class="ti ti-trash"></i></button>
                                            </form>
                                            @if($sub->status == 'active')
                                                <form action="{{ route('dashboard.subscriptions.suspend', $sub) }}" method="POST" style="display:inline-block">
                                                    @csrf
                                                    <button class="btn btn-xs btn-warning" title="تعليق" onclick="return confirm('سيتم تعليق الموقع على السيرفر. هل أنت متأكد؟')"><i class="ti ti-ban"></i></button>
                                                </form>
                                            @elseif($sub->status == 'suspended')
                                                <form action="{{ route('dashboard.subscriptions.unsuspend', $sub) }}" method="POST" style="display:inline-block">
                                                    @csrf
                                                    <button class="btn btn-xs btn-success" title="إلغاء التعليق" onclick="return confirm('سيتم إلغاء تعليق الموقع على السيرفر. هل أنت متأكد؟')"><i class="ti ti-check"></i></button>
                                                </form>
                                            @endif
                                            <form action="{{ route('dashboard.subscriptions.sync', $sub) }}" method="POST" style="display:inline-block">
                                                @csrf
                                                <button class="btn btn-xs btn-dark" title="مزامنة مع المزود"><i class="ti ti-refresh"></i></button>
                                            </form>
                                            <form action="{{ route('dashboard.subscriptions.terminate', $sub) }}" method="POST" style="display:inline-block">
                                                @csrf
                                                <button class="btn btn-xs btn-danger" title="حذف نهائي" onclick="return confirm('سيتم حذف الموقع من السيرفر نهائيًا. هل أنت متأكد؟')"><i class="ti ti-trash-x"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</x-dashboard-layout>
