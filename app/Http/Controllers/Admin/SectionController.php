<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Models\Section;
use App\Models\SectionTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * List all sections for a given marketing page.
     *
     * Route example:
     *  GET /admin/pages/{page}/sections
     *  Name: dashboard.pages.sections.index
     */
    public function index(Page $page)
    {
        // Eager-load translations for better performance
        $sections = Section::with('translations')
            ->where('page_id', $page->id)
            ->orderBy('order')
            ->get();

        return view('dashboard.pages.sections.index', [
            'page'     => $page,
            'sections' => $sections,
        ]);
    }

    /**
     * Show the "Create Section" form for a specific page.
     *
     * Route example:
     *  GET /admin/pages/{page}/sections/create
     *  Name: dashboard.pages.sections.create
     */
    public function create(Page $page)
    {
        // Load active languages (used for multi-locale content)
        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        // Pre-calc a suggested "order" (append to the end)
        $nextOrder = Section::where('page_id', $page->id)->max('order');
        $nextOrder = is_null($nextOrder) ? 1 : $nextOrder + 1;

        // Available section types for the Page Builder
        $sectionTypes = $this->availableSectionTypes();

        // Make sure we have translations loaded for page title usage in breadcrumb, etc.
        $page->loadMissing('translations');

        return view('dashboard.pages.sections.create', [
            'page'         => $page,
            'languages'    => $languages,
            'sectionTypes' => $sectionTypes,
            'nextOrder'    => $nextOrder,
        ]);
    }

    /**
     * Store a newly created section and its translations for a specific page.
     *
     * Route example:
     *  POST /admin/pages/{page}/sections
     *  Name: dashboard.pages.sections.store
     *
     * Expected request shape (simplified):
     *  - type, variant, order, is_active
     *  - translations[LOCALE][locale]
     *  - translations[LOCALE][title]
     *  - translations[LOCALE][content] = array (structure depends on section type)
     */
    public function store(Request $request, Page $page)
    {
        // Basic validation for core section fields + translations
        $validated = $request->validate([
            'type'      => 'required|string|max:100',
            'variant'   => 'nullable|string|max:100',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            // Translations (per locale)
            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $page) {
            // Compute display order (fallback to "append at end")
            $order = $validated['order'] ?? null;

            if ($order === null) {
                $maxOrder = Section::where('page_id', $page->id)->max('order');
                $order    = is_null($maxOrder) ? 1 : $maxOrder + 1;
            }

            // Create the base section row
            $section = Section::create([
                'page_id'   => $page->id,
                'type'      => $validated['type'],
                'variant'   => $validated['variant'] ?? null,
                'order'     => $order,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            // Create translations per locale
            foreach ($validated['translations'] as $t) {
                $locale  = $t['locale'];
                $title   = $t['title'] ?? null;
                $content = $t['content'] ?? [];

                // 🔹 Normalize content structure per section type (hero_default ...etc)
                $normalizedContent = $this->normalizeContentByType(
                    $validated['type'],
                    $content
                );

                SectionTranslation::create([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                    'title'      => $title,
                    // "content" is a JSON column casted to array in the model,
                    // so we can safely store any structured array here
                    'content'    => $normalizedContent,
                ]);
            }
        });

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('success', 'Section has been created successfully.');
    }

    /**
     * Show the "Edit Section" form for a specific page section.
     *
     * Route example:
     *  GET /admin/pages/{page}/sections/{section}/edit
     *  Name: dashboard.pages.sections.edit
     */
    public function edit(Page $page, Section $section)
    {
        // Safety check: ensure the section really belongs to this page
        if ($section->page_id !== $page->id) {
            abort(404);
        }

        // Load translations for the section and page
        $section->load('translations');
        $page->loadMissing('translations');

        // Active languages for multi-locale content
        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        // Section types registry
        $sectionTypes = $this->availableSectionTypes();

        return view('dashboard.pages.sections.edit', [
            'page'         => $page,
            'section'      => $section,
            'languages'    => $languages,
            'sectionTypes' => $sectionTypes,
        ]);
    }

    /**
     * Update an existing section and its translations.
     *
     * Route example:
     *  PUT/PATCH /admin/pages/{page}/sections/{section}
     *  Name: dashboard.pages.sections.update
     */
    public function update(Request $request, Page $page, Section $section)
    {
        // Safety check: ensure the section really belongs to this page
        if ($section->page_id !== $page->id) {
            abort(404);
        }

        // Validate inputs similar to "store", but allow partial updates
        $validated = $request->validate([
            'type'      => 'required|string|max:100',
            'variant'   => 'nullable|string|max:100',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $section) {
            // Update base section data
            $section->update([
                'type'      => $validated['type'],
                'variant'   => $validated['variant'] ?? null,
                'order'     => $validated['order'] ?? $section->order,
                // ✅ keep current value if checkbox not present
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : $section->is_active,
            ]);

            $translationsData = $validated['translations'];

            // Collect locales that we are updating
            $locales = [];

            foreach ($translationsData as $t) {
                $locale        = $t['locale'];
                $locales[]     = $locale;
                $title         = $t['title'] ?? null;
                $content       = $t['content'] ?? [];

                // 🔹 Normalize content structure per section type (hero_default ...etc)
                $normalizedContent = $this->normalizeContentByType(
                    $validated['type'],
                    $content
                );

                // Find existing translation or create a new one
                $translation = SectionTranslation::firstOrNew([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                ]);

                $translation->title   = $title;
                $translation->content = $normalizedContent;
                $translation->save();
            }

            // Optional: delete translations for locales that are no longer present
            if (! empty($locales)) {
                SectionTranslation::where('section_id', $section->id)
                    ->whereNotIn('locale', $locales)
                    ->delete();
            }
        });

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('success', 'Section has been updated successfully.');
    }

    /**
     * Delete a section (and cascade delete translations via FK, if configured).
     *
     * Route example:
     *  DELETE /admin/pages/{page}/sections/{section}
     *  Name: dashboard.pages.sections.destroy
     */
    public function destroy(Page $page, Section $section)
    {
        // Safety check: ensure the section really belongs to this page
        if ($section->page_id !== $page->id) {
            abort(404);
        }

        $section->delete();

        return redirect()
            ->route('dashboard.pages.sections.index', $page)
            ->with('success', 'Section has been deleted successfully.');
    }

    /**
     * Helper: list of available section types for the Page Builder.
     *
     * Keys here MUST match the "type" values used in front/pages/page.blade.php:
     *  hero, hero_default, features, features-2, features-3, cta, services,
     *  templates, works, home-works, testimonials, blog, banner,
     *  search-domain, templates-pages, hosting-plans, faq
     */
    protected function availableSectionTypes(): array
    {
        return [
            'hero_default' => [
                'label'       => 'Hero - Default',
                'description' => 'Main hero section with title, subtitle, buttons, features and media.',
            ],
            'hero' => [
                'label'       => 'Hero (Legacy)',
                'description' => 'Legacy hero section, useful for old layouts.',
            ],
            'features' => [
                'label'       => 'Features',
                'description' => 'List of features with simple text items or bullets.',
            ],
            'features-2' => [
                'label'       => 'Features v2',
                'description' => 'Feature cards with icon, title and description.',
            ],
            'features-3' => [
                'label'       => 'Features v3',
                'description' => 'Another layout variation for feature cards.',
            ],
            'services' => [
                'label'       => 'Services',
                'description' => 'Listing of services in cards with titles and descriptions.',
            ],
            'templates' => [
                'label'       => 'Templates',
                'description' => 'Showcase a limited number of Palgoals templates.',
            ],
            'works' => [
                'label'       => 'Works / Portfolio',
                'description' => 'Section to highlight portfolio or projects.',
            ],
            'home-works' => [
                'label'       => 'Home Works CTA',
                'description' => 'Special home section that links to portfolio.',
            ],
            'testimonials' => [
                'label'       => 'Testimonials',
                'description' => 'Client testimonials with name, role, and comment.',
            ],
            'blog' => [
                'label'       => 'Blog',
                'description' => 'Section to promote latest blog posts.',
            ],
            'banner' => [
                'label'       => 'Banner',
                'description' => 'Simple full-width banner with title and subtitle.',
            ],
            'search-domain' => [
                'label'       => 'Domain Search',
                'description' => 'Domain search hero for TLDs with fallback pricing.',
            ],
            'templates-pages' => [
                'label'       => 'Templates Listing Page',
                'description' => 'Full templates listing with filters and categories.',
            ],
            'hosting-plans' => [
                'label'       => 'Hosting Plans',
                'description' => 'Listing of hosting plans for a specific category.',
            ],
            'faq' => [
                'label'       => 'FAQ',
                'description' => 'Frequently asked questions with collapsible answers.',
            ],
            'cta' => [
                'label'       => 'Call-to-Action',
                'description' => 'Section with one main CTA button and short text.',
            ],
        ];
    }

    /**
     * Normalize translation "content" structure depending on section type.
     *
     * This is where we convert raw form content into a clean JSON schema
     * used in the frontend components (hero_default, features, etc.).
     *
     * NOTE:
     *  - You can keep forms simple (content[eyebrow], content[primary_button_label]...),
     *    and we reshape here to nested arrays when needed.
     */
    protected function normalizeContentByType(string $type, array $content): array
    {
        switch ($type) {
            case 'hero_default':
                // "features_raw" is optional textarea (one feature per line)
                $featuresRaw = $content['features_raw'] ?? ($content['features'] ?? '');
                if (is_array($featuresRaw)) {
                    $features = array_values(array_filter(array_map('trim', $featuresRaw)));
                } else {
                    $features = array_values(array_filter(
                        array_map('trim', preg_split("/\r\n|\r|\n/", (string) $featuresRaw))
                    ));
                }

                return [
                    'eyebrow'  => $content['eyebrow'] ?? null,
                    'title'    => $content['title'] ?? null,
                    'subtitle' => $content['subtitle'] ?? null,

                    'primary_button' => [
                        'label' => $content['primary_button_label']
                            ?? ($content['primary_button']['label'] ?? null),
                        'url'   => $content['primary_button_url']
                            ?? ($content['primary_button']['url'] ?? null),
                    ],

                    'secondary_button' => [
                        'label' => $content['secondary_button_label']
                            ?? ($content['secondary_button']['label'] ?? null),
                        'url'   => $content['secondary_button_url']
                            ?? ($content['secondary_button']['url'] ?? null),
                    ],

                    'features'   => $features,
                    'media_type' => $content['media_type'] ?? 'image',
                    'media_url'  => $content['media_url'] ?? null,
                ];

                // لاحقًا: أضف هنا normalize لباقي الأنواع (features, cta, hosting-plans ... إلخ)

            default:
                // Default: return as-is (no special schema)
                return $content;
        }
    }
}
