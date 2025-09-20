<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.plan_categories.index') }}">Plan Categories</a></li>
                <li class="breadcrumb-item" aria-current="page">Add Category</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Add Plan Category</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.plan_categories.store') }}" method="POST" class="grid grid-cols-12 gap-x-6 gap-y-4">
                        @csrf

                        {{-- Active toggle --}}
                        <div class="col-span-12 md:col-span-6 flex items-center mt-2">
                            <label class="form-label mr-2">Active</label>
                            <input type="checkbox" name="is_active" value="1" class="form-checkbox"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                        </div>

                        {{-- Translations --}}
                        <div class="col-span-12">
                            <h5 class="mb-2 font-bold">Translations</h5>
                            @php
                                $locales = $languages ?? [
                                    (object) ['code' => 'ar', 'name' => 'العربية'],
                                    (object) ['code' => 'en', 'name' => 'English'],
                                ];
                            @endphp

                            {{-- Language Tabs --}}
                            <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse" id="langTabs" role="tablist">
                                @foreach ($locales as $lang)
                                    <button type="button" onclick="showLangTab('{{ $lang->code }}')"
                                        class="px-4 py-2 rounded-t focus:outline-none transition-all @if($loop->first) bg-white border-t border-l border-r font-bold @else bg-gray-200 text-gray-600 @endif"
                                        id="tab-{{ $lang->code }}">
                                        {{ $lang->name }}
                                    </button>
                                @endforeach
                            </ul>

                            <input type="hidden" name="active_locale" id="active_locale" value="{{ $locales[0]->code }}">

                            {{-- Language Panes --}}
                            <div class="tab-content" id="langTabsContent">
                                @foreach ($locales as $lang)
                                    <div id="pane-{{ $lang->code }}" class="lang-pane @if($loop->first) block @else hidden @endif">
                                        <div class="col-span-12 md:col-span-6">
                                            <label class="form-label">Title ({{ $lang->name }}) *</label>
                                            <input type="text" name="translations[{{ $lang->code }}][title]"
                                                class="form-control" value="{{ old('translations.' . $lang->code . '.title') }}" required
                                                placeholder="Enter the title in {{ $lang->name }}">
                                            @error('translations.' . $lang->code . '.title')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label class="form-label">Slug ({{ $lang->name }})</label>
                                            <input type="text" name="translations[{{ $lang->code }}][slug]"
                                                class="form-control"
                                                value="{{ old('translations.' . $lang->code . '.slug') }}"
                                                placeholder="مثال: web-hosting - اتركه فارغًا للتوليد الآلي">
                                            @error('translations.' . $lang->code . '.slug')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-span-12">
                                            <label class="form-label">Description ({{ $lang->name }})</label>
                                            <textarea name="translations[{{ $lang->code }}][description]" class="form-control" rows="2"
                                                placeholder="Optional description in {{ $lang->name }}">{{ old('translations.' . $lang->code . '.description') }}</textarea>
                                            @error('translations.' . $lang->code . '.description')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Form actions --}}
                        <div class="col-span-12 flex items-center justify-end gap-3 mt-4">
                            <a href="{{ route('dashboard.plan_categories.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Language tab switching script --}}
    <script>
        function showLangTab(locale) {
            document.querySelectorAll('.lang-pane').forEach(pane => {
                pane.classList.add('hidden');
                pane.classList.remove('block');
            });
            document.getElementById('pane-' + locale).classList.remove('hidden');
            document.getElementById('pane-' + locale).classList.add('block');

            document.getElementById('active_locale').value = locale;

            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.classList.remove('bg-white', 'border-t', 'border-l', 'border-r', 'font-bold');
                btn.classList.add('bg-gray-200', 'text-gray-600');
            });

            const activeBtn = document.getElementById('tab-' + locale);
            activeBtn.classList.add('bg-white', 'border-t', 'border-l', 'border-r', 'font-bold');
            activeBtn.classList.remove('bg-gray-200', 'text-gray-600');
        }
    </script>
</x-dashboard-layout>
