<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.plan_categories.index') }}">{{ t('dashboard.plan-categories', 'Plan Categories') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Category', 'Add Category') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_Plan_Category', 'Add Plan Category') }}</h2>
            </div>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $locales = $languages ?? [
            (object) ['code' => 'ar', 'name' => 'العربية'],
            (object) ['code' => 'en', 'name' => 'English'],
        ];
    @endphp

    <form action="{{ route('dashboard.plan_categories.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-12 gap-6">

            {{-- ═══ FORM COLUMN (col-span-8) ═════════════════════════════════ --}}
            <div class="col-span-12 xl:col-span-8">

                {{-- ── Section ١: Basic Info & Translations ─────────── --}}
                <div class="card mb-4">
                    <div class="card-header flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">١</span>
                        <h5 class="mb-0">{{ t('dashboard.Category_Info', 'Category Information') }}</h5>
                    </div>
                    <div class="card-body">

                        {{-- is_active — radio buttons (PHP loose comparison fix) --}}
                        <div class="mb-5">
                            <label class="form-label d-block mb-2">{{ t('dashboard.Status', 'Status') }}</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="1"
                                           {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4" />
                                    <span class="text-sm text-gray-700">{{ t('dashboard.Active_Available', 'Active (available)') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="is_active" value="0"
                                           {{ old('is_active') === '0' ? 'checked' : '' }}
                                           class="accent-primary w-4 h-4" />
                                    <span class="text-sm text-gray-700">{{ t('dashboard.Inactive_Hidden', 'Inactive (hidden)') }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Language Tabs --}}
                        <div class="flex border-b mb-5 gap-1" id="langTabs" role="tablist">
                            @foreach ($locales as $lang)
                                <button type="button" id="tab-{{ $lang->code }}"
                                        onclick="showLangTab('{{ $lang->code }}')"
                                        class="px-4 py-2 text-sm rounded-t transition-all focus:outline-none
                                               @if($loop->first) border-b-2 border-primary font-semibold text-primary @else text-gray-500 hover:text-gray-700 @endif">
                                    {{ $lang->name }}
                                </button>
                            @endforeach
                        </div>

                        <input type="hidden" name="active_locale" id="active_locale" value="{{ $locales[0]->code }}">

                        {{-- Language Panes --}}
                        @foreach ($locales as $lang)
                            <div id="pane-{{ $lang->code }}"
                                 class="lang-pane @if(!$loop->first) hidden @endif">

                                {{-- Title --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ t('dashboard.Category_Title', 'Title') }} ({{ $lang->name }})
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $lang->code }}][title]"
                                           class="form-control"
                                           value="{{ old('translations.' . $lang->code . '.title') }}"
                                           placeholder="{{ t('dashboard.Category_Title_Placeholder', 'e.g. Web Hosting') }}" />
                                    @error('translations.' . $lang->code . '.title')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Slug --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ t('dashboard.Category_Slug', 'Slug') }} ({{ $lang->name }})
                                        <span class="text-xs text-gray-400 font-normal">— {{ t('dashboard.Optional', 'optional') }}</span>
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $lang->code }}][slug]"
                                           class="form-control font-mono" dir="ltr"
                                           value="{{ old('translations.' . $lang->code . '.slug') }}"
                                           placeholder="{{ t('dashboard.Slug_Auto_Generated', 'Auto-generated if left blank') }}" />
                                    @error('translations.' . $lang->code . '.slug')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ t('dashboard.Description', 'Description') }} ({{ $lang->name }})
                                        <span class="text-xs text-gray-400 font-normal">— {{ t('dashboard.Optional', 'optional') }}</span>
                                    </label>
                                    <textarea name="translations[{{ $lang->code }}][description]"
                                              class="form-control" rows="3"
                                              placeholder="{{ t('dashboard.Category_Desc_Placeholder', 'Short description of this category…') }}">{{ old('translations.' . $lang->code . '.description') }}</textarea>
                                    @error('translations.' . $lang->code . '.description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        @endforeach

                    </div>
                </div>

                {{-- Submit buttons --}}
                <div class="flex items-center gap-3 mt-2">
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i class="ti ti-circle-check text-base"></i>
                        {{ t('dashboard.Add_Category', 'Add Category') }}
                    </button>
                    <a href="{{ route('dashboard.plan_categories.index') }}" class="btn btn-light">
                        {{ t('dashboard.Cancel', 'Cancel') }}
                    </a>
                </div>

            </div>

            {{-- ═══ HELP SIDEBAR (col-span-4) ═══════════════════════════════ --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-info-circle text-primary"></i>
                            {{ t('dashboard.Help', 'Help') }}
                        </h5>
                    </div>
                    <div class="card-body space-y-5 text-sm text-gray-600">

                        <div>
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Category_Status', 'Status') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Category_Status_Desc', 'Active categories are visible to clients when choosing a plan. Inactive categories are hidden.') }}</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Category_Slug', 'Slug') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Category_Slug_Desc', 'The slug is used in URLs. It is auto-generated from the title if left blank. Use lowercase letters and hyphens only (e.g. web-hosting).') }}</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Category_Translations', 'Translations') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Category_Translations_Desc', 'Enter the title and description for each language. The slug is shared across locales unless you specify one per language.') }}</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>

    <script>
        function showLangTab(locale) {
            // Hide all panes
            document.querySelectorAll('.lang-pane').forEach(function (p) {
                p.classList.add('hidden');
            });
            // Show selected pane
            var pane = document.getElementById('pane-' + locale);
            if (pane) pane.classList.remove('hidden');

            // Reset all tab buttons
            document.querySelectorAll('[id^="tab-"]').forEach(function (btn) {
                btn.classList.remove('border-b-2', 'border-primary', 'font-semibold', 'text-primary');
                btn.classList.add('text-gray-500');
            });
            // Activate selected tab button
            var activeBtn = document.getElementById('tab-' + locale);
            if (activeBtn) {
                activeBtn.classList.add('border-b-2', 'border-primary', 'font-semibold', 'text-primary');
                activeBtn.classList.remove('text-gray-500');
            }

            document.getElementById('active_locale').value = locale;
        }
    </script>
</x-dashboard-layout>
