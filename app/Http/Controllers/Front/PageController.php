<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * ------------------------------------------------------------------
     * Show the marketing homepage "/"
     * ------------------------------------------------------------------
     *
     * Logic:
     *  - Find the active marketing page marked as "is_home = 1".
     *  - If none is marked as home, fall back to the first active
     *    marketing page.
     *  - Render resources/views/front/pages/page.blade.php which will
     *    handle SEO + sections rendering.
     */
    public function home(): View
    {
        $locale = app()->getLocale();

        // Try to get the marketing homepage
        $page = Page::with(['translations', 'sections.translations', 'builderStructure'])
            ->where('context', 'marketing')
            ->where('is_active', true)
            ->where('is_home', true)
            ->first();

        // Fallback: first active marketing page if no homepage is set
        if (! $page) {
            $page = Page::with(['translations', 'sections.translations', 'builderStructure'])
                ->where('context', 'marketing')
                ->where('is_active', true)
                ->orderBy('id', 'asc')
                ->firstOrFail();
        }

        // Optional guard: if for any reason it's not active or not marketing, abort
        if (! $page->is_active || $page->context !== 'marketing') {
            abort(404);
        }

        return view('front.pages.page', [
            'page'   => $page,
            'locale' => $locale,
        ]);
    }

    /**
     * ------------------------------------------------------------------
     * Show a marketing CMS page by slug: "/{slug}"
     * ------------------------------------------------------------------
     *
     * This is used by the route:
     *   Route::get('/{slug}', [FrontPageController::class, 'show'])
     *
     * Behavior:
     *  - Search for a marketing, active page that has a translation
     *    whose "slug" matches the requested $slug.
     *  - We first try to match the current locale's translation.
     *  - If not found, we try any translation that matches this slug.
     *  - If we find a page but the canonical slug for the current locale
     *    is different, we do a 301 redirect to the canonical URL.
     *  - If nothing is found or page is not active/marketing → 404.
     */
    public function show($slug)
    {
        $locale = app()->getLocale();

        $baseQuery = Page::with([
            'translations',
            'sections' => function ($q) {
                $q->orderBy('order');
            },
            'sections.translations',
            'builderStructure',
        ])
            ->where('context', 'marketing')
            ->where('is_active', true);

        // Try current locale first
        $page = (clone $baseQuery)
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('locale', $locale)
                    ->where('slug', $slug);
            })
            ->first();

        // Fallback: any locale
        if (! $page) {
            $page = (clone $baseQuery)
                ->whereHas('translations', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                })
                ->firstOrFail();
        }

        // Canonical redirect if the slug for this locale differs
        $canonicalSlug = $page->translation($locale)?->slug;
        if ($canonicalSlug && $canonicalSlug !== $slug) {
            return redirect()->to('/' . ltrim($canonicalSlug, '/'), 301);
        }

        return view('front.pages.page', [
            'page'     => $page,
            'sections' => $page->sections,
        ]);
    }
}
