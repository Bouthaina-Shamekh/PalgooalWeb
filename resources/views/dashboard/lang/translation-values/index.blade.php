<x-dashboard-layout>

<h2 class="text-xl font-bold mb-4">Translation Values</h2>

<a href="{{ route('dashboard.translation-values.create') }}" class="btn btn-primary mb-4">Add New Translation</a>

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<!-- Language Filter -->
<form method="GET" action="{{ route('dashboard.translation-values.index') }}" class="mb-4">
    <select name="locale" onchange="this.form.submit()" class="w-48 border px-2 py-1 rounded">
        <option value="">-- All Languages --</option>
        @foreach($languages as $lang)
            <option value="{{ $lang->code }}" {{ $localeFilter == $lang->code ? 'selected' : '' }}>
                {{ $lang->native }} ({{ $lang->code }})
            </option>
        @endforeach
    </select>
</form>

<table class="table table-hover">
    <thead>
        <tr>
            <th>Key</th>
            <th>Type</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($translations as $key => $items)
            @php
                $type = Str::startsWith($key, 'dashboard.') ? 'Dashboard' :
                        (Str::startsWith($key, 'frontend.') ? 'Frontend' : 'General');
            @endphp
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $type }}</td>
                <td>
                    <a href="{{ route('dashboard.translation-values.edit', ['key' => $key]) }}" class="btn btn-sm btn-primary">
                        تعديل الترجمة
                    </a>

                    <form action="{{ route('dashboard.translation-values.destroy', ['key' => $key]) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</x-dashboard-layout>
