<div class="bg-white dark:bg-gray-800 rounded p-6 shadow mb-8 border">
    <h3 class="text-xl font-semibold mb-4">سكشن: الخدمات (services)</h3>
    
    <button wire:click="deleteMySection" class="text-red-600 hover:underline mb-4">حذف</button>

    {{-- لغات --}}
    <div class="flex gap-2 mb-4">
        @foreach($languages as $lang)
            <button wire:click="setActiveLang('{{ $lang->code }}')"
                class="px-4 py-2 rounded {{ $activeLang === $lang->code ? 'bg-primary text-white' : 'bg-gray-200' }}">
                {{ $lang->native }}
            </button>
        @endforeach
    </div>

    {{-- عنوان ووصف --}}
    <div wire:key="services-{{ $activeLang }}" class="space-y-4">
        <input type="text" wire:model="translationsData.{{ $activeLang }}.title"
            placeholder="عنوان القسم" class="form-input w-full px-4 py-2 rounded border" />

        <input type="text" wire:model="translationsData.{{ $activeLang }}.subtitle"
            placeholder="الوصف المختصر" class="form-input w-full px-4 py-2 rounded border" />
    </div>

    <button wire:click="updateservicesSection"
        class="mt-6 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
        حفظ التعديلات
    </button>
</div>

