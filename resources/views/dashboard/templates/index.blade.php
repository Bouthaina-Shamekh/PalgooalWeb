<x-dashboard-layout>
    @php
        $hasFilters = $search !== '' || !empty($selectedCategory);
    @endphp

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[32px] bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-900/10 sm:px-8 lg:px-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(168,85,247,0.35),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.24),_transparent_30%)]"></div>
                <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div class="max-w-3xl text-right">
                        <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold tracking-[0.24em] text-violet-100">
                            {{ t('dashboard.Template_Library', 'TEMPLATE LIBRARY') }}
                        </span>
                        <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                            {{ t('dashboard.Manage_Site_Templates', 'إدارة قوالب الموقع') }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                            {{ t('dashboard.Template_Index_Hero_Desc', 'صفحة تحكم موحدة لعرض القوالب، متابعة التسعير، التحقق من المعاينات، والوصول السريع إلى التعديل أو العرض العام.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-stretch gap-3 lg:justify-end">
                        <a href="{{ route('dashboard.templates.create') }}"
                            class="inline-flex min-h-[56px] items-center justify-center gap-2 whitespace-nowrap rounded-2xl border border-violet-300/25 bg-violet-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-violet-950/30 transition hover:-translate-y-0.5 hover:bg-violet-400">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z" />
                            </svg>
                            {{ t('dashboard.Add_New_Template', 'إضافة قالب جديد') }}
                        </a>

                        <div class="min-w-[140px] rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-xs font-medium text-slate-300">{{ t('dashboard.Total_Templates', 'إجمالي القوالب') }}</p>
                            <p class="mt-1 text-2xl font-black text-white">{{ number_format($stats['total']) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 shadow-sm">
                    {{ $errors->first('error') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ t('dashboard.All_Templates', 'كل القوالب') }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['total']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-900 p-3 text-white">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ t('dashboard.Current_Results', 'النتائج الحالية') }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['visible']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-violet-100 p-3 text-violet-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M10 6h10M10 12h10M10 18h10M4 6h.01M4 12h.01M4 18h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ t('dashboard.Active_Offers', 'عروض مفعلة') }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['discounted']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-amber-100 p-3 text-amber-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 8c-1.657 0-3 1.12-3 2.5S10.343 13 12 13s3 1.12 3 2.5S13.657 18 12 18m0-10V6m0 12v-2M6 7h.01M18 17h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ t('dashboard.Ready_Previews', 'معاينات جاهزة') }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['with_preview']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M2 12s3.636-6 10-6 10 6 10 6-3.636 6-10 6-10-6-10-6Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                                <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ t('dashboard.Categories', 'التصنيفات') }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['categories']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-sky-100 p-3 text-sky-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H10l2 2h5.5A2.5 2.5 0 0 1 20 9.5v7A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">{{ t('dashboard.Search_And_Filter', 'بحث وفلترة') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ t('dashboard.Template_Search_Desc', 'ابحث بالاسم أو الـ slug أو التصنيف، ثم انتقل مباشرة إلى القالب المطلوب.') }}</p>
                    </div>

                    @if ($hasFilters)
                        <a href="{{ route('dashboard.templates.index') }}"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 3a1 1 0 01.993.883L11 4v5h5a1 1 0 01.117 1.993L16 11h-5v5a1 1 0 01-1.993.117L9 16v-5H4a1 1 0 01-.117-1.993L4 9h5V4a1 1 0 011-1Z" />
                            </svg>
                            {{ t('dashboard.Clear_Filters', 'مسح الفلاتر') }}
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('dashboard.templates.index') }}" class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(220px,0.7fr)_auto]">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.General_Search', 'بحث عام') }}</span>
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-violet-400 focus-within:bg-white focus-within:ring-4 focus-within:ring-violet-100">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                            <input type="text" name="q" value="{{ $search }}"
                                placeholder="{{ t('dashboard.Search_Template_Placeholder', 'ابحث باسم القالب أو الـ slug أو رقم القالب') }}"
                                class="w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">{{ t('dashboard.Category', 'التصنيف') }}</span>
                        <select name="category"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-violet-400 focus:bg-white focus:ring-4 focus:ring-violet-100">
                            <option value="">{{ t('dashboard.All_Categories', 'كل التصنيفات') }}</option>
                            @foreach ($categories as $category)
                                @php
                                    $categoryName = $category->getTranslation(app()->getLocale())?->name
                                        ?? $category->getTranslation('ar')?->name
                                        ?? ('#' . $category->id);
                                @endphp
                                <option value="{{ $category->id }}" @selected((int) $selectedCategory === (int) $category->id)>
                                    {{ $categoryName }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex gap-3 xl:justify-end">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-slate-800 xl:w-auto">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1.172a2 2 0 0 1-.586 1.414l-3.828 3.828A2 2 0 0 0 12 13.828V17a1 1 0 0 1-1.447.894l-2-1A1 1 0 0 1 8 16v-2.172a2 2 0 0 0-.586-1.414L3.586 8.586A2 2 0 0 1 3 7.172V5Z" clip-rule="evenodd" />
                            </svg>
                            {{ t('dashboard.Apply_Filters', 'تطبيق الفلاتر') }}
                        </button>
                    </div>
                </form>

                @if ($hasFilters)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($search !== '')
                            <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800">
                                {{ t('dashboard.Search', 'البحث') }}: {{ $search }}
                            </span>
                        @endif

                        @if (!empty($selectedCategory))
                            @php
                                $selectedCategoryName = optional($categories->firstWhere('id', $selectedCategory))->getTranslation(app()->getLocale())?->name
                                    ?? optional($categories->firstWhere('id', $selectedCategory))->getTranslation('ar')?->name;
                            @endphp

                            @if ($selectedCategoryName)
                                <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                                    {{ t('dashboard.Category', 'التصنيف') }}: {{ $selectedCategoryName }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                <div class="flex flex-col gap-5 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold tracking-[0.18em] text-slate-600">
                            {{ t('dashboard.Templates_List', 'TEMPLATES LIST') }}
                        </span>
                        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">{{ t('dashboard.Templates_List_Title', 'قائمة القوالب') }}</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-500">
                            {{ t('dashboard.Showing', 'عرض') }}
                            @if ($templates->total() > 0)
                                {{ $templates->firstItem() }} - {{ $templates->lastItem() }}
                            @else
                                0
                            @endif
                            {{ t('dashboard.Of_Total', 'من أصل') }} {{ number_format($templates->total()) }} {{ t('dashboard.Results_With_Quick_Actions', 'نتيجة مع الوصول السريع للتعديل والمعاينة.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-2 text-xs font-bold text-violet-800">
                            {{ number_format($templates->count()) }} {{ t('dashboard.Items_On_This_Page', 'عنصر في هذه الصفحة') }}
                        </span>
                        @if ($hasFilters)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-2 text-xs font-bold text-sky-800">
                                {{ t('dashboard.Filters_Active', 'فلترة مفعلة') }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700">
                            {{ t('dashboard.Data_From_Database', 'البيانات مباشرة من قاعدة البيانات') }}
                        </span>
                    </div>
                </div>

                @if ($templates->count())
                    <div class="mt-6 hidden rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-xs font-bold tracking-[0.18em] text-slate-500 lg:grid lg:grid-cols-[minmax(0,1.45fr)_180px_170px_220px] lg:items-center">
                        <div>{{ t('dashboard.Template', 'القالب') }}</div>
                        <div>{{ t('dashboard.Category_And_Plan', 'التصنيف والخطة') }}</div>
                        <div>{{ t('dashboard.Price', 'السعر') }}</div>
                        <div>{{ t('dashboard.Actions', 'الإجراءات') }}</div>
                    </div>

                    <div class="mt-4 space-y-4">
                        @foreach ($templates as $template)
                            @php
                                $translation = $template->translations->firstWhere('locale', app()->getLocale())
                                    ?? $template->translations->firstWhere('locale', 'ar')
                                    ?? $template->translations->first();

                                $templateName = $translation?->name ?? t('dashboard.No_Title', 'بدون عنوان');
                                $templateSlug = $translation?->slug;
                                $description = \Illuminate\Support\Str::limit(strip_tags((string) ($translation?->description ?? '')), 90);
                                $categoryName = $template->categoryTemplate?->getTranslation(app()->getLocale())?->name
                                    ?? $template->categoryTemplate?->getTranslation('ar')?->name
                                    ?? t('dashboard.Uncategorized', 'غير مصنف');
                                $planTitle = $template->plan?->title ?? t('dashboard.No_Plan', 'بدون خطة');
                                $hasPreview = filled($translation?->preview_url);
                                $hasDiscount = !is_null($template->discount_price)
                                    && $template->discount_price > 0
                                    && $template->discount_price < $template->price;
                                $currentPrice = $hasDiscount ? $template->discount_price : $template->price;
                                $imageUrl = $template->image ? asset('storage/' . $template->image) : null;
                                $publicUrl = $templateSlug ? route('template.show.redesign', ['slug' => $templateSlug]) : null;
                                $previewUrl = $templateSlug && $hasPreview ? route('template.preview', ['slug' => $templateSlug]) : null;
                                $locales = $template->translations->pluck('locale')->filter()->unique()->values();
                            @endphp

                            <article class="group rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-lg hover:shadow-slate-200/70 sm:p-5">
                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.45fr)_180px_170px_220px] lg:items-center">
                                    <div class="min-w-0">
                                        <div class="flex min-w-0 items-start gap-4">
                                            <div class="h-24 w-32 shrink-0 overflow-hidden rounded-2xl bg-slate-100 sm:h-28 sm:w-40">
                                                @if ($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $templateName }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                            <path d="M4 16 8.586 11.414a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-9-5h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white">
                                                        #{{ $template->id }}
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $hasPreview ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                        {{ $hasPreview ? t('dashboard.Preview_Available', 'معاينة متاحة') : t('dashboard.No_Preview', 'بدون معاينة') }}
                                                    </span>
                                                    @if ($hasDiscount)
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                            {{ t('dashboard.Discount_Active', 'خصم مفعل') }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <h3 class="mt-3 truncate text-lg font-black tracking-tight text-slate-900 sm:text-xl">
                                                    {{ $templateName }}
                                                </h3>

                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                                                        {{ $templateSlug ?: t('dashboard.No_Slug', 'بدون slug') }}
                                                    </span>
                                                    <span class="text-slate-300">•</span>
                                                    <span>{{ $template->updated_at?->diffForHumans() ?? t('dashboard.Not_Available', 'غير متوفر') }}</span>
                                                </div>

                                                <p class="mt-3 text-sm text-slate-500">
                                                    {{ $description !== '' ? $description : t('dashboard.No_Short_Description_Yet', 'لا يوجد وصف مختصر متاح لهذا القالب بعد.') }}
                                                </p>

                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @foreach ($locales as $locale)
                                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                            {{ strtoupper($locale) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ t('dashboard.Category_Label', 'Category') }}</p>
                                            <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $categoryName }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ t('dashboard.Plan_Label', 'Plan') }}</p>
                                            <p class="mt-1.5 text-sm font-semibold text-slate-700">{{ $planTitle }}</p>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ t('dashboard.Price_Label', 'Price') }}</p>
                                        <div class="mt-2 flex flex-wrap items-end gap-2">
                                            <span class="text-2xl font-black text-slate-900">${{ number_format((float) $currentPrice, 2) }}</span>
                                            @if ($hasDiscount)
                                                <span class="text-sm font-semibold text-slate-400 line-through">${{ number_format((float) $template->price, 2) }}</span>
                                            @endif
                                        </div>

                                        @if ($hasDiscount && $template->discount_ends_at)
                                            <p class="mt-2 text-xs font-medium text-amber-700">
                                                {{ t('dashboard.Discount_Ends', 'ينتهي الخصم') }} {{ $template->discount_ends_at->diffForHumans() }}
                                            </p>
                                        @else
                                            <p class="mt-2 text-xs font-medium text-slate-500">
                                                {{ t('dashboard.Base_Price', 'السعر الأساسي') }}: ${{ number_format((float) $template->price, 2) }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="grid gap-2">
                                        <a href="{{ route('dashboard.templates.edit', $template->id) }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-slate-800">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M17.414 2.586a2 2 0 0 0-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 0 0 0-2.828Z" />
                                                <path fill-rule="evenodd" d="M2 16a2 2 0 0 1 2-2h2a1 1 0 1 1 0 2H4v2h12v-2h-2a1 1 0 1 1 0-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2Z" clip-rule="evenodd" />
                                            </svg>
                                            {{ t('dashboard.Edit', 'تعديل') }}
                                        </a>

                                        @if ($publicUrl)
                                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:border-violet-200 hover:text-violet-700">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9a1 1 0 1 0-2 0v6H5V5h6a1 1 0 1 0 0-2H5Zm8.293 1.293a1 1 0 0 1 1.414 0L17 6.586V5a1 1 0 1 1 2 0v4a1 1 0 0 1-1 1h-4a1 1 0 1 1 0-2h1.586l-2.293-2.293a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                                </svg>
                                                {{ t('dashboard.View_Page', 'عرض الصفحة') }}
                                            </a>
                                        @endif

                                        @if ($previewUrl)
                                            <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="M10 4.5C5.75 4.5 2.365 7.163 1 10c1.365 2.837 4.75 5.5 9 5.5s7.635-2.663 9-5.5c-1.365-2.837-4.75-5.5-9-5.5Zm0 9A3.5 3.5 0 1 1 10 6.5a3.5 3.5 0 0 1 0 7Z" />
                                                </svg>
                                                {{ t('dashboard.Preview', 'معاينة') }}
                                            </a>
                                        @endif

                                        <form action="{{ route('dashboard.templates.destroy', $template->id) }}" method="POST"
                                            onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Template', 'هل أنت متأكد من حذف هذا القالب؟') }}');">
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
                                <path d="M4 7h16M4 12h10M4 17h7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-slate-900">{{ t('dashboard.No_Matching_Templates', 'لا توجد قوالب مطابقة') }}</h3>
                        <p class="mt-2 text-sm leading-7 text-slate-500">
                            @if ($hasFilters)
                                {{ t('dashboard.Try_Adjusting_Search', 'جرّب تعديل البحث أو إزالة الفلاتر الحالية ثم أعد المحاولة.') }}
                            @else
                                {{ t('dashboard.No_Templates_Yet', 'لم يتم إنشاء أي قالب بعد. ابدأ بإضافة أول قالب إلى المكتبة.') }}
                            @endif
                        </p>

                        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                            @if ($hasFilters)
                                <a href="{{ route('dashboard.templates.index') }}"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                                    {{ t('dashboard.Clear_Filters', 'مسح الفلاتر') }}
                                </a>
                            @endif

                            <a href="{{ route('dashboard.templates.create') }}"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                {{ t('dashboard.Add_New_Template', 'إضافة قالب جديد') }}
                            </a>
                        </div>
                    </div>
                @endif

                <div class="mt-8 border-t border-slate-200 pt-6">
                    {{ $templates->onEachSide(1)->links() }}
                </div>
            </section>
        </div>
    </div>
</x-dashboard-layout>
