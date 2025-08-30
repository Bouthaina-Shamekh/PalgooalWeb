<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">سجل المزامنات</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">سجل المزامنات</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-visible">
                <div class="px-4 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h5 class="text-lg font-semibold">سجل المزامنات</h5>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="بحث بالعميل أو الدومين..."
                                class="rounded-md border px-3 py-2 text-sm w-48" />
                            <select name="server_id" class="rounded-md border px-2 py-2 text-sm bg-white">
                                <option value="">كل السيرفرات</option>
                                @foreach ($servers as $s)
                                    <option value="{{ $s->id }}"
                                        {{ request('server_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="date" name="from" value="{{ request('from') }}"
                                class="rounded-md border px-2 py-2 text-sm" />
                            <input type="date" name="to" value="{{ request('to') }}"
                                class="rounded-md border px-2 py-2 text-sm" />
                            <button class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm">بحث</button>
                        </form>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">العميل
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">السيرفر
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الدومين
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الرسالة
                                        الأخيرة</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ
                                        آخر تزامن</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($subscriptions as $sub)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $sub->id }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $sub->client->first_name ?? '' }}
                                            {{ $sub->client->last_name ?? '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $sub->server->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $sub->domain_name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{!! nl2br(e($sub->last_sync_message)) !!}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ $sub->updated_at->diffForHumans() ?? $sub->updated_at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-muted">لا توجد سجلات لعرضها.
                                        </td>
                                    </tr>
                                @endforelse
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
