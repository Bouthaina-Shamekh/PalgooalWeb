<div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-200 dark:border-gray-700 space-y-6">
    @if (session()->has('success'))
        <div class="flex items-center gap-3 bg-green-100 text-green-900 border border-green-300 dark:bg-green-800/20 dark:text-green-100 dark:border-green-600 px-4 py-3 rounded-lg shadow transition-all duration-300" role="alert">
            <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="flex items-center gap-3 bg-red-100 text-red-900 border border-red-300 dark:bg-red-800/20 dark:text-red-100 dark:border-red-600 px-4 py-3 rounded-lg shadow transition-all duration-300" role="alert">
            <svg class="w-5 h-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white">{{ t('section.faq', 'FAQ') }}</h3>
        <button onclick="confirmDeleteSection({{ $section->id }})" class="text-red-600 hover:underline text-sm">
            {{ t('section.Delete', 'Delete') }}
        </button>
    </div>

    <div class="col-span-12 md:col-span-2 mb-4">
        <label class="form-label">{{ t('section.Section_Arrangement', 'Section Arrangement') }}</label>
        <input type="number" wire:model.defer="order" class="form-control" placeholder="{{ t('section.Example:', 'Example: 1, 2, 3') }}" />
    </div>

    <div class="flex flex-wrap gap-2 mt-4">
        @foreach ($languages as $lang)
            <button wire:click="setActiveLang('{{ $lang->code }}')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition {{ $activeLang === $lang->code ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-primary hover:text-white' }}">
                {{ $lang->native }}
            </button>
        @endforeach
    </div>

    <div wire:key="faq-{{ $activeLang }}" class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Title', 'Title') }}</label>
            <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.title" class="form-control" placeholder="{{ t('section.Title', 'Title') }}" />
        </div>

        <div class="col-span-12 md:col-span-6 mb-4">
            <label class="form-label">{{ t('section.Brief_description', 'Brief description') }}</label>
            <textarea wire:model.defer="translationsData.{{ $activeLang }}.subtitle" class="form-textarea w-full rounded-lg border dark:bg-gray-900" rows="3" placeholder="{{ t('section.Brief_description', 'Brief description') }}"></textarea>
        </div>

        <div class="col-span-12">
            <div class="flex items-center justify-between mb-3">
                <label class="form-label mb-0">{{ t('section.FAQ_List', 'FAQ Entries') }}</label>
                <button type="button" wire:click="addFaq('{{ $activeLang }}')" class="btn btn-sm btn-primary">
                    {{ t('section.Add_FAQ', '+ Add FAQ') }}
                </button>
            </div>

            <div class="space-y-4">
                @foreach ($translationsData[$activeLang]['items'] ?? [] as $index => $item)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-gray-50 dark:bg-gray-900/40" wire:key="faq-{{ $activeLang }}-{{ $index }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ t('section.Question', 'Question') }} #{{ $index + 1 }}
                            </span>
                            <button type="button" wire:click="removeFaq('{{ $activeLang }}', {{ $index }})" class="text-red-600 hover:text-red-500 text-sm">
                                {{ t('section.Remove', 'Remove') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <input type="text" wire:model.defer="translationsData.{{ $activeLang }}.items.{{ $index }}.question"
                                   class="form-control" placeholder="{{ t('section.Question', 'Question') }}">

                            <textarea wire:model.defer="translationsData.{{ $activeLang }}.items.{{ $index }}.answer"
                                      class="form-textarea w-full rounded-lg border dark:bg-gray-900" rows="3"
                                      placeholder="{{ t('section.Answer', 'Answer') }}"></textarea>
                        </div>
                    </div>
                @endforeach

                @if (empty($translationsData[$activeLang]['items']))
                    <p class="text-sm text-gray-500">
                        {{ t('section.No_FAQ_Items', 'No FAQ entries yet. Add your first question above.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="text-end">
        <button wire:click="updateFaqSection" class="btn btn-primary">
            {{ t('section.Save_changes', 'Save changes') }}
        </button>
    </div>
</div>
