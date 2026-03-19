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
                'media_type' => 'image',
                'media_url' => 'assets/tamplate/images/Fu.svg',
            ],

            'features_grid' => [
                'title'    => 'Why choose us',
                'subtitle' => 'Add your key benefits here.',
                'features' => [],
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

            'our_work_showcase' => [
                'brand_prefix' => 'PAL',
                'brand_suffix' => 'GOALS',
                'title' => 'OUR WORK',
                'description' => 'Customer opinions drive innovation, trust, and growth.',
                'visit_label' => 'Visit',
                'limit' => 6,
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
            'media_type' => $content['media_type'] ?? 'image',
            'media_url' => $content['media_url'] ?? null,
        ];
    }

    /**
     * Normalize the programming showcase payload.
     */
    protected function normalizeProgrammingContent(array $content): array
    {
        $outputsRaw = $content['outputs_textarea'] ?? ($content['outputs'] ?? '');

        if (is_array($outputsRaw)) {
            $outputs = array_values(array_filter(array_map(
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $outputsRaw
            )));
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
        return [
            'title' => $content['title'] ?? null,
            'subtitle' => $content['subtitle'] ?? null,
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
            $services = array_values(array_filter(array_map(
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $servicesRaw
            )));
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
            $services = array_values(array_filter(array_map(
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $servicesRaw
            )));
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
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
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
     * Convert campaign features into stable structured items.
     *
     * @return array<int, array{text: string, icon_source: string, icon: string|null, icon_svg: string|null, icon_media: int|string|null}>
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
                ->map(fn ($translation) => data_get($translation, "content.$sharedKey"))
                ->first(fn ($value) => ! is_null($value) && $value !== '');

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
