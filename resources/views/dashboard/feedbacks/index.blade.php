<x-dashboard-layout>


    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">إدارة التقييمات</h1>

        @can('create', 'App\\Models\\Feedback')
            <a href="{{ route('dashboard.feedbacks.create') }}" class="btn btn-primary mb-4">➕ إضافة تقييم جديد</a>
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
                        <th>التقييم</th>
                        <th>النص ({{ app()->getLocale() }})</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($feedbacks as $feedback)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <img src="{{ asset('storage/' . $feedback->image) }}" class="w-10 h-10">
                            </td>
                            <td>
                                {{ $feedback->translation()?->name }}
                            </td>
                            <td>{{ $feedback->star }}</td>
                            <td>
                                {{ $feedback->translation()?->feedback }}
                            </td>
                            <td style="display: flex; gap: 5px;">
                                @can('edit', 'App\\Models\\Feedback')
                                    <a href="{{ route('dashboard.feedbacks.edit', $feedback->id) }}"
                                        class="btn btn-sm btn-warning">تعديل</a>
                                @endcan
                                @can('delete', 'App\\Models\\Feedback')
                                    <form action="{{ route('dashboard.feedbacks.destroy', $feedback->id) }}" method="POST">
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
            {{ $feedbacks->links() }}
        </div>
    </div>
</x-dashboard-layout>
