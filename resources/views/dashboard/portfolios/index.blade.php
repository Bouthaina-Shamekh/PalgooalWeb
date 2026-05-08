<x-dashboard-layout>

<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">إدارة المعرض</h1>

    @can('create', 'App\Models\Portfolio')
        <a href="{{ route('dashboard.portfolios.create') }}" class="btn btn-primary mb-4">➕ إضافة معرض جديد</a>
    @endcan

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('error'))
        <div class="bg-red-100 text-red-800 p-4 rounded mb-4">
            {{ $errors->first('error') }}
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
                    @php
                        // P2+P3 fix: use the already eager-loaded collection, not the query builder
                        $trans = $portfolio->translations->firstWhere('locale', app()->getLocale());
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if($portfolio->default_image)
                                <img src="{{ asset('storage/' . $portfolio->default_image) }}"
                                     class="w-10 h-10 object-cover rounded" alt="">
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        {{-- P2 fix: null-safe operator prevents fatal error when translation is missing --}}
                        <td>{{ $trans?->title ?? '—' }}</td>
                        <td>{{ $trans?->status ?? '—' }}</td>
                        <td>{{ $trans?->type ?? '—' }}</td>
                        <td>{{ $portfolio->order }}</td>
                        <td style="display: flex; gap: 5px;">
                            {{-- P7 fix: 'update' matches ModelPolicy ability 'portfolios.update' --}}
                            @can('update', $portfolio)
                                <a href="{{ route('dashboard.portfolios.edit', $portfolio->id) }}"
                                   class="btn btn-sm btn-warning">تعديل</a>
                            @endcan
                            {{-- P14 fix: confirm before delete --}}
                            @can('delete', $portfolio)
                                <form action="{{ route('dashboard.portfolios.destroy', $portfolio->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا المعرض؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
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
