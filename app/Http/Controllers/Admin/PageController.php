<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    /**
     * Display list of all marketing pages in the admin dashboard.
     */
    public function index()
    {
        $pages = Page::with('translations')
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
        $validated = $request->validate([
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
                     * $attribute looks like: "translations.ar.slug"
                     * We extract the locale part: "ar".
                     */
                    $parts  = explode('.', $attribute);
                    $locale = $parts[1] ?? null;

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
        ]);

        DB::transaction(function () use ($validated) {
            // Create the base Page record (context = marketing)
            $page = Page::create([
                'context'      => 'marketing',
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
                ]);
            }
        });

        return redirect()
            ->route('dashboard.pages.index')
            ->with('success', 'تم إنشاء الصفحة بنجاح.');
    }

    /**
     * Show form to edit an existing marketing page.
     */
    public function edit(Page $page)
    {
        if ($page->context !== 'marketing') {
            abort(404);
        }

        $page->load('translations');

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
        if ($page->context !== 'marketing') {
            abort(404);
        }

        $validated = $request->validate([
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
                function ($attribute, $value, $fail) use ($page) {
                    if (! $value) {
                        return;
                    }

                    $parts  = explode('.', $attribute);
                    $locale = $parts[1] ?? null;

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
        ]);

        DB::transaction(function () use ($validated, $page) {
            // Update main page flags
            $page->update([
                'is_active'    => (bool) ($validated['is_active'] ?? false),
                'is_home'      => (bool) ($validated['is_home'] ?? false),
                'published_at' => $validated['published_at'] ?? null,
            ]);

            // Update or create translations for each locale
            foreach ($validated['translations'] as $t) {
                PageTranslation::updateOrCreate(
                    [
                        // If "id" exists → update that record, otherwise create new
                        'id' => $t['id'] ?? null,
                    ],
                    [
                        'page_id'          => $page->id,
                        'locale'           => $t['locale'],
                        'title'            => $t['title'],
                        'slug'             => $t['slug'] ?? null,
                        'meta_title'       => $t['meta_title'] ?? null,
                        'meta_description' => $t['meta_description'] ?? null,
                        'meta_keywords'    => $t['meta_keywords'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->back()
            ->with('success', 'تم تحديث الصفحة بنجاح.');
    }

    /**
     * Delete a marketing page and all its relations.
     */
    public function destroy(Page $page)
    {
        if ($page->context !== 'marketing') {
            abort(404);
        }

        $page->delete();

        return redirect()
            ->back()
            ->with('success', 'تم حذف الصفحة.');
    }

    /**
     * Toggle activation (on/off) for a marketing page.
     */
    public function toggleActive(Page $page)
    {
        if ($page->context !== 'marketing') {
            abort(404);
        }

        $page->update(['is_active' => ! $page->is_active]);

        return redirect()
            ->back()
            ->with('success', 'تم تحديث حالة الصفحة.');
    }

    /**
     * Set this marketing page as the homepage.
     */
    public function setHome(Page $page)
    {
        if ($page->context !== 'marketing') {
            abort(404);
        }

        DB::transaction(function () use ($page) {
            // Unset home from all marketing pages
            Page::where('context', 'marketing')->update(['is_home' => false]);

            // Mark current page as home
            $page->update(['is_home' => true]);
        });

        return redirect()
            ->back()
            ->with('success', 'تم تعيين الصفحة كصفحة رئيسية.');
    }
}
