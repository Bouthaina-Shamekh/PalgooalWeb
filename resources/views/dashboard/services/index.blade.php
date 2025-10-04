<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('dashboard.services.index') }}">{{ t('dashboard.services', 'Services') }}</a>
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.All_Services', 'ALL Services') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">إدارة الخدمات</h1>

        @can('create', 'App\\Models\\Service')
            <a href="{{ route('dashboard.services.create') }}" class="btn btn-primary mb-4">➕ إضافة خدمة جديدة</a>
        @endcan

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive dt-responsive">
            <table class="table table-striped table-bordered nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الأيقونة</th>
                        <th>العنوان ({{ app()->getLocale() }})</th>
                        <th>الترتيب</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <img src="{{ asset('storage/' . $service->icon) }}" class="w-10 h-10">
                            </td>
                            <td>
                                {{ $service->translation()?->title }}
                            </td>
                            <td>{{ $service->order }}</td>
                            <td style="display: flex; gap: 5px;">
                                @can('edit', 'App\\Models\\Service')
                                    <a href="{{ route('dashboard.services.edit', $service->id) }}"
                                        class="btn btn-sm btn-warning">تعديل</a>
                                @endcan
                                @can('delete', 'App\\Models\\Service')
                                    <form action="{{ route('dashboard.services.destroy', $service->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $services->links() }}
        </div>
    </div>
</x-dashboard-layout>
