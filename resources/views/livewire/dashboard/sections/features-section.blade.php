<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-200 dark:border-gray-700 space-y-6">
    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="flex items-center gap-3 bg-green-100 text-green-900 border border-green-300 dark:bg-green-800/20 dark:text-green-100 dark:border-green-600 px-4 py-3 rounded-lg shadow transition-all duration-300" role="alert">
            <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif
    <div class="flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">{{ ucfirst($section->key) }}</h3>
        <button onclick="confirmDeleteSection({{ $section->id }})" class="text-red-600 hover:underline text-sm">{{ t('section.Delete', 'Delete')}}</button> 
    </div>

    <!-- Section arrangement -->
    <div class="col-span-12 md:col-span-2 mb-4">
        <label class="form-label">{{ t('section.Section_Arrangement', 'Section Arrangement')}}</label>
        <input type="number" wire:model.defer="order" class="form-control" placeholder="{{ t('section.Example:', 'Example: 1, 2, 3')}}" />
    </div>

    <!-- Language tabs -->
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach($languages as $lang)
            <button wire:click="setActiveLang('{{ $lang->code }}')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition 
                    {{ $activeLang === $lang->code 
                        ? 'bg-primary text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-primary hover:text-white' }}">
                {{ $lang->native }}
            </button>
        @endforeach
    </div>
 
    {{-- عنوان ووصف --}}
    <div wire:key="features-{{ $activeLang }}" class="space-y-4">
        <input type="text" wire:model="translationsData.{{ $activeLang }}.title"
            placeholder="عنوان القسم" class="form-input w-full px-4 py-2 rounded border" />

        <input type="text" wire:model="translationsData.{{ $activeLang }}.subtitle"
            placeholder="الوصف المختصر" class="form-input w-full px-4 py-2 rounded border" />

        {{-- المميزات --}}
        <div class="space-y-4">
            @foreach ($translationsData[$activeLang]['features'] ?? [] as $index => $feature)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start border p-4 rounded relative">
                    <input type="text" wire:model="translationsData.{{ $activeLang }}.features.{{ $index }}.icon"
                        placeholder="كود SVG أو اسم الأيقونة"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <input type="text" wire:model="translationsData.{{ $activeLang }}.features.{{ $index }}.title"
                        placeholder="عنوان الميزة"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <input type="text" wire:model="translationsData.{{ $activeLang }}.features.{{ $index }}.description"
                        placeholder="وصف مختصر"
                        class="form-input w-full px-4 py-2 rounded border" />

                    <button wire:click="removeFeature('{{ $activeLang }}', {{ $index }})"
                        class="absolute -top-2 -left-2 bg-red-500 text-white text-sm rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
                        &times;
                    </button>
                </div>
            @endforeach
        </div>

        {{-- زر إضافة ميزة جديدة --}}
        <button wire:click="addFeature('{{ $activeLang }}')"
            class="mt-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
            + إضافة ميزة
        </button>
    </div>

    <button wire:click="updateFeatureSection"
        class="mt-6 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
        حفظ التعديلات
    </button>
</div>
