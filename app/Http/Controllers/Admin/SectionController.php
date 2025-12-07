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
        // Basic validation
        $validated = $request->validate([
            'type'      => 'required|string|max:100',
            'variant'   => 'nullable|string|max:100',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'                      => 'required|array',
            'translations.*.locale'             => 'required|string',
            'translations.*.title'              => 'nullable|string|max:255',
            'translations.*.content'            => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $page) {
            // ترتيب السكشن
            $order = $validated['order'] ?? null;
            if ($order === null) {
                $maxOrder = Section::where('page_id', $page->id)->max('order');
                $order    = is_null($maxOrder) ? 1 : $maxOrder + 1;
            }

            // إنشاء السكشن
            $section = Section::create([
                'page_id'   => $page->id,
                'type'      => $validated['type'],          // hero_default
                'variant'   => $validated['variant'] ?? null,
                'order'     => $order,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            // إنشاء الترجمات
            foreach ($validated['translations'] as $locale => $t) {
                $content = $t['content'] ?? [];

                // 👇 معالجة خاصة لـ Hero Default: تحويل الـ textarea → array
                // إذا السكشن من نوع hero_default
                if ($section->type === 'hero_default') {
                    // features_textarea → features[]
                    if (! empty($content['features_textarea'])) {
                        $lines = preg_split('/\r\n|\r|\n/', (string) $content['features_textarea']);
                        $features = collect($lines)
                            ->map(fn($line) => trim($line))
                            ->filter()
                            ->values()
                            ->all();

                        $content['features'] = $features;
                    }

                    // لا نريد تخزين الـ features_textarea في JSON
                    unset($content['features_textarea']);
                }

                SectionTranslation::create([
                    'section_id' => $section->id,
                    'locale'     => $t['locale'],
                    'title'      => $t['title'] ?? null,
                    'content'    => $content,
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
        if ($section->page_id !== $page->id) {
            abort(404);
        }

        $validated = $request->validate([
            'type'      => 'required|string|max:100',
            'variant'   => 'nullable|string|max:100',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'                      => 'required|array',
            'translations.*.locale'             => 'required|string',
            'translations.*.title'              => 'nullable|string|max:255',
            'translations.*.content'            => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $section) {
            // تحديث السكشن نفسه
            $section->update([
                'type'      => $validated['type'],
                'variant'   => $validated['variant'] ?? null,
                'order'     => $validated['order'] ?? $section->order,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $translationsData = $validated['translations'];
            $locales          = [];

            foreach ($translationsData as $locale => $t) {
                $locales[] = $locale;
                $content   = $t['content'] ?? [];

                // 👇 نفس المعالجة الخاصة لـ Hero Default
                if ($section->type === 'hero_default') {
                    if (! empty($content['features_textarea'])) {
                        $lines = preg_split('/\r\n|\r|\n/', (string) $content['features_textarea']);
                        $features = collect($lines)
                            ->map(fn($line) => trim($line))
                            ->filter()
                            ->values()
                            ->all();

                        $content['features'] = $features;
                    }
                    unset($content['features_textarea']);
                }

                $translation = SectionTranslation::firstOrNew([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                ]);

                $translation->title   = $t['title'] ?? null;
                $translation->content = $content;
                $translation->save();
            }

            // (اختياري) حذف الترجمات للغات التي لم تعد موجودة في الفورم
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
                'type'        => 'hero_default',
                'label'       => 'Hero – Default',
                'description' => 'Main hero with title, subtitle, 2 buttons, and media.',
                'category'    => 'hero',
                'preview'     => 'assets/admin/sections/hero-default.png',
            ],

            'hero_minimal' => [
                'type'        => 'hero_minimal',
                'label'       => 'Hero – Minimal',
                'description' => 'Simple hero with title and single CTA.',
                'category'    => 'hero',
                'preview'     => 'assets/admin/sections/hero-minimal.png',
            ],

            'features_grid' => [
                'type'        => 'features_grid',
                'label'       => 'Features Grid',
                'description' => '4–6 feature cards in responsive grid.',
                'category'    => 'features',
                'preview'     => 'assets/admin/sections/features-grid.png',
            ],

            'services_grid' => [
                'type'        => 'services_grid',
                'label'       => 'Services Grid',
                'description' => 'Services with icons and short description.',
                'category'    => 'services',
                'preview'     => 'assets/admin/sections/services-grid.png',
            ],

            'templates_showcase' => [
                'type'        => 'templates_showcase',
                'label'       => 'Templates Showcase',
                'description' => 'Palgoals templates in grid or slider.',
                'category'    => 'templates',
                'preview'     => 'assets/admin/sections/templates-showcase.png',
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
