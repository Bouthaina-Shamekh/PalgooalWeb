<x-dashboard-layout>

<h2 class="text-xl font-bold mb-4">تعديل الترجمة - {{ $key }}</h2>

<form action="{{ route('dashboard.translation-values.update', ['key' => $key]) }}" method="POST">
    @csrf

    @foreach($languages as $lang)
        <div class="mb-4">
            <label class="block mb-1 font-semibold">{{ $lang->native }} ({{ $lang->code }})</label>
            <input type="text" name="values[{{ $lang->code }}]" value="{{ $translations[$lang->code]->value ?? '' }}" class="w-full border px-3 py-2 rounded">
        </div>
    @endforeach

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
</form>

</x-dashboard-layout>
