<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    /**
     * Display list of all marketing pages in the admin dashboard.
     */
    public function index()
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::with('translations')
            ->withCount('sections')
            ->where('context', 'marketing') // Only marketing-site pages
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('dashboard.pages.index', compact('pages'));
    }

    /**
     * Show form for creating a new marketing page.
     */
    public function create()
    {
        $this->authorize('create', Page::class);

        // Load all active languages to build translation tabs
        $languages = Language::where('is_active', true)
            ->orderBy('id') // You can switch to priority if the column exists
            ->get();

        // Default state for new page
        $defaultIsActive    = 0; // draft by default
        $defaultPublishedAt = now()->format('Y-m-d\TH:i');

        return view('dashboard.pages.create', [
            'languages'          => $languages,
            'defaultIsActive'    => $defaultIsActive,
            'defaultPublishedAt' => $defaultPublishedAt,
        ]);
    }

    /**
     * Store newly created marketing page + its translations.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Page::class);

        $validator = Validator::make($request->all(), [
            'builder_mode' => 'nullable|in:sections',
            'is_active'    => 'nullable|boolean',
            'is_home'      => 'nullable|boolean',
            'published_at' => 'nullable|date',

            'translations.*.locale' => 'required|string',
            'translations.*.title'  => 'required|string|max:255',

            // Slug: optional, but must be unique per locale if provided
            'translations.*.slug'   => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $value) {
                        return;
                    }

                    /**
                     * $attribute looks like: "translations.0.slug" (numeric index).
                     * We extract the numeric index then look up the locale from the
                     * submitted translations array, because the form sends a
                     * numerically-indexed array — not keyed by locale code.
                     */
                    $parts = explode('.', $attribute); // ['translations', '0', 'slug']
                    $index = $parts[1] ?? null;

                    $locale = $request->input("translations.{$index}.locale");

                    if (! $locale) {
                        return;
                    }

                    $exists = PageTranslation::where('slug', $value)
                        ->where('locale', $locale)
                        ->exists();

                    if ($exists) {
                        $fail("The slug '{$value}' already exists for locale '{$locale}'.");
                    }
                },
            ],

            'translations.*.meta_title'       => 'nullable|string|max:255',
            'translations.*.meta_description' => 'nullable|string|max:500',
            'translations.*.meta_keywords'    => 'nullable|string',
            'translations.*.og_image'         => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard.pages.create')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated) {
            // Create the base Page record (context = marketing)
            $page = Page::create([
                'context'      => 'marketing',
                'builder_mode' => 'sections',
                'is_active'    => (bool) ($validated['is_active'] ?? false),
                'is_home'      => (bool) ($validated['is_home'] ?? false),
                'published_at' => $validated['published_at'] ?? null,
            ]);

            // Create translation rows per locale
            foreach ($validated['translations'] as $t) {
                PageTranslation::create([
                    'page_id'          => $page->id,
                    'locale'           => $t['locale'],
                    'title'            => $t['title'],
                    'slug'             => $t['slug'] ?? null,
                    'meta_title'       => $t['meta_title'] ?? null,
                    'meta_description' => $t['meta_description'] ?? null,
                    'meta_keywords'    => $t['meta_keywords'] ?? null,
                    'og_image'         => $t['og_image'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('dashboard.pages.index')
            ->with('ok', t('dashboard.Page_Created_Successfully', 'Page created successfully.'));
    }

    /**
     * Show form to edit an existing marketing page.
     */
    public function edit(Page $page)
    {
        $this->authorize('update', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        $page->load('translations');
        $page->loadCount('sections');

        // Load all active languages to show per-locale fields
        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('dashboard.pages.edit', compact('page', 'languages'));
    }

    /**
     * Update page + its translations.
     */
    public function update(Request $request, Page $page)
    {
        $this->authorize('update', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'builder_mode' => 'nullable|in:sections',
            'is_active'    => 'nullable|boolean',
            'is_home'      => 'nullable|boolean',
            'published_at' => 'nullable|date',

            'translations.*.id'               => 'nullable|integer|exists:page_translations,id',
            'translations.*.locale'           => 'required|string',
            'translations.*.title'            => 'required|string',

            // Slug uniqueness validation on update (ignore current page)
            'translations.*.slug' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($page, $request) {
                    if (! $value) {
                        return;
                    }

                    // $attribute is "translations.0.slug" (numeric index)
                    $parts = explode('.', $attribute);
                    $index = $parts[1] ?? null;

                    $locale = $request->input("translations.{$index}.locale");

                    if (! $locale) {
                        return;
                    }

                    $exists = PageTranslation::where('slug', $value)
                        ->where('locale', $locale)
                        ->where('page_id', '!=', $page->id)
                        ->exists();

                    if ($exists) {
                        $fail("The slug '{$value}' already exists for locale '{$locale}'.");
                    }
                },
            ],

            'translations.*.meta_title'       => 'nullable|string',
            'translations.*.meta_description' => 'nullable|string',
            'translations.*.meta_keywords'    => 'nullable|string',
            'translations.*.og_image'         => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard.pages.edit', $page)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // P3: Ensure all submitted translation IDs belong to this page.
        $ownTranslationIds = $page->translations()->pluck('id')->all();
        foreach ($validated['translations'] as $t) {
            if (! empty($t['id']) && ! in_array((int) $t['id'], $ownTranslationIds, true)) {
                abort(403, 'Translation does not belong to this page.');
            }
        }

        DB::transaction(function () use ($validated, $page) {
            // Update main page flags
            $page->update([
                'builder_mode' => $validated['builder_mode'] ?? 'sections',
                'is_active'    => (bool) ($validated['is_active'] ?? false),
                'is_home'      => (bool) ($validated['is_home'] ?? false),
                'published_at' => $validated['published_at'] ?? null,
            ]);

            // P2: Match on page_id + locale to prevent duplicate rows per locale.
            foreach ($validated['translations'] as $t) {
                $page->translations()->updateOrCreate(
                    [
                        'locale' => $t['locale'],
                    ],
                    [
                        'title'            => $t['title'],
                        'slug'             => $t['slug'] ?? null,
                        'meta_title'       => $t['meta_title'] ?? null,
                        'meta_description' => $t['meta_description'] ?? null,
                        'meta_keywords'    => $t['meta_keywords'] ?? null,
                        'og_image'         => $t['og_image'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('dashboard.pages.edit', $page)
            ->with('ok', t('dashboard.Page_Updated_Successfully', 'Page updated successfully.'));
    }

    /**
     * Delete a marketing page and all its relations.
     */
    public function destroy(Page $page)
    {
        $this->authorize('delete', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        $page->delete();

        return redirect()
            ->route('dashboard.pages.index')
            ->with('ok', t('dashboard.Page_Deleted_Successfully', 'Page deleted successfully.'));
    }

    /**
     * Toggle activation (on/off) for a marketing page.
     */
    public function toggleActive(Page $page)
    {
        $this->authorize('toggleActive', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        $page->update(['is_active' => ! $page->is_active]);

        return redirect()
            ->route('dashboard.pages.index')
            ->with('ok', t('dashboard.Page_Status_Updated_Successfully', 'Page status updated successfully.'));
    }

    /**
     * Set this marketing page as the homepage.
     */
    public function setHome(Page $page)
    {
        $this->authorize('setHome', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        DB::transaction(function () use ($page) {
            // Unset home from all marketing pages
            Page::where('context', 'marketing')->update(['is_home' => false]);

            // Mark current page as home
            $page->update(['is_home' => true]);
        });

        return redirect()
            ->route('dashboard.pages.index')
            ->with('ok', t('dashboard.Page_Set_As_Homepage_Successfully', 'Page set as homepage successfully.'));
    }
    /**
     * Update the preferred builder mode for a marketing page.
     */
    public function updateBuilderMode(Request $request, Page $page)
    {
        $this->authorize('updateBuilderMode', $page);

        if (! $this->isMarketingContext($page)) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'builder_mode' => 'required|in:sections',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard.pages.edit', $page)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $page->update([
            'builder_mode' => $validated['builder_mode'],
        ]);

        return redirect()
            ->route('dashboard.pages.edit', $page)
            ->with('ok', t('dashboard.Builder_Mode_Updated_Successfully', 'Builder mode updated successfully.'));
    }

    /**
     * Determine whether the current request concerns a marketing-context page.
     */
    private function isMarketingContext(Page $page): bool
    {
        return $page->context === 'marketing';
    }
}
