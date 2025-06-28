<div class="space-y-6">
    <h2 class="text-xl font-bold mb-4">إدارة سكشنات الصفحة</h2>

    @if (session()->has('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- التبويبات للغات --}}
    <div class="flex flex-wrap gap-2 border-b pb-2 mb-6">
        @foreach($languages as $lang)
            <button wire:click="setActiveLang('{{ $lang->code }}')"
                    class="px-4 py-2 text-sm font-medium transition-all duration-200
                        {{ $activeLang === $lang->code ? 'text-primary border-b-2 border-primary' : 'text-gray-600 hover:text-primary' }}">
                {{ $lang->name }}
            </button>
        @endforeach
    </div>

    {{-- عرض السكشنات --}}
    @foreach ($sections as $section)
        @if ($section->key === 'hero')
            <div class="p-6 border rounded bg-white shadow space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold">سكشن: {{ ucfirst($section->key) }}</h3>
                    <button wire:click="deleteSection({{ $section->id }})"
                            class="text-red-600 hover:underline">حذف</button>
                </div>

                @php
                    $data = $translationsData[$section->id][$activeLang] ?? [];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">العنوان الرئيسي</label>
                        <input type="text" wire:model.defer="translationsData.{{ $section->id }}.{{ $activeLang }}.title"
                               class="w-full border p-2 rounded" placeholder="العنوان الرئيسي">
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">النص الفرعي</label>
                        <input type="text" wire:model.defer="translationsData.{{ $section->id }}.{{ $activeLang }}.subtitle"
                               class="w-full border p-2 rounded" placeholder="النص الفرعي">
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">نص الزر</label>
                        <input type="text" wire:model.defer="translationsData.{{ $section->id }}.{{ $activeLang }}.button_text"
                               class="w-full border p-2 rounded" placeholder="نص الزر">
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">رابط الزر</label>
                        <input type="text" wire:model.defer="translationsData.{{ $section->id }}.{{ $activeLang }}.button_url"
                               class="w-full border p-2 rounded" placeholder="رابط الزر">
                    </div>
                </div>

                <div class="text-end">
                    <button wire:click="updateSection({{ $section->id }}, '{{ $activeLang }}')" ...>
                        حفظ التعديلات للغة {{ $activeLang }}
                    </button>
                </div>
            </div>
        @endif
    @endforeach

    {{-- إضافة سكشن جديد --}}
    <div class="mt-10 border-t pt-6 space-y-6">
        <h3 class="text-lg font-semibold">إضافة سكشن جديد</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <select wire:model="sectionKey" class="border p-2 rounded w-full">
                <option value="">اختر نوع السكشن</option>
                @foreach ($availableKeys as $key)
                    <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                @endforeach
            </select>

            <input type="number" wire:model="sectionOrder" class="border p-2 rounded w-full" placeholder="الترتيب">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($languages as $lang)
                <div class="col-span-2 border p-4 rounded bg-gray-50">
                    <h4 class="font-bold mb-3">{{ $lang->name }} ({{ $lang->code }})</h4>

                    <label class="block text-sm mb-1">العنوان</label>
                    <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                           class="w-full border p-2 rounded mb-2">

                    <label class="block text-sm mb-1">النص الفرعي</label>
                    <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                           class="w-full border p-2 rounded mb-2">

                    <label class="block text-sm mb-1">نص الزر</label>
                    <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_text"
                           class="w-full border p-2 rounded mb-2">

                    <label class="block text-sm mb-1">رابط الزر</label>
                    <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_url"
                           class="w-full border p-2 rounded">
                </div>
            @endforeach
        </div>

        <button wire:click="addSection"
                class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90 transition">
            إضافة سكشن
        </button>
    </div>
</div>
<script>
    document.querySelectorAll('button[wire\\:click^="setActiveLang"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('⚠️ هل حفظت التعديلات قبل تغيير اللغة؟')) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    });
</script>