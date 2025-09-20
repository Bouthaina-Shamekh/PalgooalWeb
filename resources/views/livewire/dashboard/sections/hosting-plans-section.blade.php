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
        <button onclick="confirmDeleteSection({{ $section->id }})" class="text-red-600 hover:underline text-sm">{{ t('section.Delete', 'Delete') }}</button>
    </div>

    <!-- Section order -->
    <div class="col-span-12 md:col-span-2 mb-4">
        <label class="form-label">{{ t('section.Order', 'Order') }}</label>
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

    {{-- Fields for the active language --}}
    <div wire:key="hosting-plans-{{ $activeLang }}" class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Title', 'Title') }}</label>
            <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.title" class="form-control" placeholder="{{ t('section.Title', 'Title') }}" />
            @error("translationsData.{$activeLang}.title") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Brief_description', 'Brief description') }}</label>
            <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.subtitle" class="form-control" placeholder="{{ t('section.Brief_description', 'Brief description') }}" />
            @error("translationsData.{$activeLang}.subtitle") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- NEW: choose a category by select OR provide slug --}}
        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Select category', 'Select category') }}</label>
            <select wire:model="selectedCategoryId" class="form-select">
                <option value="">{{ t('section.None', '-- None --') }}</option>
                @foreach($availableCategories as $cat)
                    @php
                        $catLabel = $cat->translation()?->title ?? $cat->translations->first()?->title ?? ('#' . $cat->id);
                    @endphp
                    <option value="{{ $cat->id }}">{{ $catLabel }} (id: {{ $cat->id }})</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ t('section.Or provide slug', 'Or provide category slug for finer control') }}</p>
        </div>

        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Category slug', 'Category slug (optional)') }}</label>
            <input type="text" wire:model.defer="categorySlug" class="form-control" placeholder="{{ t('section.Slug_example', 'e.g. web-hosting') }}" />
            @error('categorySlug') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <!-- Small preview -->
    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-dashed">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ t('section.Preview', 'Preview') }}</p>
                @if($selectedCategory)
                    <p class="font-semibold">{{ $selectedCategory->translation()?->title ?? $selectedCategory->translations->first()?->title ?? __('Category :id', ['id' => $selectedCategory->id]) }}</p>
                    <p class="text-xs text-gray-500">{{ $selectedCategory->translation()?->description ?? '' }}</p>
                @elseif(!empty($categorySlug))
                    <p class="font-semibold">Slug: {{ $categorySlug }}</p>
                    <p class="text-xs text-gray-500">{{ t('section.Slug_will_be_used', 'The slug will be resolved on frontend') }}</p>
                @else
                    <p class="text-sm text-gray-500">{{ t('section.No_category_selected', 'No category selected — all plans will be shown') }}</p>
                @endif
            </div>

            <div>
                <p class="text-sm text-gray-500">{{ t('section.Available_plans', 'Available plans count') }}</p>
                <p class="text-lg font-semibold">{{ $previewPlansCount }}</p>
            </div>
        </div>
    </div>

    <!-- Save -->
    <div class="text-end">
        <button wire:click="updateHostingPlansSection" wire:loading.attr="disabled" class="btn btn-primary">
            {{ t('section.Save_changes', 'Save changes')}}
        </button>
    </div>
</div>
