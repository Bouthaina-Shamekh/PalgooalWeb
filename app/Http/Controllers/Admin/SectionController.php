<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    /**
     * List all sections for a given marketing page.
     */
    public function index(Page $page)
    {
        $page->loadMissing('translations');

        $sections = Section::with('translations')
            ->where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('dashboard.pages.sections.index', [
            'page'         => $page,
            'sections'     => $sections,
            'languages'    => $languages,
            'sectionTypes' => $this->availableSectionTypes(),
        ]);
    }

    /**
     * Render a front-like preview for the sections workspace iframe.
     */
    public function preview(Request $request, Page $page)
    {
        $page->loadMissing('translations');

        $sections = Section::with('translations')
            ->where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $previewTemplates = Template::query()
            ->with([
                'translations',
                'categoryTemplate.translation',
                'categoryTemplate.translations',
            ])
            ->latest('id')
            ->limit(8)
            ->get();

        return view('dashboard.pages.sections.preview', [
            'page' => $page,
            'sections' => $sections,
            'sectionTypes' => $this->availableSectionTypes(),
            'previewTemplates' => $previewTemplates,
            'highlightSectionId' => $request->integer('highlight'),
        ]);
    }

    /**
     * Show the full create form for a specific page.
     */
    public function create(Page $page)
    {
        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        $page->loadMissing('translations');

        return view('dashboard.pages.sections.create', [
            'page'         => $page,
            'languages'    => $languages,
            'sectionTypes' => $this->availableSectionTypes(),
            'nextOrder'    => $this->nextOrderForPage($page),
        ]);
    }

    /**
     * Store a newly created section and its translations.
     */
    public function store(Request $request, Page $page)
    {
        $validated = $request->validate([
            'type'      => ['required', 'string', 'max:100', Rule::in($this->availableSectionTypeKeys())],
            'variant'   => 'nullable|string|max:100',
            'style'     => 'nullable|array',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        $validated['translations'] = $this->syncSharedSectionContent(
            $validated['type'],
            $validated['translations'] ?? []
        );

        DB::transaction(function () use ($validated, $page) {
            $type = $validated['type'];

            $section = Section::create([
                'page_id'   => $page->id,
                'type'      => $type,
                'variant'   => $validated['variant'] ?? null,
                'style'     => $validated['style'] ?? $this->defaultStyleForType($type),
                'order'     => $validated['order'] ?? $this->nextOrderForPage($page),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            foreach ($validated['translations'] as $translationData) {
                $content = $this->normalizeContentByType($type, $translationData['content'] ?? []);

                SectionTranslation::create([
                    'section_id' => $section->id,
                    'locale'     => $translationData['locale'],
                    'title'      => $translationData['title'] ?? null,
                    'content'    => $content,
                ]);
            }
        });

        $this->normalizePageSectionOrders($page);

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('success', 'Section has been created successfully.');
    }

    /**
     * Quick-add a section from the workspace library, then open its editor.
     */
    public function quickStore(Request $request, Page $page)
    {
        $validated = $request->validate([
            'type'    => ['required', 'string', 'max:100', Rule::in($this->availableSectionTypeKeys())],
            'variant' => 'nullable|string|max:100',
        ]);

        $page->loadMissing('translations');
        $createdSection = null;

        DB::transaction(function () use ($page, $validated, &$createdSection) {
            $createdSection = $this->createDefaultSection(
                $page,
                $validated['type'],
                $validated['variant'] ?? null
            );
        });

        $redirectUrl = route('dashboard.pages.sections.index', [
            'page' => $page,
            'highlight' => $createdSection->id,
            'edit' => $createdSection->id,
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'redirect_url' => $redirectUrl,
                'section_id' => $createdSection->id,
                'message' => 'Section added. Continue customizing it in the editor.',
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Section added. Continue customizing it in the editor.');
    }

    /**
     * Show the edit form for a specific page section.
     */
    public function edit(Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        return view('dashboard.pages.sections.edit', $this->sectionEditorViewData($page, $section));
    }

    /**
     * Render the inline workspace editor panel for a specific section.
     */
    public function editor(Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        return view('dashboard.pages.sections.partials.sidebar-editor', $this->sectionEditorViewData($page, $section));
    }

    /**
     * Update an existing section and its translations.
     */
    public function update(Request $request, Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        $validated = $request->validate([
            'type'      => ['required', 'string', 'max:100', Rule::in($this->availableSectionTypeKeys())],
            'variant'   => 'nullable|string|max:100',
            'style'     => 'nullable|array',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        $validated['translations'] = $this->syncSharedSectionContent(
            $validated['type'],
            $validated['translations'] ?? []
        );

        DB::transaction(function () use ($validated, $section) {
            $type = $validated['type'];

            $section->update([
                'type'      => $type,
                'variant'   => $validated['variant'] ?? null,
                'style'     => $validated['style'] ?? $section->style,
                'order'     => $validated['order'] ?? $section->order,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $locales = [];

            foreach ($validated['translations'] as $locale => $translationData) {
                $locales[] = $locale;

                $translation = SectionTranslation::firstOrNew([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                ]);

                $translation->title = $translationData['title'] ?? null;
                $translation->content = $this->normalizeContentByType(
                    $type,
                    $translationData['content'] ?? []
                );
                $translation->save();
            }

            if (! empty($locales)) {
                SectionTranslation::where('section_id', $section->id)
                    ->whereNotIn('locale', $locales)
                    ->delete();
            }
        });

        $this->normalizePageSectionOrders($page);

        $section->refresh()->load('translations');

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            $typeLabel = $this->availableSectionTypes()[$section->type]['label']
                ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $section->type));

            $translation = $section->translation(app()->getLocale()) ?? $section->translations->first();

            return response()->json([
                'ok' => true,
                'message' => 'Section has been updated successfully.',
                'section' => [
                    'id' => $section->id,
                    'title' => $translation?->title ?: $typeLabel,
                    'type_label' => $typeLabel,
                    'is_active' => (bool) $section->is_active,
                ],
            ]);
        }

        return redirect()
            ->route('dashboard.pages.sections.index', [
                'page' => $page,
                'highlight' => $section->id,
            ])
            ->with('success', 'Section has been updated successfully.');
    }

    /**
     * Toggle section visibility without opening the editor.
     */
    public function toggleActive(Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        $section->update([
            'is_active' => ! $section->is_active,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Section visibility has been updated.');
    }

    /**
     * Quickly rename a section in a specific locale from the workspace.
     */
    public function rename(Request $request, Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $translation = SectionTranslation::firstOrNew([
            'section_id' => $section->id,
            'locale' => $validated['locale'],
        ]);

        $translation->title = trim((string) $validated['title']);

        if (! is_array($translation->content)) {
            $translation->content = [];
        }

        $translation->save();

        return redirect()
            ->route('dashboard.pages.sections.index', ['page' => $page, 'highlight' => $section->id])
            ->with('success', 'Section title has been updated.');
    }

    /**
     * Move a section up or down in the current page outline.
     */
    public function move(Request $request, Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $this->normalizePageSectionOrders($page);
        $section->refresh();

        $currentOrder = (int) ($section->order ?? 0);

        $targetQuery = Section::where('page_id', $page->id)
            ->where('id', '!=', $section->id);

        $target = $validated['direction'] === 'up'
            ? $targetQuery->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first()
            : $targetQuery->where('order', '>', $currentOrder)->orderBy('order')->first();

        if (! $target) {
            return redirect()->back();
        }

        DB::transaction(function () use ($section, $target, $currentOrder) {
            $section->update(['order' => $target->order]);
            $target->update(['order' => $currentOrder]);
        });

        return redirect()
            ->back()
            ->with('success', 'Section order has been updated.');
    }

    /**
     * Reorder all sections for the current page via drag and drop.
     */
    public function reorder(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $orderedIds = collect($validated['ids'])
            ->map(fn ($id) => (int) $id)
            ->values();

        $pageSectionIds = Section::where('page_id', $page->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if (
            $orderedIds->count() !== $pageSectionIds->count()
            || $orderedIds->diff($pageSectionIds)->isNotEmpty()
            || $pageSectionIds->diff($orderedIds)->isNotEmpty()
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid reorder payload.',
            ], 422);
        }

        DB::transaction(function () use ($page, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                Section::where('page_id', $page->id)
                    ->where('id', $id)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Section order has been updated.',
        ]);
    }

    /**
     * Duplicate a section as a new draft copy.
     */
    public function duplicate(Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);
        $section->loadMissing('translations');

        $duplicate = null;

        DB::transaction(function () use ($page, $section, &$duplicate) {
            $duplicate = Section::create([
                'page_id'   => $page->id,
                'type'      => $section->type,
                'variant'   => $section->variant,
                'style'     => $section->style,
                'order'     => $this->nextOrderForPage($page),
                'is_active' => false,
            ]);

            foreach ($section->translations as $translation) {
                SectionTranslation::create([
                    'section_id' => $duplicate->id,
                    'locale'     => $translation->locale,
                    'title'      => $translation->title ? $translation->title . ' (Copy)' : null,
                    'content'    => $translation->content ?? [],
                ]);
            }
        });

        return redirect()
            ->route('dashboard.pages.sections.index', ['page' => $page, 'highlight' => $duplicate->id])
            ->with('success', 'Section duplicated as a draft copy.');
    }

    /**
     * Delete a section.
     */
    public function destroy(Page $page, Section $section)
    {
        $this->ensureSectionBelongsToPage($page, $section);

        $section->delete();
        $this->normalizePageSectionOrders($page);

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('success', 'Section has been deleted successfully.');
    }

    /**
     * Registry of section types available to the workspace.
     */
    protected function availableSectionTypes(): array
    {
        return [
            'hero_default' => [
                'type'        => 'hero_default',
                'label'       => 'Hero - Default',
                'description' => 'Main hero with title, subtitle, 2 buttons, and media.',
                'category'    => 'hero',
                'preview'     => 'assets/admin/sections/hero-default.png',
            ],

            'hero_minimal' => [
                'type'        => 'hero_minimal',
                'label'       => 'Hero - Minimal',
                'description' => 'Simple hero with title and single CTA.',
                'category'    => 'hero',
                'preview'     => 'assets/admin/sections/hero-minimal.png',
            ],

            'hero_campaign' => [
                'type'        => 'hero_campaign',
                'label'       => 'Hero - Campaign',
                'description' => 'Two-line hero with campaign benefits, CTA, and side illustration.',
                'category'    => 'hero',
                'preview'     => null,
            ],

            'features_grid' => [
                'type'        => 'features_grid',
                'label'       => 'Features Grid',
                'description' => 'Grid of feature cards with a short intro.',
                'category'    => 'features',
                'preview'     => 'assets/admin/sections/features-grid.png',
            ],

            'services_grid' => [
                'type'        => 'services_grid',
                'label'       => 'Services Grid',
                'description' => 'Services with icons and short descriptions.',
                'category'    => 'services',
                'preview'     => 'assets/admin/sections/services-grid.png',
            ],

            'templates_showcase' => [
                'type'        => 'templates_showcase',
                'label'       => 'Templates Showcase',
                'description' => 'Selected templates in a grid or slider.',
                'category'    => 'templates',
                'preview'     => 'assets/admin/sections/templates-showcase.png',
            ],
        ];
    }

    /**
     * Convert form payloads into a stable JSON shape for frontend rendering.
     */
    protected function normalizeContentByType(string $type, array $content): array
    {
        switch ($type) {
            case 'hero_default':
            case 'hero_minimal':
                return $this->normalizeHeroContent($content);

            case 'hero_campaign':
                return $this->normalizeHeroCampaignContent($content);

            default:
                return $content;
        }
    }

    /**
     * Create a section with lightweight defaults for the quick-add workflow.
     */
    protected function createDefaultSection(Page $page, string $type, ?string $variant = null): Section
    {
        $sectionTypes = $this->availableSectionTypes();
        $label = $sectionTypes[$type]['label'] ?? ucfirst(str_replace('_', ' ', $type));

        $section = Section::create([
            'page_id'   => $page->id,
            'type'      => $type,
            'variant'   => $variant,
            'style'     => $this->defaultStyleForType($type),
            'order'     => $this->nextOrderForPage($page),
            'is_active' => true,
        ]);

        foreach ($this->activeLocaleCodes() as $locale) {
            $pageTitle = $page->translation($locale)?->title
                ?? $page->translation()?->title
                ?? ('Page #' . $page->id);

            SectionTranslation::create([
                'section_id' => $section->id,
                'locale'     => $locale,
                'title'      => $label,
                'content'    => $this->defaultContentForType($type, $pageTitle),
            ]);
        }

        return $section;
    }

    /**
     * Seed starter content so a newly-added section is not completely empty.
     */
    protected function defaultContentForType(string $type, string $pageTitle): array
    {
        return match ($type) {
            'hero_default' => [
                'eyebrow'  => 'New section',
                'title'    => $pageTitle,
                'subtitle' => 'Update this hero from the section editor.',
                'primary_button' => [
                    'label' => 'Get Started',
                    'url'   => '#',
                ],
                'secondary_button' => [
                    'label' => 'Learn More',
                    'url'   => '#',
                ],
                'features' => [
                    'First benefit',
                    'Second benefit',
                ],
                'media_type' => 'image',
                'media_url'  => null,
            ],

            'hero_minimal' => [
                'title'    => $pageTitle,
                'subtitle' => 'Update this hero from the section editor.',
                'primary_button' => [
                    'label' => 'Get Started',
                    'url'   => '#',
                ],
            ],

            'hero_campaign' => [
                'title' => 'in 5 Minutes',
                'subtitle' => 'Launch your website at Minimal Cost',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.',
                'features_heading' => 'The campaign includes:',
                'primary_button' => [
                    'label' => 'Choose Your template',
                    'url' => '#',
                ],
                'features' => [
                    ['text' => 'Choose Your Template', 'icon' => 'ti ti-layout-grid'],
                    ['text' => 'Website Hosting', 'icon' => 'ti ti-server'],
                    ['text' => 'Control Panel', 'icon' => 'ti ti-settings'],
                    ['text' => 'Email Addresses', 'icon' => 'ti ti-mail'],
                    ['text' => 'Private Domain', 'icon' => 'ti ti-world'],
                    ['text' => '24/7 technical support', 'icon' => 'ti ti-headset'],
                    ['text' => 'Private Domain', 'icon' => 'ti ti-world'],
                    ['text' => '24/7 technical support', 'icon' => 'ti ti-headset'],
                ],
                'media_type' => 'image',
                'media_url' => 'assets/tamplate/images/Fu.svg',
            ],

            'features_grid' => [
                'title'    => 'Why choose us',
                'subtitle' => 'Add your key benefits here.',
                'features' => [],
            ],

            'services_grid' => [
                'title'    => 'Services',
                'subtitle' => 'Highlight your core services.',
                'items'    => [],
            ],

            'templates_showcase' => [
                'title'    => 'Templates',
                'subtitle' => 'Show your best templates.',
                'items'    => [],
            ],

            default => [
                'title'    => $pageTitle,
                'subtitle' => '',
            ],
        };
    }

    /**
     * Default style values for quick-created sections.
     */
    protected function defaultStyleForType(string $type): array
    {
        return match ($type) {
            'hero_default' => [
                'background_color' => 'bg-background dark:bg-gray-950',
                'text_align'       => 'rtl:text-right ltr:text-left',
                'padding_y'        => 'py-16 sm:py-20',
            ],
            'hero_campaign' => [
                'padding_y' => 'pt-6 pb-8 lg:pt-10 lg:pb-18',
            ],
            default => [],
        };
    }

    /**
     * Normalize all hero-like section payloads to one stable structure.
     */
    protected function normalizeHeroContent(array $content): array
    {
        $featuresRaw = $content['features_textarea'] ?? ($content['features_raw'] ?? ($content['features'] ?? ''));

        if (is_array($featuresRaw)) {
            $features = array_values(array_filter(array_map(
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $featuresRaw
            )));
        } else {
            $features = array_values(array_filter(
                array_map('trim', preg_split("/\r\n|\r|\n/", (string) $featuresRaw))
            ));
        }

        return [
            'eyebrow' => $content['eyebrow'] ?? null,
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'description' => $content['description'] ?? null,
            'features_heading' => $content['features_heading'] ?? null,

            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
            ],

            'secondary_button' => [
                'label' => $content['secondary_button_label']
                    ?? ($content['secondary_button']['label'] ?? null),
                'url' => $content['secondary_button_url']
                    ?? ($content['secondary_button']['url'] ?? null),
            ],

            'features' => $features,
            'media_type' => $content['media_type'] ?? 'image',
            'media_url' => $content['media_url'] ?? null,
        ];
    }

    /**
     * Normalize the campaign hero payload while preserving per-feature icon choices.
     */
    protected function normalizeHeroCampaignContent(array $content): array
    {
        return [
            'eyebrow' => $content['eyebrow'] ?? null,
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'description' => $content['description'] ?? null,
            'features_heading' => $content['features_heading'] ?? null,

            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
            ],

            'secondary_button' => [
                'label' => null,
                'url' => null,
            ],

            'features' => $this->normalizeCampaignFeatureItems(
                $content['features'] ?? ($content['features_textarea'] ?? '')
            ),
            'media_type' => $content['media_type'] ?? 'image',
            'media_url' => $content['media_url'] ?? null,
        ];
    }

    /**
     * Convert campaign features into stable {text, icon} items.
     *
     * @return array<int, array{text: string, icon: string|null}>
     */
    protected function normalizeCampaignFeatureItems(mixed $featuresRaw): array
    {
        if (! is_array($featuresRaw)) {
            return collect(preg_split("/\r\n|\r|\n/", (string) $featuresRaw))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->map(fn ($item) => [
                    'text' => $item,
                    'icon' => null,
                ])
                ->all();
        }

        return collect($featuresRaw)
            ->map(function ($item): ?array {
                if (is_string($item)) {
                    $text = trim($item);

                    return $text !== ''
                        ? ['text' => $text, 'icon' => null]
                        : null;
                }

                if (! is_array($item)) {
                    return null;
                }

                $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));

                if ($text === '') {
                    return null;
                }

                return [
                    'text' => $text,
                    'icon' => $this->sanitizeFeatureIconClass($item['icon'] ?? null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Keep icon classes limited to safe class-name characters.
     */
    protected function sanitizeFeatureIconClass(mixed $icon): ?string
    {
        $value = trim((string) $icon);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    /**
     * Copy shared non-translatable fields across locales for section types that need it.
     */
    protected function syncSharedSectionContent(string $type, array $translations): array
    {
        if ($type !== 'hero_campaign' || $translations === []) {
            return $translations;
        }

        $sharedMedia = collect($translations)
            ->map(fn ($translation) => data_get($translation, 'content.media_url'))
            ->first(fn ($value) => ! is_null($value) && $value !== '');

        if ($sharedMedia === null || $sharedMedia === '') {
            return $translations;
        }

        foreach ($translations as $key => $translation) {
            $content = is_array($translation['content'] ?? null) ? $translation['content'] : [];
            $content['media_url'] = $sharedMedia;
            $translations[$key]['content'] = $content;
        }

        return $translations;
    }

    /**
     * Keep orders contiguous after move/delete/manual changes.
     */
    protected function normalizePageSectionOrders(Page $page): void
    {
        $sections = Section::where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'order']);

        foreach ($sections as $index => $item) {
            $expectedOrder = $index + 1;

            if ((int) ($item->order ?? 0) !== $expectedOrder) {
                Section::whereKey($item->id)->update([
                    'order' => $expectedOrder,
                ]);
            }
        }
    }

    /**
     * Guard route-model binding so a section cannot be edited from another page.
     */
    protected function ensureSectionBelongsToPage(Page $page, Section $section): void
    {
        if ($section->page_id !== $page->id) {
            abort(404);
        }
    }

    /**
     * Return type keys for validation.
     */
    protected function availableSectionTypeKeys(): array
    {
        return array_keys($this->availableSectionTypes());
    }

    /**
     * Return the next display order for a page.
     */
    protected function nextOrderForPage(Page $page): int
    {
        $maxOrder = Section::where('page_id', $page->id)->max('order');

        return is_null($maxOrder) ? 1 : ((int) $maxOrder + 1);
    }

    /**
     * Return active locale codes, with a safe fallback to the app locale.
     */
    protected function activeLocaleCodes(): array
    {
        $localeCodes = Language::where('is_active', true)
            ->orderBy('id')
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        return $localeCodes !== [] ? $localeCodes : [app()->getLocale()];
    }

    /**
     * Shared payload for the standalone edit page and inline workspace editor.
     */
    protected function sectionEditorViewData(Page $page, Section $section): array
    {
        $section->load('translations');
        $page->loadMissing('translations');

        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        return [
            'page' => $page,
            'section' => $section,
            'languages' => $languages,
            'sectionTypes' => $this->availableSectionTypes(),
        ];
    }
}
