<x-dashboard-layout>
    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">إدارة الشهادات</h1>

        @can('create', 'App\\Models\\Testimonial')
            <a href="{{ route('dashboard.testimonials.create') }}" class="btn btn-primary mb-4">إضافة شهادة جديدة</a>
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
                        <th>الصورة</th>
                        <th>الاسم ({{ app()->getLocale() }})</th>
                        <th>عدد النجوم</th>
                        <th>نص الشهادة ({{ app()->getLocale() }})</th>
                        <th>حالة الاعتماد</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testimonials as $testimonial)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <img src="{{ asset('storage/' . $testimonial->image) }}" class="w-10 h-10" alt="صورة الشهادة">
                            </td>
                            <td>
                                {{ $testimonial->translation()?->name ?? 'غير متوفر' }}
                            </td>
                            <td>{{ $testimonial->star }}</td>
                            <td>
                                {{ $testimonial->translation()?->feedback ?? 'لا يوجد نص' }}
                            </td>
                            <td>
                                @if ($testimonial->is_approved)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">معتمد</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">بانتظار الموافقة</span>
                                @endif
                            </td>
                            <td style="display: flex; gap: 5px;">
                                @can('edit', 'App\\Models\\Testimonial')
                                    <a href="{{ route('dashboard.testimonials.edit', $testimonial->id) }}"
                                        class="btn btn-sm btn-warning">تعديل</a>
                                @endcan
                                @can('delete', 'App\\Models\\Testimonial')
                                    <form action="{{ route('dashboard.testimonials.destroy', $testimonial->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الشهادة؟');">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $testimonials->links() }}
        </div>
    </div>
</x-dashboard-layout>
