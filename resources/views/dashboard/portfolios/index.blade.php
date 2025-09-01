<x-dashboard-layout>


<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">إدارة المعرض</h1>

    @can('create','App\\Models\\Portfolio')
    <a href="{{ route('dashboard.portfolios.create') }}" class="btn btn-primary mb-4">➕ إضافة معرض جديد</a>
    @endcan

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive dt-responsive">
        <table class="table table-striped table-bordered nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الصورة</th>
                    <th>العنوان ({{ app()->getLocale() }})</th>
                    <th>الحالة</th>
                    <th>النوع</th>
                    <th>الترتيب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($portfolios as $portfolio)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <img src="{{ asset('storage/' . $portfolio->default_image) }}" class="w-10 h-10">
                    </td>
                    <td>
                        {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->title }}
                    </td>
                    <td>
                        {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->status }}</td>
                    <td>
                        {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->type }}</td>
                    <td>{{ $portfolio->order }}</td>
                    <td style="display: flex; gap: 5px;">
                        @can('edit','App\\Models\\Portfolio')
                            <a href="{{ route('dashboard.portfolios.edit', $portfolio->id) }}"
                                class="btn btn-sm btn-warning">تعديل</a>
                        @endcan
                        @can('delete','App\\Models\\Portfolio')
                                <form action="{{ route('dashboard.portfolios.destroy', $portfolio->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="btn btn-danger">حذف</button>
                                </form>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $portfolios->links() }}
    </div>
</div>
</x-dashboard-layout>
