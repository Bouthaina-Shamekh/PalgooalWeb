<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Header;
use App\Models\HeaderItem;
use App\Models\HeaderItemTranslation;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $languages = $this->loadLanguages();
        $pages = $this->loadPages();
        $menuLocations = $this->menuLocations();

        $menus = Header::query()->orderBy('name')->get();

        if ($menus->isEmpty()) {
            Header::query()->create([
                'name' => 'Main Menu',
                'slug' => 'main-menu',
                'location_key' => 'header_primary',
                'is_active' => true,
            ]);

            $menus = Header::query()->orderBy('name')->get();
        }

        $requestedMenuId = (int) $request->query('menu', 0);
        $selectedMenu = $menus->firstWhere('id', $requestedMenuId)
            ?? $menus->firstWhere('location_key', 'header_primary')
            ?? $menus->first();

        $selectedMenu = Header::query()
            ->with(['items.translations', 'items.page.translations'])
            ->find($selectedMenu?->id);

        $editItemId = (int) $request->query('edit_item', 0);
        $editingItem = null;
        if ($selectedMenu && $editItemId > 0) {
            $editingItem = $selectedMenu->items()
                ->with('translations')
                ->find($editItemId);
        }

        $itemForm = $this->buildItemFormState($selectedMenu, $editingItem, $languages, $pages, $request);

        return view('dashboard.header', [
            'languages' => $languages,
            'pages' => $pages,
            'menuLocations' => $menuLocations,
            'menus' => $menus,
            'selectedMenu' => $selectedMenu,
            'editingItem' => $editingItem,
            'itemForm' => $itemForm,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $locations = $this->menuLocations();

        $validated = $request->validate([
            'menu_name' => ['required', 'string', 'max:120'],
            'menu_location' => ['required', Rule::in(array_keys($locations))],
        ]);

        $name = trim((string) $validated['menu_name']);
        $slug = $this->makeUniqueSlug($name);

        $menu = Header::query()->create([
            'name' => $name,
            'slug' => $slug,
            'location_key' => (string) $validated['menu_location'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('dashboard.menus', ['menu' => $menu->id])
            ->with('success', t('dashboard.Menu_Created_Success', 'Menu created successfully.'));
    }

    public function update(Request $request, Header $menu): RedirectResponse
    {
        $locations = $this->menuLocations();

        $validated = $request->validate([
            'menu_title' => ['required', 'string', 'max:120'],
            'menu_slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('headers', 'slug')->ignore($menu->id),
            ],
            'menu_location' => ['required', Rule::in(array_keys($locations))],
            'menu_is_active' => ['nullable', 'boolean'],
        ]);

        $menu->update([
            'name' => trim((string) $validated['menu_title']),
            'slug' => strtolower(trim((string) $validated['menu_slug'])),
            'location_key' => (string) $validated['menu_location'],
            'is_active' => $request->boolean('menu_is_active'),
        ]);

        return redirect()
            ->route('dashboard.menus', ['menu' => $menu->id])
            ->with('success', t('dashboard.Menu_Updated_Success', 'Menu settings updated successfully.'));
    }

    public function destroy(Header $menu): RedirectResponse
    {
        $allMenus = Header::query()->orderBy('id')->get();
        if ($allMenus->count() <= 1) {
            return redirect()
                ->route('dashboard.menus', ['menu' => $menu->id])
                ->withErrors([
                    'menu_delete' => t('dashboard.Menu_Delete_Last_Forbidden', 'At least one menu must remain.'),
                ]);
        }

        $menuId = $menu->id;
        $menu->delete();

        $fallback = Header::query()->where('id', '!=', $menuId)->orderBy('id')->first();

        return redirect()
            ->route('dashboard.menus', ['menu' => $fallback?->id])
            ->with('success', t('dashboard.Menu_Deleted_Success', 'Menu deleted successfully.'));
    }

    public function duplicate(Header $menu): RedirectResponse
    {
        $menu->loadMissing(['items.translations']);

        $copiedMenu = DB::transaction(function () use ($menu) {
            $copyName = trim((string) $menu->name) . ' (Copy)';
            $copySlug = $this->makeUniqueSlug($copyName);

            $newMenu = Header::query()->create([
                'name' => $copyName,
                'slug' => $copySlug,
                'location_key' => $menu->location_key,
                'is_active' => $menu->is_active,
            ]);

            foreach ($menu->items as $item) {
                $newItem = $newMenu->items()->create([
                    'type' => $item->type,
                    'page_id' => $item->page_id,
                    'children' => $item->children,
                    'order' => $item->order,
                ]);

                foreach ($item->translations as $translation) {
                    $newItem->translations()->create([
                        'locale' => strtolower((string) $translation->locale),
                        'label' => $translation->label,
                        'url' => $translation->url,
                    ]);
                }
            }

            return $newMenu;
        });

        return redirect()
            ->route('dashboard.menus', ['menu' => $copiedMenu->id])
            ->with('success', t('dashboard.Menu_Duplicated_Success', 'Menu duplicated successfully.'));
    }

    public function storeItem(Request $request, Header $menu): RedirectResponse
    {
        $languages = $this->loadLanguages();
        $pages = $this->loadPages();
        $payload = $this->validateAndNormalizeItemPayload($request, $languages, $pages);

        $item = $menu->items()->create([
            'type' => $payload['type'],
            'page_id' => $payload['type'] === 'page' ? $payload['page_id'] : null,
            'order' => $payload['order'] ?? $this->nextOrder($menu),
            'children' => $payload['type'] === 'dropdown' ? $payload['children'] : null,
        ]);

        if ($payload['type'] === 'page' && $payload['page_id']) {
            $page = $pages->firstWhere('id', $payload['page_id']) ?? Page::with('translations')->find($payload['page_id']);
            if ($page) {
                $this->syncPageTranslations($item, $page, $languages);
            }
        } else {
            $this->syncManualTranslations($item, $payload['translations']);
        }

        return redirect()
            ->route('dashboard.menus', ['menu' => $menu->id])
            ->with('success', t('dashboard.Menu_Item_Created_Success', 'Menu item added successfully.'));
    }

    public function updateItem(Request $request, Header $menu, HeaderItem $item): RedirectResponse
    {
        if ((int) $item->header_id !== (int) $menu->id) {
            abort(404);
        }

        $languages = $this->loadLanguages();
        $pages = $this->loadPages();
        $payload = $this->validateAndNormalizeItemPayload($request, $languages, $pages);

        $item->update([
            'type' => $payload['type'],
            'page_id' => $payload['type'] === 'page' ? $payload['page_id'] : null,
            'order' => $payload['order'] ?? $item->order,
            'children' => $payload['type'] === 'dropdown' ? $payload['children'] : null,
        ]);

        if ($payload['type'] === 'page' && $payload['page_id']) {
            $page = $pages->firstWhere('id', $payload['page_id']) ?? Page::with('translations')->find($payload['page_id']);
            if ($page) {
                $this->syncPageTranslations($item, $page, $languages);
            }
        } else {
            $this->syncManualTranslations($item, $payload['translations']);
        }

        return redirect()
            ->route('dashboard.menus', ['menu' => $menu->id])
            ->with('success', t('dashboard.Menu_Item_Updated_Success', 'Menu item updated successfully.'));
    }

    public function destroyItem(Header $menu, HeaderItem $item): RedirectResponse
    {
        if ((int) $item->header_id !== (int) $menu->id) {
            abort(404);
        }

        $item->delete();

        return redirect()
            ->route('dashboard.menus', ['menu' => $menu->id])
            ->with('success', t('dashboard.Menu_Item_Deleted_Success', 'Menu item deleted successfully.'));
    }

    public function reorderItems(Request $request, Header $menu): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $orderedIds = collect($validated['ids'])->map(fn ($id) => (int) $id)->values();
        $allowedIds = $menu->items()
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($orderedIds->diff($allowedIds)->isNotEmpty()) {
            return response()->json([
                'message' => t('dashboard.Invalid_Reorder_Payload', 'Invalid reorder payload.'),
            ], 422);
        }

        DB::transaction(function () use ($menu, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                $menu->items()->where('id', $id)->update(['order' => $index]);
            }
        });

        return response()->json(['ok' => true]);
    }

    protected function loadLanguages(): Collection
    {
        $active = Language::query()->where('is_active', true)->orderBy('name')->get();
        if ($active->isNotEmpty()) {
            return $active;
        }

        return Language::query()->orderBy('name')->get();
    }

    protected function loadPages(): Collection
    {
        return Page::query()
            ->with('translations')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('context', 'marketing')
                    ->orWhereNull('context');
            })
            ->orderBy('id')
            ->get();
    }

    protected function menuLocations(): array
    {
        $configured = config('front_layouts.menu_locations', []);
        if (! is_array($configured) || $configured === []) {
            return [
                'header_primary' => ['label' => 'Header Primary', 'scope' => 'header'],
                'footer_primary' => ['label' => 'Footer Primary', 'scope' => 'footer'],
            ];
        }

        $locations = [];
        foreach ($configured as $key => $meta) {
            $locationKey = strtolower(trim((string) $key));
            if ($locationKey === '') {
                continue;
            }

            $label = is_array($meta) ? trim((string) ($meta['label'] ?? '')) : trim((string) $meta);
            if ($label === '') {
                $label = ucwords(str_replace(['_', '-'], ' ', $locationKey));
            }

            $scope = is_array($meta) ? strtolower(trim((string) ($meta['scope'] ?? ''))) : '';
            if (! in_array($scope, ['header', 'footer'], true)) {
                $scope = str_starts_with($locationKey, 'footer_') ? 'footer' : 'header';
            }

            $locations[$locationKey] = [
                'label' => $label,
                'scope' => $scope,
            ];
        }

        return $locations !== [] ? $locations : [
            'header_primary' => ['label' => 'Header Primary', 'scope' => 'header'],
            'footer_primary' => ['label' => 'Footer Primary', 'scope' => 'footer'],
        ];
    }

    protected function buildItemFormState(
        ?Header $selectedMenu,
        ?HeaderItem $editingItem,
        Collection $languages,
        Collection $pages,
        Request $request,
    ): array {
        $defaultType = $editingItem?->type ?? 'link';
        $defaultOrder = $editingItem?->order ?? $this->nextOrder($selectedMenu);
        $defaultPageId = $editingItem?->page_id;

        $translations = $this->blankTranslations($languages);
        if ($editingItem) {
            foreach ($editingItem->translations as $translation) {
                $locale = strtolower((string) $translation->locale);
                if (! array_key_exists($locale, $translations)) {
                    continue;
                }

                $translations[$locale] = [
                    'label' => (string) $translation->label,
                    'url' => (string) ($translation->url ?? ''),
                ];
            }
        }

        $children = [];
        if ($editingItem && $editingItem->type === 'dropdown') {
            foreach ((array) ($editingItem->children ?? []) as $child) {
                $childType = in_array(($child['type'] ?? 'link'), ['link', 'page'], true)
                    ? (string) $child['type']
                    : 'link';

                if ($childType === 'page') {
                    $pageId = (int) ($child['page_id'] ?? 0);
                    $page = $pages->firstWhere('id', $pageId);
                    $labels = $page ? $this->buildLabelsFromPage($page, $languages) : $this->blankTranslations($languages);

                    $children[] = [
                        'type' => 'page',
                        'page_id' => $pageId > 0 ? $pageId : null,
                        'labels' => $labels,
                    ];
                } else {
                    $children[] = [
                        'type' => 'link',
                        'page_id' => null,
                        'labels' => $this->normalizeTranslations((array) ($child['labels'] ?? []), $languages),
                    ];
                }
            }
        }

        return [
            'type' => (string) old('type', $defaultType),
            'order' => (int) old('order', $defaultOrder),
            'page_id' => old('page_id', $defaultPageId),
            'translations' => old('translations', $translations),
            'children' => old('children', $children),
            'active_lang' => strtolower((string) old('active_lang', app()->getLocale())),
            'menu_id' => (int) old('menu_id', $selectedMenu?->id),
            'title' => (string) old('menu_title', $selectedMenu?->name ?? ''),
            'slug' => (string) old('menu_slug', $selectedMenu?->slug ?? ''),
            'location' => (string) old('menu_location', $selectedMenu?->location_key ?? 'header_primary'),
            'is_active' => (bool) old('menu_is_active', $selectedMenu?->is_active ?? true),
        ];
    }

    protected function blankTranslations(Collection $languages): array
    {
        $translations = [];

        foreach ($languages as $language) {
            $code = strtolower((string) ($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            $translations[$code] = [
                'label' => '',
                'url' => '',
            ];
        }

        if ($translations === []) {
            $translations[strtolower((string) config('app.locale', 'en'))] = [
                'label' => '',
                'url' => '',
            ];
        }

        return $translations;
    }

    protected function normalizeTranslations(array $translations, Collection $languages): array
    {
        $normalized = $this->blankTranslations($languages);

        foreach ($normalized as $locale => $value) {
            $source = is_array($translations[$locale] ?? null) ? $translations[$locale] : [];
            $normalized[$locale] = [
                'label' => trim((string) ($source['label'] ?? '')),
                'url' => trim((string) ($source['url'] ?? '')),
            ];
        }

        return $normalized;
    }

    protected function buildLabelsFromPage(Page $page, Collection $languages): array
    {
        $labels = $this->blankTranslations($languages);

        foreach ($labels as $locale => $value) {
            $translation = $page->translation($locale) ?? $page->translation();
            $slug = trim((string) ($translation?->slug ?? ''));

            $labels[$locale] = [
                'label' => trim((string) ($translation?->title ?? '')),
                'url' => $slug !== '' ? '/' . ltrim($slug, '/') : '#',
            ];
        }

        return $labels;
    }

    protected function hasAnyFilled(array $translations, string $field): bool
    {
        foreach ($translations as $translation) {
            if (trim((string) ($translation[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function validateAndNormalizeItemPayload(Request $request, Collection $languages, Collection $pages): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['link', 'page', 'dropdown'])],
            'order' => ['nullable', 'integer', 'min:0'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'translations' => ['nullable', 'array'],
            'children' => ['nullable', 'array'],
        ]);

        $type = (string) $validated['type'];
        $order = isset($validated['order']) ? (int) $validated['order'] : null;

        if ($type === 'page') {
            $request->validate([
                'page_id' => ['required', 'integer', 'exists:pages,id'],
            ]);

            return [
                'type' => 'page',
                'order' => $order,
                'page_id' => (int) $request->input('page_id'),
                'translations' => $this->blankTranslations($languages),
                'children' => [],
            ];
        }

        $translations = $this->normalizeTranslations((array) ($validated['translations'] ?? []), $languages);
        if (! $this->hasAnyFilled($translations, 'label')) {
            throw ValidationException::withMessages([
                'translations' => t('dashboard.Menu_Item_Label_Required', 'Enter at least one translated label.'),
            ]);
        }

        if ($type === 'link' && ! $this->hasAnyFilled($translations, 'url')) {
            throw ValidationException::withMessages([
                'translations' => t('dashboard.Menu_Item_URL_Required', 'Enter at least one URL for this link item.'),
            ]);
        }

        $children = [];
        if ($type === 'dropdown') {
            $childrenInput = array_values((array) ($validated['children'] ?? []));
            if ($childrenInput === []) {
                throw ValidationException::withMessages([
                    'children' => t('dashboard.Dropdown_Requires_Children', 'Dropdown items must include at least one child link/page.'),
                ]);
            }

            foreach ($childrenInput as $index => $child) {
                $childType = in_array(($child['type'] ?? 'link'), ['link', 'page'], true)
                    ? (string) $child['type']
                    : 'link';

                if ($childType === 'page') {
                    $pageId = (int) ($child['page_id'] ?? 0);
                    $page = $pages->firstWhere('id', $pageId) ?? Page::with('translations')->find($pageId);
                    if (! $page) {
                        throw ValidationException::withMessages([
                            "children.$index.page_id" => t('dashboard.Dropdown_Child_Page_Required', 'Select a valid page for the dropdown child item.'),
                        ]);
                    }

                    $children[] = [
                        'type' => 'page',
                        'page_id' => $pageId,
                        'labels' => $this->buildLabelsFromPage($page, $languages),
                    ];
                    continue;
                }

                $labels = $this->normalizeTranslations((array) ($child['labels'] ?? []), $languages);
                if (! $this->hasAnyFilled($labels, 'label') || ! $this->hasAnyFilled($labels, 'url')) {
                    throw ValidationException::withMessages([
                        "children.$index.labels" => t('dashboard.Dropdown_Child_Label_And_URL_Required', 'Each custom child link needs label and URL in at least one language.'),
                    ]);
                }

                $children[] = [
                    'type' => 'link',
                    'page_id' => null,
                    'labels' => $labels,
                ];
            }
        }

        return [
            'type' => $type,
            'order' => $order,
            'page_id' => null,
            'translations' => $translations,
            'children' => $children,
        ];
    }

    protected function syncPageTranslations(HeaderItem $item, Page $page, Collection $languages): void
    {
        foreach ($languages as $language) {
            $locale = strtolower((string) ($language->code ?? ''));
            if ($locale === '') {
                continue;
            }

            $translation = $page->translation($locale) ?? $page->translation();
            $slug = trim((string) ($translation?->slug ?? ''));

            HeaderItemTranslation::updateOrCreate(
                ['header_item_id' => $item->id, 'locale' => $locale],
                [
                    'label' => trim((string) ($translation?->title ?? '')),
                    'url' => $slug !== '' ? '/' . ltrim($slug, '/') : '',
                ]
            );
        }
    }

    protected function syncManualTranslations(HeaderItem $item, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            HeaderItemTranslation::updateOrCreate(
                ['header_item_id' => $item->id, 'locale' => $locale],
                [
                    'label' => trim((string) ($translation['label'] ?? '')),
                    'url' => trim((string) ($translation['url'] ?? '')),
                ]
            );
        }
    }

    protected function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'menu';
        }

        $candidate = $base;
        $suffix = 2;

        while (
            Header::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function nextOrder(?Header $menu): int
    {
        if (! $menu) {
            return 0;
        }

        return (int) ($menu->items()->max('order') ?? -1) + 1;
    }
}

