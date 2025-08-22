<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">السيرفرات</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">قائمة السيرفرات</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                @if(session('connection_result'))
                    <div class="alert alert-info mb-4">{{ session('connection_result') }}</div>
                @endif
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">قائمة السيرفرات</h5>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dashboard.servers.create') }}" class="btn btn-primary">
                                إضافة سيرفر جديد
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Filters UI فقط (بدون backend wire) --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4 px-4">
                    <input
                        type="text"
                        placeholder="بحث عن السيرفرات..."
                        class="w-full sm:w-80 border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        disabled
                    />
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">لكل صفحة</label>
                        <select
                            class="border rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/30"
                            disabled
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                        </select>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">الاسم</th>
                                    <th class="text-right">النوع</th>
                                    <th class="text-right">IP</th>
                                    <th class="text-right">Hostname</th>
                                    <th class="text-right">الحالة</th>
                                    <th class="text-right">خيارات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($servers as $server)
                                    @php
                                        $rowIndex = ($servers->firstItem() ?? 1) + $loop->index;
                                    @endphp
                                    <tr>
                                        <td>{{ $rowIndex }}</td>
                                        <td class="font-semibold">{{ $server->name }}</td>
                                        <td class="text-sm">{{ $server->type }}</td>
                                        <td>{{ $server->ip }}</td>
                                        <td>{{ $server->hostname }}</td>
                                        <td>
                                            @if($server->is_active)
                                                <span class="badge bg-emerald-500/10 text-emerald-600 rounded-full text-xs px-2 py-0.5">مفعل</span>
                                            @else
                                                <span class="badge bg-gray-500/10 text-gray-600 rounded-full text-xs px-2 py-0.5">معطل</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap">
                                            <a href="{{ route('dashboard.servers.edit', $server) }}" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary" title="تعديل">
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </a>
                                            <form action="{{ route('dashboard.servers.destroy', $server) }}" method="POST" style="display:inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary" title="حذف">
                                                    <i class="ti ti-trash text-xl leading-none"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('dashboard.servers.test-connection', $server) }}" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary" title="فحص الاتصال">
                                                <i class="ti ti-plug text-xl leading-none"></i>
                                            </a>
                                            @if($server->type == 'cpanel')
                                                <a href="{{ route('dashboard.servers.sso-whm', $server) }}" target="_blank" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary" title="دخول السيرفر (SSO)">
                                                    <i class="ti ti-login text-xl leading-none"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-gray-500 py-8">لا يوجد سيرفرات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $servers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
