<x-dashboard-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .template-save-button {
            background: linear-gradient(135deg, #240b36 0%, #6d28d9 100%) !important;
            border: 1px solid #240b36 !important;
            color: #fff !important;
            box-shadow: 0 16px 32px -20px rgba(36, 11, 54, 0.55);
        }

        .template-save-button:hover {
            background: linear-gradient(135deg, #1b0829 0%, #5b21b6 100%) !important;
        }

        .template-back-button {
            background: #fff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
        }

        .template-back-button:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
        }
    </style>

    @php
        $firstLocale = $languages->first()->code ?? null;
        $selectedImageMediaId = old('image_media_id');
        $selectedImageMedia = $selectedImageMediaId ? \App\Models\Media::find($selectedImageMediaId) : null;
        $selectedImagePreviewUrls = $selectedImageMedia ? [$selectedImageMedia->url] : [];
    @endphp

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[32px] bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-900/10 sm:px-8 lg:px-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(168,85,247,0.35),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.22),_transparent_28%)]"></div>
                <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div class="max-w-3xl text-right">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.24em] text-violet-100">
                            {{ t('dashboard.templates.form.template_creator_badge', 'TEMPLATE CREATOR') }}
                        </span>
                        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                            {{ t('dashboard.templates.form.create_title', 'إنشاء قالب جديد') }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                            {{ t('dashboard.templates.form.create_intro', 'جهّز القالب بكل بياناته الأساسية، ثم أضف الترجمة والمميزات والمعرض وروابط المعاينة من شاشة واحدة مرتبة وواضحة.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[360px]">
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs font-medium text-slate-300">{{ t('dashboard.templates.form.languages', 'اللغات') }}</p>
                            <p class="mt-2 text-2xl font-black text-white">{{ number_format($languages->count()) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs font-medium text-slate-300">{{ t('dashboard.templates.form.categories', 'التصنيفات') }}</p>
                            <p class="mt-2 text-2xl font-black text-white">{{ number_format($categories->count()) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs font-medium text-slate-300">{{ t('dashboard.templates.form.plans', 'الخطط') }}</p>
                            <p class="mt-2 text-2xl font-black text-white">{{ number_format($plans->count()) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 rounded-full bg-rose-100 p-2 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-8.75-3a.75.75 0 0 0 1.5 0 .75.75 0 0 0-1.5 0ZM10 8.75a.75.75 0 0 0-.75.75v4a.75.75 0 0 0 1.5 0v-4A.75.75 0 0 0 10 8.75Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold">{{ t('dashboard.templates.form.save_failed', 'تعذر حفظ القالب. راجع الحقول التالية ثم أعد المحاولة.') }}</p>
                            <ul class="mt-2 list-disc space-y-1 pe-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('dashboard.templates.store') }}" method="POST" enctype="multipart/form-data"
                class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_340px]">
                @csrf

                <div class="space-y-8">
                    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tracking-[0.18em] text-slate-600">
                                {{ t('dashboard.templates.form.core_setup_badge', 'CORE SETUP') }}
                            </span>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ t('dashboard.templates.form.basic_settings_title', 'الإعدادات الأساسية') }}</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-500">
                                {{ t('dashboard.templates.form.basic_settings_intro', 'اختر التصنيف والخطة ثم اضبط التسعير وصورة الغلاف قبل الانتقال إلى بيانات الترجمة.') }}
                            </p>
                        </div>

                        <div class="grid gap-6 px-6 py-6 sm:px-8 lg:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.category_label', 'تصنيف القالب') }}</span>
                                <select name="category_template_id" required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                                    <option value="">{{ t('dashboard.templates.form.choose_category', 'اختر التصنيف') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_template_id') == $category->id)>
                                            {{ $category->translation?->name ?? ('#' . $category->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.plan_label', 'الخطة المرتبطة') }}</span>
                                <select name="plan_id" required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                                    <option value="">{{ t('dashboard.templates.form.choose_plan', 'اختر الخطة') }}</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                            {{ $plan->title ?: ($plan->name ?? ('#' . $plan->id)) }}
                                            @if ($plan->monthly_price_formatted || $plan->annual_price_formatted)
                                                (
                                                @if ($plan->monthly_price_formatted)
                                                    {{ t('dashboard.templates.form.monthly', 'شهري') }} {{ $plan->monthly_price_formatted }}
                                                @endif
                                                @if ($plan->monthly_price_formatted && $plan->annual_price_formatted)
                                                    /
                                                @endif
                                                @if ($plan->annual_price_formatted)
                                                    {{ t('dashboard.templates.form.yearly', 'سنوي') }} {{ $plan->annual_price_formatted }}
                                                @endif
                                                )
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.base_price', 'السعر الأساسي ($)') }}</span>
                                <input type="number" name="price" step="0.01" required value="{{ old('price') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                    placeholder="99.00" />
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.discount_price', 'سعر الخصم ($)') }}</span>
                                <input type="number" name="discount_price" step="0.01" value="{{ old('discount_price') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                    placeholder="{{ t('dashboard.templates.form.optional', 'اختياري') }}" />
                            </label>

                            <label class="block lg:col-span-2">
                                <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.discount_ends_at', 'تاريخ انتهاء الخصم') }}</span>
                                <input type="datetime-local" name="discount_ends_at" value="{{ old('discount_ends_at') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100" />
                            </label>

                            <div class="lg:col-span-2 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-5 py-5">
                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-center">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ t('dashboard.templates.form.main_image_title', 'صورة القالب الرئيسية') }}</p>
                                        <p class="mt-2 text-sm leading-7 text-slate-500">
                                            {{ t('dashboard.templates.form.main_image_help', 'ارفع صورة غلاف واضحة بنسبة قريبة من 16:9 أو 5:3 لتظهر بشكل مناسب في صفحة العرض وفي قوائم القوالب.') }}
                                        </p>
                                    </div>
                                    <div class="block">
                                        <span class="sr-only">{{ t('dashboard.templates.form.main_image_sr', 'صورة القالب') }}</span>
                                        <x-dashboard.media-picker
                                            id="template_image_media_id"
                                            name="image_media_id"
                                            :label="t('dashboard.templates.form.main_image_picker_label', 'اختر صورة القالب من مكتبة الميديا')"
                                            :value="$selectedImageMediaId"
                                            :preview-urls="$selectedImagePreviewUrls"
                                            :button-text="t('dashboard.templates.form.choose_from_media_picker', 'اختيار من Media Picker')"
                                            class="col-span-12"
                                        />
                                        @error('image_media_id')
                                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-3 text-xs leading-6 text-slate-500">
                                            {{ t('dashboard.templates.form.main_image_picker_help', 'سيتم استخدام الصورة المختارة من Media Picker كغلاف رئيسي للقالب.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-xs font-bold tracking-[0.18em] text-violet-700">
                                        {{ t('dashboard.templates.form.translations_badge', 'TRANSLATIONS') }}
                                    </span>
                                    <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ t('dashboard.templates.form.translation_content_title', 'بيانات الترجمة والمحتوى') }}</h2>
                                    <p class="mt-2 text-sm leading-7 text-slate-500">
                                        {{ t('dashboard.templates.form.translation_content_intro', 'لكل لغة اسم ورابط ووصف مستقل، مع مميزات ومعرض وصور وتفاصيل إضافية محفوظة تلقائيًا بصيغة JSON.') }}
                                    </p>
                                </div>

                                <div class="w-full lg:w-auto lg:min-w-[440px]">
                                    <p class="mb-3 text-xs font-bold tracking-[0.18em] text-slate-500">
                                        {{ t('dashboard.templates.form.choose_locale', 'اختر اللغة التي تريد تعديلها') }}
                                    </p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($languages as $language)
                                        @php
                                            $locale = $language->code;
                                            $isActiveLocale = $locale === $firstLocale;
                                        @endphp
                                        <button type="button"
                                            data-locale-tab="{{ $locale }}"
                                            aria-pressed="{{ $isActiveLocale ? 'true' : 'false' }}"
                                            class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-start transition {{ $isActiveLocale ? 'border-violet-300 bg-violet-50 text-violet-950 shadow-sm ring-2 ring-violet-100' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                                            <span class="flex flex-1 items-center gap-3">
                                                <span data-locale-badge="{{ $locale }}"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full text-xs font-black {{ $isActiveLocale ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                                                    {{ strtoupper($locale) }}
                                                </span>
                                                <span class="min-w-0 flex-1 text-right">
                                                    <span class="block text-sm font-black">{{ $language->name }}</span>
                                                    <span data-locale-meta="{{ $locale }}"
                                                        class="mt-1 block text-xs {{ $isActiveLocale ? 'text-violet-700' : 'text-slate-500' }}">{{ t('dashboard.templates.form.locale_meta', 'بيانات هذه اللغة') }}</span>
                                                </span>
                                            </span>
                                            <span data-locale-check="{{ $locale }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $isActiveLocale ? 'bg-violet-100 text-violet-700' : 'hidden bg-slate-100 text-slate-400' }}">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.261a1 1 0 0 1-1.42-.008L4.3 10.08a1 1 0 1 1 1.4-1.428l2.998 2.94 6.5-6.55a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </button>
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 px-6 py-6 sm:px-8">
                            @foreach ($languages as $language)
                                @php
                                    $locale = $language->code;
                                    $i = $loop->index;
                                    $initialDetailsRaw = old("translations.$i.details", '');
                                    $initialDetailsDecoded = is_string($initialDetailsRaw) && $initialDetailsRaw !== ''
                                        ? json_decode($initialDetailsRaw, true)
                                        : [];
                                    $initialGalleryPaths = collect(is_array($initialDetailsDecoded) ? ($initialDetailsDecoded['gallery'] ?? []) : [])
                                        ->map(fn($item) => is_array($item) ? trim((string) ($item['src'] ?? '')) : '')
                                        ->filter()
                                        ->values()
                                        ->all();
                                    $initialGalleryPreviewUrls = collect($initialGalleryPaths)
                                        ->map(function ($path) {
                                            if (filter_var($path, FILTER_VALIDATE_URL)) {
                                                return $path;
                                            }

                                            if (str_starts_with($path, '/')) {
                                                return $path;
                                            }

                                            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                        })
                                        ->all();
                                    $initialDashboardPayload = is_array($initialDetailsDecoded['specs'] ?? null)
                                        ? ($initialDetailsDecoded['specs'] ?? [])
                                        : [];
                                    $initialDashboardDescription = trim((string) ($initialDashboardPayload['description'] ?? ''));
                                    $initialDashboardPaths = collect(is_array($initialDashboardPayload['images'] ?? null) ? ($initialDashboardPayload['images'] ?? []) : [])
                                        ->map(fn($item) => is_array($item) ? trim((string) ($item['src'] ?? '')) : '')
                                        ->filter()
                                        ->values()
                                        ->all();
                                    $initialDashboardPreviewUrls = collect($initialDashboardPaths)
                                        ->map(function ($path) {
                                            if (filter_var($path, FILTER_VALIDATE_URL)) {
                                                return $path;
                                            }

                                            if (str_starts_with($path, '/')) {
                                                return $path;
                                            }

                                            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                        })
                                        ->all();
                                @endphp

                                <div data-locale-panel="{{ $locale }}"
                                    class="rounded-[26px] border border-slate-200 bg-slate-50/80 p-5 sm:p-6 {{ $locale === $firstLocale ? '' : 'hidden' }}"
                                    data-locale-section>
                                    <div class="mb-6 flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900">{{ $language->name }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">{{ t('dashboard.templates.form.locale_section_intro_prefix', 'جميع الحقول أدناه تخص لغة') }} {{ $language->name }} {{ t('dashboard.templates.form.locale_section_intro_suffix', 'فقط.') }}</p>
                                        </div>
                                        <span class="inline-flex items-center self-start rounded-full bg-white px-3 py-1 text-xs font-bold tracking-[0.18em] text-slate-600">
                                            {{ strtoupper($locale) }}
                                        </span>
                                    </div>

                                    <input type="hidden" name="translations[{{ $i }}][locale]" value="{{ $locale }}">

                                    <div class="grid gap-5 lg:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.template_name', 'اسم القالب') }}</span>
                                            <input type="text" name="translations[{{ $i }}][name]"
                                                class="name-input w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                                                required value="{{ old("translations.$i.name") }}" placeholder="{{ t('dashboard.templates.form.template_name_placeholder', 'Single Product Ecommerce') }}" />
                                        </label>

                                        <div class="block">
                                            <label class="mb-2 flex items-center justify-between gap-3 text-sm font-semibold text-slate-700">
                                                <span>{{ t('dashboard.templates.form.slug_label', 'الرابط المختصر (slug)') }}</span>
                                                <button type="button" class="generate-slug text-xs font-bold text-violet-700 transition hover:text-violet-900">
                                                    {{ t('dashboard.templates.form.generate_slug', 'توليد تلقائي') }}
                                                </button>
                                            </label>
                                            <input type="text" name="translations[{{ $i }}][slug]"
                                                class="slug-input w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                                                required value="{{ old("translations.$i.slug") }}" placeholder="single-product-ecommerce" />
                                            <p class="mt-2 text-xs text-slate-500">{{ t('dashboard.templates.form.slug_help', 'يستخدم هذا الرابط في المسار العام لصفحة القالب.') }}</p>
                                        </div>

                                        <label class="block lg:col-span-2">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.preview_url', 'رابط المعاينة') }}</span>
                                            <input type="url" name="translations[{{ $i }}][preview_url]"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                                                value="{{ old("translations.$i.preview_url") }}" placeholder="https://example.com/demo" />
                                        </label>

                                        <label class="block lg:col-span-2">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.templates.form.description', 'الوصف') }}</span>
                                            <textarea name="translations[{{ $i }}][description]" rows="5"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                                                required placeholder="{{ t('dashboard.templates.form.description_placeholder', 'اكتب وصفًا مختصرًا يشرح وظيفة القالب وأبرز ما يقدمه.') }}">{{ old("translations.$i.description") }}</textarea>
                                        </label>
                                    </div>

                                    <div class="mt-6 grid gap-6 2xl:grid-cols-2">
                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-features-wrapper>
                                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.features_title', 'المميزات') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.features_intro', 'أضف نقاط البيع الأساسية التي ستظهر للمستخدم.') }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        class="add-feature btn btn-sm btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold shadow-sm">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                                        </svg>
                                                        {{ t('dashboard.templates.form.add_feature', 'إضافة ميزة') }}
                                                    </button>
                                                    <button type="button"
                                                        class="clear-features btn btn-sm btn-light inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold shadow-sm">
                                                        {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="space-y-3" data-features-list></div>
                                        </div>

                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-gallery-wrapper>
                                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.gallery_title', 'المعرض') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.gallery_intro', 'روابط صور الشاشات أو الأمثلة الخاصة بالقالب.') }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        class="add-image hidden btn btn-sm btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold shadow-sm">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                                        </svg>
                                                        {{ t('dashboard.templates.form.add_image', 'إضافة صورة') }}
                                                    </button>
                                                    <button type="button"
                                                        class="clear-images btn btn-sm btn-light inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold shadow-sm">
                                                        {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                                <x-dashboard.media-picker
                                                    id="gallery_picker_{{ $i }}"
                                                    name="gallery_picker_helper_{{ $i }}"
                                                    :label="t('dashboard.templates.form.gallery_picker_label', 'اختيار صور من مكتبة الميديا')"
                                                    :value="$initialGalleryPaths"
                                                    :preview-urls="$initialGalleryPreviewUrls"
                                                    :multiple="true"
                                                    store-value="path"
                                                    :button-text="t('dashboard.templates.form.choose_multiple_from_media_picker', 'اختيار متعدد من Media Picker')"
                                                    class="col-span-12"
                                                />
                                                <p class="mt-3 text-xs leading-6 text-slate-500">
                                                    {{ t('dashboard.templates.form.gallery_picker_help', 'يمكنك اختيار عدة صور دفعة واحدة من مكتبة الميديا، وستتم إضافتها تلقائيًا إلى المعرض أدناه.') }}
                                                </p>
                                            </div>
                                            <div class="space-y-3" data-images-list></div>
                                        </div>

                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-details-wrapper>
                                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.development_tools_title', 'الأدوات المستخدمة في التطوير') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.development_tools_intro', 'أضف اسم الأداة أو التقنية مع شعارها من مكتبة الميديا ليتم عرضها في قسم Used in development.') }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        class="add-detail btn btn-sm btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold shadow-sm">
                                                        {{ t('dashboard.templates.form.add_tool', 'إضافة أداة') }}
                                                    </button>
                                                    <button type="button"
                                                        class="clear-details btn btn-sm btn-light inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold shadow-sm">
                                                        {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="space-y-3" data-details-list></div>
                                        </div>

                                        <div class="rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-specs-wrapper>
                                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.dashboard_title', 'Dashboard') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.dashboard_intro', 'أضف وصفًا وصورًا متعددة لعرضها داخل قسم Dashboard في صفحة القالب.') }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        class="clear-specs btn btn-sm btn-light inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold shadow-sm">
                                                        {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mb-4">
                                                <textarea
                                                    class="dashboard-description w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                                    rows="4"
                                                    placeholder="{{ t('dashboard.templates.form.dashboard_description_placeholder', 'وصف قسم Dashboard') }}">{{ $initialDashboardDescription }}</textarea>
                                            </div>
                                            <div class="mb-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                                <x-dashboard.media-picker
                                                    id="dashboard_picker_{{ $i }}"
                                                    name="dashboard_picker_helper_{{ $i }}"
                                                    :label="t('dashboard.templates.form.dashboard_picker_label', 'اختيار صور الـ Dashboard من مكتبة الميديا')"
                                                    :value="$initialDashboardPaths"
                                                    :preview-urls="$initialDashboardPreviewUrls"
                                                    :multiple="true"
                                                    store-value="path"
                                                    :button-text="t('dashboard.templates.form.choose_multiple_from_media_picker', 'اختيار متعدد من Media Picker')"
                                                    class="col-span-12"
                                                />
                                                <p class="mt-3 text-xs leading-6 text-slate-500">
                                                    {{ t('dashboard.templates.form.dashboard_picker_help', 'يمكنك اختيار عدة صور دفعة واحدة، وسيتم استخدامها داخل قسم Dashboard مع الحفاظ على شكل الصفحة.') }}
                                                </p>
                                            </div>
                                            <div class="space-y-3" data-specs-list></div>
                                        </div>
                                    </div>

                                    <div class="mt-6 rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-browsers-wrapper>
                                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.compatible_browsers_title', 'Compatible Browsers') }}</h4>
                                                <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.compatible_browsers_intro', 'اختر المتصفحات المتوافقة مع القالب، ويمكنك إضافة متصفح مخصص إذا لزم.') }}</p>
                                            </div>
                                            <button type="button"
                                                class="clear-browsers btn btn-sm btn-light inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold shadow-sm">
                                                {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                            </button>
                                        </div>
                                        <div class="mb-4 flex flex-wrap gap-2">
                                            @foreach (['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'IE11'] as $browserPreset)
                                                <button type="button"
                                                    class="browser-preset inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700"
                                                    data-browser-preset="{{ $browserPreset }}">
                                                    {{ $browserPreset }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="mb-3 flex flex-col gap-3 sm:flex-row">
                                            <input type="text"
                                                class="browser-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                                placeholder="{{ t('dashboard.templates.form.browser_placeholder', 'أضف متصفحًا مخصصًا ثم اضغط إضافة') }}" />
                                            <button type="button"
                                                class="add-browser btn btn-sm btn-primary inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-bold shadow-sm">
                                                {{ t('dashboard.templates.form.add_browser', 'إضافة متصفح') }}
                                            </button>
                                        </div>
                                        <div class="flex flex-wrap gap-2" data-browsers-list></div>
                                    </div>

                                    <div class="mt-6 rounded-[22px] border border-slate-200 bg-white p-4 sm:p-5" data-tags-wrapper>
                                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <h4 class="text-base font-black text-slate-900">{{ t('dashboard.templates.form.tags_title', 'الوسوم') }}</h4>
                                                <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.templates.form.tags_intro', 'أضف كلمات مفتاحية قصيرة تساعد في تنظيم القالب وعرضه.') }}</p>
                                            </div>
                                        </div>
                                        <div class="mb-3 flex flex-col gap-3 sm:flex-row">
                                            <input type="text"
                                                class="tag-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100"
                                                placeholder="{{ t('dashboard.templates.form.tag_placeholder', 'اكتب الوسم ثم اضغط إضافة') }}" />
                                            <div class="flex gap-2">
                                                <button type="button"
                                                    class="add-tag btn btn-sm btn-primary inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-bold shadow-sm">
                                                    {{ t('dashboard.templates.form.add_tag', 'إضافة وسم') }}
                                                </button>
                                                <button type="button"
                                                    class="clear-tags btn btn-sm btn-light inline-flex items-center justify-center rounded-xl border px-4 py-3 text-sm font-bold shadow-sm">
                                                    {{ t('dashboard.templates.form.clear_all', 'تفريغ الكل') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2" data-tags-list></div>
                                    </div>

                                    <input type="hidden" name="translations[{{ $i }}][details]" class="details-json"
                                        value="{{ old("translations.$i.details", '') }}">
                                    <p class="mt-4 text-xs leading-6 text-slate-500">
                                        {{ t('dashboard.templates.form.details_json_help', 'يتم توليد بيانات المميزات والمعرض وDashboard والتفاصيل والمتصفحات والوسوم تلقائيًا داخل حقل JSON موحد وقت الحفظ.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <aside class="space-y-6 xl:sticky xl:top-8 xl:self-start">
                    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold tracking-[0.18em] text-violet-700">
                                {{ t('dashboard.templates.form.ready_to_save_badge', 'READY TO SAVE') }}
                            </span>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ t('dashboard.templates.form.save_actions_title', 'إجراءات الحفظ') }}</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                {{ t('dashboard.templates.form.save_actions_intro', 'بعد الحفظ يمكنك الرجوع للتعديل، إضافة صور أكثر، أو مراجعة صفحة العرض العامة.') }}
                            </p>
                        </div>

                        <div class="space-y-5 px-6 py-6">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold tracking-[0.18em] text-slate-500">{{ t('dashboard.templates.form.quick_summary', 'ملخص سريع') }}</p>
                                <div class="mt-4 space-y-3 text-sm text-slate-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <span>{{ t('dashboard.templates.form.translation_languages', 'لغات الترجمة') }}</span>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-900 shadow-sm ring-1 ring-slate-200">{{ number_format($languages->count()) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>{{ t('dashboard.templates.form.available_categories', 'التصنيفات المتاحة') }}</span>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-900 shadow-sm ring-1 ring-slate-200">{{ number_format($categories->count()) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>{{ t('dashboard.templates.form.available_plans', 'الخطط المتاحة') }}</span>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-900 shadow-sm ring-1 ring-slate-200">{{ number_format($plans->count()) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 pt-3">
                                <button type="submit"
                                    class="template-save-button inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-bold transition hover:-translate-y-0.5">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 2a1 1 0 0 1 1 1v1.293l4.854 4.853a.5.5 0 0 1 .146.354V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9.5a.5.5 0 0 1 .146-.354L9 4.293V3a1 1 0 0 1 1-1Z" />
                                    </svg>
                                    {{ t('dashboard.templates.form.save_template', 'حفظ القالب') }}
                                </button>

                                <a href="{{ route('dashboard.templates.index') }}"
                                    class="template-back-button inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-bold transition">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.56l3.22 3.22a.75.75 0 1 1-1.06 1.06l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 0 1 1.06 1.06L5.56 9.25h10.69A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ t('dashboard.templates.form.back_to_list', 'العودة إلى القائمة') }}
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                        <h3 class="text-lg font-black text-slate-900">{{ t('dashboard.templates.form.before_save', 'قبل الحفظ') }}</h3>
                        <div class="mt-4 space-y-4 text-sm leading-7 text-slate-600">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                {{ t('dashboard.templates.form.before_save_rule_one', 'تأكد من أن لكل لغة اسمًا و`slug` ووصفًا على الأقل، لأن هذه الحقول مطلوبة.') }}
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                {{ t('dashboard.templates.form.before_save_rule_two', 'إذا أضفت `Preview URL` فاستخدم رابطًا كاملاً وصحيحًا مثل `https://example.com/demo`.') }}
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                {{ t('dashboard.templates.form.before_save_rule_three', 'الوسوم والمميزات والمواصفات تحفظ تلقائيًا بمجرد تعبئة الحقول، ولا تحتاج أي خطوة إضافية.') }}
                            </div>
                        </div>
                    </section>
                </aside>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = Array.from(document.querySelectorAll('[data-locale-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-locale-panel]'));

            if (!tabs.length || !panels.length) {
                return;
            }

            const activeClasses = ['border-violet-300', 'bg-violet-50', 'text-violet-950', 'shadow-sm', 'ring-2', 'ring-violet-100'];
            const inactiveClasses = ['border-slate-200', 'bg-white', 'text-slate-700', 'hover:border-slate-300', 'hover:bg-slate-50'];
            const activeBadgeClasses = ['bg-violet-600', 'text-white'];
            const inactiveBadgeClasses = ['bg-slate-100', 'text-slate-700'];
            const activeMetaClasses = ['text-violet-700'];
            const inactiveMetaClasses = ['text-slate-500'];

            function activateLocale(locale) {
                tabs.forEach((tab) => {
                    const isActive = tab.dataset.localeTab === locale;
                    const badge = tab.querySelector('[data-locale-badge]');
                    const meta = tab.querySelector('[data-locale-meta]');
                    const check = tab.querySelector('[data-locale-check]');

                    tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    tab.classList.remove(...activeClasses, ...inactiveClasses);
                    tab.classList.add(...(isActive ? activeClasses : inactiveClasses));

                    if (badge) {
                        badge.classList.remove(...activeBadgeClasses, ...inactiveBadgeClasses);
                        badge.classList.add(...(isActive ? activeBadgeClasses : inactiveBadgeClasses));
                    }

                    if (meta) {
                        meta.classList.remove(...activeMetaClasses, ...inactiveMetaClasses);
                        meta.classList.add(...(isActive ? activeMetaClasses : inactiveMetaClasses));
                    }

                    if (check) {
                        check.classList.toggle('hidden', !isActive);
                    }
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.localePanel !== locale);
                });
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', function() {
                    activateLocale(tab.dataset.localeTab);
                });
            });

            activateLocale(tabs.find((tab) => tab.getAttribute('aria-pressed') === 'true')?.dataset.localeTab || tabs[0].dataset.localeTab);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('[data-locale-section]');

            sections.forEach(section => {
                const nameInput = section.querySelector('.name-input');
                const slugInput = section.querySelector('.slug-input');
                const generateBtn = section.querySelector('.generate-slug');

                if (nameInput && slugInput && generateBtn) {
                    generateBtn.addEventListener('click', function() {
                        slugInput.value = generateSlug(nameInput.value);
                    });
                }

                if (slugInput) {
                    slugInput.addEventListener('input', function() {
                        this.value = generateSlug(this.value);
                    });
                }
            });

            function generateSlug(input) {
                return (input || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[\s_]+/g, '-')
                    .replace(/[^a-zA-Z0-9\u0600-\u06FF\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const i18n = @json([
                'untitledImage' => t('dashboard.templates.form.js.untitled_image', 'Untitled image'),
                'featureTitlePlaceholder' => t('dashboard.templates.form.js.feature_title_placeholder', 'عنوان الميزة'),
                'featureIconPlaceholder' => t('dashboard.templates.form.js.feature_icon_placeholder', 'أيقونة أو رمز'),
                'imageUrlPlaceholder' => t('dashboard.templates.form.js.image_url_placeholder', 'رابط الصورة'),
                'imageLabel' => t('dashboard.templates.form.js.image_label', 'Image'),
                'altPlaceholder' => t('dashboard.templates.form.js.alt_placeholder', 'ALT'),
                'displayNamePlaceholder' => t('dashboard.templates.form.js.display_name_placeholder', 'ALT / اسم العرض'),
                'delete' => t('dashboard.templates.form.js.delete', 'حذف'),
                'noImage' => t('dashboard.templates.form.js.no_image', 'No image'),
                'noImageSelected' => t('dashboard.templates.form.js.no_image_selected', 'لم يتم اختيار صورة بعد'),
                'chooseImageFromLibrary' => t('dashboard.templates.form.js.choose_image_from_library', 'اختيار صورة من المكتبة'),
            ], JSON_UNESCAPED_UNICODE);

            document.querySelectorAll('[data-locale-section]').forEach(section => {
                const listFeatures = section.querySelector('[data-features-list]');
                const addFeature = section.querySelector('.add-feature');
                const clearFeatures = section.querySelector('.clear-features');

                const listImages = section.querySelector('[data-images-list]');
                const addImage = section.querySelector('.add-image');
                const clearImages = section.querySelector('.clear-images');
                const galleryPickerInput = section.querySelector('[id^="gallery_picker_"]');
                const galleryPickerPreview = galleryPickerInput
                    ? document.getElementById(`${galleryPickerInput.id}_preview`)
                    : null;

                const listSpecs = section.querySelector('[data-specs-list]');
                const clearSpecs = section.querySelector('.clear-specs');
                const dashboardDescriptionInput = section.querySelector('.dashboard-description');
                const dashboardPickerInput = section.querySelector('[id^="dashboard_picker_"]');
                const dashboardPickerPreview = dashboardPickerInput
                    ? document.getElementById(`${dashboardPickerInput.id}_preview`)
                    : null;

                const listDetails = section.querySelector('[data-details-list]');
                const addDetail = section.querySelector('.add-detail');
                const clearDetails = section.querySelector('.clear-details');

                const browserInput = section.querySelector('.browser-input');
                const addBrowserBtn = section.querySelector('.add-browser');
                const clearBrowsersBtn = section.querySelector('.clear-browsers');
                const browsersList = section.querySelector('[data-browsers-list]');
                const browserPresetButtons = Array.from(section.querySelectorAll('[data-browser-preset]'));

                const tagsInput = section.querySelector('.tag-input');
                const addTagBtn = section.querySelector('.add-tag');
                const clearTagsBtn = section.querySelector('.clear-tags');
                const tagsList = section.querySelector('[data-tags-list]');

                const detailsInp = section.querySelector('.details-json');
                let detailMediaCounter = 0;

                function escapeHtml(str) {
                    return (str || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function galleryPreviewUrl(path) {
                    const value = (path || '').trim();
                    if (!value) return '';
                    if (/^(https?:)?\/\//i.test(value) || value.startsWith('data:')) return value;
                    if (value.startsWith('/')) return value;
                    return `/storage/${value.replace(/^storage\//i, '').replace(/^\/+/, '')}`;
                }

                function galleryFileName(path) {
                    const value = (path || '').trim();
                    if (!value) return i18n.untitledImage;

                    const normalized = value.split('?')[0].split('#')[0];
                    const parts = normalized.split('/').filter(Boolean);
                    return parts[parts.length - 1] || value;
                }

                function renderGalleryPickerPreview(paths = []) {
                    if (!galleryPickerPreview) return;

                    galleryPickerPreview.innerHTML = '';

                    paths.forEach((path) => {
                        const previewUrl = galleryPreviewUrl(path);
                        if (!previewUrl) return;

                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
                        wrapper.innerHTML = `<img src="${escapeHtml(previewUrl)}" alt="" class="h-full w-full object-cover">`;
                        galleryPickerPreview.appendChild(wrapper);
                    });
                }

                function syncGalleryPickerFromRows() {
                    if (!galleryPickerInput) return;

                    const paths = Array.from(listImages?.querySelectorAll('.image-row .img-src') || [])
                        .map((input) => input.value.trim())
                        .filter(Boolean);

                    galleryPickerInput.value = paths.join(',');
                    renderGalleryPickerPreview(paths);
                }

                function applyGalleryPickerSelection() {
                    if (!galleryPickerInput || !listImages) return;

                    const selectedPaths = galleryPickerInput.value
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean);

                    const existingAltByPath = Object.fromEntries(
                        Array.from(listImages.querySelectorAll('.image-row'))
                            .map((row) => [
                                row.querySelector('.img-src')?.value.trim() || '',
                                row.querySelector('.img-alt')?.value.trim() || '',
                            ])
                            .filter(([src]) => src)
                    );

                    listImages.innerHTML = '';

                    selectedPaths.forEach((path) => {
                        listImages.appendChild(imageRow({
                            src: path,
                            alt: existingAltByPath[path] || '',
                        }));
                    });

                    renderGalleryPickerPreview(selectedPaths);
                    syncJson();
                }

                function renderDashboardPickerPreview(paths = []) {
                    if (!dashboardPickerPreview) return;

                    dashboardPickerPreview.innerHTML = '';

                    paths.forEach((path) => {
                        const previewUrl = galleryPreviewUrl(path);
                        if (!previewUrl) return;

                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative h-20 w-20 overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
                        wrapper.innerHTML = `<img src="${escapeHtml(previewUrl)}" alt="" class="h-full w-full object-cover">`;
                        dashboardPickerPreview.appendChild(wrapper);
                    });
                }

                function syncDashboardPickerFromRows() {
                    if (!dashboardPickerInput) return;

                    const paths = Array.from(listSpecs?.querySelectorAll('.spec-row .spec-img-src') || [])
                        .map((input) => input.value.trim())
                        .filter(Boolean);

                    dashboardPickerInput.value = paths.join(',');
                    renderDashboardPickerPreview(paths);
                }

                function applyDashboardPickerSelection() {
                    if (!dashboardPickerInput || !listSpecs) return;

                    const selectedPaths = dashboardPickerInput.value
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean);

                    const existingAltByPath = Object.fromEntries(
                        Array.from(listSpecs.querySelectorAll('.spec-row'))
                            .map((row) => [
                                row.querySelector('.spec-img-src')?.value.trim() || '',
                                row.querySelector('.spec-img-alt')?.value.trim() || '',
                            ])
                            .filter(([src]) => src)
                    );

                    listSpecs.innerHTML = '';

                    selectedPaths.forEach((path) => {
                        listSpecs.appendChild(specRow({
                            src: path,
                            alt: existingAltByPath[path] || '',
                        }));
                    });

                    renderDashboardPickerPreview(selectedPaths);
                    syncJson();
                }

                function featureRow(item = {
                    title: '',
                    icon: ''
                }) {
                    const row = document.createElement('div');
                    row.className = 'feature-row grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 p-3 sm:grid-cols-[1fr_160px_auto]';
                    row.innerHTML = `
                        <input type="text"
                               class="feat-title w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-900 focus:border-primary focus:ring-primary"
                               placeholder="${escapeHtml(i18n.featureTitlePlaceholder)}"
                               value="${escapeHtml(item.title)}">
                        <input type="text"
                               class="feat-icon w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-900 focus:border-primary focus:ring-primary"
                               placeholder="${escapeHtml(i18n.featureIconPlaceholder)}"
                               value="${escapeHtml(item.icon || '')}">
                        <button type="button"
                                class="remove-feature inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                            ${escapeHtml(i18n.delete)}
                        </button>`;
                    row.querySelector('.remove-feature').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
                    return row;
                }

                function imageRow(item = {
                    src: '',
                    alt: ''
                }) {
                    const row = document.createElement('div');
                    row.className = 'image-row grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 sm:grid-cols-[88px_1fr_auto] sm:items-center';
                    row.innerHTML = `
                        <input type="hidden"
                               class="img-src"
                               placeholder="${escapeHtml(i18n.imageUrlPlaceholder)}"
                               value="${escapeHtml(item.src)}">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            <div class="h-[88px] w-full overflow-hidden bg-slate-100">
                                <img src="${escapeHtml(galleryPreviewUrl(item.src))}"
                                     alt=""
                                     class="h-full w-full object-cover">
                            </div>
                            <div class="border-t border-slate-200 px-3 py-2">
                                <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(i18n.imageLabel)}</div>
                                <div class="mt-1 truncate text-xs font-semibold text-slate-700">${escapeHtml(galleryFileName(item.src))}</div>
                            </div>
                        </div>
                        <input type="text"
                               class="img-alt w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-900 focus:border-primary focus:ring-primary"
                               placeholder="${escapeHtml(i18n.altPlaceholder)}"
                               value="${escapeHtml(item.alt || '')}">
                        <button type="button"
                                class="remove-image inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                            ${escapeHtml(i18n.delete)}
                        </button>`;
                    row.querySelector('.remove-image').addEventListener('click', () => {
                        row.remove();
                        syncGalleryPickerFromRows();
                        syncJson();
                    });
                    row.querySelector('.img-alt')?.addEventListener('input', syncJson);
                    return row;
                }

                function specRow(item = {
                    src: '',
                    alt: ''
                }) {
                    const row = document.createElement('div');
                    row.className = 'spec-row grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 sm:grid-cols-[88px_1fr_auto] sm:items-center';
                    row.innerHTML = `
                        <input type="hidden"
                               class="spec-img-src"
                               value="${escapeHtml(item.src)}">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            <div class="h-[88px] w-full overflow-hidden bg-slate-100">
                                <img src="${escapeHtml(galleryPreviewUrl(item.src))}"
                                     alt=""
                                     class="h-full w-full object-cover">
                            </div>
                            <div class="border-t border-slate-200 px-3 py-2">
                                <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(i18n.imageLabel)}</div>
                                <div class="mt-1 truncate text-xs font-semibold text-slate-700">${escapeHtml(galleryFileName(item.src))}</div>
                            </div>
                        </div>
                        <input type="text"
                               class="spec-img-alt w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-900 focus:border-primary focus:ring-primary"
                               placeholder="${escapeHtml(i18n.altPlaceholder)}"
                               value="${escapeHtml(item.alt || '')}">
                        <button type="button"
                                class="remove-spec inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                            ${escapeHtml(i18n.delete)}
                        </button>`;
                    row.querySelector('.remove-spec').addEventListener('click', () => {
                        row.remove();
                        syncDashboardPickerFromRows();
                        syncJson();
                    });
                    row.querySelector('.spec-img-alt')?.addEventListener('input', syncJson);
                    return row;
                }

                function detailRow(item = {
                    src: '',
                    alt: '',
                    name: '',
                    value: ''
                }) {
                    const initialSrc = (item.src || item.image || item.value || '').trim();
                    const initialAlt = (item.alt || item.name || '').trim();
                    const mediaInputId = `detail_media_${Date.now()}_${detailMediaCounter++}`;
                    const row = document.createElement('div');
                    row.className = 'detail-row grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 p-3 sm:grid-cols-[88px_minmax(0,1fr)_auto] sm:items-center';
                    row.innerHTML = `
                        <input type="hidden"
                               class="detail-src"
                               id="${mediaInputId}"
                               value="${escapeHtml(initialSrc)}">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            <div class="detail-preview h-[88px] w-full overflow-hidden bg-slate-100">
                                ${initialSrc ? `<img src="${escapeHtml(galleryPreviewUrl(initialSrc))}" alt="" class="h-full w-full object-cover">` : `<div class="flex h-full items-center justify-center text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(i18n.noImage)}</div>`}
                            </div>
                            <div class="border-t border-slate-200 px-3 py-2">
                                <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(i18n.imageLabel)}</div>
                                <div class="detail-file mt-1 truncate text-xs font-semibold text-slate-700">${escapeHtml(initialSrc ? galleryFileName(initialSrc) : i18n.noImageSelected)}</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <button type="button"
                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                                        data-target-input="${mediaInputId}"
                                        data-multiple="false"
                                        data-store-value="path">
                                    ${escapeHtml(i18n.chooseImageFromLibrary)}
                                </button>
                            </div>
                            <input type="text"
                                   class="detail-alt w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-900 focus:border-primary focus:ring-primary"
                                   placeholder="${escapeHtml(i18n.displayNamePlaceholder)}"
                                   value="${escapeHtml(initialAlt)}">
                        </div>
                        <button type="button"
                                class="remove-detail inline-flex items-center justify-center rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                            ${escapeHtml(i18n.delete)}
                        </button>`;
                    row.querySelector('.remove-detail').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    const detailSrcInput = row.querySelector('.detail-src');
                    const detailFileLabel = row.querySelector('.detail-file');
                    const detailAltInput = row.querySelector('.detail-alt');
                    const detailPreview = row.querySelector('.detail-preview');
                    const syncDetailImage = () => {
                        const src = detailSrcInput?.value.trim() || '';
                        if (detailFileLabel) {
                            detailFileLabel.textContent = src ? galleryFileName(src) : i18n.noImageSelected;
                        }
                        if (detailPreview) {
                            detailPreview.innerHTML = src
                                ? `<img src="${escapeHtml(galleryPreviewUrl(src))}" alt="" class="h-full w-full object-cover">`
                                : `<div class="flex h-full items-center justify-center text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">${escapeHtml(i18n.noImage)}</div>`;
                        }
                        syncJson();
                    };
                    detailAltInput?.addEventListener('input', syncJson);
                    detailSrcInput?.addEventListener('input', syncDetailImage);
                    detailSrcInput?.addEventListener('change', syncDetailImage);
                    return row;
                }

                function deriveLegacyBrowsers(existing = {}) {
                    if (Array.isArray(existing.browsers) && existing.browsers.length) {
                        return existing.browsers;
                    }

                    const fromText = (value) => (value || '')
                        .split(',')
                        .map((item) => item.trim())
                        .filter(Boolean);

                    const legacyDetail = Array.isArray(existing.details)
                        ? existing.details.find((item) => /browser/i.test((item?.name || item?.label || '').toLowerCase()) && typeof item?.value === 'string')
                        : null;

                    if (legacyDetail?.value) {
                        return fromText(legacyDetail.value);
                    }

                    const legacySpec = Array.isArray(existing.specs)
                        ? existing.specs.find((item) => /browser/i.test((item?.name || '').toLowerCase()) && typeof item?.value === 'string')
                        : null;

                    if (legacySpec?.value) {
                        return fromText(legacySpec.value);
                    }

                    return [];
                }

                function addBrowserChip(label) {
                    const text = (label || '').trim();
                    if (!text || !browsersList) return;

                    const exists = Array.from(browsersList.querySelectorAll('[data-browser]'))
                        .some((el) => (el.dataset.browser || '').toLowerCase() === text.toLowerCase());
                    if (exists) return;

                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1 rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-bold text-cyan-700';
                    chip.setAttribute('data-browser', text);
                    chip.innerHTML = `
                        ${escapeHtml(text)}
                        <button type="button" class="remove-browser ml-1 text-cyan-700 transition hover:text-cyan-900">&times;</button>
                    `;
                    chip.querySelector('.remove-browser').addEventListener('click', () => {
                        chip.remove();
                        syncJson();
                    });
                    browsersList.appendChild(chip);
                    syncJson();
                }

                function addTagChip(label) {
                    const text = (label || '').trim();
                    if (!text || !tagsList) return;

                    const exists = Array.from(tagsList.querySelectorAll('[data-tag]'))
                        .some(el => (el.dataset.tag || '').toLowerCase() === text.toLowerCase());
                    if (exists) return;

                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary';
                    chip.setAttribute('data-tag', text);
                    chip.innerHTML = `
                        ${escapeHtml(text)}
                        <button type="button" class="remove-tag ml-1 text-primary transition hover:text-primary/70">&times;</button>
                    `;
                    chip.querySelector('.remove-tag').addEventListener('click', () => {
                        chip.remove();
                        syncJson();
                    });
                    tagsList.appendChild(chip);
                    syncJson();
                }

                function syncJson() {
                    const features = Array.from(listFeatures?.querySelectorAll('.feature-row') || [])
                        .map(r => ({
                            title: r.querySelector('.feat-title')?.value.trim() || '',
                            icon: r.querySelector('.feat-icon')?.value.trim() || '',
                        }))
                        .filter(x => x.title.length);

                    const gallery = Array.from(listImages?.querySelectorAll('.image-row') || [])
                        .map(r => ({
                            src: r.querySelector('.img-src')?.value.trim() || '',
                            alt: r.querySelector('.img-alt')?.value.trim() || '',
                        }))
                        .filter(x => x.src.length);

                    const specs = {
                        description: dashboardDescriptionInput?.value.trim() || '',
                        images: Array.from(listSpecs?.querySelectorAll('.spec-row') || [])
                            .map(r => ({
                                src: r.querySelector('.spec-img-src')?.value.trim() || '',
                                alt: r.querySelector('.spec-img-alt')?.value.trim() || '',
                            }))
                            .filter(x => x.src),
                    };

                    const details = Array.from(listDetails?.querySelectorAll('.detail-row') || [])
                        .map(r => {
                            const src = r.querySelector('.detail-src')?.value.trim() || '';
                            const alt = r.querySelector('.detail-alt')?.value.trim() || '';
                            const derivedName = alt || (src ? galleryFileName(src).replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').trim() : '');

                            return {
                                name: derivedName,
                                alt,
                                value: src,
                                src,
                            };
                        })
                        .filter(x => x.src);

                    const browsers = Array.from(browsersList?.querySelectorAll('[data-browser]') || [])
                        .map((el) => (el.dataset.browser || '').trim())
                        .filter(Boolean);

                    const tags = Array.from(tagsList?.querySelectorAll('[data-tag]') || [])
                        .map(el => (el.dataset.tag || '').trim())
                        .filter(Boolean);

                    let payload = {};
                    try {
                        if (detailsInp.value) payload = JSON.parse(detailsInp.value) || {};
                    } catch (e) {
                        payload = {};
                    }

                    payload.features = features;
                    payload.gallery = gallery;
                    payload.specs = specs;
                    payload.details = details;
                    payload.browsers = browsers;
                    payload.tags = tags;

                    detailsInp.value = JSON.stringify(payload);
                }

                (function init() {
                    let existing = {};
                    try {
                        if (detailsInp.value) {
                            existing = JSON.parse(detailsInp.value) || {};
                        } else if (detailsInp.dataset.existing) {
                            existing = JSON.parse(detailsInp.dataset.existing) || {};
                        }
                    } catch (e) {
                        existing = {};
                    }

                    if (listFeatures) {
                        if (Array.isArray(existing.features) && existing.features.length) {
                            existing.features.forEach(f => listFeatures.appendChild(featureRow(f)));
                        } else {
                            listFeatures.appendChild(featureRow());
                        }
                    }

                    if (listImages) {
                        if (Array.isArray(existing.gallery) && existing.gallery.length) {
                            existing.gallery.forEach(img => listImages.appendChild(imageRow(img)));
                        }
                    }

                    if (listSpecs) {
                        const dashboardSpecs = existing.specs && !Array.isArray(existing.specs) ? existing.specs : {};
                        const dashboardImages = Array.isArray(dashboardSpecs.images) ? dashboardSpecs.images : [];

                        if (dashboardDescriptionInput && typeof dashboardSpecs.description === 'string') {
                            dashboardDescriptionInput.value = dashboardSpecs.description;
                        }

                        dashboardImages.forEach((item) => listSpecs.appendChild(specRow(item)));
                        syncDashboardPickerFromRows();
                    }

                    if (listDetails) {
                        if (Array.isArray(existing.details) && existing.details.length) {
                            existing.details.forEach(d => listDetails.appendChild(detailRow(d)));
                        } else {
                            listDetails.appendChild(detailRow());
                        }
                    }

                    if (browsersList) {
                        deriveLegacyBrowsers(existing).forEach((browser) => addBrowserChip(browser));
                    }

                    if (tagsList && Array.isArray(existing.tags) && existing.tags.length) {
                        existing.tags.forEach(t => addTagChip(t));
                    }

                    addFeature?.addEventListener('click', () => {
                        listFeatures.appendChild(featureRow());
                        syncJson();
                    });
                    clearFeatures?.addEventListener('click', () => {
                        listFeatures.innerHTML = '';
                        syncJson();
                    });

                    addImage?.addEventListener('click', () => {
                        listImages.appendChild(imageRow());
                        syncGalleryPickerFromRows();
                        syncJson();
                    });
                    clearImages?.addEventListener('click', () => {
                        listImages.innerHTML = '';
                        if (galleryPickerInput) {
                            galleryPickerInput.value = '';
                        }
                        renderGalleryPickerPreview([]);
                        syncJson();
                    });

                    galleryPickerInput?.addEventListener('input', applyGalleryPickerSelection);
                    galleryPickerInput?.addEventListener('change', applyGalleryPickerSelection);

                    dashboardDescriptionInput?.addEventListener('input', syncJson);
                    clearSpecs?.addEventListener('click', () => {
                        listSpecs.innerHTML = '';
                        if (dashboardPickerInput) {
                            dashboardPickerInput.value = '';
                        }
                        renderDashboardPickerPreview([]);
                        syncJson();
                    });
                    dashboardPickerInput?.addEventListener('input', applyDashboardPickerSelection);
                    dashboardPickerInput?.addEventListener('change', applyDashboardPickerSelection);

                    addDetail?.addEventListener('click', () => {
                        listDetails.appendChild(detailRow());
                        syncJson();
                    });
                    clearDetails?.addEventListener('click', () => {
                        listDetails.innerHTML = '';
                        syncJson();
                    });

                    addBrowserBtn?.addEventListener('click', () => {
                        addBrowserChip(browserInput?.value || '');
                        if (browserInput) {
                            browserInput.value = '';
                            browserInput.focus();
                        }
                    });
                    browserInput?.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') return;
                        event.preventDefault();
                        addBrowserChip(browserInput.value);
                        browserInput.value = '';
                    });
                    browserPresetButtons.forEach((button) => {
                        button.addEventListener('click', () => addBrowserChip(button.dataset.browserPreset || ''));
                    });
                    clearBrowsersBtn?.addEventListener('click', () => {
                        if (browsersList) {
                            browsersList.innerHTML = '';
                        }
                        syncJson();
                    });

                    addTagBtn?.addEventListener('click', () => {
                        addTagChip(tagsInput.value);
                        tagsInput.value = '';
                        tagsInput.focus();
                    });
                    tagsInput?.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addTagChip(tagsInput.value);
                            tagsInput.value = '';
                        }
                    });
                    clearTagsBtn?.addEventListener('click', () => {
                        if (tagsList) tagsList.innerHTML = '';
                        syncJson();
                    });

                    syncGalleryPickerFromRows();
                    syncJson();
                })();
            });
        });
    </script>
</x-dashboard-layout>
