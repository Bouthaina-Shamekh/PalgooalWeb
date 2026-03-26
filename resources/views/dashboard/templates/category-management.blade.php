<x-dashboard-layout :title="t('dashboard.Template_Categories', 'Template Categories')">
    @php
        $isEditing = $editingCategory !== null;
        $tabbedLanguages = $languages->filter(fn ($language) => filled($language->code))->values();
        $initialActiveLang = old('active_lang', $activeLang ?? $tabbedLanguages->first()?->code ?? app()->getLocale());
    @endphp

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[32px] bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-900/10 sm:px-8 lg:px-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,0.28),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.2),_transparent_30%)]"></div>
                <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div class="max-w-3xl text-right">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.24em] text-emerald-100">
                            {{ t('dashboard.Template_Categories', 'TEMPLATE CATEGORIES') }}
                        </span>
                        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                            {{ t('dashboard.Template_Categories', 'إدارة تصنيفات القوالب') }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                            {{ t('dashboard.Template_Categories_Desc', 'واجهة إدارة قائمة التصنيفات الخاصة بالقوالب بدون Livewire، مع الحفاظ على نفس البيانات ومسارات الحفظ والحذف من خلال Controllers وBlade فقط.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-stretch gap-3 lg:justify-end">
                        <a href="{{ route('dashboard.templates.index') }}"
                            class="inline-flex min-h-[56px] items-center justify-center gap-2 whitespace-nowrap rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 0 1 2-2h3a1 1 0 1 1 0 2H5v10h10v-3a1 1 0 1 1 2 0v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm8.293-1.707a1 1 0 0 1 1.414 0L17 7.586V6a1 1 0 1 1 2 0v4a1 1 0 0 1-1 1h-4a1 1 0 1 1 0-2h1.586l-4.293-4.293a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                            </svg>
                            {{ t('dashboard.All_templates', 'كل القوالب') }}
                        </a>

                        <div class="min-w-[140px] rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-xs font-medium text-slate-300">{{ t('dashboard.Categories', 'التصنيفات') }}</p>
                            <p class="mt-1 text-2xl font-black text-white">{{ number_format($categories->count()) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 shadow-sm">
                    <p class="font-bold">{{ __('Please review the highlighted category fields.') }}</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(360px,0.95fr)]">
                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tracking-[0.18em] text-slate-600">
                                {{ t('dashboard.Current_Categories', 'CURRENT CATEGORIES') }}
                            </span>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ t('dashboard.Current_Categories', 'التصنيفات الحالية') }}</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-500">
                                {{ t('dashboard.Template_Categories_List_Desc', 'هذه القائمة تعرض التصنيفات المحفوظة حاليًا مع روابط التعديل والحذف عبر Controllers فقط.') }}
                            </p>
                        </div>
                        <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700">
                            {{ number_format($categories->count()) }} {{ t('dashboard.Items_On_This_Page', 'عنصر في هذه الصفحة') }}
                        </div>
                    </div>

                    @if ($categories->isNotEmpty())
                        <div class="mt-6 space-y-4">
                            @foreach ($categories as $category)
                                @php
                                    $primaryTranslation = $category->getTranslation(app()->getLocale())
                                        ?? $category->getTranslation('ar')
                                        ?? $category->translations->first();
                                @endphp
                                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4 shadow-sm transition hover:border-slate-300 hover:bg-white">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white">
                                                    #{{ $category->id }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                    {{ $category->translations->count() }} {{ __('translations') }}
                                                </span>
                                            </div>

                                            <h3 class="mt-3 truncate text-lg font-black tracking-tight text-slate-900">
                                                {{ $primaryTranslation?->name ?? __('Untitled Category') }}
                                            </h3>

                                            <p class="mt-2 text-sm font-semibold text-slate-500">
                                                {{ $primaryTranslation?->slug ?? __('No slug yet') }}
                                            </p>

                                            <p class="mt-3 text-sm leading-7 text-slate-500">
                                                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($primaryTranslation?->description ?? '')), 140) ?: __('No description yet.') }}
                                            </p>

                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($category->translations as $translation)
                                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                        {{ strtoupper((string) $translation->locale) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="grid gap-2 sm:min-w-[180px]">
                                            <a href="{{ route('dashboard.category.edit', $category) }}"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-slate-800">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M17.414 2.586a2 2 0 0 0-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 0 0 0-2.828Z" />
                                                    <path fill-rule="evenodd" d="M2 16a2 2 0 0 1 2-2h2a1 1 0 1 1 0 2H4v2h12v-2h-2a1 1 0 1 1 0-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2Z" clip-rule="evenodd" />
                                                </svg>
                                                {{ t('dashboard.Edit', 'تعديل') }}
                                            </a>

                                            <form action="{{ route('dashboard.category.destroy', $category) }}" method="POST"
                                                onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Category', 'هل أنت متأكد من حذف هذه الفئة؟') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 transition hover:-translate-y-0.5 hover:bg-rose-100">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M7 4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1h3a1 1 0 1 1 0 2h-1v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7H4a1 1 0 1 1 0-2h3V4Zm2 0v1h2V4H9Zm-1 5a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V9Zm4-1a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0V9a1 1 0 0 0-1-1Z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ t('dashboard.Delete', 'حذف') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-8 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-400 shadow-sm">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H10l2 2h5.5A2.5 2.5 0 0 1 20 9.5v7A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-xl font-black text-slate-900">{{ t('dashboard.No_categories_found', 'لا توجد تصنيفات بعد') }}</h3>
                            <p class="mt-2 text-sm leading-7 text-slate-500">
                                {{ t('dashboard.Add_First_Category', 'ابدأ بإضافة أول تصنيف للقوالب من النموذج الجانبي.') }}
                            </p>
                        </div>
                    @endif
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tracking-[0.18em] text-slate-600">
                                {{ $isEditing ? t('dashboard.Edit_Category', 'EDIT CATEGORY') : t('dashboard.Add_New_Category', 'ADD CATEGORY') }}
                            </span>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">
                                {{ $isEditing ? t('dashboard.Edit_Category', 'تعديل الفئة') : t('dashboard.Add_New_Category', 'إضافة فئة جديدة') }}
                            </h2>
                            <p class="mt-2 text-sm leading-7 text-slate-500">
                                {{ t('dashboard.Template_Category_Form_Desc', 'احفظ كل ترجمة من نفس الصفحة، ثم ارجع مباشرة إلى قائمة القوالب أو واصل التعديل.') }}
                            </p>
                        </div>

                        @if ($isEditing)
                            <a href="{{ route('dashboard.category') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-900">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 0 1 .993.883L11 4v5h5a1 1 0 0 1 .117 1.993L16 11h-5v5a1 1 0 0 1-1.993.117L9 16v-5H4a1 1 0 0 1-.117-1.993L4 9h5V4a1 1 0 0 1 1-1Z" clip-rule="evenodd" />
                                </svg>
                                {{ t('dashboard.Cancel', 'إلغاء') }}
                            </a>
                        @endif
                    </div>

                    <form method="POST"
                        action="{{ $isEditing ? route('dashboard.category.update', $editingCategory) : route('dashboard.category.store') }}"
                        class="mt-6 space-y-6">
                        @csrf
                        @if ($isEditing)
                            @method('PATCH')
                        @endif

                        <input type="hidden" name="active_lang" id="template-category-active-lang" value="{{ $initialActiveLang }}">

                        <div class="flex flex-wrap gap-2 rounded-3xl border border-slate-200 bg-slate-100/80 p-2">
                            @foreach ($tabbedLanguages as $language)
                                <button type="button"
                                    data-category-tab-button="{{ $language->code }}"
                                    class="rounded-2xl px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-white hover:text-slate-900">
                                    {{ $language->name ?? strtoupper((string) $language->code) }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ($tabbedLanguages as $language)
                            @php
                                $languageCode = (string) $language->code;
                                $translation = $formTranslations[$languageCode] ?? ['name' => '', 'slug' => '', 'description' => ''];
                            @endphp
                            <div data-category-tab-panel="{{ $languageCode }}" class="space-y-5 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-900">{{ $language->name ?? strtoupper($languageCode) }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ __('Category translation fields for this language.') }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 shadow-sm">
                                        {{ strtoupper($languageCode) }}
                                    </span>
                                </div>

                                <div class="grid gap-5">
                                    <label class="block">
                                        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.Category_Name', 'اسم التصنيف') }}</span>
                                        <input type="text"
                                            name="translations[{{ $languageCode }}][name]"
                                            value="{{ old("translations.{$languageCode}.name", $translation['name']) }}"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                            placeholder="{{ t('dashboard.Category_Name', 'اسم التصنيف') }}">
                                        @error("translations.{$languageCode}.name")
                                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.Slug', 'الرابط المختصر') }}</span>
                                        <input type="text"
                                            name="translations[{{ $languageCode }}][slug]"
                                            value="{{ old("translations.{$languageCode}.slug", $translation['slug']) }}"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                            placeholder="{{ t('dashboard.Slug', 'الرابط المختصر') }}">
                                        @error("translations.{$languageCode}.slug")
                                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.Description', 'الوصف') }}</span>
                                        <textarea
                                            name="translations[{{ $languageCode }}][description]"
                                            rows="5"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                            placeholder="{{ t('dashboard.Description', 'الوصف') }}">{{ old("translations.{$languageCode}.description", $translation['description']) }}</textarea>
                                        @error("translations.{$languageCode}.description")
                                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </label>
                                </div>
                            </div>
                        @endforeach

                        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-slate-800">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-7.25 7.25a1 1 0 0 1-1.414 0l-3.25-3.25a1 1 0 1 1 1.414-1.414l2.543 2.543 6.543-6.543a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
                                </svg>
                                {{ $isEditing ? t('dashboard.Save_Changes', 'حفظ التعديلات') : t('dashboard.Add_Category', 'إضافة التصنيف') }}
                            </button>

                            @if ($isEditing)
                                <a href="{{ route('dashboard.category') }}"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-900">
                                    {{ t('dashboard.Cancel', 'إلغاء') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const buttons = Array.from(document.querySelectorAll('[data-category-tab-button]'));
                const panels = Array.from(document.querySelectorAll('[data-category-tab-panel]'));
                const activeInput = document.getElementById('template-category-active-lang');

                if (!buttons.length || !panels.length || !activeInput) {
                    return;
                }

                const activateTab = (languageCode) => {
                    const targetCode = buttons.some((button) => button.dataset.categoryTabButton === languageCode)
                        ? languageCode
                        : (buttons[0]?.dataset.categoryTabButton || '');

                    activeInput.value = targetCode;

                    buttons.forEach((button) => {
                        const isActive = button.dataset.categoryTabButton === targetCode;
                        button.classList.toggle('bg-white', isActive);
                        button.classList.toggle('text-slate-900', isActive);
                        button.classList.toggle('shadow-sm', isActive);
                        button.classList.toggle('text-slate-600', !isActive);
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.categoryTabPanel !== targetCode);
                    });
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', function () {
                        activateTab(button.dataset.categoryTabButton || '');
                    });
                });

                activateTab(activeInput.value || buttons[0]?.dataset.categoryTabButton || '');
            });
        </script>
    @endpush
</x-dashboard-layout>
