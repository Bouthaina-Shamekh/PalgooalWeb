<x-dashboard-layout>
    @php
        $activeLang = strtolower((string) ($itemForm['active_lang'] ?? app()->getLocale()));
        $availableLangs = $languages
            ->pluck('code')
            ->map(fn($code) => strtolower((string) $code))
            ->filter()
            ->values();

        if (! $availableLangs->contains($activeLang)) {
            $activeLang = (string) ($availableLangs->first() ?? strtolower((string) config('app.locale', 'en')));
        }

        $isEditing = $editingItem !== null;
        $openMenuManagement = $errors->has('menu_name')
            || $errors->has('menu_location')
            || $errors->has('menu_title')
            || $errors->has('menu_slug')
            || $errors->has('menu_is_active')
            || $errors->has('menu_delete');

        $pageLabelMap = [];
        foreach ($pages as $page) {
            $titles = [];
            foreach ($page->translations as $translation) {
                $locale = strtolower((string) ($translation->locale ?? ''));
                $title = trim((string) ($translation->title ?? ''));
                if ($locale !== '' && $title !== '') {
                    $titles[$locale] = $title;
                }
            }

            $fallback = (string) (collect($titles)->first() ?? '');
            if ($fallback === '') {
                $fallback = trim((string) ($page->translation()?->title ?? ''));
            }

            $pageLabelMap[(int) $page->id] = [
                'titles' => $titles,
                'fallback' => $fallback !== '' ? $fallback : 'Page #' . $page->id,
            ];
        }
    @endphp

    <div class="space-y-6">
        <div class="page-header">
            <div class="page-block">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.menus') }}">{{ t('dashboard.Menus', 'Menus') }}</a></li>
                </ul>
                <div class="page-header-title">
                    <h2 class="mb-0">{{ t('dashboard.Menus', 'Menus') }}</h2>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <details class="card" @if($openMenuManagement) open @endif>
            <summary class="card-header cursor-pointer">
                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h5 class="mb-0">{{ t('dashboard.Menu_Management', 'Menu Management') }}</h5>
                        <p class="text-muted mb-0 text-sm">
                            {{ t('dashboard.Menu_Management_Description', 'Manage menu collections, then assign items and order.') }}
                        </p>
                    </div>
                    <div class="text-muted text-sm">
                        {{ t('dashboard.Current_Selected_Menu', 'Current menu') }}:
                        <span class="fw-semibold text-dark">{{ $selectedMenu?->name ?? '-' }}</span>
                    </div>
                </div>
            </summary>
            <div class="card-body space-y-4">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <div class="border rounded-xl px-3 py-2 bg-light d-flex align-items-center justify-content-between">
                            <div class="text-muted small">{{ t('dashboard.Total_Menus', 'Total Menus') }}</div>
                            <div class="fs-5 fw-semibold">{{ $menus->count() }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-xl px-3 py-2 bg-light d-flex align-items-center justify-content-between">
                            <div class="text-muted small">{{ t('dashboard.Items_In_Selected_Menu', 'Items In Selected Menu') }}</div>
                            <div class="fs-5 fw-semibold">{{ $selectedMenu?->items?->count() ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-xl px-3 py-2 bg-light d-flex align-items-center justify-content-between">
                            <div class="text-muted small">{{ t('dashboard.Active_Languages', 'Active Languages') }}</div>
                            <div class="fs-5 fw-semibold">{{ $languages->count() }}</div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-xl p-3">
                    @if ($selectedMenu)
                        <form method="POST" action="{{ route('dashboard.menus.update', $selectedMenu) }}"
                            class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-end">
                            @csrf
                            @method('PATCH')

                            <div class="lg:col-span-2">
                                <h6 class="mb-1">{{ t('dashboard.Menu_Settings', 'Menu Settings') }}</h6>
                                <p class="text-muted text-sm mb-0">
                                    {{ t('dashboard.Menu_Settings_Description', 'Edit menu identity and where this menu appears.') }}
                                </p>
                            </div>

                            <div>
                                <label for="menu_title" class="form-label mb-1">{{ t('dashboard.Menu_Title', 'Menu Title') }}</label>
                                <input id="menu_title" name="menu_title" type="text" class="form-control"
                                    value="{{ old('menu_title', $itemForm['title'] ?? '') }}">
                            </div>
                            <div>
                                <label for="menu_slug" class="form-label mb-1">{{ t('dashboard.Menu_Slug', 'Menu Slug') }}</label>
                                <input id="menu_slug" name="menu_slug" type="text" class="form-control" placeholder="main-menu"
                                    value="{{ old('menu_slug', $itemForm['slug'] ?? '') }}">
                            </div>
                            <div>
                                <label for="menu_location" class="form-label mb-1">{{ t('dashboard.Menu_Location', 'Menu Location') }}</label>
                                <select id="menu_location" name="menu_location" class="form-select">
                                    @foreach ($menuLocations as $locationKey => $location)
                                        <option value="{{ $locationKey }}"
                                            @selected(old('menu_location', $itemForm['location'] ?? '') === $locationKey)>
                                            {{ $location['label'] ?? $locationKey }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <label class="form-check mb-0">
                                    <input type="hidden" name="menu_is_active" value="0">
                                    <input type="checkbox" class="form-check-input" name="menu_is_active" value="1"
                                        @checked((int) old('menu_is_active', (int) ($itemForm['is_active'] ?? true)) === 1)>
                                    <span class="form-check-label">{{ t('dashboard.Active', 'Active') }}</span>
                                </label>
                                <button type="submit" class="btn btn-success">
                                    {{ t('dashboard.Save_Menu', 'Save Menu') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                <details class="border rounded-xl p-3 bg-light"
                    @if ($errors->has('menu_name') || $errors->has('menu_location')) open @endif>
                    <summary class="cursor-pointer fw-semibold">
                        {{ t('dashboard.Create_New_Menu', 'Create New Menu') }}
                    </summary>
                    <form method="POST" action="{{ route('dashboard.menus.store') }}"
                        class="grid grid-cols-1 lg:grid-cols-3 gap-3 items-end pt-3">
                        @csrf
                        <div>
                            <label for="new_menu_name" class="form-label mb-1">{{ t('dashboard.New_Menu_Name', 'New Menu Name') }}</label>
                            <input id="new_menu_name" name="menu_name" type="text" class="form-control"
                                value="{{ old('menu_name') }}"
                                placeholder="{{ t('dashboard.Example_Main_Menu', 'Example: Main Menu') }}">
                        </div>
                        <div>
                            <label for="new_menu_location" class="form-label mb-1">{{ t('dashboard.Menu_Location', 'Menu Location') }}</label>
                            <select id="new_menu_location" name="menu_location" class="form-select">
                                @foreach ($menuLocations as $locationKey => $location)
                                    <option value="{{ $locationKey }}"
                                        @selected(old('menu_location', array_key_first($menuLocations)) === $locationKey)>
                                        {{ $location['label'] ?? $locationKey }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary w-full">
                                {{ t('dashboard.Create_Menu', 'Create Menu') }}
                            </button>
                        </div>
                    </form>
                </details>
            </div>
        </details>

        @if ($selectedMenu)
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">{{ t('dashboard.Menu_Library', 'Menu Library') }}</h5>
                            <p class="text-muted text-sm mb-0 mt-1">
                                {{ t('dashboard.Menu_Library_Description', 'Switch between menus or duplicate/delete current one.') }}
                            </p>
                        </div>
                        <div class="card-body space-y-3">
                            <form method="GET" action="{{ route('dashboard.menus') }}">
                                <label for="selected_menu" class="form-label mb-1">{{ t('dashboard.Select_Menu', 'Select Menu') }}</label>
                                <select id="selected_menu" name="menu" class="form-select">
                                    @foreach ($menus as $menu)
                                        <option value="{{ $menu->id }}" @selected((int) ($selectedMenu?->id ?? 0) === (int) $menu->id)>
                                            {{ $menu->name }}
                                            @if (! empty($menu->location_key))
                                                - {{ $menuLocations[$menu->location_key]['label'] ?? $menu->location_key }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </form>

                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('dashboard.menus.duplicate', $selectedMenu) }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-full">
                                        {{ t('dashboard.Duplicate', 'Duplicate') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('dashboard.menus.destroy', $selectedMenu) }}" class="w-full"
                                    data-confirm="{{ t('dashboard.Delete_Menu_Confirm', 'Delete this menu?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-full">
                                        {{ t('dashboard.Delete', 'Delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                {{ $isEditing ? t('dashboard.Edit_Menu_Item', 'Edit Menu Item') : t('dashboard.Add_Menu_Item', 'Add Menu Item') }}
                            </h5>
                            <p class="text-muted text-sm mb-0 mt-1">
                                {{ t('dashboard.Menu_Item_Form_Description', 'Fill one language, then switch tabs to complete other translations.') }}
                            </p>
                        </div>
                        <div class="card-body space-y-4">
                            <form id="menu-item-form"
                                action="{{ $isEditing ? route('dashboard.menus.items.update', [$selectedMenu, $editingItem]) : route('dashboard.menus.items.store', $selectedMenu) }}"
                                method="POST" class="space-y-4">
                                @csrf
                                @if ($isEditing)
                                    @method('PATCH')
                                @endif

                                <input type="hidden" name="active_lang" value="{{ $activeLang }}">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="form-label mb-1">{{ t('dashboard.Item_Type', 'Item Type') }}</label>
                                        <select id="item-type-select" name="type" class="form-select">
                                            <option value="link" @selected(($itemForm['type'] ?? 'link') === 'link')>
                                                {{ t('dashboard.Custom_Link', 'Custom Link') }}
                                            </option>
                                            <option value="page" @selected(($itemForm['type'] ?? 'link') === 'page')>
                                                {{ t('dashboard.Internal_Page', 'Internal Page') }}
                                            </option>
                                            <option value="dropdown" @selected(($itemForm['type'] ?? 'link') === 'dropdown')>
                                                {{ t('dashboard.Dropdown', 'Dropdown') }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label mb-1">{{ t('dashboard.Order', 'Order') }}</label>
                                        <input name="order" type="number" min="0" class="form-control"
                                            value="{{ (int) ($itemForm['order'] ?? 0) }}">
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($languages as $lang)
                                        @php
                                            $localeCode = strtolower((string) $lang->code);
                                        @endphp
                                        <button type="button" data-lang-tab data-locale="{{ $localeCode }}"
                                            class="{{ $activeLang === $localeCode ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary' }}">
                                            {{ $lang->name }}
                                        </button>
                                    @endforeach
                                </div>

                                <div id="item-page-panel" @class(['hidden' => ($itemForm['type'] ?? 'link') !== 'page'])>
                                    <label class="form-label mb-1">{{ t('dashboard.Select_Page', 'Select Page') }}</label>
                                    <select name="page_id" class="form-select" data-page-select>
                                        <option value="">{{ t('dashboard.Select_Page_Placeholder', 'Select page...') }}</option>
                                        @foreach ($pages as $page)
                                            @php
                                                $pageMeta = $pageLabelMap[(int) $page->id] ?? ['titles' => [], 'fallback' => 'Page #' . $page->id];
                                                $pageTitles = (array) ($pageMeta['titles'] ?? []);
                                                $pageFallbackTitle = (string) ($pageMeta['fallback'] ?? 'Page #' . $page->id);
                                                $pageCurrentTitle = (string) ($pageTitles[$activeLang] ?? $pageFallbackTitle);
                                            @endphp
                                            <option value="{{ $page->id }}"
                                                data-page-title-fallback="{{ $pageFallbackTitle }}"
                                                @foreach ($languages as $language)
                                                    @php
                                                        $optionLocale = strtolower((string) $language->code);
                                                        $optionTitle = (string) ($pageTitles[$optionLocale] ?? $pageFallbackTitle);
                                                    @endphp
                                                    data-page-title-{{ $optionLocale }}="{{ $optionTitle }}"
                                                @endforeach
                                                @selected((int) ($itemForm['page_id'] ?? 0) === (int) $page->id)>
                                                {{ $pageCurrentTitle }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="item-translations-panel" @class(['hidden' => ($itemForm['type'] ?? 'link') === 'page'])>
                                    @foreach ($languages as $lang)
                                        @php
                                            $localeCode = strtolower((string) $lang->code);
                                            $translation = $itemForm['translations'][$localeCode] ?? ['label' => '', 'url' => ''];
                                        @endphp
                                        <div class="menu-lang-panel space-y-3 {{ $activeLang === $localeCode ? '' : 'hidden' }}"
                                            data-lang-panel data-locale="{{ $localeCode }}">
                                            <div>
                                                <label class="form-label mb-1">
                                                    {{ t('dashboard.Label', 'Label') }} ({{ strtoupper($localeCode) }})
                                                </label>
                                                <input type="text" class="form-control"
                                                    name="translations[{{ $localeCode }}][label]"
                                                    value="{{ $translation['label'] ?? '' }}"
                                                    placeholder="{{ t('dashboard.Item_Label_Placeholder', 'Menu label') }}">
                                            </div>
                                            <div class="menu-url-wrapper">
                                                <label class="form-label mb-1">
                                                    {{ t('dashboard.URL', 'URL') }} ({{ strtoupper($localeCode) }})
                                                </label>
                                                <input type="text" class="form-control"
                                                    name="translations[{{ $localeCode }}][url]"
                                                    value="{{ $translation['url'] ?? '' }}"
                                                    placeholder="/services or https://example.com">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="item-dropdown-panel"
                                    class="border rounded-xl p-3 space-y-3 {{ ($itemForm['type'] ?? 'link') === 'dropdown' ? '' : 'hidden' }}">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h6 class="mb-0">{{ t('dashboard.Dropdown_Children', 'Dropdown Children') }}</h6>
                                        <div class="flex gap-2">
                                            <button type="button" data-add-dropdown-child data-child-type="link"
                                                class="btn btn-outline-primary btn-sm">
                                                {{ t('dashboard.Add_Custom_Link', 'Add Custom Link') }}
                                            </button>
                                            <button type="button" data-add-dropdown-child data-child-type="page"
                                                class="btn btn-outline-success btn-sm">
                                                {{ t('dashboard.Add_Page', 'Add Page') }}
                                            </button>
                                        </div>
                                    </div>

                                    @php
                                        $children = array_values((array) ($itemForm['children'] ?? []));
                                    @endphp

                                    <div id="menu-children-sortable" class="space-y-3"
                                        data-next-index="{{ count($children) }}">
                                        @forelse ($children as $childIndex => $child)
                                            @php
                                                $childType = in_array($child['type'] ?? 'link', ['link', 'page'], true)
                                                    ? (string) $child['type']
                                                    : 'link';
                                                $childPageId = (int) ($child['page_id'] ?? 0);
                                                $childLabels = is_array($child['labels'] ?? null) ? $child['labels'] : [];
                                            @endphp
                                            <div class="card card-body menu-child-card" data-index="{{ $childIndex }}">
                                                <div class="flex flex-col gap-3">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span class="cursor-grab text-muted" title="{{ t('dashboard.Drag_To_Reorder', 'Drag and drop to reorder items.') }}">
                                                                <i class="ti ti-grip-vertical"></i>
                                                            </span>
                                                            <select name="children[{{ $childIndex }}][type]"
                                                                class="form-select form-select-sm w-auto menu-child-type">
                                                                <option value="link" @selected($childType === 'link')>
                                                                    {{ t('dashboard.Custom_Link', 'Custom Link') }}
                                                                </option>
                                                                <option value="page" @selected($childType === 'page')>
                                                                    {{ t('dashboard.Internal_Page', 'Internal Page') }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="btn btn-link text-danger p-0 menu-remove-child">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="menu-child-page-panel {{ $childType === 'page' ? '' : 'hidden' }}">
                                                        <label class="form-label mb-1">{{ t('dashboard.Select_Page', 'Select Page') }}</label>
                                                        <select name="children[{{ $childIndex }}][page_id]" class="form-select" data-page-select>
                                                            <option value="">{{ t('dashboard.Select_Page_Placeholder', 'Select page...') }}</option>
                                                            @foreach ($pages as $page)
                                                                @php
                                                                    $pageMeta = $pageLabelMap[(int) $page->id] ?? ['titles' => [], 'fallback' => 'Page #' . $page->id];
                                                                    $pageTitles = (array) ($pageMeta['titles'] ?? []);
                                                                    $pageFallbackTitle = (string) ($pageMeta['fallback'] ?? 'Page #' . $page->id);
                                                                    $pageCurrentTitle = (string) ($pageTitles[$activeLang] ?? $pageFallbackTitle);
                                                                @endphp
                                                                <option value="{{ $page->id }}"
                                                                    data-page-title-fallback="{{ $pageFallbackTitle }}"
                                                                    @foreach ($languages as $language)
                                                                        @php
                                                                            $optionLocale = strtolower((string) $language->code);
                                                                            $optionTitle = (string) ($pageTitles[$optionLocale] ?? $pageFallbackTitle);
                                                                        @endphp
                                                                        data-page-title-{{ $optionLocale }}="{{ $optionTitle }}"
                                                                    @endforeach
                                                                    @selected($childPageId === (int) $page->id)>
                                                                    {{ $pageCurrentTitle }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="menu-child-link-panel {{ $childType === 'link' ? '' : 'hidden' }}">
                                                        @foreach ($languages as $lang)
                                                            @php
                                                                $localeCode = strtolower((string) $lang->code);
                                                                $childLocaleData = $childLabels[$localeCode] ?? ['label' => '', 'url' => ''];
                                                            @endphp
                                                            <div class="menu-lang-panel grid grid-cols-1 md:grid-cols-2 gap-3 {{ $activeLang === $localeCode ? '' : 'hidden' }}"
                                                                data-lang-panel data-locale="{{ $localeCode }}">
                                                                <div>
                                                                    <label class="form-label mb-1">
                                                                        {{ t('dashboard.Label', 'Label') }} ({{ strtoupper($localeCode) }})
                                                                    </label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $childIndex }}][labels][{{ $localeCode }}][label]"
                                                                        value="{{ $childLocaleData['label'] ?? '' }}"
                                                                        placeholder="{{ t('dashboard.Item_Label_Placeholder', 'Menu label') }}">
                                                                </div>
                                                                <div>
                                                                    <label class="form-label mb-1">
                                                                        {{ t('dashboard.URL', 'URL') }} ({{ strtoupper($localeCode) }})
                                                                    </label>
                                                                    <input type="text" class="form-control"
                                                                        name="children[{{ $childIndex }}][labels][{{ $localeCode }}][url]"
                                                                        value="{{ $childLocaleData['url'] ?? '' }}"
                                                                        placeholder="/service-child">
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center text-muted py-4 border rounded-xl menu-no-children">
                                                {{ t('dashboard.No_Dropdown_Children', 'No dropdown children yet.') }}
                                            </div>
                                        @endforelse
                                    </div>

                                    <template id="dropdown-child-template">
                                        <div class="card card-body menu-child-card" data-index="__INDEX__">
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="cursor-grab text-muted" title="{{ t('dashboard.Drag_To_Reorder', 'Drag and drop to reorder items.') }}">
                                                            <i class="ti ti-grip-vertical"></i>
                                                        </span>
                                                        <select name="children[__INDEX__][type]"
                                                            class="form-select form-select-sm w-auto menu-child-type">
                                                            <option value="link">{{ t('dashboard.Custom_Link', 'Custom Link') }}</option>
                                                            <option value="page">{{ t('dashboard.Internal_Page', 'Internal Page') }}</option>
                                                        </select>
                                                    </div>
                                                    <button type="button" class="btn btn-link text-danger p-0 menu-remove-child">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>

                                                <div class="menu-child-page-panel hidden">
                                                    <label class="form-label mb-1">{{ t('dashboard.Select_Page', 'Select Page') }}</label>
                                                    <select name="children[__INDEX__][page_id]" class="form-select" data-page-select>
                                                        <option value="">{{ t('dashboard.Select_Page_Placeholder', 'Select page...') }}</option>
                                                        @foreach ($pages as $page)
                                                            @php
                                                                $pageMeta = $pageLabelMap[(int) $page->id] ?? ['titles' => [], 'fallback' => 'Page #' . $page->id];
                                                                $pageTitles = (array) ($pageMeta['titles'] ?? []);
                                                                $pageFallbackTitle = (string) ($pageMeta['fallback'] ?? 'Page #' . $page->id);
                                                                $pageCurrentTitle = (string) ($pageTitles[$activeLang] ?? $pageFallbackTitle);
                                                            @endphp
                                                            <option value="{{ $page->id }}"
                                                                data-page-title-fallback="{{ $pageFallbackTitle }}"
                                                                @foreach ($languages as $language)
                                                                    @php
                                                                        $optionLocale = strtolower((string) $language->code);
                                                                        $optionTitle = (string) ($pageTitles[$optionLocale] ?? $pageFallbackTitle);
                                                                    @endphp
                                                                    data-page-title-{{ $optionLocale }}="{{ $optionTitle }}"
                                                                @endforeach>
                                                                {{ $pageCurrentTitle }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="menu-child-link-panel">
                                                    @foreach ($languages as $lang)
                                                        @php
                                                            $localeCode = strtolower((string) $lang->code);
                                                        @endphp
                                                        <div class="menu-lang-panel grid grid-cols-1 md:grid-cols-2 gap-3 {{ $activeLang === $localeCode ? '' : 'hidden' }}"
                                                            data-lang-panel data-locale="{{ $localeCode }}">
                                                            <div>
                                                                <label class="form-label mb-1">
                                                                    {{ t('dashboard.Label', 'Label') }} ({{ strtoupper($localeCode) }})
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                    name="children[__INDEX__][labels][{{ $localeCode }}][label]"
                                                                    placeholder="{{ t('dashboard.Item_Label_Placeholder', 'Menu label') }}">
                                                            </div>
                                                            <div>
                                                                <label class="form-label mb-1">
                                                                    {{ t('dashboard.URL', 'URL') }} ({{ strtoupper($localeCode) }})
                                                                </label>
                                                                <input type="text" class="form-control"
                                                                    name="children[__INDEX__][labels][{{ $localeCode }}][url]"
                                                                    placeholder="/service-child">
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="btn {{ $isEditing ? 'btn-success' : 'btn-primary' }} w-full">
                                        {{ $isEditing ? t('dashboard.Save_Changes', 'Save Changes') : t('dashboard.Add_Menu_Item', 'Add Menu Item') }}
                                    </button>
                                    @if ($isEditing)
                                        <a href="{{ route('dashboard.menus', ['menu' => $selectedMenu->id]) }}"
                                            class="btn btn-outline-secondary w-full">
                                            {{ t('dashboard.Cancel', 'Cancel') }}
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ t('dashboard.Current_Menu_Items', 'Current Menu Items') }}</h5>
                            <span class="badge bg-primary">{{ $selectedMenu->items->count() }}</span>
                        </div>
                        <div class="card-body">
                            @if ($selectedMenu->items->count() > 0)
                                <div class="text-muted text-sm mb-3">
                                    {{ t('dashboard.Drag_To_Reorder', 'Drag and drop to reorder items.') }}
                                </div>

                                <div id="menu-items-sortable" class="space-y-3"
                                    data-reorder-url="{{ route('dashboard.menus.items.reorder', $selectedMenu) }}">
                                    @foreach ($selectedMenu->items as $item)
                                        @php
                                            $itemTranslation = $item->translations->firstWhere('locale', $activeLang)
                                                ?? $item->translations->first();
                                            $pageTranslation = $item->page?->translation($activeLang) ?? $item->page?->translation();
                                            $itemTitle =
                                                $item->type === 'page'
                                                    ? ($pageTranslation?->title ?? t('dashboard.Untitled', 'Untitled'))
                                                    : ($itemTranslation?->label ?? t('dashboard.Untitled', 'Untitled'));
                                        @endphp
                                        <div class="card border border-gray-200 menu-item-card transition-all hover:shadow-sm" data-id="{{ $item->id }}">
                                            <div class="card-body p-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-start gap-3 flex-grow-1">
                                                        <span class="cursor-grab text-muted mt-1" title="{{ t('dashboard.Drag_To_Reorder', 'Drag and drop to reorder items.') }}">
                                                            <i class="ti ti-grip-vertical"></i>
                                                        </span>
                                                        <div>
                                                            <h6 class="mb-1 fw-semibold">{{ $itemTitle }}</h6>
                                                            <div class="text-muted text-sm">
                                                                @if ($item->type === 'link')
                                                                    <span class="badge bg-light-primary text-primary me-1">{{ t('dashboard.Custom_Link', 'Custom Link') }}</span>
                                                                    {{ $itemTranslation?->url ?: '#' }}
                                                                @elseif ($item->type === 'page')
                                                                    <span class="badge bg-light-success text-success">{{ t('dashboard.Internal_Page', 'Internal Page') }}</span>
                                                                @else
                                                                    <span class="badge bg-light-warning text-warning">{{ t('dashboard.Dropdown', 'Dropdown') }}</span>
                                                                    <span class="ms-1">{{ count((array) ($item->children ?? [])) }} {{ t('dashboard.Children', 'children') }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-1">
                                                        <a href="{{ route('dashboard.menus', ['menu' => $selectedMenu->id, 'edit_item' => $item->id]) }}"
                                                            class="btn btn-outline-warning btn-sm">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                        <form method="POST"
                                                            action="{{ route('dashboard.menus.items.destroy', [$selectedMenu, $item]) }}"
                                                            data-confirm="{{ t('dashboard.Delete_Item_Confirm', 'Delete this item?') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-5">
                                    {{ t('dashboard.No_Menu_Items', 'No menu items yet.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            (function() {
                'use strict';

                const selectedMenu = document.getElementById('selected_menu');
                if (selectedMenu) {
                    selectedMenu.addEventListener('change', () => {
                        selectedMenu.form.submit();
                    });
                }

                document.querySelectorAll('form[data-confirm]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        const message = form.getAttribute('data-confirm');
                        if (message && !window.confirm(message)) {
                            event.preventDefault();
                        }
                    });
                });

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const itemsEl = document.getElementById('menu-items-sortable');

                const bindItemsSortable = () => {
                    if (!itemsEl || typeof Sortable === 'undefined' || itemsEl.dataset.sortableReady === '1') {
                        return;
                    }

                    new Sortable(itemsEl, {
                        animation: 180,
                        handle: '.cursor-grab',
                        onEnd: () => {
                            const ids = Array.from(itemsEl.querySelectorAll('.menu-item-card'))
                                .map((card) => Number(card.dataset.id))
                                .filter((id) => Number.isInteger(id) && id > 0);

                            if (!ids.length || !csrfToken) {
                                return;
                            }

                            fetch(itemsEl.dataset.reorderUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({ ids }),
                                })
                                .then((response) => {
                                    if (!response.ok) {
                                        throw new Error('reorder_failed');
                                    }
                                })
                                .catch(() => {
                                    window.location.reload();
                                });
                        },
                    });
                    itemsEl.dataset.sortableReady = '1';
                };

                const menuItemForm = document.getElementById('menu-item-form');
                if (!menuItemForm) {
                    return;
                }

                const activeLangInput = menuItemForm.querySelector('input[name="active_lang"]');
                const langButtons = menuItemForm.querySelectorAll('[data-lang-tab]');
                const itemTypeSelect = menuItemForm.querySelector('#item-type-select');
                const pagePanel = menuItemForm.querySelector('#item-page-panel');
                const translationsPanel = menuItemForm.querySelector('#item-translations-panel');
                const dropdownPanel = menuItemForm.querySelector('#item-dropdown-panel');
                const childrenContainer = menuItemForm.querySelector('#menu-children-sortable');
                const childTemplate = document.getElementById('dropdown-child-template');

                const updatePageOptionLabels = (locale) => {
                    if (!locale) {
                        return;
                    }

                    menuItemForm.querySelectorAll('select[data-page-select]').forEach((select) => {
                        Array.from(select.options).forEach((option) => {
                            if (!option.value) {
                                return;
                            }

                            const localizedTitle = option.getAttribute(`data-page-title-${locale}`)
                                || option.getAttribute('data-page-title-fallback')
                                || option.textContent;
                            option.textContent = localizedTitle;
                        });
                    });
                };

                const setActiveLang = (locale) => {
                    if (!locale) {
                        return;
                    }

                    if (activeLangInput) {
                        activeLangInput.value = locale;
                    }

                    menuItemForm.querySelectorAll('[data-lang-tab]').forEach((button) => {
                        const isActive = button.dataset.locale === locale;
                        button.classList.toggle('btn-primary', isActive);
                        button.classList.toggle('btn-outline-secondary', !isActive);
                    });

                    menuItemForm.querySelectorAll('.menu-lang-panel[data-locale]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.locale !== locale);
                    });

                    updatePageOptionLabels(locale);
                };

                const syncItemType = () => {
                    const type = itemTypeSelect ? itemTypeSelect.value : 'link';

                    pagePanel?.classList.toggle('hidden', type !== 'page');
                    translationsPanel?.classList.toggle('hidden', type === 'page');
                    dropdownPanel?.classList.toggle('hidden', type !== 'dropdown');

                    menuItemForm.querySelectorAll('.menu-url-wrapper').forEach((wrapper) => {
                        wrapper.classList.toggle('hidden', type !== 'link');
                    });
                };

                const syncChildCardType = (card) => {
                    if (!card) {
                        return;
                    }

                    const typeSelect = card.querySelector('.menu-child-type');
                    if (!typeSelect) {
                        return;
                    }

                    const isPage = typeSelect.value === 'page';
                    card.querySelector('.menu-child-page-panel')?.classList.toggle('hidden', !isPage);
                    card.querySelector('.menu-child-link-panel')?.classList.toggle('hidden', isPage);
                };

                const reindexChildren = () => {
                    if (!childrenContainer) {
                        return;
                    }

                    const childCards = Array.from(childrenContainer.querySelectorAll('.menu-child-card'));
                    childCards.forEach((card, index) => {
                        card.dataset.index = String(index);
                        card.querySelectorAll('[name]').forEach((input) => {
                            input.name = input.name.replace(/children\[\d+\]/g, `children[${index}]`);
                        });
                    });

                    const emptyState = childrenContainer.querySelector('.menu-no-children');
                    if (emptyState) {
                        emptyState.classList.toggle('hidden', childCards.length > 0);
                    }

                    childrenContainer.dataset.nextIndex = String(childCards.length);
                };

                const addChild = (type) => {
                    if (!childrenContainer || !childTemplate) {
                        return;
                    }

                    const nextIndex = Number(childrenContainer.dataset.nextIndex || childrenContainer.querySelectorAll('.menu-child-card').length || 0);
                    const html = childTemplate.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                    childrenContainer.insertAdjacentHTML('beforeend', html);

                    const addedCard = childrenContainer.querySelector('.menu-child-card:last-of-type');
                    if (!addedCard) {
                        return;
                    }

                    const typeSelect = addedCard.querySelector('.menu-child-type');
                    if (typeSelect) {
                        typeSelect.value = type === 'page' ? 'page' : 'link';
                    }

                    syncChildCardType(addedCard);
                    reindexChildren();
                    setActiveLang(activeLangInput?.value || '');
                };

                langButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        setActiveLang(button.dataset.locale || '');
                    });
                });

                itemTypeSelect?.addEventListener('change', syncItemType);
                syncItemType();

                menuItemForm.addEventListener('click', (event) => {
                    const addButton = event.target.closest('[data-add-dropdown-child]');
                    if (addButton) {
                        event.preventDefault();
                        addChild(addButton.dataset.childType || 'link');
                        return;
                    }

                    const removeButton = event.target.closest('.menu-remove-child');
                    if (removeButton) {
                        event.preventDefault();
                        removeButton.closest('.menu-child-card')?.remove();
                        reindexChildren();
                    }
                });

                menuItemForm.addEventListener('change', (event) => {
                    const childType = event.target.closest('.menu-child-type');
                    if (childType) {
                        syncChildCardType(childType.closest('.menu-child-card'));
                    }
                });

                const bindChildrenSortable = () => {
                    if (!childrenContainer || typeof Sortable === 'undefined' || childrenContainer.dataset.sortableReady === '1') {
                        return;
                    }

                    new Sortable(childrenContainer, {
                        animation: 150,
                        handle: '.cursor-grab',
                        draggable: '.menu-child-card',
                        onEnd: reindexChildren,
                    });
                    childrenContainer.dataset.sortableReady = '1';
                };

                menuItemForm.addEventListener('submit', reindexChildren);

                childrenContainer?.querySelectorAll('.menu-child-card').forEach((card) => {
                    syncChildCardType(card);
                });

                bindItemsSortable();
                bindChildrenSortable();
                window.addEventListener('load', () => {
                    bindItemsSortable();
                    bindChildrenSortable();
                }, { once: true });

                reindexChildren();
                setActiveLang(activeLangInput?.value || langButtons[0]?.dataset.locale || '');
            })();
        </script>
    @endpush
</x-dashboard-layout>
