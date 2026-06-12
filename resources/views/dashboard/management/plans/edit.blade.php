<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.plans.index') }}">{{ t('dashboard.Plans', 'Plans') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Plan', 'Edit Plan') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Edit_Hosting_Plan', 'Edit Hosting Plan') }}
                    @php
                        $planTrans = $plan->translations->firstWhere('locale', app()->getLocale())
                                  ?? $plan->translations->first();
                    @endphp
                    @if ($planTrans?->title)
                        <span class="text-gray-400 font-normal text-lg ms-2">— {{ $planTrans->title }}</span>
                    @endif
                </h2>
            </div>
        </div>
    </div>

    {{-- Flash / Validation --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            <ul class="mb-0 list-unstyled">
                @foreach ($errors->all() as $error)
                    <li><i class="ti ti-alert-circle me-1"></i>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $localesCollection = $languages?->pluck('name', 'code');
        $locales = $localesCollection ? $localesCollection->filter()->toArray() : [];
        if (empty($locales)) {
            $locales = config('app.locales', ['ar' => 'العربية', 'en' => 'English']);
        }
        $activeLocale = old('active_locale', app()->getLocale());
        $selectedType = old('plan_type', $plan->plan_type ?? \App\Models\Plan::TYPE_MULTI_TENANT);
    @endphp

    <form id="planForm" action="{{ route('dashboard.plans.update', $plan->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_locale" id="active_locale" value="{{ $activeLocale }}">

    <div class="grid grid-cols-12 gap-6">

        {{-- ═══ FORM (col-span-8) ════════════════════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-8">

            {{-- ── القسم ١: معلومات أساسية ──────────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <span class="badge bg-primary rounded-circle flex items-center justify-center"
                              style="width:28px;height:28px;font-size:14px;">١</span>
                        <h5 class="mb-0">{{ t('dashboard.Basic_Info', 'Basic Information') }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-5">

                        {{-- Category --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="plan-category">
                                {{ t('dashboard.Category', 'Category') }}
                            </label>
                            <select id="plan-category" name="plan_category_id" class="form-select">
                                <option value="">-- {{ t('dashboard.None', 'None') }} --</option>
                                @foreach ($categories as $cat)
                                    @php
                                        $catLabel = $cat->translations->firstWhere('locale', app()->getLocale())?->title
                                                 ?? $cat->translations->first()?->title
                                                 ?? '#' . $cat->id;
                                    @endphp
                                    <option value="{{ $cat->id }}"
                                        {{ old('plan_category_id', $plan->plan_category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $catLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_category_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="plan-slug">
                                {{ t('dashboard.Plan_Slug', 'Plan Slug') }}
                                <span class="text-muted small">({{ t('dashboard.Optional', 'optional') }})</span>
                            </label>
                            <input type="text" id="plan-slug" name="slug"
                                   class="form-control font-mono @error('slug') is-invalid @enderror"
                                   dir="ltr"
                                   value="{{ old('slug', $plan->slug) }}"
                                   placeholder="{{ t('dashboard.Auto_Generated', 'auto-generated if empty') }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Plan Type --}}
                        <div class="col-span-12">
                            <label class="form-label" for="plan-type">
                                {{ t('dashboard.Plan_Type', 'Plan Type') }}
                            </label>
                            <select id="plan-type" name="plan_type" class="form-select">
                                <option value="{{ \App\Models\Plan::TYPE_MULTI_TENANT }}"
                                    {{ $selectedType === \App\Models\Plan::TYPE_MULTI_TENANT ? 'selected' : '' }}>
                                    {{ t('dashboard.Plan_Type_Multi', 'Multi-Tenant (بدون cPanel)') }}
                                </option>
                                <option value="{{ \App\Models\Plan::TYPE_HOSTING }}"
                                    {{ $selectedType === \App\Models\Plan::TYPE_HOSTING ? 'selected' : '' }}>
                                    {{ t('dashboard.Plan_Type_Hosting', 'Hosting / WordPress (يتضمن cPanel)') }}
                                </option>
                            </select>
                            <div class="text-muted small mt-1">
                                {{ t('dashboard.Plan_Type_Hint', 'يحدد إن كان الاشتراك يحتاج تفعيل استضافة أم يكتفي بمنصة Palgoals.') }}
                            </div>
                            @error('plan_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status (radio buttons — إصلاح PHP loose comparison) --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Status', 'Status') }}</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="1"
                                           {{ old('is_active', $plan->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4">
                                    <span class="text-sm text-emerald-600 font-medium">
                                        {{ t('dashboard.Active_Available', 'نشط (متاح للبيع)') }}
                                    </span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="0"
                                           {{ old('is_active', $plan->is_active ? '1' : '0') === '0' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4">
                                    <span class="text-sm text-gray-500 font-medium">
                                        {{ t('dashboard.Inactive_Hidden', 'غير نشط (مخفي)') }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Featured (radio buttons) --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Featured_Plan', 'Featured Plan') }}</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_featured" value="1"
                                           {{ old('is_featured', $plan->is_featured ? '1' : '0') === '1' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4">
                                    <span class="text-sm text-amber-600 font-medium">
                                        {{ t('dashboard.Featured_Badge_Label', 'مميزة (تظهر بشارة)') }}
                                    </span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_featured" value="0"
                                           {{ old('is_featured', $plan->is_featured ? '1' : '0') === '0' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4">
                                    <span class="text-sm text-gray-500 font-medium">
                                        {{ t('dashboard.Normal', 'عادية') }}
                                    </span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── القسم ٢: التسعير ─────────────────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <span class="badge bg-primary rounded-circle flex items-center justify-center"
                              style="width:28px;height:28px;font-size:14px;">٢</span>
                        <h5 class="mb-0">{{ t('dashboard.Pricing', 'Pricing') }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-5">

                        {{-- Monthly Price --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="monthly_price_ui">
                                {{ t('dashboard.Monthly_Price_USD', 'Monthly Price (USD)') }}
                            </label>
                            <div class="input-group" dir="ltr">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" id="monthly_price_ui"
                                       name="monthly_price_ui"
                                       class="form-control @error('monthly_price_cents') is-invalid @enderror"
                                       value="{{ old('monthly_price_ui', optional($plan)->monthly_price) }}"
                                       placeholder="0.00">
                            </div>
                            <input type="hidden" name="monthly_price_cents" id="monthly_price_cents"
                                   value="{{ old('monthly_price_cents', $plan->monthly_price_cents) }}">
                            @error('monthly_price_cents')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Annual Price --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="annual_price_ui">
                                {{ t('dashboard.Annual_Price_USD', 'Annual Price (USD)') }}
                            </label>
                            <div class="input-group" dir="ltr">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" id="annual_price_ui"
                                       name="annual_price_ui"
                                       class="form-control @error('annual_price_cents') is-invalid @enderror"
                                       value="{{ old('annual_price_ui', optional($plan)->annual_price) }}"
                                       placeholder="0.00">
                            </div>
                            <input type="hidden" name="annual_price_cents" id="annual_price_cents"
                                   value="{{ old('annual_price_cents', $plan->annual_price_cents) }}">
                            @error('annual_price_cents')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── القسم ٣: إعدادات السيرفر ───────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <span class="badge bg-primary rounded-circle flex items-center justify-center"
                              style="width:28px;height:28px;font-size:14px;">٣</span>
                        <h5 class="mb-0">{{ t('dashboard.Server_Package', 'Server Package') }}</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-5">

                        {{-- Server --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="server_select">
                                {{ t('dashboard.Server', 'Server') }}
                            </label>
                            <select id="server_select" name="server_id" class="form-select">
                                <option value="">-- {{ t('dashboard.None', 'None') }} --</option>
                                @foreach ($servers as $server)
                                    <option value="{{ $server->id }}"
                                        {{ old('server_id', $plan->server_id) == $server->id ? 'selected' : '' }}>
                                        {{ $server->name }} ({{ $server->ip ?? $server->hostname }})
                                    </option>
                                @endforeach
                            </select>
                            @error('server_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Server Package --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label" for="server_package_select">
                                {{ t('dashboard.Server_Package', 'Server Package') }}
                            </label>
                            <select id="server_package_select" name="server_package" class="form-select">
                                <option value="">-- {{ t('dashboard.Select_Server_First', 'اختر السيرفر أولاً') }} --</option>
                            </select>
                            <div id="pkg_warning" class="text-warning small mt-1 hidden"></div>
                            @error('server_package')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── القسم ٤: ترجمات الباقة ──────────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <span class="badge bg-primary rounded-circle flex items-center justify-center"
                              style="width:28px;height:28px;font-size:14px;">٤</span>
                        <h5 class="mb-0">{{ t('dashboard.Plan_Translations', 'Plan Translations') }}</h5>
                    </div>
                </div>
                <div class="card-body">

                    {{-- تبويبات اللغات --}}
                    <div class="border-b border-gray-200 mb-4" role="tablist">
                        <div class="flex gap-0 overflow-x-auto">
                            @foreach ($locales as $locale => $label)
                                <button type="button"
                                    id="plan-lang-tab-{{ $locale }}"
                                    onclick="showLangTab('{{ $locale }}')"
                                    role="tab"
                                    aria-selected="{{ $activeLocale == $locale ? 'true' : 'false' }}"
                                    class="plan-lang-tab px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                                           {{ $activeLocale == $locale ? 'border-primary text-primary' : 'border-transparent text-muted' }}">
                                    {{ strtoupper(substr($locale, 0, 2)) }} — {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- لوحات اللغات --}}
                    @foreach ($locales as $locale => $label)
                        @php
                            $transForLocale = $plan->translations->where('locale', $locale)->first();
                            $rawFeatures = old('features.' . $locale);
                            if ($rawFeatures === null) {
                                $rawFeatures = $transForLocale?->features ?? [];
                            }
                            $billingOptions = [
                                'monthly' => t('dashboard.Monthly', 'شهري'),
                                'annual'  => t('dashboard.Annual', 'سنوي'),
                            ];
                            $rawFeatures = is_array($rawFeatures) ? $rawFeatures : [];
                            $hasBillingSplit = array_intersect(array_keys($rawFeatures), array_keys($billingOptions)) !== [];
                            $normalizeFeature = function ($item) {
                                if (is_array($item)) {
                                    $text      = isset($item['text']) ? trim((string) $item['text']) : '';
                                    $available = array_key_exists('available', $item)
                                        ? filter_var($item['available'], FILTER_VALIDATE_BOOLEAN) : true;
                                } else {
                                    $text      = trim((string) $item);
                                    $available = true;
                                }
                                return ['text' => $text, 'available' => (bool) $available];
                            };
                            $featureBuckets = [];
                            foreach ($billingOptions as $billingKey => $billingLabel) {
                                $bucketSource = $hasBillingSplit
                                    ? ($rawFeatures[$billingKey] ?? [])
                                    : ($billingKey === 'monthly' ? $rawFeatures : []);
                                $featureBuckets[$billingKey] = collect(is_array($bucketSource) ? $bucketSource : [])
                                    ->map($normalizeFeature)
                                    ->filter(fn($f) => $f['text'] !== '')
                                    ->values();
                            }
                        @endphp
                        <div id="pane-{{ $locale }}"
                             class="lang-pane {{ $activeLocale == $locale ? '' : 'hidden' }}">
                            <div class="grid grid-cols-12 gap-5">

                                {{-- Plan Name --}}
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label" for="plan-name-{{ $locale }}">
                                        {{ t('dashboard.Plan_Name', 'Plan Name') }}
                                        ({{ $label }})
                                        @if ($loop->first) <span class="text-danger">*</span> @endif
                                    </label>
                                    <input type="text"
                                           id="plan-name-{{ $locale }}"
                                           name="name[{{ $locale }}]"
                                           class="form-control @error('name.' . $locale) is-invalid @enderror"
                                           value="{{ old('name.' . $locale, $transForLocale?->title ?? ($locale == app()->getLocale() ? ($plan->name ?? '') : '')) }}"
                                           @if ($activeLocale == $locale) required @endif>
                                    @error('name.' . $locale)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Featured Badge Label --}}
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label" for="plan-badge-{{ $locale }}">
                                        {{ t('dashboard.Featured_Badge_Label', 'Featured Badge Label') }}
                                        ({{ $label }})
                                    </label>
                                    <input type="text"
                                           id="plan-badge-{{ $locale }}"
                                           name="featured_label[{{ $locale }}]"
                                           class="form-control"
                                           value="{{ old('featured_label.' . $locale, $transForLocale?->featured_label ?? '') }}"
                                           placeholder="{{ t('dashboard.Most_Popular', 'الأكثر شيوعاً') }}">
                                    <div class="text-muted small mt-1">
                                        {{ t('dashboard.Featured_Badge_Hint', 'يظهر عند تفعيل الخيار المميز. اتركه فارغاً للنص الافتراضي.') }}
                                    </div>
                                </div>

                                {{-- Description --}}
                                <div class="col-span-12">
                                    <label class="form-label" for="plan-desc-{{ $locale }}">
                                        {{ t('dashboard.Description', 'Description') }}
                                        ({{ $label }})
                                    </label>
                                    <textarea id="plan-desc-{{ $locale }}"
                                              name="description[{{ $locale }}]"
                                              class="form-control"
                                              rows="3">{{ old('description.' . $locale, $transForLocale?->description ?? '') }}</textarea>
                                </div>

                                {{-- Features --}}
                                <div class="col-span-12">
                                    <label class="form-label">
                                        {{ t('dashboard.Plan_Features', 'Plan Features') }}
                                        ({{ $label }})
                                    </label>

                                    {{-- تبويبات شهري / سنوي --}}
                                    <div class="flex gap-2 mb-3" data-feature-tabs>
                                        @foreach ($billingOptions as $billingKey => $billingLabel)
                                            <button type="button"
                                                class="feature-cycle-tab px-3 py-1 rounded-md border transition text-sm
                                                       {{ $loop->first ? 'bg-white border-gray-300 text-gray-800 font-semibold shadow-sm' : 'bg-gray-100 border-transparent text-gray-500' }}"
                                                data-feature-tab
                                                data-locale="{{ $locale }}"
                                                data-billing="{{ $billingKey }}">
                                                {{ $billingLabel }}
                                            </button>
                                        @endforeach
                                    </div>

                                    @foreach ($billingOptions as $billingKey => $billingLabel)
                                        @php $featureItems = $featureBuckets[$billingKey] ?? collect(); @endphp
                                        <div class="{{ $loop->first ? 'block' : 'hidden' }}"
                                             data-feature-panel
                                             data-locale="{{ $locale }}"
                                             data-billing="{{ $billingKey }}">
                                            <div class="space-y-2"
                                                 data-feature-wrapper
                                                 data-locale="{{ $locale }}"
                                                 data-billing="{{ $billingKey }}"
                                                 data-next-index="{{ $featureItems->count() }}"
                                                 data-available-label="{{ t('dashboard.Available', 'متاح') }}"
                                                 data-remove-label="{{ t('dashboard.Remove_Feature', 'حذف') }}">
                                                @foreach ($featureItems as $index => $feature)
                                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3" data-feature-row>
                                                        <div class="flex-1">
                                                            <input type="text"
                                                                name="features[{{ $locale }}][{{ $billingKey }}][{{ $index }}][text]"
                                                                class="form-control"
                                                                value="{{ $feature['text'] }}"
                                                                placeholder="{{ t('dashboard.Feature_Placeholder', 'e.g. Domain') }}">
                                                        </div>
                                                        <label class="inline-flex items-center gap-2 text-sm shrink-0">
                                                            <input type="hidden"
                                                                name="features[{{ $locale }}][{{ $billingKey }}][{{ $index }}][available]"
                                                                value="0">
                                                            <input type="checkbox"
                                                                name="features[{{ $locale }}][{{ $billingKey }}][{{ $index }}][available]"
                                                                value="1"
                                                                class="h-4 w-4 accent-primary"
                                                                @checked($feature['available'])>
                                                            <span>{{ t('dashboard.Available', 'متاح') }}</span>
                                                        </label>
                                                        <button type="button"
                                                                class="text-danger p-1 border-0 bg-transparent shrink-0"
                                                                data-remove-feature
                                                                title="{{ t('dashboard.Remove_Feature', 'حذف') }}">
                                                            <i class="ti ti-trash" style="font-size:1.1rem;"></i>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="mt-2">
                                                <button type="button"
                                                    class="btn btn-light btn-sm flex items-center gap-1"
                                                    data-add-feature
                                                    data-locale="{{ $locale }}"
                                                    data-billing="{{ $billingKey }}">
                                                    <i class="ti ti-plus"></i>
                                                    {{ t('dashboard.Add_Feature', 'Add Feature') }}
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            {{-- أزرار الحفظ --}}
            <div class="flex items-center gap-3 mb-6">
                <button type="submit" class="btn btn-primary flex items-center gap-2">
                    <i class="ti ti-device-floppy"></i>
                    {{ t('dashboard.Update_Plan', 'Update Plan') }}
                </button>
                <a href="{{ route('dashboard.plans.index') }}" class="btn btn-light">
                    {{ t('dashboard.Cancel', 'Cancel') }}
                </a>
            </div>

        </div>

        {{-- ═══ HELP SIDEBAR (col-span-4) ══════════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-6">
                <div class="card-header">
                    <h5 class="mb-0 flex items-center gap-2">
                        <i class="ti ti-info-circle text-primary"></i>
                        {{ t('dashboard.Help', 'Help') }}
                    </h5>
                </div>
                <div class="card-body space-y-5 text-sm">

                    <div>
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Plan_Type', 'نوع الباقة') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Plan_Type_Desc', 'Multi-Tenant: فقط منصة Palgoals بدون استضافة. Hosting: تفعيل استضافة cPanel تلقائياً عند الاشتراك.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Featured', 'الباقة المميزة') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Featured_Desc', 'تُعرض بشارة مميزة على صفحة الأسعار لجذب الانتباه. يمكن تخصيص نص الشارة لكل لغة.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Server_Package', 'حزمة السيرفر') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Server_Package_Desc', 'اختر الباقة من السيرفر التي ستُطبَّق تلقائياً عند تفعيل الاشتراك. تأكد من إنشاء الباقات في WHM أولاً.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Features', 'المميزات') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Features_Desc', 'يمكن إضافة مميزات منفصلة للخطة الشهرية والسنوية، أو استخدام نفس القائمة للاثنتين.') }}
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>
    </form>

    @push('scripts')
    <script>
    (() => {
        // ── تبديل تبويبات اللغات ──────────────────────────────────────
        function showLangTab(locale) {
            document.querySelectorAll('.lang-pane').forEach(p => p.classList.add('hidden'));
            const pane = document.getElementById('pane-' + locale);
            if (pane) pane.classList.remove('hidden');

            document.getElementById('active_locale').value = locale;

            document.querySelectorAll('.plan-lang-tab').forEach(btn => {
                const isActive = btn.id === 'plan-lang-tab-' + locale;
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.classList.remove('border-primary', 'text-primary', 'border-transparent', 'text-muted');
                btn.classList.add(isActive ? 'border-primary' : 'border-transparent');
                btn.classList.add(isActive ? 'text-primary' : 'text-muted');
            });
        }
        window.showLangTab = showLangTab;

        // ── sync cents ────────────────────────────────────────────────
        const monthlyUI    = document.getElementById('monthly_price_ui');
        const monthlyCents = document.getElementById('monthly_price_cents');
        const annualUI     = document.getElementById('annual_price_ui');
        const annualCents  = document.getElementById('annual_price_cents');

        function syncCents() {
            if (monthlyCents && monthlyUI?.value !== undefined)
                monthlyCents.value = monthlyUI.value ? Math.round(parseFloat(monthlyUI.value) * 100) : '';
            if (annualCents && annualUI?.value !== undefined)
                annualCents.value = annualUI.value ? Math.round(parseFloat(annualUI.value) * 100) : '';
        }
        monthlyUI?.addEventListener('input', syncCents);
        annualUI?.addEventListener('input', syncCents);
        document.getElementById('planForm')?.addEventListener('submit', syncCents);

        // ── تحميل باقات السيرفر ──────────────────────────────────────
        async function fetchPackagesForServer(serverId, selected) {
            const select  = document.getElementById('server_package_select');
            const warning = document.getElementById('pkg_warning');
            if (!select) return;

            if (!serverId) {
                select.innerHTML = '<option value="">-- {{ t('dashboard.Select_Server_First', 'اختر السيرفر أولاً') }} --</option>';
                if (warning) warning.classList.add('hidden');
                return;
            }

            select.innerHTML = '<option value="">{{ t('dashboard.Loading', 'جارٍ التحميل...') }}</option>';
            if (warning) warning.classList.add('hidden');

            try {
                const res = await fetch('{{ url('admin/servers') }}/' + serverId + '/packages', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (!res.ok || data.error) {
                    select.innerHTML = '<option value="">-- {{ t('dashboard.Error_Loading', 'خطأ في التحميل') }} --</option>';
                    if (warning) { warning.textContent = data.error || 'خطأ HTTP ' + res.status; warning.classList.remove('hidden'); }
                    return;
                }

                // تحذير عند عدم وجود باقات (رسيلر بدون باقات مُنشأة)
                if (data.warning && warning) {
                    warning.innerHTML = '<i class="ti ti-alert-triangle me-1"></i>' + data.warning;
                    warning.classList.remove('hidden');
                }

                const packages = data.packages || [];
                select.innerHTML = '<option value="">-- {{ t('dashboard.None', 'None') }} --</option>';
                packages.forEach(pkg => {
                    const val  = typeof pkg === 'string' ? pkg : (pkg.name || pkg.pkg || pkg.package || '');
                    const text = val;
                    if (!val) return;
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = text;
                    if (val === selected) opt.selected = true;
                    select.appendChild(opt);
                });

            } catch (e) {
                select.innerHTML = '<option value="">-- {{ t('dashboard.Error_Loading', 'خطأ في التحميل') }} --</option>';
                if (warning) { warning.textContent = e.message; warning.classList.remove('hidden'); }
            }
        }

        // ── Features logic ────────────────────────────────────────────
        function escapeVal(v) {
            return String(v || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function appendFeatureRow(wrapper, locale, billing, text, available) {
            const idx    = parseInt(wrapper.dataset.nextIndex || '0', 10);
            const prefix = 'features[' + locale + '][' + billing + '][' + idx + ']';
            const chk    = available ? ' checked' : '';
            const avLbl  = wrapper.dataset.availableLabel || '{{ t('dashboard.Available', 'متاح') }}';
            const row = document.createElement('div');
            row.className = 'flex flex-col sm:flex-row sm:items-center gap-3';
            row.setAttribute('data-feature-row', '');
            row.innerHTML =
                '<div class="flex-1">' +
                '<input type="text" name="' + prefix + '[text]" class="form-control" value="' + escapeVal(text) + '" placeholder="{{ t('dashboard.Feature_Placeholder', 'e.g. Domain') }}">' +
                '</div>' +
                '<label class="inline-flex items-center gap-2 text-sm shrink-0">' +
                '<input type="hidden" name="' + prefix + '[available]" value="0">' +
                '<input type="checkbox" name="' + prefix + '[available]" value="1" class="h-4 w-4 accent-primary"' + chk + '>' +
                '<span>' + avLbl + '</span>' +
                '</label>' +
                '<button type="button" class="text-danger p-1 border-0 bg-transparent shrink-0" data-remove-feature title="{{ t('dashboard.Remove_Feature', 'حذف') }}">' +
                '<i class="ti ti-trash" style="font-size:1.1rem;"></i></button>';
            wrapper.appendChild(row);
            wrapper.dataset.nextIndex = idx + 1;
            row.querySelector('input[type=text]')?.focus();
        }

        // ── DOMContentLoaded ──────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            syncCents();

            // تحميل باقات السيرفر المحفوظة
            const serverSel = document.getElementById('server_select');
            const pkgOld    = @json(old('server_package', $plan->server_package));
            if (serverSel?.value) fetchPackagesForServer(serverSel.value, pkgOld ?? '');
            serverSel?.addEventListener('change', e => fetchPackagesForServer(e.target.value, ''));

            // Remove feature
            document.querySelectorAll('[data-feature-wrapper]').forEach(wrapper => {
                wrapper.addEventListener('click', e => {
                    const btn = e.target.closest('[data-remove-feature]');
                    if (btn) btn.closest('[data-feature-row]')?.remove();
                });
            });

            // Add feature
            document.querySelectorAll('[data-add-feature]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const w = document.querySelector('[data-feature-wrapper][data-locale="' + btn.dataset.locale + '"][data-billing="' + btn.dataset.billing + '"]');
                    if (w) appendFeatureRow(w, btn.dataset.locale, btn.dataset.billing, '', true);
                });
            });

            // Feature tabs
            const ACT = ['bg-white','border-gray-300','text-gray-800','font-semibold','shadow-sm'];
            const INA = ['bg-gray-100','border-transparent','text-gray-500'];
            document.querySelectorAll('[data-feature-tab]').forEach(tab => {
                tab.addEventListener('click', () => {
                    const locale  = tab.dataset.locale;
                    const billing = tab.dataset.billing;
                    document.querySelectorAll('[data-feature-tab][data-locale="' + locale + '"]').forEach(t => {
                        t.classList.remove(...ACT); t.classList.add(...INA);
                    });
                    tab.classList.remove(...INA); tab.classList.add(...ACT);
                    document.querySelectorAll('[data-feature-panel][data-locale="' + locale + '"]').forEach(p => {
                        p.classList.toggle('hidden', p.dataset.billing !== billing);
                        p.classList.toggle('block',  p.dataset.billing === billing);
                    });
                });
            });
        });
    })();
    </script>
    @endpush

</x-dashboard-layout>
