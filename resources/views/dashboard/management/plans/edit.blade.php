<x-dashboard-layout>
    <!-- Breadcrumb -->
    <div class="page-header mb-6">
        <div class="page-block">
            <ul class="flex flex-wrap gap-2 text-sm text-gray-500 mb-2">
                <li><a href="{{ route('dashboard.home') }}" class="hover:underline">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('dashboard.plans.index') }}" class="hover:underline">Plans</a></li>
                <li>/</li>
                <li class="font-semibold">Edit Plan</li>
            </ul>
            <h2 class="text-2xl font-bold">Edit Hosting Plan</h2>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">

                    <form id="planForm" action="{{ route('dashboard.plans.update', $plan->id) }}" method="POST"
                        class="space-y-6">
                        @csrf
                        @method('PUT')

                        @php
                            $localesCollection = $languages?->pluck('name', 'code');
                            $locales = $localesCollection ? $localesCollection->filter()->toArray() : [];
                            if (empty($locales)) {
                                $locales = config('app.locales', ['ar' => 'العربية', 'en' => 'English']);
                            }
                            $activeLocale = old('active_locale', app()->getLocale());
                            // translation passed from controller (may be null)
                        @endphp

                        <!-- Basic Info -->
                        <div class="grid grid-cols-12 gap-6">
                            <!-- Category -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Category</label>
                                <select name="plan_category_id"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/30">
                                    <option value="">-- None --</option>
                                    @foreach ($categories as $cat)
                                        @php
                                            $label =
                                                $cat->translation()?->title ??
                                                ($cat->translations->first()?->title ?? '#' . $cat->id);
                                        @endphp
                                        <option value="{{ $cat->id }}"
                                            {{ old('plan_category_id', $plan->plan_category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plan_category_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Slug -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Plan Slug <span
                                        class="text-gray-400">(optional)</span></label>
                                <input type="text" name="slug" class="w-full border rounded-lg px-3 py-2"
                                    value="{{ old('slug', $plan->slug) }}" placeholder="auto-generated if empty">
                                @error('slug')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Monthly Price -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Monthly Price (USD)</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border bg-gray-50">$</span>
                                    <input type="number" step="0.01" min="0" id="monthly_price_ui"
                                        name="monthly_price_ui" class="w-full border rounded-r-lg px-3 py-2"
                                        value="{{ old('monthly_price_ui', optional($plan)->monthly_price) }}">
                                </div>
                                <input type="hidden" name="monthly_price_cents" id="monthly_price_cents"
                                    value="{{ old('monthly_price_cents', $plan->monthly_price_cents) }}">
                                @error('monthly_price_cents')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Annual Price -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Annual Price (USD)</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border bg-gray-50">$</span>
                                    <input type="number" step="0.01" min="0" id="annual_price_ui"
                                        name="annual_price_ui" class="w-full border rounded-r-lg px-3 py-2"
                                        value="{{ old('annual_price_ui', optional($plan)->annual_price) }}">
                                </div>
                                <input type="hidden" name="annual_price_cents" id="annual_price_cents"
                                    value="{{ old('annual_price_cents', $plan->annual_price_cents) }}">
                                @error('annual_price_cents')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-span-12 md:col-span-6 flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1"
                                    @checked(old('is_active', $plan->is_active)) class="w-4 h-4">
                                <span class="text-sm">Active (available to sell)</span>
                                @error('is_active')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Featured toggle -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Featured Plan</label>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_featured" value="1"
                                        @checked(old('is_featured', $plan->is_featured)) class="w-4 h-4">
                                    <span class="text-sm">Highlight this plan with a special badge</span>
                                </div>
                                @error('is_featured')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Featured label -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Featured Badge Label</label>
                                <input type="text" name="featured_label"
                                    class="w-full border rounded-lg px-3 py-2"
                                    value="{{ old('featured_label', $plan->getOriginal('featured_label')) }}"
                                    placeholder="Most Popular">
                                <p class="text-xs text-gray-500 mt-1">Shown when the plan is marked as featured.
                                    Leave empty to use the default text.</p>
                                @error('featured_label')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Server -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Server</label>
                                <select id="server_select" name="server_id"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary/30">
                                    <option value="">-- None --</option>
                                    @foreach ($servers as $server)
                                        <option value="{{ $server->id }}"
                                            {{ old('server_id', $plan->server_id) == $server->id ? 'selected' : '' }}>
                                            {{ $server->name }} ({{ $server->ip ?? $server->hostname }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('server_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Server Package -->
                            <div class="col-span-12 md:col-span-6">
                                <label class="block text-sm font-medium mb-1">Server Package</label>
                                <select name="server_package" id="server_package_select"
                                    class="w-full border rounded-lg px-3 py-2">
                                    <option value="">-- (select server first) --</option>
                                </select>
                                @error('server_package')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Translations Tabs -->
                        <div class="mt-6">
                            <h3 class="font-semibold mb-2">Translations</h3>
                            <div class="flex gap-2 mb-4 rtl:space-x-reverse">
                                @foreach ($locales as $locale => $label)
                                    <button type="button" onclick="showLangTab('{{ $locale }}')"
                                        class="px-4 py-2 rounded-t-lg focus:outline-none transition-all {{ $activeLocale == $locale ? 'bg-white border border-b-0 font-bold' : 'bg-gray-200 text-gray-600' }}"
                                        id="tab-{{ $locale }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="active_locale" id="active_locale" value="{{ $activeLocale }}">

                            @foreach ($locales as $locale => $label)
                                @php
                                    // translation might be for current locale only; fall back to plan->translation for other locales if needed
                                    $transForLocale = $plan->translations->where('locale', $locale)->first();
                                    $rawFeatures = old('features.' . $locale);
                                    if ($rawFeatures === null) {
                                        $rawFeatures = $transForLocale?->features ?? [];
                                    }
                                    $featureItems = collect($rawFeatures)
                                        ->map(function ($item) {
                                            if (is_array($item)) {
                                                $text = isset($item['text']) ? trim((string) $item['text']) : '';
                                                $available = array_key_exists('available', $item)
                                                    ? filter_var($item['available'], FILTER_VALIDATE_BOOLEAN)
                                                    : true;
                                            } else {
                                                $text = trim((string) $item);
                                                $available = true;
                                            }
                                            return [
                                                'text' => $text,
                                                'available' => (bool) $available,
                                            ];
                                        })
                                        ->filter(fn($feature) => $feature['text'] !== '')
                                        ->values();
                                @endphp
                                <div id="pane-{{ $locale }}"
                                    class="lang-pane {{ $activeLocale == $locale ? 'block' : 'hidden' }}">
                                    <div class="grid grid-cols-12 gap-6">
                                        <!-- Name -->
                                        <div class="col-span-12 md:col-span-6">
                                            <label class="block text-sm font-medium mb-1">Plan Name
                                                ({{ $label }}) *</label>
                                            <input type="text" name="name[{{ $locale }}]"
                                                class="w-full border rounded-lg px-3 py-2"
                                                value="{{ old('name.' . $locale, $transForLocale?->title ?? ($locale == app()->getLocale() ? $plan->name : '')) }}"
                                                @if ($activeLocale == $locale) required @endif>
                                            @error('name.' . $locale)
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Description -->
                                        <div class="col-span-12">
                                            <label class="block text-sm font-medium mb-1">Description
                                                ({{ $label }})</label>
                                            <textarea name="description[{{ $locale }}]" rows="3" class="w-full border rounded-lg px-3 py-2">{{ old('description.' . $locale, $transForLocale?->description) }}</textarea>
                                            @error('description.' . $locale)
                                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Features -->
                                        <div class="col-span-12">
                                            <label class="block text-sm font-medium mb-1">Features
                                                ({{ $label }})</label>
                                            <div class="space-y-2" data-feature-wrapper data-locale="{{ $locale }}"
                                                data-next-index="{{ $featureItems->count() }}"
                                                data-available-label="{{ __('Available') }}"
                                                data-remove-label="{{ __('Remove feature') }}">
                                                @foreach ($featureItems as $index => $feature)
                                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3"
                                                        data-feature-row>
                                                        <div class="flex-1 w-full">
                                                            <input type="text"
                                                                name="features[{{ $locale }}][{{ $index }}][text]"
                                                                class="w-full border rounded-lg px-3 py-2"
                                                                value="{{ $feature['text'] }}"
                                                                placeholder="e.g. Domain">
                                                        </div>
                                                        <label class="inline-flex items-center gap-2 text-sm">
                                                            <input type="hidden"
                                                                name="features[{{ $locale }}][{{ $index }}][available]"
                                                                value="0">
                                                            <input type="checkbox"
                                                                name="features[{{ $locale }}][{{ $index }}][available]"
                                                                value="1"
                                                                class="h-4 w-4 text-primary border-gray-300 rounded"
                                                                @checked($feature['available'])>
                                                            <span>{{ __('Available') }}</span>
                                                        </label>
                                                        <button type="button"
                                                            class="text-red-600 hover:text-red-800"
                                                            data-remove-feature>
                                                            &times;
                                                            <span class="sr-only">{{ __('Remove feature') }}</span>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-2 flex items-center gap-2">
                                                <button type="button"
                                                    class="px-3 py-2 bg-primary text-white rounded-lg"
                                                    data-add-feature="{{ $locale }}">Add Feature</button>
                                                <span class="text-xs text-gray-500">Use the availability toggle to
                                                    highlight whether the feature is included.</span>
                                            </div>
                                            @if ($errors->has("features.$locale"))
                                                <p class="text-red-600 text-sm mt-1">
                                                    {{ $errors->first("features.$locale") }}</p>
                                            @endif
                                            @if ($errors->has("features.$locale.*.text"))
                                                <p class="text-red-600 text-sm mt-1">
                                                    {{ $errors->first("features.$locale.*.text") }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Form Buttons -->
                        <div class="flex justify-end gap-3 mt-4">
                            <a href="{{ route('dashboard.plans.index') }}"
                                class="px-4 py-2 border rounded-lg bg-gray-200 hover:bg-gray-300">Cancel</a>
                            <button type="submit"
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Update
                                Plan</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function showLangTab(locale) {
            document.querySelectorAll('.lang-pane').forEach(p => p.classList.add('hidden'));
            document.getElementById('pane-' + locale).classList.remove('hidden');
            document.getElementById('active_locale').value = locale;
            document.querySelectorAll('[id^="tab-"]').forEach(btn => btn.classList.remove('bg-white', 'border',
                'border-b-0', 'font-bold', 'text-gray-800'));
            document.getElementById('tab-' + locale).classList.add('bg-white', 'border', 'border-b-0', 'font-bold');
        }

        // Sync cents
        const monthlyUI = document.getElementById('monthly_price_ui');
        const monthlyCents = document.getElementById('monthly_price_cents');
        const annualUI = document.getElementById('annual_price_ui');
        const annualCents = document.getElementById('annual_price_cents');

        function syncCents() {
            monthlyCents.value = monthlyUI?.value ? Math.round(parseFloat(monthlyUI.value) * 100) : '';
            annualCents.value = annualUI?.value ? Math.round(parseFloat(annualUI.value) * 100) : '';
        }

        monthlyUI?.addEventListener('input', syncCents);
        annualUI?.addEventListener('input', syncCents);
        document.getElementById('planForm')?.addEventListener('submit', syncCents);

        // Fetch server packages and populate select
        async function fetchPackagesForServer(serverId, selected = '') {
            const select = document.getElementById('server_package_select');
            if (!select) {
                return;
            }
            select.innerHTML = '<option value="">Loading...</option>';
            if (!serverId) {
                select.innerHTML = '<option value="">-- (select server first) --</option>';
                return;
            }
            try {
                const res = await fetch(`{{ url('admin/servers') }}/${serverId}/packages`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) {
                    const text = await res.text();
                    let msg = `HTTP ${res.status}`;
                    try {
                        const j = JSON.parse(text);
                        if (j.message) msg = j.message;
                        else if (j.error) msg = j.error;
                    } catch (err) {
                        if (text) msg = text.substring(0, 200);
                    }
                    select.innerHTML = `<option value="">-- Error loading packages: ${msg} --</option>`;
                    console.error('Package fetch failed', res.status, text);
                    return;
                }

                const data = await res.json();
                select.innerHTML = '<option value="">-- None --</option>';
                const packages = data?.packages || data?.pkg || data?.data || [];
                if (Array.isArray(packages) && packages.length) {
                    packages.forEach(pkg => {
                        const opt = document.createElement('option');
                        opt.value = typeof pkg === 'string' ? pkg : (pkg.name || pkg.package || pkg.pkg || JSON.stringify(pkg));
                        opt.textContent = typeof pkg === 'string' ? pkg : (pkg.name || pkg.package || pkg.pkg || JSON.stringify(pkg));
                        if (opt.value === selected) opt.selected = true;
                        select.appendChild(opt);
                    });
                } else if (packages && typeof packages === 'object' && !Array.isArray(packages)) {
                    Object.keys(packages).forEach(k => {
                        const opt = document.createElement('option');
                        opt.value = k;
                        opt.textContent = k;
                        if (k === selected) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                const msg = e?.message || e;
                select.innerHTML = `<option value="">-- Error loading packages: ${msg} --</option>`;
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

        function appendFeatureRow(wrapper, locale, textValue = '', available = true) {
            const nextIndex = parseInt(wrapper.dataset.nextIndex || '0', 10);
            const namePrefix = 'features[' + locale + '][' + nextIndex + ']';
            const row = document.createElement('div');
            row.className = 'flex flex-col sm:flex-row sm:items-center gap-3';
            row.setAttribute('data-feature-row', '');

            const availableLabel = wrapper.dataset.availableLabel || 'Available';
            const removeLabel = wrapper.dataset.removeLabel || 'Remove feature';
            const escapedValue = escapeFeatureValue(textValue);
            const checkedAttr = available ? ' checked' : '';

            row.innerHTML =
                "<div class=\"flex-1 w-full\">" +
                "<input type=\"text\" name=\"" + namePrefix + "[text]\" class=\"w-full border rounded-lg px-3 py-2\" placeholder=\"e.g. Domain\" value=\"" + escapedValue + "\">" +
                "</div>" +
                "<label class=\"inline-flex items-center gap-2 text-sm\">" +
                "<input type=\"hidden\" name=\"" + namePrefix + "[available]\" value=\"0\">" +
                "<input type=\"checkbox\" name=\"" + namePrefix + "[available]\" value=\"1\" class=\"h-4 w-4 text-primary border-gray-300 rounded\"" + checkedAttr + ">" +
                "<span>" + availableLabel + "</span>" +
                "</label>" +
                "<button type=\"button\" class=\"text-red-600 hover:text-red-800\" data-remove-feature>&times;<span class=\"sr-only\">" + removeLabel + "</span></button>";

            wrapper.appendChild(row);
            wrapper.dataset.nextIndex = nextIndex + 1;
            const textInput = row.querySelector('input[type=\"text\"]');
            textInput?.focus();
        }

        document.addEventListener('DOMContentLoaded', () => {
            syncCents();

            const serverSelectEl = document.querySelector('select[name=\"server_id\"]');
            const serverPackageOld = @json(old('server_package', $plan->server_package));
            if (serverSelectEl && serverSelectEl.value) {
                fetchPackagesForServer(serverSelectEl.value, serverPackageOld ?? '');
            }
            serverSelectEl?.addEventListener('change', (e) => fetchPackagesForServer(e.target.value, ''));

            document.querySelectorAll('[data-feature-wrapper]').forEach(wrapper => {
                if (!wrapper.dataset.nextIndex) {
                    const preset = wrapper.querySelectorAll('[data-feature-row]').length;
                    wrapper.dataset.nextIndex = preset;
                }
                wrapper.addEventListener('click', (event) => {
                    const removeBtn = event.target.closest('[data-remove-feature]');
                    if (removeBtn) {
                        const row = removeBtn.closest('[data-feature-row]');
                        row?.remove();
                    }
                });
            });

            document.querySelectorAll('[data-add-feature]').forEach(button => {
                button.addEventListener('click', () => {
                    const locale = button.dataset.addFeature;
                    const wrapper = document.querySelector('[data-feature-wrapper][data-locale=\"' + locale + '\"]');
                    if (!wrapper) {
                        return;
                    }
                    appendFeatureRow(wrapper, locale, '', true);
                });
            });
        });
    </script>
</x-dashboard-layout>
