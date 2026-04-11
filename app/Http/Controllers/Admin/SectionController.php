<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Template;
use App\Support\Sections\SectionEditorDataFactory;
use App\Support\Sections\SectionSidebarEditorViewDataFactory;
use App\Support\Sections\SectionWorkspacePreviewViewDataFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Canonical controller-driven section management entrypoint.
 * Deprecated admin Livewire section editors are retained only for fallback
 * safety and must not be used for active routing.
 */
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
            'sectionLibraryTypes' => $this->sectionLibraryTypes(),
        ] + $this->workspaceViewData($page));
    }

    /**
     * Render a front-like preview for the sections workspace iframe.
     */
    public function preview(
        Request $request,
        Page $page,
        ?SectionWorkspacePreviewViewDataFactory $previewViewFactory = null,
    )
    {
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
        $sectionTypes = $this->availableSectionTypes();
        $previewView = ($previewViewFactory ?? app(SectionWorkspacePreviewViewDataFactory::class))->build(
            $page,
            $sections,
            $sectionTypes,
            $previewTemplates,
            $request->integer('highlight'),
        );

        return view('dashboard.pages.sections.preview', [
            'previewView' => $previewView,
        ] + $this->workspaceViewData($page));
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
            'sectionLibraryTypes' => $this->sectionLibraryTypes(),
            'nextOrder'    => $this->nextOrderForPage($page),
        ] + $this->workspaceViewData($page));
    }

    /**
     * Store a newly created section and its translations.
     */
    public function store(Request $request, Page $page)
    {
        $sectionDefinitionIdRules = ['nullable', 'integer'];

        if (Schema::hasTable('section_definitions')) {
            $sectionDefinitionIdRules[] = Rule::exists('section_definitions', 'id')
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_visible', true));
        }

        $validated = $request->validate([
            'type'      => ['required', 'string', 'max:100', Rule::in($this->allowedSectionTypeKeys())],
            'section_definition_id' => $sectionDefinitionIdRules,
            'variant'   => 'nullable|string|max:100',
            'style'     => 'nullable|array',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        $sectionSelection = $this->normalizeSectionCreateSelection(
            (string) $validated['type'],
            isset($validated['section_definition_id']) ? (int) $validated['section_definition_id'] : null,
        );
        $validated['type'] = $sectionSelection['type'];

        $validated['translations'] = $this->syncSharedSectionContent(
            $validated['type'],
            $validated['translations'] ?? []
        );

        DB::transaction(function () use ($validated, $page, $sectionSelection) {
            $type = $validated['type'];

            $section = Section::create([
                'page_id'   => $page->id,
                ...$this->sectionDefinitionLinkAttributes($sectionSelection['section_definition_id']),
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
            ->to($this->workspaceRoute('index', $page))
            ->with('success', 'Section has been created successfully.');
    }

    /**
     * Quick-add a section from the workspace library, then open its editor.
     */
    public function quickStore(Request $request, Page $page)
    {
        $sectionDefinitionIdRules = ['nullable', 'integer'];

        if (Schema::hasTable('section_definitions')) {
            $sectionDefinitionIdRules[] = Rule::exists('section_definitions', 'id')
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_visible', true));
        }

        $validated = $request->validate([
            'type'    => ['required', 'string', 'max:100', Rule::in($this->allowedSectionTypeKeys())],
            'section_definition_id' => $sectionDefinitionIdRules,
            'variant' => 'nullable|string|max:100',
        ]);

        $sectionSelection = $this->normalizeSectionCreateSelection(
            (string) $validated['type'],
            isset($validated['section_definition_id']) ? (int) $validated['section_definition_id'] : null,
        );

        $page->loadMissing('translations');
        $createdSection = null;

        DB::transaction(function () use ($page, $validated, $sectionSelection, &$createdSection) {
            $createdSection = $this->createDefaultSection(
                $page,
                $sectionSelection['type'],
                $validated['variant'] ?? null,
                $sectionSelection['section_definition_id'],
                $sectionSelection['section_definition'],
            );
        });

        if (! $createdSection instanceof Section) {
            abort(500, 'Section could not be created.');
        }

        $createdSection->refresh()->load('translations');

        $redirectUrl = $this->workspaceRoute('index', $page, null, [
            'highlight' => $createdSection->id,
            'edit' => $createdSection->id,
        ]);
        $editorUrl = $this->workspaceRoute('editor', $page, $createdSection, [], false);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'redirect_url' => $redirectUrl,
                'editor_url' => $editorUrl,
                'section_id' => $createdSection->id,
                'section_card_html' => $this->renderSectionOutlineItem($page, $createdSection),
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
            'type'      => ['required', 'string', 'max:100', Rule::in($this->allowedSectionTypeKeys($section))],
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
                ...$this->sectionDefinitionAttributesForType($type, $section),
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
            ->to($this->workspaceRoute('index', $page, null, [
                'highlight' => $section->id,
            ]))
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
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $section->id]))
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
            'ids.*' => ['required'],
        ]);

        $orderedIds = collect($validated['ids'])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($orderedIds->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid reorder payload.',
            ], 422);
        }

        $pageSectionIds = Section::where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();

        if ($orderedIds->diff($pageSectionIds)->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid reorder payload.',
            ], 422);
        }

        $missingIds = $pageSectionIds
            ->reject(fn($id) => $orderedIds->contains($id))
            ->values();

        $finalOrder = $orderedIds
            ->concat($missingIds)
            ->values();

        DB::transaction(function () use ($page, $finalOrder): void {
            foreach ($finalOrder as $index => $id) {
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
                ...$this->duplicatedSectionDefinitionAttributes($section),
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

        if (! $duplicate instanceof Section) {
            abort(500, 'Section could not be duplicated.');
        }

        return redirect()
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $duplicate->id]))
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
            ->to($this->workspaceRoute('index', $page))
            ->with('success', 'Section has been deleted successfully.');
    }

    /**
     * Route prefix used by the current sections workspace surface.
     */
    protected function workspaceRoutePrefix(): string
    {
        return 'dashboard.pages.sections.';
    }

    /**
     * Current workspace consumer mode.
     */
    protected function workspaceMode(): string
    {
        return 'admin';
    }

    /**
     * Small UI label for the current workspace mode.
     */
    protected function workspaceModeLabel(): ?string
    {
        return __('Admin workspace');
    }

    /**
     * Base route parameters for the current workspace surface.
     *
     * @return array<string, mixed>
     */
    protected function workspaceBaseRouteParameters(Page $page): array
    {
        return ['page' => $page];
    }

    /**
     * Build route parameters for workspace routes.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function workspaceRouteParameters(Page $page, ?Section $section = null, array $extra = []): array
    {
        $parameters = $this->workspaceBaseRouteParameters($page);

        if ($section instanceof Section) {
            $parameters['section'] = $section;
        }

        return array_merge($parameters, $extra);
    }

    /**
     * Resolve a workspace route URL.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function workspaceRoute(
        string $name,
        Page $page,
        ?Section $section = null,
        array $extra = [],
        bool $absolute = true
    ): string {
        return route(
            $this->workspaceRoutePrefix() . $name,
            $this->workspaceRouteParameters($page, $section, $extra),
            $absolute
        );
    }

    /**
     * Back link for the workspace shell.
     */
    protected function workspaceShellBackUrl(Page $page): string
    {
        return route('dashboard.pages.index');
    }

    /**
     * Back link label for the workspace shell.
     */
    protected function workspaceShellBackLabel(): string
    {
        return __('Back to pages');
    }

    /**
     * Front preview URL for the current page.
     */
    protected function workspaceFrontUrl(Page $page): string
    {
        $translation = $page->translation();

        return $page->is_home
            ? url('/')
            : ($translation?->slug ? url($translation->slug) : url('/'));
    }

    /**
     * Optional visual builder URL for the workspace shell.
     */
    protected function workspaceVisualBuilderUrl(Page $page): ?string
    {
        return route('dashboard.pages.builder', $page);
    }

    /**
     * Optional builder-mode toggle URL for the workspace sidebar.
     */
    protected function workspaceBuilderModeUrl(Page $page): ?string
    {
        return route('dashboard.pages.builder-mode', $page);
    }

    /**
     * Shared view config for any workspace surface.
     *
     * @return array<string, mixed>
     */
    protected function workspaceViewData(Page $page): array
    {
        return [
            'workspaceMode' => $this->workspaceMode(),
            'workspaceModeLabel' => $this->workspaceModeLabel(),
            'workspaceRoutePrefix' => $this->workspaceRoutePrefix(),
            'workspaceRouteBaseParameters' => $this->workspaceBaseRouteParameters($page),
            'workspaceShellBackUrl' => $this->workspaceShellBackUrl($page),
            'workspaceShellBackLabel' => $this->workspaceShellBackLabel(),
            'workspaceFrontUrl' => $this->workspaceFrontUrl($page),
            'workspaceVisualBuilderUrl' => $this->workspaceVisualBuilderUrl($page),
            'workspaceBuilderModeUrl' => $this->workspaceBuilderModeUrl($page),
        ];
    }

    /**
     * Registry of section types available to the workspace.
     */
    protected function availableSectionTypes(): array
    {
        return [
            'hero' => [
                'type'        => 'hero',
                'label'       => 'Hero',
                'description' => 'Landing-page hero with headline, actions, highlights, and media.',
                'category'    => 'hero',
                'preview'     => null,
            ],

            'hero_default' => [
                'type'        => 'hero_default',
                'label'       => 'Hero - Default',
                'description' => 'Main hero with title, subtitle, 2 buttons, and media.',
                'category'    => 'hero',
                'preview'     => 'assets/admin/sections/hero-default.png',
            ],

            'hero_campaign' => [
                'type'        => 'hero_campaign',
                'label'       => 'Hero - Campaign',
                'description' => 'Two-line hero with campaign benefits, CTA, and side illustration.',
                'category'    => 'hero',
                'preview'     => null,
            ],

            // Legacy code-side registry entry kept for backward compatibility.
            // Definition-driven hero variants should now be created from the
            // section-definition library path instead of this template key.
            'hero.hosting' => [
                'type' => 'hero.hosting',
                'label' => 'Hosting Hero',
                'description' => 'Legacy hosting hero entry preserved for already-linked content.',
                'category' => 'hero',
                'preview' => null,
                'library_hidden' => true,
                'view' => 'front.sections.hero.hosting',
            ],

            'programming_showcase' => [
                'type'        => 'programming_showcase',
                'label'       => 'Programming Showcase',
                'description' => 'Programming department section with outputs list, CTA, and featured image.',
                'category'    => 'services',
                'preview'     => null,
            ],

            'mobile_app_showcase' => [
                'type'        => 'mobile_app_showcase',
                'label'       => 'Mobile App Showcase',
                'description' => 'Mobile app department section with brand label, CTA, and a three-image gallery.',
                'category'    => 'services',
                'preview'     => null,
            ],

            'how_we_build' => [
                'type'        => 'how_we_build',
                'label'       => 'How We Build',
                'description' => 'A five-step build process section with highlighted final launch step.',
                'category'    => 'process',
                'preview'     => null,
            ],

            'design_showcase' => [
                'type'        => 'design_showcase',
                'label'       => 'Design Showcase',
                'description' => 'Design services section with brand label, service list, CTA, and six-image gallery.',
                'category'    => 'services',
                'preview'     => null,
            ],

            'digital_marketing_showcase' => [
                'type'        => 'digital_marketing_showcase',
                'label'       => 'Digital Marketing Showcase',
                'description' => 'Digital marketing section with services list, CTA, and a two-image gallery.',
                'category'    => 'services',
                'preview'     => null,
            ],

            'tech_stack_showcase' => [
                'type'        => 'tech_stack_showcase',
                'label'       => 'Technology Stack',
                'description' => 'Horizontally scrollable strip of technology logos from the media library.',
                'category'    => 'services',
                'preview'     => null,
            ],

            'reviews_showcase' => [
                'type'        => 'reviews_showcase',
                'label'       => 'Reviews Showcase',
                'description' => 'Reviews slider with brand heading, intro text, and testimonial cards.',
                'category'    => 'testimonials',
                'preview'     => null,
            ],

            'our_work_showcase' => [
                'type'        => 'our_work_showcase',
                'label'       => 'Our Work Showcase',
                'description' => 'Portfolio slider with brand heading, intro text, and visit button on each project card.',
                'category'    => 'portfolio',
                'preview'     => null,
            ],

            'hosting_pricing_showcase' => [
                'type'        => 'hosting_pricing_showcase',
                'label'       => 'Hosting Pricing',
                'description' => 'Tabbed hosting plans with editable categories, feature lists, and per-plan CTAs.',
                'category'    => 'pricing',
                'preview'     => null,
            ],

            'domains_showcase' => [
                'type'        => 'domains_showcase',
                'label'       => 'Domains Showcase',
                'description' => 'Domain search teaser with brand heading, search card, and search form CTA.',
                'category'    => 'domains',
                'preview'     => null,
            ],

            'templates_slider_showcase' => [
                'type'        => 'templates_slider_showcase',
                'label'       => 'Templates Slider Showcase',
                'description' => 'Template cards slider with buy and live preview actions loaded from the Templates module.',
                'category'    => 'templates',
                'preview'     => null,
            ],

            'templates_listing_showcase' => [
                'type'        => 'templates_listing_showcase',
                'label'       => 'Templates Listing Showcase',
                'description' => 'Templates archive grid with category filters, sort buttons, and client-side pagination from the Templates module.',
                'category'    => 'templates',
                'preview'     => null,
            ],

            'features_grid' => [
                'type'        => 'features_grid',
                'label'       => 'Features Grid',
                'description' => 'Grid of feature cards with a short intro.',
                'category'    => 'features',
                'preview'     => 'assets/admin/sections/features-grid.png',
            ],

            'features' => [
                'type'        => 'features',
                'label'       => 'Features',
                'description' => 'Simple landing-page features block with editable benefit bullets.',
                'category'    => 'features',
                'preview'     => null,
            ],

            'cta' => [
                'type'        => 'cta',
                'label'       => 'CTA',
                'description' => 'Compact call-to-action strip with badge, copy, and one button.',
                'category'    => 'other',
                'preview'     => null,
            ],

            'testimonials' => [
                'type'        => 'testimonials',
                'label'       => 'Testimonials',
                'description' => 'Manual testimonial cards for client landing pages.',
                'category'    => 'testimonials',
                'preview'     => null,
            ],

            'faq' => [
                'type'        => 'faq',
                'label'       => 'FAQ',
                'description' => 'Question-and-answer list for client landing pages.',
                'category'    => 'other',
                'preview'     => null,
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
            case 'hero':
            case 'hero_default':
                return $this->normalizeHeroContent($content);

            case 'features':
            case 'features_grid':
                return $this->normalizeSimpleFeaturesContent($content);

            case 'cta':
                return $this->normalizeSimpleCtaContent($content);

            case 'site_header':
                return $this->normalizeSiteHeaderContent($content);

            case 'site_footer':
                return $this->normalizeSiteFooterContent($content);

            case 'testimonials':
                return $this->normalizeManualTestimonialsContent($content);

            case 'faq':
                return $this->normalizeFaqContent($content);

            case 'hero_campaign':
                return $this->normalizeHeroCampaignContent($content);

            case 'programming_showcase':
                return $this->normalizeProgrammingContent($content);

            case 'mobile_app_showcase':
                return $this->normalizeMobileAppContent($content);

            case 'how_we_build':
                return $this->normalizeHowWeBuildContent($content);

            case 'design_showcase':
                return $this->normalizeDesignShowcaseContent($content);

            case 'digital_marketing_showcase':
                return $this->normalizeDigitalMarketingShowcaseContent($content);

            case 'tech_stack_showcase':
                return $this->normalizeTechStackShowcaseContent($content);

            case 'reviews_showcase':
                return $this->normalizeReviewsShowcaseContent($content);

            case 'our_work_showcase':
                return $this->normalizeOurWorkShowcaseContent($content);

            case 'hosting_pricing_showcase':
                return $this->normalizeHostingPricingShowcaseContent($content);

            case 'domains_showcase':
                return $this->normalizeDomainsShowcaseContent($content);

            case 'templates_slider_showcase':
                return $this->normalizeTemplatesSliderShowcaseContent($content);

            case 'templates_listing_showcase':
                return $this->normalizeTemplatesListingShowcaseContent($content);

            default:
                return $content;
        }
    }

    /**
     * Create a section with lightweight defaults for the quick-add workflow.
     */
    protected function createDefaultSection(
        Page $page,
        string $type,
        ?string $variant = null,
        ?int $sectionDefinitionId = null,
        ?SectionDefinition $sectionDefinition = null,
    ): Section
    {
        $sectionTypes = $this->sectionLibraryTypes();
        $label = $sectionDefinition?->label
            ?? ($sectionTypes[$type]['label'] ?? ucfirst(str_replace('_', ' ', $type)));

        $section = Section::create([
            'page_id'   => $page->id,
            ...$this->sectionDefinitionLinkAttributes($sectionDefinitionId),
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
            'hero' => [
                'eyebrow'  => 'New section',
                'title'    => $pageTitle,
                'subtitle' => 'Update this hero from the section editor.',
                'primary_button' => [
                    'label' => 'Get Started',
                    'url'   => '#',
                    'new_tab' => false,
                ],
                'secondary_button' => [
                    'label' => 'Learn More',
                    'url'   => '#',
                ],
                'features' => [
                    'First benefit',
                    'Second benefit',
                    'Third benefit',
                ],
                'media_type' => 'image',
                'media_url'  => null,
            ],

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

            'hero_campaign' => [
                'title' => 'in 5 Minutes',
                'subtitle' => 'Launch your website at Minimal Cost',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.',
                'features_heading' => 'The campaign includes:',
                'primary_button' => [
                    'label' => 'Choose Your template',
                    'url' => '#',
                    'new_tab' => false,
                ],
                'features' => [
                    ['text' => 'Choose Your Template', 'icon_source' => 'class', 'icon' => 'ti ti-layout-grid'],
                    ['text' => 'Website Hosting', 'icon_source' => 'class', 'icon' => 'ti ti-server'],
                    ['text' => 'Control Panel', 'icon_source' => 'class', 'icon' => 'ti ti-settings'],
                    ['text' => 'Email Addresses', 'icon_source' => 'class', 'icon' => 'ti ti-mail'],
                    ['text' => 'Private Domain', 'icon_source' => 'class', 'icon' => 'ti ti-world'],
                    ['text' => '24/7 technical support', 'icon_source' => 'class', 'icon' => 'ti ti-headset'],
                    ['text' => 'Private Domain', 'icon_source' => 'class', 'icon' => 'ti ti-world'],
                    ['text' => '24/7 technical support', 'icon_source' => 'class', 'icon' => 'ti ti-headset'],
                ],
                'trust_items' => [
                    'بدون خبرة تقنية',
                    'جاهز خلال دقائق',
                    'دعم كامل',
                ],
                'media_type' => 'image',
                'media_url' => 'assets/tamplate/images/Fu.svg',
            ],

            'features_grid' => [
                'title'    => 'Why choose us',
                'subtitle' => 'Add your key benefits here.',
                'features' => [],
            ],

            'features' => [
                'title'    => 'Why choose us',
                'subtitle' => 'Add your key benefits here.',
                'features' => [
                    'First benefit',
                    'Second benefit',
                    'Third benefit',
                ],
            ],

            'cta' => [
                'eyebrow' => 'Ready to customize',
                'subtitle' => 'Guide visitors toward one clear next step.',
                'primary_button' => [
                    'label' => 'Start Now',
                    'url' => '#',
                    'new_tab' => false,
                ],
            ],

            'site_header' => [
                'title' => 'My Website',
                'logo' => null,
                'primary_button' => [
                    'label' => 'Contact us',
                    'url' => '#contact',
                    'new_tab' => false,
                ],
            ],

            'site_footer' => [
                'title' => 'My Website',
                'footer_links' => [
                    ['label' => 'About', 'url' => '#'],
                    ['label' => 'Blog', 'url' => '#'],
                    ['label' => 'Jobs', 'url' => '#'],
                    ['label' => 'Press', 'url' => '#'],
                    ['label' => 'Accessibility', 'url' => '#'],
                    ['label' => 'Partners', 'url' => '#'],
                ],
                'social_links' => [
                    'facebook' => '',
                    'instagram' => '',
                    'x' => '',
                    'github' => '',
                    'youtube' => '',
                ],
                'copyright' => sprintf('© %s My Website. All rights reserved.', now()->year),
            ],

            'programming_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'PROGRAMMING',
                'description' => 'The Programming Department is the core of our web development company, turning ideas into functional websites. Our developers build dynamic, user-friendly platforms with modern tools, ensuring precision, performance, and innovation in every project.',
                'outputs_heading' => 'What Are Our Outputs?',
                'outputs' => [
                    'Landing Sites',
                    'Company and organization websites',
                    'E-Commerce Stores',
                    'SaaS Platforms',
                ],
                'primary_button' => [
                    'label' => 'Send Your idea',
                    'url' => '#',
                ],
                'media_type' => 'image',
                'media_url' => 'assets/tamplate/images/tech company-rafiki.svg',
            ],

            'mobile_app_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'MOBILE APP',
                'description' => 'The Mobile Programming Department harnesses cutting-edge technologies to craft seamless, high-performance applications. Its outputs reflect precision, creativity, and innovation that elevate user experiences to global standards.',
                'primary_button' => [
                    'label' => 'Send Your idea',
                    'url' => '#',
                ],
                'image_one' => 'assets/dashboard/images/landing/img-productivity-1.png',
                'image_two' => 'assets/dashboard/images/landing/img-productivity-2.png',
                'image_three' => 'assets/dashboard/images/landing/business-presentation-1.png',
            ],

            'how_we_build' => [
                'title' => 'How We Build',
                'subtitle' => 'We Build with precision, passion, and purpose',
                'primary_button' => [
                    'label' => 'ابدأ الآن — موقعك جاهز خلال دقائق',
                    'url' => '#',
                    'visible' => true,
                    'new_tab' => true,
                ],
                'steps' => [
                    ['title' => 'Analysis', 'icon_source' => 'class', 'icon' => 'ti ti-search', 'is_accent' => false],
                    ['title' => 'Ux/Ui', 'icon_source' => 'class', 'icon' => 'ti ti-palette', 'is_accent' => false],
                    ['title' => 'Development', 'icon_source' => 'class', 'icon' => 'ti ti-code', 'is_accent' => false],
                    ['title' => 'Testing And Review', 'icon_source' => 'class', 'icon' => 'ti ti-test-pipe', 'is_accent' => false],
                    ['title' => 'Launch', 'icon_source' => 'class', 'icon' => 'ti ti-rocket', 'is_accent' => true],
                ],
            ],

            'design_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'DESIGN',
                'description' => 'Customer opinions drive innovation, trust, and growth',
                'services' => [
                    'UI/UX',
                    'Branding',
                    'Packaging Design',
                    'Print Design',
                    'Digital Media',
                    'Motion Graphics & Animation',
                ],
                'primary_button' => [
                    'label' => 'Send Order',
                    'url' => '#',
                ],
                'image_one' => 'assets/dashboard/images/landing/img-productivity-1.png',
                'image_two' => 'assets/dashboard/images/landing/img-productivity-2.png',
                'image_three' => 'assets/dashboard/images/landing/business-presentation-1.png',
                'image_four' => 'assets/dashboard/images/landing/img-productivity-1.png',
                'image_five' => 'assets/dashboard/images/landing/img-productivity-2.png',
                'image_six' => 'assets/dashboard/images/landing/business-presentation-1.png',
            ],

            'digital_marketing_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'DIGITAL MARKETING',
                'services' => [
                    'Search Engine Optimization (SEO)',
                    'Content writing',
                    'E-Commerce store management and data analysis',
                    'Pay-Per-Click Advertising (PPC)',
                    'Social Media Marketing',
                    'Content Marketing',
                ],
                'primary_button' => [
                    'label' => 'Send Order',
                    'url' => '#',
                ],
                'image_one' => 'assets/dashboard/images/landing/img-productivity-1.png',
                'image_two' => 'assets/dashboard/images/landing/img-productivity-2.png',
            ],

            'tech_stack_showcase' => [
                'logos' => [],
            ],

            'reviews_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'REVIEWS',
                'description' => 'Customer opinions drive innovation, trust, and growth',
                'limit' => 8,
            ],

            'testimonials' => [
                'subtitle' => 'Short proof points help build trust.',
                'items' => [
                    ['name' => 'Amina', 'text' => 'This block is ready to customize.', 'rating' => 5],
                    ['name' => 'Omar', 'text' => 'Add your own testimonials here.', 'rating' => 5],
                ],
            ],

            'faq' => [
                'subtitle' => 'Answer the most common questions here.',
                'items' => [
                    ['question' => 'Can I edit this later?', 'answer' => 'Yes, you can update the content from the section editor any time.'],
                    ['question' => 'Does this work on mobile?', 'answer' => 'Yes, the layout is responsive by default.'],
                ],
            ],

            'our_work_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'OUR WORK',
                'description' => 'Customer opinions drive innovation, trust, and growth.',
                'visit_label' => 'Visit',
                'limit' => 6,
            ],

            'hosting_pricing_showcase' => [
                'title' => 'HOSTING',
                'description' => 'Pricing Details & What Included In This Pricing Package',
                'button_label' => 'Choose Now',
                'visible_category_ids' => [],
            ],

            'domains_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'DOMAINS',
                'search_heading' => 'Find your perfect Domain name',
                'description' => 'Welcome to our domain hosting platform, where your online journey begins.',
                'input_placeholder' => 'enter your domain here...',
                'primary_button' => [
                    'label' => 'Search',
                    'url' => route('domains.page', [], false),
                    'new_tab' => false,
                ],
            ],

            'templates_slider_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'TEMPLATE',
                'description' => 'Choose from a range of templates and publish them instantly',
                'buy_label' => 'Buy Now',
                'preview_label' => 'Live Preview',
                'limit' => 6,
            ],

            'templates_listing_showcase' => [
                'breadcrumb_label' => 'Templates',
                'title' => 'TEMPLATE',
                'description' => 'Choose from a range of templates and publish them instantly',
                'all_categories_label' => 'All Hosting',
                'type_label' => 'Type',
                'best_sellers_label' => 'Best Sellers',
                'price_label' => 'Price',
                'buy_label' => 'Buy Now',
                'preview_label' => 'Live Preview',
                'items_per_page' => 12,
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
            'programming_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'mobile_app_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'how_we_build' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'design_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'digital_marketing_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'tech_stack_showcase' => [
                'padding_y' => 'py-12',
            ],
            'reviews_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'our_work_showcase' => [
                'padding_y' => 'py-16 lg:py-24',
            ],
            'hosting_pricing_showcase' => [
                'padding_y' => 'py-12 lg:py-20',
            ],
            'domains_showcase' => [
                'padding_y' => 'py-20',
            ],
            'templates_slider_showcase' => [
                'padding_y' => 'py-20',
            ],
            'templates_listing_showcase' => [
                'padding_y' => 'py-4',
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
                static fn($item) => is_scalar($item) ? trim((string) $item) : '',
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
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
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
     * Normalize simple feature blocks used by tenant landing pages.
     */
    protected function normalizeSimpleFeaturesContent(array $content): array
    {
        $itemsRaw = $content['features_textarea'] ?? ($content['items'] ?? ($content['features'] ?? []));

        $items = is_array($itemsRaw)
            ? collect($itemsRaw)
                ->map(function ($item): ?array {
                    if (is_scalar($item)) {
                        $title = trim((string) $item);

                        return $title !== ''
                            ? ['icon' => '', 'title' => $title, 'description' => '']
                            : null;
                    }

                    if (! is_array($item)) {
                        return null;
                    }

                    $title = trim((string) ($item['title'] ?? $item['text'] ?? $item['label'] ?? ''));
                    $description = trim((string) ($item['description'] ?? $item['subtitle'] ?? ''));
                    $icon = trim((string) ($item['icon'] ?? ''));

                    if ($title === '' && $description === '') {
                        return null;
                    }

                    return [
                        'icon' => $icon,
                        'title' => $title,
                        'description' => $description,
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : collect(preg_split("/\r\n|\r|\n/", (string) $itemsRaw))
                ->map(fn($item) => trim((string) $item))
                ->filter()
                ->values()
                ->map(fn($item) => [
                    'icon' => '',
                    'title' => $item,
                    'description' => '',
                ])
                ->all();

        return [
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'features' => $items,
            'items' => $items,
        ];
    }

    /**
     * Normalize simple CTA blocks used by tenant landing pages.
     */
    protected function normalizeSimpleCtaContent(array $content): array
    {
        $buttonLabel = $content['primary_button_text']
            ?? ($content['primary_button_label'] ?? ($content['primary_button']['label'] ?? null));
        $buttonUrl = $content['primary_button_url']
            ?? ($content['primary_button']['url'] ?? null);
        $buttonNewTab = filter_var(
            $content['primary_button_new_tab']
                ?? ($content['primary_button']['new_tab'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
        $badge = trim((string) ($content['badge'] ?? ($content['eyebrow'] ?? '')));

        return [
            'eyebrow' => $badge !== '' ? $badge : null,
            'badge' => $badge !== '' ? $badge : null,
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'primary_button_text' => $buttonLabel,
            'primary_button_url' => $buttonUrl,
            'primary_button_new_tab' => $buttonNewTab,
            'primary_button' => [
                'label' => $buttonLabel,
                'url' => $buttonUrl,
                'new_tab' => $buttonNewTab,
            ],
        ];
    }

    protected function normalizeSiteHeaderContent(array $content): array
    {
        $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];

        return [
            'title' => trim((string) ($content['title'] ?? __('My Website'))),
            'logo' => $this->sanitizeMediaReference($content['logo'] ?? null),
            'primary_button' => [
                'label' => trim((string) ($content['primary_button_label'] ?? ($primaryButton['label'] ?? ''))),
                'url' => trim((string) ($content['primary_button_url'] ?? ($primaryButton['url'] ?? ''))),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab'] ?? ($primaryButton['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
        ];
    }

    protected function normalizeSiteFooterContent(array $content): array
    {
        $socialLinks = is_array($content['social_links'] ?? null) ? $content['social_links'] : [];
        $footerLinksRaw = $content['footer_links_textarea'] ?? ($content['footer_links'] ?? []);

        $footerLinks = is_array($footerLinksRaw)
            ? collect($footerLinksRaw)
                ->map(function ($item): ?array {
                    if (! is_array($item)) {
                        return null;
                    }

                    $label = trim((string) ($item['label'] ?? ''));
                    $url = trim((string) ($item['url'] ?? '#'));

                    if ($label === '') {
                        return null;
                    }

                    return [
                        'label' => $label,
                        'url' => $url !== '' ? $url : '#',
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : collect(preg_split("/\r\n|\r|\n/", (string) $footerLinksRaw))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->map(function (string $item): ?array {
                    $parts = preg_split('/\s*\|\|\s*|\s*\|\s*/', $item, 2);
                    $label = trim((string) ($parts[0] ?? ''));
                    $url = trim((string) ($parts[1] ?? '#'));

                    if ($label === '') {
                        return null;
                    }

                    return [
                        'label' => $label,
                        'url' => $url !== '' ? $url : '#',
                    ];
                })
                ->filter()
                ->values()
                ->all();

        return [
            'title' => trim((string) ($content['title'] ?? __('My Website'))),
            'copyright' => trim((string) ($content['copyright'] ?? '')),
            'footer_links' => $footerLinks,
            'social_links' => [
                'facebook' => trim((string) ($socialLinks['facebook'] ?? ($content['facebook_url'] ?? ''))),
                'instagram' => trim((string) ($socialLinks['instagram'] ?? ($content['instagram_url'] ?? ''))),
                'x' => trim((string) ($socialLinks['x'] ?? ($content['x_url'] ?? ''))),
                'github' => trim((string) ($socialLinks['github'] ?? ($content['github_url'] ?? ''))),
                'youtube' => trim((string) ($socialLinks['youtube'] ?? ($content['youtube_url'] ?? ''))),
            ],
        ];
    }

    /**
     * Normalize manual testimonial cards for tenant landing pages.
     */
    protected function normalizeManualTestimonialsContent(array $content): array
    {
        $items = collect(is_array($content['items'] ?? null) ? $content['items'] : [])
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $name = trim((string) ($item['name'] ?? ''));
                $role = trim((string) ($item['role'] ?? ''));
                $text = trim((string) ($item['text'] ?? ($item['quote'] ?? '')));
                $rating = max(0, min(5, (int) ($item['rating'] ?? 5)));

                if ($name === '' && $role === '' && $text === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'role' => $role,
                    'text' => $text,
                    'rating' => $rating,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $description = trim((string) ($content['description'] ?? ($content['subtitle'] ?? '')));

        return [
            'eyebrow' => trim((string) ($content['eyebrow'] ?? '')) ?: null,
            'title' => $content['title'] ?? null,
            'description' => $description !== '' ? $description : null,
            'subtitle' => $description !== '' ? $description : null,
            'items' => $items,
        ];
    }

    /**
     * Normalize FAQ blocks with either textarea or structured items.
     */
    protected function normalizeFaqContent(array $content): array
    {
        $itemsRaw = $content['faq_textarea'] ?? ($content['items'] ?? ($content['faq'] ?? []));

        $items = is_array($itemsRaw)
            ? collect($itemsRaw)
                ->map(function ($item): ?array {
                    if (! is_array($item)) {
                        $question = trim((string) $item);

                        return $question === ''
                            ? null
                            : ['question' => $question, 'answer' => ''];
                    }

                    $question = trim((string) ($item['question'] ?? ''));
                    $answer = trim((string) ($item['answer'] ?? ''));

                    if ($question === '' && $answer === '') {
                        return null;
                    }

                    return [
                        'question' => $question,
                        'answer' => $answer,
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : collect(preg_split("/\r\n|\r|\n/", (string) $itemsRaw))
                ->map(function ($line): ?array {
                    $line = trim((string) $line);

                    if ($line === '') {
                        return null;
                    }

                    [$question, $answer] = array_pad(array_map('trim', explode('||', $line, 2)), 2, '');

                    return [
                        'question' => $question,
                        'answer' => $answer,
                    ];
                })
                ->filter()
                ->values()
                ->all();

        return [
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'items' => $items,
            'faq' => $items,
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
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],

            'secondary_button' => [
                'label' => null,
                'url' => null,
            ],

            'features' => $this->normalizeCampaignFeatureItems(
                $content['features'] ?? ($content['features_textarea'] ?? '')
            ),
            'trust_items' => $this->normalizeLineTextItems(
                $content['trust_items'] ?? ($content['trust_items_textarea'] ?? '')
            ),
            'media_type' => $content['media_type'] ?? 'image',
            'media_url' => $content['media_url'] ?? null,
        ];
    }

    /**
     * Normalize the programming showcase payload.
     */
    protected function normalizeProgrammingContent(array $content): array
    {
        $outputsRaw = $content['outputs'] ?? ($content['outputs_textarea'] ?? '');

        if (is_array($outputsRaw)) {
            $outputs = collect($outputsRaw)
                ->map(function ($item): ?array {
                    if (is_string($item)) {
                        $text = trim($item);

                        return $text !== ''
                            ? ['text' => $text, 'icon' => null, 'icon_source' => 'class', 'icon_media' => null]
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
                        ...$this->normalizeStructuredIconPayload($item),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } else {
            $outputs = array_values(array_filter(
                array_map('trim', preg_split("/\r\n|\r|\n/", (string) $outputsRaw))
            ));
        }

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'outputs_heading' => $content['outputs_heading'] ?? null,
            'outputs' => $outputs,
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            'media_type' => $content['media_type'] ?? 'image',
            'media_url' => $content['media_url'] ?? null,
        ];
    }

    /**
     * Normalize the mobile app showcase payload.
     */
    protected function normalizeMobileAppContent(array $content): array
    {
        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            'image_one' => $content['image_one'] ?? null,
            'image_two' => $content['image_two'] ?? null,
            'image_three' => $content['image_three'] ?? null,
        ];
    }

    /**
     * Normalize the how-we-build process payload.
     */
    protected function normalizeHowWeBuildContent(array $content): array
    {
        $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];

        return [
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($primaryButton['label'] ?? 'ابدأ الآن — موقعك جاهز خلال دقائق'),
                'url' => $content['primary_button_url']
                    ?? ($primaryButton['url'] ?? '#'),
                'visible' => filter_var(
                    $content['primary_button_visible']
                        ?? ($primaryButton['visible'] ?? true),
                    FILTER_VALIDATE_BOOLEAN
                ),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($primaryButton['new_tab'] ?? true),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            'steps' => collect(is_array($content['steps'] ?? null) ? $content['steps'] : [])
                ->map(function ($step): ?array {
                    if (! is_array($step)) {
                        return null;
                    }

                    $title = trim((string) ($step['title'] ?? $step['label'] ?? ''));
                    if ($title === '') {
                        return null;
                    }

                    return [
                        'title' => $title,
                        ...$this->normalizeStructuredIconPayload($step),
                        'is_accent' => filter_var($step['is_accent'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * Normalize the design showcase payload.
     */
    protected function normalizeDesignShowcaseContent(array $content): array
    {
        $servicesRaw = $content['services_textarea'] ?? ($content['services'] ?? '');

        if (is_array($servicesRaw)) {
            $services = collect($servicesRaw)
                ->map(function ($item): ?array {
                    if (is_array($item)) {
                        $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                    } elseif (is_scalar($item)) {
                        $text = trim((string) $item);
                    } else {
                        return null;
                    }

                    if ($text === '') {
                        return null;
                    }

                    return [
                        'text' => $text,
                        ...$this->normalizeStructuredIconPayload(is_array($item) ? $item : []),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } else {
            $services = array_values(array_filter(
                array_map('trim', preg_split("/\r\n|\r|\n/", (string) $servicesRaw))
            ));
        }

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'services' => $services,
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            'image_one' => $content['image_one'] ?? null,
            'image_two' => $content['image_two'] ?? null,
            'image_three' => $content['image_three'] ?? null,
            'image_four' => $content['image_four'] ?? null,
            'image_five' => $content['image_five'] ?? null,
            'image_six' => $content['image_six'] ?? null,
        ];
    }

    /**
     * Normalize the digital marketing showcase payload.
     */
    protected function normalizeDigitalMarketingShowcaseContent(array $content): array
    {
        $servicesRaw = $content['services_textarea'] ?? ($content['services'] ?? '');

        if (is_array($servicesRaw)) {
            $services = collect($servicesRaw)
                ->map(function ($item): ?array {
                    if (is_array($item)) {
                        $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                    } elseif (is_scalar($item)) {
                        $text = trim((string) $item);
                    } else {
                        return null;
                    }

                    if ($text === '') {
                        return null;
                    }

                    return [
                        'text' => $text,
                        ...$this->normalizeStructuredIconPayload(is_array($item) ? $item : []),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } else {
            $services = array_values(array_filter(
                array_map('trim', preg_split("/\r\n|\r|\n/", (string) $servicesRaw))
            ));
        }

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'services' => $services,
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? null),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? null),
                'new_tab' => filter_var(
                    $content['primary_button_new_tab']
                        ?? ($content['primary_button']['new_tab'] ?? false),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
            'image_one' => $content['image_one'] ?? null,
            'image_two' => $content['image_two'] ?? null,
        ];
    }

    /**
     * Normalize the technology stack payload.
     */
    protected function normalizeTechStackShowcaseContent(array $content): array
    {
        $logosRaw = $content['logos'] ?? [];

        if (is_string($logosRaw)) {
            $logos = array_values(array_filter(array_map(
                'trim',
                explode(',', $logosRaw)
            )));
        } elseif (is_array($logosRaw)) {
            $logos = array_values(array_filter(array_map(
                static fn($item) => is_scalar($item) ? trim((string) $item) : '',
                $logosRaw
            )));
        } else {
            $logos = [];
        }

        return [
            'logos' => $logos,
        ];
    }

    /**
     * Normalize the reviews showcase payload.
     */
    protected function normalizeReviewsShowcaseContent(array $content): array
    {
        $limit = $content['limit'] ?? null;
        $limit = is_numeric($limit) ? max(1, (int) $limit) : null;

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'limit' => $limit,
        ];
    }

    /**
     * Normalize the our-work showcase payload.
     */
    protected function normalizeOurWorkShowcaseContent(array $content): array
    {
        $limit = $content['limit'] ?? null;
        $limit = is_numeric($limit) ? max(1, (int) $limit) : null;

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'visit_label' => $content['visit_label'] ?? null,
            'limit' => $limit,
        ];
    }

    /**
     * Normalize the hosting pricing showcase payload.
     */
    protected function normalizeHostingPricingShowcaseContent(array $content): array
    {
        return [
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'button_label' => trim((string) ($content['button_label'] ?? __('Choose Now'))),
            'visible_category_ids' => collect($content['visible_category_ids'] ?? [])
                ->map(function ($id) {
                    if (is_array($id)) {
                        return null;
                    }

                    $id = is_string($id) ? trim($id) : $id;

                    return is_numeric($id) ? (int) $id : null;
                })
                ->filter(fn($id) => $id && $id > 0)
                ->values()
                ->all(),
        ];
    }

    /**
     * Normalize the domains showcase payload.
     */
    protected function normalizeDomainsShowcaseContent(array $content): array
    {
        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'search_heading' => trim((string) ($content['search_heading'] ?? '')),
            'description' => $content['description'] ?? null,
            'input_placeholder' => trim((string) ($content['input_placeholder'] ?? '')),
            'primary_button' => [
                'label' => $content['primary_button_label']
                    ?? ($content['primary_button']['label'] ?? __('Search')),
                'url' => $content['primary_button_url']
                    ?? ($content['primary_button']['url'] ?? route('domains.page', [], false)),
                'new_tab' => false,
            ],
        ];
    }

    /**
     * Normalize the templates slider showcase payload.
     */
    protected function normalizeTemplatesSliderShowcaseContent(array $content): array
    {
        $limit = isset($content['limit']) && is_numeric($content['limit']) ? (int) $content['limit'] : 6;

        if ($limit <= 0) {
            $limit = 6;
        }

        return [
            'brand_prefix' => $content['brand_prefix'] ?? null,
            'brand_suffix' => $content['brand_suffix'] ?? null,
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'buy_label' => trim((string) ($content['buy_label'] ?? __('Buy Now'))),
            'preview_label' => trim((string) ($content['preview_label'] ?? __('Live Preview'))),
            'limit' => $limit,
        ];
    }

    /**
     * Normalize the templates listing showcase payload.
     */
    protected function normalizeTemplatesListingShowcaseContent(array $content): array
    {
        $itemsPerPage = isset($content['items_per_page']) && is_numeric($content['items_per_page'])
            ? (int) $content['items_per_page']
            : 12;

        if ($itemsPerPage <= 0) {
            $itemsPerPage = 12;
        }

        return [
            'breadcrumb_label' => trim((string) ($content['breadcrumb_label'] ?? __('Templates'))),
            'title' => $content['title'] ?? null,
            'description' => $content['description'] ?? null,
            'all_categories_label' => trim((string) ($content['all_categories_label'] ?? __('All Hosting'))),
            'type_label' => trim((string) ($content['type_label'] ?? __('Type'))),
            'best_sellers_label' => trim((string) ($content['best_sellers_label'] ?? __('Best Sellers'))),
            'price_label' => trim((string) ($content['price_label'] ?? __('Price'))),
            'buy_label' => trim((string) ($content['buy_label'] ?? __('Buy Now'))),
            'preview_label' => trim((string) ($content['preview_label'] ?? __('Live Preview'))),
            'items_per_page' => $itemsPerPage,
        ];
    }

    /**
     * Normalize pricing category tabs.
     *
     * @return array<int, array{label:string,key:string}>
     */
    protected function normalizeHostingPricingCategories(mixed $categoriesRaw): array
    {
        if (! is_array($categoriesRaw)) {
            return [];
        }

        return collect($categoriesRaw)
            ->map(function ($item): ?array {
                if (is_string($item)) {
                    $label = trim($item);
                    $key = $this->sanitizeHostingPricingCategoryKey($label);
                } elseif (is_array($item)) {
                    $label = trim((string) ($item['label'] ?? $item['title'] ?? $item['name'] ?? ''));
                    $key = $this->sanitizeHostingPricingCategoryKey(
                        $item['key'] ?? $item['slug'] ?? $item['value'] ?? $label
                    );
                } else {
                    return null;
                }

                if ($label === '' || $key === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'key' => $key,
                ];
            })
            ->filter()
            ->unique('key')
            ->values()
            ->all();
    }

    /**
     * Normalize pricing plans with stable CTA and feature payloads.
     *
     * @return array<int, array{category:string,title:string,features:array<int,string>,button:array{label:?string,url:?string,new_tab:bool}}>
     */
    protected function normalizeHostingPricingPlans(mixed $plansRaw): array
    {
        if (! is_array($plansRaw)) {
            return [];
        }

        return collect($plansRaw)
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $title = trim((string) ($item['title'] ?? $item['name'] ?? $item['label'] ?? ''));
                $category = $this->sanitizeHostingPricingCategoryKey(
                    $item['category'] ?? $item['category_key'] ?? $item['tab'] ?? ''
                );

                if ($title === '' || $category === '') {
                    return null;
                }

                $button = is_array($item['button'] ?? null) ? $item['button'] : [];

                return [
                    'category' => $category,
                    'title' => $title,
                    'features' => $this->normalizeHostingPricingPlanFeatures(
                        $item['features'] ?? ($item['features_textarea'] ?? [])
                    ),
                    'button' => [
                        'label' => $item['button_label'] ?? ($button['label'] ?? null),
                        'url' => $item['button_url'] ?? ($button['url'] ?? null),
                        'new_tab' => filter_var(
                            $item['button_new_tab'] ?? ($button['new_tab'] ?? false),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Normalize line-based plan features.
     *
     * @return array<int, string>
     */
    protected function normalizeHostingPricingPlanFeatures(mixed $featuresRaw): array
    {
        if (is_array($featuresRaw)) {
            return collect($featuresRaw)
                ->map(function ($item): string {
                    if (is_array($item)) {
                        return trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                    }

                    return is_scalar($item) ? trim((string) $item) : '';
                })
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split("/\r\n|\r|\n/", (string) $featuresRaw))
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Normalize line-based text items into a stable string array.
     *
     * @return array<int, string>
     */
    protected function normalizeLineTextItems(mixed $itemsRaw): array
    {
        if (is_array($itemsRaw)) {
            return collect($itemsRaw)
                ->map(function ($item): string {
                    if (is_array($item)) {
                        return trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                    }

                    return is_scalar($item) ? trim((string) $item) : '';
                })
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split("/\r\n|\r|\n/", (string) $itemsRaw))
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Create a stable key for a pricing category.
     */
    protected function sanitizeHostingPricingCategoryKey(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return (string) Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-');
    }

    /**
     * Convert campaign features into stable structured items.
     *
     * @return array<int, array{text: string, icon_source: string, icon: string|null, icon_svg: string|null, icon_media: int|string|null}>
     */
    protected function normalizeCampaignFeatureItems(mixed $featuresRaw): array
    {
        if (! is_array($featuresRaw)) {
            return collect(preg_split("/\r\n|\r|\n/", (string) $featuresRaw))
                ->map(fn($item) => trim((string) $item))
                ->filter()
                ->values()
                ->map(fn($item) => [
                    'text' => $item,
                    'icon_source' => 'class',
                    'icon' => null,
                    'icon_svg' => null,
                    'icon_media' => null,
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
                    ...$this->normalizeStructuredIconPayload($item),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Normalize class/svg/media icon payloads into one stable structure.
     *
     * @return array{icon_source:string,icon:?string,icon_svg:?string,icon_media:int|string|null}
     */
    protected function normalizeStructuredIconPayload(array $item): array
    {
        $iconClass = $this->sanitizeFeatureIconClass($item['icon'] ?? null);
        $iconSvg = $this->sanitizeInlineSvg($item['icon_svg'] ?? null);
        $iconMedia = $this->sanitizeMediaReference($item['icon_media'] ?? null);

        $requestedSource = trim((string) ($item['icon_source'] ?? ''));
        $source = in_array($requestedSource, ['class', 'media'], true) ? $requestedSource : null;

        if ($source === 'media' && ($iconMedia === null || $iconMedia === '')) {
            $source = null;
        }

        if ($source === 'class' && ! $iconClass) {
            $source = null;
        }

        if (! $source) {
            if ($iconMedia !== null && $iconMedia !== '') {
                $source = 'media';
            } else {
                $source = 'class';
            }
        }

        return [
            'icon_source' => $source,
            'icon' => $iconClass,
            'icon_svg' => null,
            'icon_media' => $iconMedia,
        ];
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
     * Keep inline SVG safe enough for admin-authored section icons.
     */
    protected function sanitizeInlineSvg(mixed $svg): ?string
    {
        $value = trim((string) $svg);

        if ($value === '' || ! preg_match('/<svg\b/i', $value)) {
            return null;
        }

        $value = preg_replace('/<\?(?:xml|php).*?\?>/is', '', $value) ?? '';
        $value = preg_replace('/<!DOCTYPE[^>]*>/i', '', $value) ?? '';
        $value = preg_replace('/<(script|style|foreignObject)\b.*?<\/\1>/is', '', $value) ?? '';
        $value = preg_replace('/\son[a-zA-Z-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/\s(?:href|xlink:href)\s*=\s*(?:"\s*javascript:[^"]*"|\'\s*javascript:[^\']*\'|javascript:[^\s>]+)/i', '', $value) ?? '';

        if (preg_match('/<svg\b[\s\S]*<\/svg>/i', $value, $matches)) {
            $value = $matches[0];
        }
        $value = strip_tags(
            $value,
            '<svg><g><path><circle><rect><line><polyline><polygon><ellipse><defs><clipPath><mask><use><linearGradient><radialGradient><stop><title><desc>'
        );
        $value = trim($value);

        if ($value === '' || ! preg_match('/^<svg\b/i', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * Normalize media references from the icon picker.
     */
    protected function sanitizeMediaReference(mixed $value): int|string|null
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Copy shared non-translatable fields across locales for section types that need it.
     */
    protected function syncSharedSectionContent(string $type, array $translations): array
    {
        if (! in_array($type, ['hero_campaign', 'programming_showcase', 'mobile_app_showcase', 'design_showcase', 'digital_marketing_showcase', 'tech_stack_showcase'], true) || $translations === []) {
            return $translations;
        }

        $sharedKeys = match ($type) {
            'mobile_app_showcase' => ['image_one', 'image_two', 'image_three'],
            'design_showcase' => ['image_one', 'image_two', 'image_three', 'image_four', 'image_five', 'image_six'],
            'digital_marketing_showcase' => ['image_one', 'image_two'],
            'tech_stack_showcase' => ['logos'],
            default => ['media_url'],
        };

        foreach ($sharedKeys as $sharedKey) {
            $sharedValue = collect($translations)
                ->map(fn($translation) => data_get($translation, "content.$sharedKey"))
                ->first(fn($value) => ! is_null($value) && $value !== '');

            if ($sharedValue === null || $sharedValue === '') {
                continue;
            }

            foreach ($translations as $key => $translation) {
                $content = is_array($translation['content'] ?? null) ? $translation['content'] : [];
                $content[$sharedKey] = $sharedValue;
                $translations[$key]['content'] = $content;
            }
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
     * Combined section library catalog for the standalone create screen and
     * the workspace quick-add drawer.
     *
     * Legacy section types keep their current cards. Active, visible section
     * definitions are appended as parallel cards that submit an explicit
     * section_definition_id plus the definition section_key as the type.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function sectionLibraryTypes(): array
    {
        $libraryTypes = $this->availableSectionTypes();

        if (! Schema::hasTable('section_definitions')) {
            return $libraryTypes;
        }

        $definitions = SectionDefinition::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($definitions as $definition) {
            $definitionKey = trim((string) $definition->section_key);

            if ($definitionKey === '' || isset($libraryTypes[$definitionKey])) {
                continue;
            }

            $libraryTypes[$definitionKey] = [
                'type' => $definitionKey,
                'label' => $definition->label,
                'description' => $definition->description ?: __('Definition-driven section'),
                'category' => $definition->category ?: 'other',
                'preview' => data_get($definition->settings, 'preview'),
                'section_definition_id' => (int) $definition->id,
                'source' => 'definition',
            ];
        }

        return $libraryTypes;
    }

    /**
     * Allow both legacy types and active section-definition keys to pass
     * through the current save pipeline.
     *
     * The library UI now submits explicit section_definition_id values for
     * definition-driven cards, but the canonical type key still needs to pass
     * validation for both create and quick-add flows.
     *
     * @return array<int, string>
     */
    protected function allowedSectionTypeKeys(?Section $currentSection = null): array
    {
        $definitionKeys = Schema::hasTable('section_definitions')
            ? SectionDefinition::query()
                ->where('is_active', true)
                ->pluck('section_key')
                ->map(fn ($key) => (string) $key)
                ->filter()
                ->values()
                ->all()
            : [];

        return collect(array_merge(
            $this->availableSectionTypeKeys(),
            $definitionKeys,
            $currentSection ? [(string) $currentSection->type] : [],
        ))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Normalize the section-library selection into a canonical type and an
     * explicit definition link when a definition-driven card was chosen.
     *
     * @return array{type: string, section_definition_id: int|null, section_definition: SectionDefinition|null}
     */
    protected function normalizeSectionCreateSelection(string $type, ?int $sectionDefinitionId = null): array
    {
        $type = trim($type);
        $sectionDefinition = $this->resolveVisibleLibrarySectionDefinition($sectionDefinitionId);

        if ($sectionDefinition instanceof SectionDefinition) {
            return [
                'type' => (string) $sectionDefinition->section_key,
                'section_definition_id' => $this->sectionDefinitionColumnAvailable()
                    ? (int) $sectionDefinition->id
                    : null,
                'section_definition' => $sectionDefinition,
            ];
        }

        return [
            'type' => $type,
            'section_definition_id' => $this->resolveSectionDefinitionIdForType($type),
            'section_definition' => null,
        ];
    }

    /**
     * Resolve an explicit section-definition selection from the library.
     *
     * Creation flows only allow active, visible definitions so the UI cannot
     * bind new sections to retired or hidden blueprints.
     */
    protected function resolveVisibleLibrarySectionDefinition(?int $sectionDefinitionId): ?SectionDefinition
    {
        if (! $sectionDefinitionId || ! Schema::hasTable('section_definitions')) {
            return null;
        }

        return SectionDefinition::query()
            ->whereKey($sectionDefinitionId)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->first();
    }

    /**
     * Build the explicit FK payload when the create flow already normalized
     * the selection and no legacy inference is needed.
     *
     * @return array<string, int|null>
     */
    protected function sectionDefinitionLinkAttributes(?int $sectionDefinitionId): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $sectionDefinitionId,
        ];
    }

    /**
     * Keep relational linking explicit for definition-driven sections while
     * leaving legacy section types unlinked.
     *
     * The current create/update UI still submits a type key, so this method
     * performs a one-time lookup only when the submitted type is not part of
     * the legacy registry. Runtime rendering/editor behavior no longer depends
     * on that string match once the foreign key is stored.
     *
     * @return array<string, int|null>
     */
    protected function sectionDefinitionAttributesForType(string $type, ?Section $currentSection = null): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $this->resolveSectionDefinitionIdForType($type, $currentSection),
        ];
    }

    /**
     * Preserve any existing explicit definition link when duplicating a
     * definition-driven section instance.
     *
     * @return array<string, int|null>
     */
    protected function duplicatedSectionDefinitionAttributes(Section $section): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $section->section_definition_id,
        ];
    }

    /**
     * Resolve the definition id that should be linked to a section instance.
     *
     * Legacy types intentionally stay unlinked. New definition-driven types
     * are resolved once at write time so runtime behavior can rely on the
     * foreign key instead of fragile type-key matching.
     */
    protected function resolveSectionDefinitionIdForType(string $type, ?Section $currentSection = null): ?int
    {
        $type = trim($type);

        if ($currentSection instanceof Section && $currentSection->section_definition_id) {
            $currentSection->loadMissing('sectionDefinition');

            if ((string) ($currentSection->sectionDefinition?->section_key ?? '') === $type) {
                return (int) $currentSection->section_definition_id;
            }
        }

        if ($type === '' || array_key_exists($type, $this->availableSectionTypes())) {
            return null;
        }

        $definitionId = SectionDefinition::query()
            ->where('section_key', $type)
            ->where('is_active', true)
            ->value('id');

        return $definitionId ? (int) $definitionId : null;
    }

    /**
     * Guard write-time FK usage during zero-downtime deploys before the new
     * sections-table migration has been applied everywhere.
     */
    protected function sectionDefinitionColumnAvailable(): bool
    {
        return Schema::hasColumn('sections', 'section_definition_id');
    }

    /**
     * Render the shared sidebar outline card for async quick-add responses.
     */
    protected function renderSectionOutlineItem(Page $page, Section $section): string
    {
        return view('dashboard.pages.sections.partials.outline-item', [
            'page' => $page,
            'section' => $section,
            'sectionTypes' => $this->availableSectionTypes(),
            'currentLocale' => app()->getLocale(),
            'selectedSectionId' => $section->id,
        ] + $this->workspaceViewData($page))->render();
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

        $sectionTypes = $this->availableSectionTypes();
        $editorState = app(SectionEditorDataFactory::class)->make($section, $languages, $sectionTypes);
        $workspaceViewData = $this->workspaceViewData($page);
        $sidebarEditor = app(SectionSidebarEditorViewDataFactory::class)->build(
            $section,
            $sectionTypes,
            (string) ($workspaceViewData['workspaceMode'] ?? $this->workspaceMode()),
            (string) ($workspaceViewData['workspaceRoutePrefix'] ?? $this->workspaceRoutePrefix()),
            is_array($workspaceViewData['workspaceRouteBaseParameters'] ?? null)
                ? $workspaceViewData['workspaceRouteBaseParameters']
                : $this->workspaceBaseRouteParameters($page),
        );

        return [
            'page' => $page,
            'section' => $section,
            'languages' => $languages,
            'sectionTypes' => $sectionTypes,
            'editorState' => $editorState,
            'sidebarEditor' => $sidebarEditor,
        ] + $workspaceViewData;
    }
}
