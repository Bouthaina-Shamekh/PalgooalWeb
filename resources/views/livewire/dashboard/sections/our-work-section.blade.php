<div class="bg-white dark:bg-gray-800 rounded p-6 shadow mb-8 border">
    <h3 class="text-xl font-semibold mb-4">سكشن: احدث الاعمال (our works)</h3>
    
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
    <div wire:key="OurWorks-{{ $activeLang }}" class="space-y-4">
        <input type="text" wire:model="translationsData.{{ $activeLang }}.title"
            placeholder="عنوان القسم" class="form-input w-full px-4 py-2 rounded border" />

        <input type="text" wire:model="translationsData.{{ $activeLang }}.subtitle"
            placeholder="الوصف المختصر" class="form-input w-full px-4 py-2 rounded border" />

        {{-- المميزات --}}
        <div class="space-y-4">
            @foreach ($translationsData[$activeLang]['OurWorks'] ?? [] as $index => $feature)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start border p-4 rounded relative">
                    <input type="text" wire:model="translationsData.{{ $activeLang }}.OurWorks.{{ $index }}.icon"
                        placeholder="كود SVG أو اسم الأيقونة"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <input type="text" wire:model="translationsData.{{ $activeLang }}.OurWorks.{{ $index }}.title"
                        placeholder="عنوان الميزة"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <input type="text" wire:model="translationsData.{{ $activeLang }}.OurWorks.{{ $index }}.description"
                        placeholder="وصف مختصر"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <button wire:click="removeOurWorks('{{ $activeLang }}', {{ $index }})"
                        class="absolute -top-2 -left-2 bg-red-500 text-white text-sm rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
                        &times;
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <button wire:click="updateOurWorksSection"
        class="mt-6 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
        حفظ التعديلات
    </button>
</div>
