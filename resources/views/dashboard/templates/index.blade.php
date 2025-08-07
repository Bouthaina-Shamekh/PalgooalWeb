<x-dashboard-layout>


<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">جميع القوالب</h1>

    <a href="{{ route('dashboard.templates.create') }}" class="btn btn-primary mb-4">➕ إضافة قالب جديد</a>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full bg-white shadow rounded-lg overflow-hidden">
        <thead class="bg-gray-100 text-right">
            <tr>
                <th class="p-4">#</th>
                <th class="p-4">الاسم</th>
                <th class="p-4">الصورة</th>
                <th class="p-4">السعر</th>
                <th class="p-4">التصنيف</th>
                <th class="p-4">خيارات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($templates as $template)
                <tr class="border-t">
                    <td class="p-4">{{ $template->id }}</td>
                    <td class="p-4">{{ $template->translation()?->name ?? '—' }}</td>
                    <td class="p-4">
                        <img src="{{ asset('storage/' . $template->image) }}" class="h-16 w-20 object-cover rounded" alt="صورة القالب">
                    </td>
                    <td class="p-4">{{ $template->price }} $</td>
                    <td class="p-4">{{ $template->categoryTemplate->translation?->name ?? '—' }}</td>
                    <td class="p-4">
                        <a href="{{ route('dashboard.templates.edit', $template->id) }}" class="text-blue-600 hover:underline">تعديل</a> |
                        <form action="{{ route('dashboard.templates.destroy', $template->id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">حذف</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</div>
</x-dashboard-layout>
