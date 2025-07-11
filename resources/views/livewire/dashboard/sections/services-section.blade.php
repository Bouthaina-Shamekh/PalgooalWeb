<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-200 dark:border-gray-700 space-y-6">
    {{-- ✅ رسالة النجاح --}}
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
        {{-- <button wire:click="deleteMySection" onclick="return confirm('هل أنت متأكد من حذف هذا السكشن؟')" class="text-red-600 hover:underline text-sm">{{ t('section.Delete', 'Delete')}}</button> --}}
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

    <!-- services Section Fields -->
    <div wire:key="services-{{ $activeLang }}" class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Title', 'Title')}}</label>
            <input type="text" wire:model="translationsData.{{ $activeLang }}.title" class="form-control" placeholder="{{ t('section.Title', 'Title')}}" />
        </div>
        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Brief_description', 'Brief description')}}</label>
            <input type="text" wire:model="translationsData.{{ $activeLang }}.subtitle" class="form-control" placeholder="{{ t('section.Brief_description', 'Brief description')}}" />
        </div>
    </div>

    <!-- Hero Section Save -->
    <div class="text-end">
        <button wire:click="updateservicesSection" class="btn btn-primary">
            {{ t('section.Save_changes', 'Save changes')}}
        </button>
    </div>
</div>

