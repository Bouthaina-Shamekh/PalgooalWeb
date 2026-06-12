<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.plans.index') }}">{{ t('dashboard.plans', 'Plans') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Plan', 'Add plan') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_Hosting_Plan', 'Add Hosting Plan') }}</h2>
            </div>
        </div>
    </div>

    @php
        $localesCollection = $languages?->pluck('name', 'code');
        $locales = $localesCollection ? $localesCollection->filter()->toArray() : [];
        if (empty($locales)) {
            $locales = config('app.locales', ['ar' => 'العربية', 'en' => 'English']);
        }
        $activeLocale = old('active_locale', app()->getLocale());
    @endphp

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 xl:col-span-8">

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <div class="flex items-start gap-3">
                        <i class="ti ti-alert-circle text-xl mt-0.5 shrink-0"></i>
                        <ul class="mb-0 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form id="planForm" action="{{ route('dashboard.plans.store') }}" method="POST">
                @csrf
                <input type="hidden" name="active_locale" id="active_locale" value="{{ $activeLocale }}">

                {{-- ── Section 1: Basic Information ─────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">١</span>
                            <h5 class="mb-0">{{ t('dashboard.Basic_Info', 'Basic information') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- Language tabs --}}
                        <div class="flex gap-2 mb-4 flex-wrap">
                            @foreach ($locales as $locale => $label)
                                <button type="button" onclick="showLangTab('{{ $locale }}')"
                                    class="px-4 py-2 rounded-t-lg focus:outline-none transition-all {{ $activeLocale == $locale ? 'bg-white border border-b-0 font-bold' : 'bg-gray-200 text-gray-600' }}"
                                    id="tab-{{ $locale }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ($locales as $locale => $label)
                            <div id="pane-{{ $locale }}" class="lang-pane {{ $activeLocale == $locale ? 'block' : 'hidden' }}">
                                <div class="grid grid-cols-1 gap-5">
                                    {{-- Plan Name --}}
                                    <div>
                                        <label class="block mb-1 font-medium text-sm">
                                            {{ t('dashboard.Plan_Name_Label', 'Plan name') }}
                                            ({{ $label }})
                                            <span class="text-red-500 mr-0.5">*</span>
                                        </label>
                                        <input type="text"
                                            name="name[{{ $locale }}]"
                                            class="form-control @error('name.' . $locale) is-invalid @enderror"
                                            value="{{ old('name.' . $locale) }}"
                                            @if($activeLocale == $locale) required @endif>
                                        @error('name.' . $locale)
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Description --}}
                                    <div>
                                        <label class="block mb-1 font-medium text-sm">
                                            {{ t('dashboard.Description', 'Description') }}
                                            ({{ $label }})
                                        </label>
                                        <textarea name="description[{{ $locale }}]" rows="3"
                                            class="form-control @error('description.' . $locale) is-invalid @enderror">{{ old('description.' . $locale) }}</textarea>
                                        @error('description.' . $locale)
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Slug & Category (locale-independent) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 pt-5 border-t border-slate-100">
                            <div>
                                <label class="block mb-1 font-medium text-sm">
                                    {{ t('dashboard.Plan_Slug', 'Plan slug') }}
                                    <span class="text-gray-400 text-xs font-normal mr-1">({{ t('dashboard.Optional', 'optional') }})</span>
                                </label>
                                <input type="text"
                                    name="slug"
                                    class="form-control font-mono @error('slug') is-invalid @enderror"
                                    value="{{ old('slug') }}"
                                    placeholder="{{ t('dashboard.Slug_Auto_Generated', 'auto-generated if empty') }}"
                                    dir="ltr">
                                @error('slug')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Plan_Category', 'Category') }}</label>
                                <select name="plan_category_id"
                                    class="form-control @error('plan_category_id') is-invalid @enderror">
                                    <option value="">— {{ t('dashboard.None', 'None') }} —</option>
                                    @foreach ($categories as $cat)
                                        @php
                                            $catLabel = $cat->translation()?->title
                                                ?? ($cat->translations->first()?->title ?? '#' . $cat->id);
                                        @endphp
                                        <option value="{{ $cat->id }}"
                                            {{ old('plan_category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $catLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plan_category_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── Section 2: Pricing ───────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">٢</span>
                            <h5 class="mb-0">{{ t('dashboard.Pricing_And_Category', 'Pricing') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Plan Type --}}
                            <div class="md:col-span-2">
                                <label class="block mb-1 font-medium text-sm">
                                    {{ t('dashboard.Plan_Type', 'Plan type') }}
                                    <span class="text-red-500 mr-0.5">*</span>
                                </label>
                                @php
                                    $selectedType = old('plan_type', \App\Models\Plan::TYPE_MULTI_TENANT);
                                @endphp
                                <select name="plan_type"
                                    class="form-control @error('plan_type') is-invalid @enderror"
                                    required>
                                    <option value="{{ \App\Models\Plan::TYPE_MULTI_TENANT }}"
                                        {{ $selectedType === \App\Models\Plan::TYPE_MULTI_TENANT ? 'selected' : '' }}>
                                        {{ t('dashboard.Plan_Type_Multi_Tenant', 'Multi-Tenant (without cPanel)') }}
                                    </option>
                                    <option value="{{ \App\Models\Plan::TYPE_HOSTING }}"
                                        {{ $selectedType === \App\Models\Plan::TYPE_HOSTING ? 'selected' : '' }}>
                                        {{ t('dashboard.Plan_Type_Hosting', 'Hosting / WordPress (includes cPanel)') }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">{{ t('dashboard.Plan_Type_Hint', 'Determines whether the subscription runs within Palgoals or needs a dedicated hosting space.') }}</p>
                                @error('plan_type')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Monthly Price --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Monthly_Price_USD', 'Monthly price (USD)') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                        id="monthly_price_ui" name="monthly_price_ui"
                                        class="form-control @error('monthly_price_cents') is-invalid @enderror"
                                        value="{{ old('monthly_price_ui') }}"
                                        dir="ltr">
                                </div>
                                <input type="hidden" name="monthly_price_cents" id="monthly_price_cents"
                                    value="{{ old('monthly_price_cents') }}">
                                @error('monthly_price_cents')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Annual Price --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Annual_Price_USD', 'Annual price (USD)') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                        id="annual_price_ui" name="annual_price_ui"
                                        class="form-control @error('annual_price_cents') is-invalid @enderror"
                                        value="{{ old('annual_price_ui') }}"
                                        dir="ltr">
                                </div>
                                <input type="hidden" name="annual_price_cents" id="annual_price_cents"
                                    value="{{ old('annual_price_cents') }}">
                                @error('annual_price_cents')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── Section 3: Settings ──────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">٣</span>
                            <h5 class="mb-0">{{ t('dashboard.Plan_Settings', 'Plan settings') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Status — radio buttons (avoids PHP null == 0 loose-comparison bug) --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Status', 'Status') }}</label>
                                <div class="flex items-center gap-6 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="1"
                                               {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                                               class="accent-primary w-4 h-4" />
                                        <span class="text-sm text-emerald-600 font-medium">{{ t('dashboard.Active_Available', 'Active (available to sell)') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="0"
                                               {{ old('is_active') === '0' ? 'checked' : '' }}
                                               class="accent-primary w-4 h-4" />
                                        <span class="text-sm text-gray-500 font-medium">{{ t('dashboard.Inactive_Hidden', 'Inactive (hidden)') }}</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Featured Plan --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Featured_Plan', 'Featured plan') }}</label>
                                <label class="flex items-center gap-2 cursor-pointer mt-2">
                                    <input type="checkbox" name="is_featured" value="1"
                                        @checked(old('is_featured', false))
                                        class="w-4 h-4 accent-primary" />
                                    <span class="text-sm">{{ t('dashboard.Featured_Plan_Hint', 'Show a special badge on this plan card') }}</span>
                                </label>
                                @error('is_featured')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Featured Badge Labels (one per locale) --}}
                            @foreach ($locales as $locale => $label)
                                <div>
                                    <label class="block mb-1 font-medium text-sm">
                                        {{ t('dashboard.Featured_Badge_Label', 'Featured badge label') }}
                                        ({{ $label }})
                                    </label>
                                    <input type="text"
                                        name="featured_label[{{ $locale }}]"
                                        class="form-control @error('featured_label.' . $locale) is-invalid @enderror"
                                        value="{{ old('featured_label.' . $locale) }}"
                                        placeholder="{{ t('dashboard.Most_Popular', 'Most Popular') }}">
                                    <p class="text-xs text-gray-400 mt-1">{{ t('dashboard.Featured_Badge_Label_Hint', 'Leave empty to use the default text.') }}</p>
                                    @error('featured_label.' . $locale)
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach

                            {{-- Server --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Plan_Server', 'Server') }}</label>
                                <select id="server_select" name="server_id"
                                    class="form-control @error('server_id') is-invalid @enderror">
                                    <option value="">— {{ t('dashboard.None', 'None') }} —</option>
                                    @foreach ($servers as $server)
                                        <option value="{{ $server->id }}"
                                            {{ old('server_id') == $server->id ? 'selected' : '' }}>
                                            {{ $server->name }} ({{ $server->ip ?? $server->hostname }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('server_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Server Package --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Server_Package', 'Server package') }}</label>
                                <select name="server_package" id="server_package_select"
                                    class="form-control @error('server_package') is-invalid @enderror">
                                    <option value="">— {{ t('dashboard.Select_Server_First', 'Select a server first') }} —</option>
                                </select>
                                @error('server_package')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── Section 4: Features ──────────────────────────── --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">٤</span>
                            <h5 class="mb-0">{{ t('dashboard.Plan_Features', 'Plan features') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- Language tabs (synced with Section 1) --}}
                        <div class="flex gap-2 mb-4 flex-wrap">
                            @foreach ($locales as $locale => $label)
                                <button type="button" onclick="showLangTab('{{ $locale }}')"
                                    class="px-4 py-2 rounded-t-lg focus:outline-none transition-all {{ $activeLocale == $locale ? 'bg-white border border-b-0 font-bold' : 'bg-gray-200 text-gray-600' }}"
                                    id="feat-tab-{{ $locale }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        @foreach ($locales as $locale => $label)
                            @php
                                $rawFeatures = old('features.' . $locale);
                                if ($rawFeatures === null) {
                                    $rawFeatures = [];
                                }
                                $billingOptions = [
                                    'monthly' => t('dashboard.Monthly', 'Monthly'),
                                    'annual'  => t('dashboard.Annual', 'Annual'),
                                ];
                                $rawFeatures = is_array($rawFeatures) ? $rawFeatures : [];
                                $hasBillingSplit = array_intersect(array_keys($rawFeatures), array_keys($billingOptions)) !== [];

                                $normalizeFeature = function ($item) {
                                    if (is_array($item)) {
                                        $text = isset($item['text']) ? trim((string) $item['text']) : '';
                                        $available = array_key_exists('available', $item)
                                            ? filter_var($item['available'], FILTER_VALIDATE_BOOLEAN)
                                            : true;
                                    } else {
                                        $text = trim((string) $item);
                                        $available = true;
                                    }
                                    return ['text' => $text, 'available' => (bool) $available];
                                };

                                $featureBuckets = [];
                                foreach ($billingOptions as $billingKey => $billingLabel) {
                                    $bucketSource = $hasBillingSplit
                                        ? ($rawFeatures[$billingKey] ?? [])
                                        : ($billingKey === 'monthly' ? $rawFeatures : []);
                                    $featureBuckets[$billingKey] = collect(
                                        is_array($bucketSource) ? $bucketSource : []
                                    )
                                        ->map($normalizeFeature)
                                        ->filter(fn($feature) => $feature['text'] !== '')
                                        ->values();
                                }
                            @endphp

                            <div id="feat-pane-{{ $locale }}"
                                class="feat-lang-pane {{ $activeLocale == $locale ? 'block' : 'hidden' }}">

                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    @foreach ($billingOptions as $billingKey => $billingLabel)
                                        <button type="button"
                                            class="feature-cycle-tab px-3 py-1 rounded-md border transition text-sm {{ $loop->first ? 'bg-white border-gray-300 text-gray-800 font-semibold shadow-sm' : 'bg-gray-100 border-transparent text-gray-500' }}"
                                            data-feature-tab
                                            data-locale="{{ $locale }}"
                                            data-billing="{{ $billingKey }}">
                                            {{ $billingLabel }}
                                        </button>
                                    @endforeach
                                </div>

                                @foreach ($billingOptions as $billingKey => $billingLabel)
                                    @php
                                        /** @var \Illuminate\Support\Collection $featureItems */
                                        $featureItems = $featureBuckets[$billingKey] ?? collect();
                                    @endphp
                                    <div class="{{ $loop->first ? 'block' : 'hidden' }}"
                                        data-feature-panel
                                        data-locale="{{ $locale }}"
                                        data-billing="{{ $billingKey }}">
                                        <div class="space-y-2"
                                            data-feature-wrapper
                                            data-locale="{{ $locale }}"
                                            data-billing="{{ $billingKey }}"
                                            data-next-index="{{ $featureItems->count() }}"
                                            data-available-label="{{ t('dashboard.Available', 'Available') }}"
                                            data-remove-label="{{ t('dashboard.Remove_Feature', 'Remove') }}">
                                            @foreach ($featureItems as $index => $feature)
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-3" data-feature-row>
                                                    <div class="flex-1 w-full">
                                                        <input type="text"
                                                            name="features[{{ $locale }}][{{ $billingKey }}][{{ $index }}][text]"
                                                            class="form-control"
                                                            value="{{ $feature['text'] }}"
                                                            placeholder="e.g. Domain">
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
                                                        <span>{{ t('dashboard.Available', 'Available') }}</span>
                                                    </label>
                                                    <button type="button"
                                                        class="text-red-500 hover:text-red-700 shrink-0 w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition"
                                                        data-remove-feature>
                                                        <i class="ti ti-x text-base leading-none"></i>
                                                        <span class="sr-only">{{ t('dashboard.Remove_Feature', 'Remove') }}</span>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-3 flex items-center gap-3">
                                            <button type="button"
                                                class="btn btn-light btn-sm flex items-center gap-1"
                                                data-add-feature
                                                data-locale="{{ $locale }}"
                                                data-billing="{{ $billingKey }}">
                                                <i class="ti ti-plus text-sm"></i>
                                                {{ t('dashboard.Add_Feature', 'Add feature') }}
                                            </button>
                                            <span class="text-xs text-gray-400">
                                                {{ t('dashboard.Feature_Toggle_Hint', 'Use the toggle to indicate whether the feature is included.') }}
                                            </span>
                                        </div>

                                        @if ($errors->has("features.$locale.$billingKey"))
                                            <p class="text-red-500 text-xs mt-1">{{ $errors->first("features.$locale.$billingKey") }}</p>
                                        @endif
                                        @if ($errors->has("features.$locale.$billingKey.*.text"))
                                            <p class="text-red-500 text-xs mt-1">{{ $errors->first("features.$locale.$billingKey.*.text") }}</p>
                                        @endif
                                    </div>
                                @endforeach

                            </div>
                        @endforeach

                    </div>
                </div>

                {{-- Form actions --}}
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i class="ti ti-device-floppy text-base"></i>
                        {{ t('dashboard.Create_Plan', 'Create plan') }}
                    </button>
                    <a href="{{ route('dashboard.plans.index') }}" class="btn btn-light">
                        {{ t('dashboard.Cancel', 'Cancel') }}
                    </a>
                </div>

            </form>
        </div>

        {{-- ── Help Sidebar ─────────────────────────────────── --}}
        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-6">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-help-circle text-primary text-lg"></i>
                        <h5 class="mb-0">{{ t('dashboard.Help', 'Help') }}</h5>
                    </div>
                </div>
                <div class="card-body space-y-4 text-sm text-gray-600">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Plan_Type', 'Plan type') }}</p>
                        <p>{{ t('dashboard.Help_Plan_Type_Desc', 'Multi-Tenant: runs within the shared Palgoals platform. Hosting: creates a dedicated cPanel account on one of your servers.') }}</p>
                    </div>
                    <hr class="border-slate-100">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Featured', 'Featured plan') }}</p>
                        <p>{{ t('dashboard.Help_Featured_Desc', 'Featured plans show a prominent badge on their card. You can customize the badge text per language.') }}</p>
                    </div>
                    <hr class="border-slate-100">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Server_Package', 'Server package') }}</p>
                        <p>{{ t('dashboard.Help_Server_Package_Desc', 'Used when auto-provisioning hosting accounts. Select a server first to load available WHM packages.') }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Scripts --}}
    <script>
        function showLangTab(locale) {
            // Switch both pane sets (Section 1 + Section 4)
            document.querySelectorAll('.lang-pane').forEach(p => p.classList.add('hidden'));
            document.querySelectorAll('.feat-lang-pane').forEach(p => p.classList.add('hidden'));
            const pane = document.getElementById('pane-' + locale);
            if (pane) pane.classList.remove('hidden');
            const featPane = document.getElementById('feat-pane-' + locale);
            if (featPane) featPane.classList.remove('hidden');

            document.getElementById('active_locale').value = locale;

            // Reset all language tab buttons
            document.querySelectorAll('[id^="tab-"],[id^="feat-tab-"]').forEach(btn => {
                btn.classList.remove('bg-white', 'border', 'border-b-0', 'font-bold');
                btn.classList.add('bg-gray-200', 'text-gray-600');
            });
            // Activate clicked tab (both the Section 1 tab and the Section 4 tab)
            ['tab-' + locale, 'feat-tab-' + locale].forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.classList.remove('bg-gray-200', 'text-gray-600');
                    btn.classList.add('bg-white', 'border', 'border-b-0', 'font-bold');
                }
            });
        }

        // Sync cents on submit
        const monthlyUI     = document.getElementById('monthly_price_ui');
        const monthlyCents  = document.getElementById('monthly_price_cents');
        const annualUI      = document.getElementById('annual_price_ui');
        const annualCents   = document.getElementById('annual_price_cents');

        function syncCents() {
            if (monthlyCents) monthlyCents.value = monthlyUI?.value ? Math.round(parseFloat(monthlyUI.value) * 100) : '';
            if (annualCents)  annualCents.value  = annualUI?.value  ? Math.round(parseFloat(annualUI.value)  * 100) : '';
        }

        monthlyUI?.addEventListener('input', syncCents);
        annualUI?.addEventListener('input', syncCents);
        document.getElementById('planForm')?.addEventListener('submit', syncCents);

        // Server package fetch
        const selectServerFirstLabel = {{ Js::from(t('dashboard.Select_Server_First', 'Select a server first')) }};
        const loadingLabel = {{ Js::from(t('dashboard.Loading', 'Loading…')) }};

        async function fetchPackagesForServer(serverId, selected = '') {
            const select = document.getElementById('server_package_select');
            if (!select) return;
            select.innerHTML = '<option value="">' + loadingLabel + '</option>';
            if (!serverId) {
                select.innerHTML = '<option value="">— ' + selectServerFirstLabel + ' —</option>';
                return;
            }
            try {
                const res = await fetch(`{{ url('admin/servers') }}/${serverId}/packages`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    const text = await res.text();
                    let msg = 'HTTP ' + res.status;
                    try { const j = JSON.parse(text); if (j.message) msg = j.message; else if (j.error) msg = j.error; }
                    catch (err) { if (text) msg = text.substring(0, 200); }
                    select.innerHTML = '<option value="">-- Error: ' + msg + ' --</option>';
                    console.error('Package fetch failed', res.status, text);
                    return;
                }
                const data = await res.json();
                select.innerHTML = '<option value="">— ' + selectServerFirstLabel + ' —</option>';
                const packages = data?.packages || data?.pkg || data?.data || [];
                if (Array.isArray(packages) && packages.length) {
                    packages.forEach(pkg => {
                        const opt = document.createElement('option');
                        opt.value = typeof pkg === 'string' ? pkg : (pkg.name || pkg.package || pkg.pkg || JSON.stringify(pkg));
                        opt.textContent = opt.value;
                        if (opt.value === selected) opt.selected = true;
                        select.appendChild(opt);
                    });
                } else if (packages && typeof packages === 'object' && !Array.isArray(packages)) {
                    Object.keys(packages).forEach(k => {
                        const opt = document.createElement('option');
                        opt.value = k; opt.textContent = k;
                        if (k === selected) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                select.innerHTML = '<option value="">-- Error: ' + (e?.message || e) + ' --</option>';
                console.error('Exception while fetching packages', e);
            }
        }

        function escapeFeatureValue(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function appendFeatureRow(wrapper, locale, billing, textValue = '', available = true) {
            const bucket    = billing || wrapper.dataset.billing || 'monthly';
            const nextIndex = parseInt(wrapper.dataset.nextIndex || '0', 10);
            const namePrefix = 'features[' + locale + '][' + bucket + '][' + nextIndex + ']';
            const row = document.createElement('div');
            row.className = 'flex flex-col sm:flex-row sm:items-center gap-3';
            row.setAttribute('data-feature-row', '');

            const availableLabel = wrapper.dataset.availableLabel || 'Available';
            const removeLabel    = wrapper.dataset.removeLabel    || 'Remove';
            const escapedValue   = escapeFeatureValue(textValue);
            const checkedAttr    = available ? ' checked' : '';

            row.innerHTML =
                '<div class="flex-1 w-full">' +
                '<input type="text" name="' + namePrefix + '[text]" class="form-control" placeholder="e.g. Domain" value="' + escapedValue + '">' +
                '</div>' +
                '<label class="inline-flex items-center gap-2 text-sm shrink-0">' +
                '<input type="hidden" name="' + namePrefix + '[available]" value="0">' +
                '<input type="checkbox" name="' + namePrefix + '[available]" value="1" class="h-4 w-4 accent-primary"' + checkedAttr + '>' +
                '<span>' + availableLabel + '</span>' +
                '</label>' +
                '<button type="button" class="text-red-500 hover:text-red-700 shrink-0 w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition" data-remove-feature>' +
                '<i class="ti ti-x text-base leading-none"></i>' +
                '<span class="sr-only">' + removeLabel + '</span>' +
                '</button>';

            wrapper.appendChild(row);
            wrapper.dataset.nextIndex = nextIndex + 1;
            const textInput = row.querySelector('input[type="text"]');
            if (textInput) textInput.focus();
        }

        document.addEventListener('DOMContentLoaded', () => {
            syncCents();

            const serverSelect     = document.getElementById('server_select');
            const serverPackageOld = @json(old('server_package'));
            if (serverSelect && serverSelect.value) {
                fetchPackagesForServer(serverSelect.value, serverPackageOld ?? '');
            }
            serverSelect?.addEventListener('change', (e) => fetchPackagesForServer(e.target.value, ''));

            document.querySelectorAll('[data-feature-wrapper]').forEach(wrapper => {
                if (!wrapper.dataset.nextIndex) {
                    wrapper.dataset.nextIndex = wrapper.querySelectorAll('[data-feature-row]').length;
                }
                wrapper.addEventListener('click', (event) => {
                    const removeBtn = event.target.closest('[data-remove-feature]');
                    if (removeBtn) {
                        const row = removeBtn.closest('[data-feature-row]');
                        if (row) row.remove();
                    }
                });
            });

            document.querySelectorAll('[data-add-feature]').forEach(button => {
                button.addEventListener('click', () => {
                    const locale  = button.dataset.locale;
                    const billing = button.dataset.billing;
                    const wrapper = document.querySelector(
                        '[data-feature-wrapper][data-locale="' + locale + '"][data-billing="' + billing + '"]'
                    );
                    if (wrapper) appendFeatureRow(wrapper, locale, billing, '', true);
                });
            });

            const ACTIVE_TAB   = ['bg-white', 'border-gray-300', 'text-gray-800', 'font-semibold', 'shadow-sm'];
            const INACTIVE_TAB = ['bg-gray-100', 'border-transparent', 'text-gray-500'];

            document.querySelectorAll('[data-feature-tab]').forEach(tab => {
                tab.addEventListener('click', () => {
                    const locale  = tab.dataset.locale;
                    const billing = tab.dataset.billing;

                    document.querySelectorAll('[data-feature-tab][data-locale="' + locale + '"]').forEach(btn => {
                        btn.classList.remove(...ACTIVE_TAB);
                        btn.classList.add(...INACTIVE_TAB);
                    });
                    tab.classList.remove(...INACTIVE_TAB);
                    tab.classList.add(...ACTIVE_TAB);

                    document.querySelectorAll('[data-feature-panel][data-locale="' + locale + '"]').forEach(panel => {
                        if (panel.dataset.billing === billing) {
                            panel.classList.remove('hidden');
                            panel.classList.add('block');
                        } else {
                            panel.classList.add('hidden');
                            panel.classList.remove('block');
                        }
                    });
                });
            });
        });
    </script>
</x-dashboard-layout>
