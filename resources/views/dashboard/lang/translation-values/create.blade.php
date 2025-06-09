<x-dashboard-layout>

<h2 class="text-xl font-bold mb-4">Add New Translation</h2>

<form action="{{ route('dashboard.translation-values.store') }}" method="POST">
    @csrf

    <div class="mb-4">
        <label class="block mb-1 font-semibold">Key</label>
        <input type="text" name="key" class="w-full border px-3 py-2 rounded" required>
    </div>

    @foreach($languages as $lang)
        <div class="mb-4">
            <label class="block mb-1 font-semibold">{{ $lang->native }} ({{ $lang->code }})</label>
            <input type="text" name="values[{{ $lang->code }}]" class="w-full border px-3 py-2 rounded">
        </div>
    @endforeach

    <button type="submit" class="btn btn-primary">حفظ الترجمة</button>
</form>

</x-dashboard-layout>
