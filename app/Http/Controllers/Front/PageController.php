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
        $page = Page::with(['translations', 'sections.translations'])
            ->where('context', 'marketing')
            ->where('is_active', true)
            ->where('is_home', true)
            ->first();

        // Fallback: first active marketing page if no homepage is set
        if (! $page) {
            $page = Page::with(['translations', 'sections.translations'])
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
        $page = $this->findPageBySlug($slug);

        return $this->renderResolvedPage($page, $slug);
    }

    /**
     * ------------------------------------------------------------------
     * Render the active marketing Page that owns a given builder-section
     * template, addressed by a fixed/stable requested slug rather than by
     * the page's own per-locale slug.
     * ------------------------------------------------------------------
     *
     * This exists for routes that must stay reachable at a constant URL
     * regardless of what slug the underlying builder Page currently has
     * per locale (e.g. GET /domains -> DomainSearchController::page()).
     *
     * It resolves the Page by content (an active Section whose
     * SectionDefinition exposes the given template key) instead of by
     * slug, then renders it through the exact same pipeline used by
     * show(): the same "front.pages.page" view and the same
     * canonical-slug redirect behavior, so both the fixed URL and the
     * page's own translated slug(s) render the same builder Page and the
     * same definition-driven section pipeline.
     *
     * Aborts with 404 if no active marketing page currently has an
     * active section backed by this template key.
     */
    public function showBySectionTemplate(string $templateKey, string $requestedSlug): RedirectResponse|View
    {
        $page = Page::with([
            'translations',
            'sections' => function ($q) {
                $q->orderBy('order');
            },
            'sections.translations',
        ])
            ->where('context', 'marketing')
            ->where('is_active', true)
            ->whereHas('sections', function ($q) use ($templateKey) {
                $q->where('is_active', true)
                    ->whereHas('sectionDefinition.templates', function ($q2) use ($templateKey) {
                        $q2->where('template_key', $templateKey)
                            ->where('section_templates.is_active', true);
                    });
            })
            ->first();

        if (! $page) {
            abort(404);
        }

        return $this->renderResolvedPage($page, $requestedSlug);
    }

    /**
     * Locate an active marketing page by its per-locale slug.
     *
     *  - We first try to match the current locale's translation.
     *  - If not found, we try any translation that matches this slug.
     */
    protected function findPageBySlug(string $slug): Page
    {
        $locale = app()->getLocale();

        $baseQuery = Page::with([
            'translations',
            'sections' => function ($q) {
                $q->orderBy('order');
            },
            'sections.translations',
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

        return $page;
    }

    /**
     * Render an already-resolved marketing Page through the shared
     * builder pipeline, issuing a redirect to its canonical slug for the
     * current locale when the requested slug differs.
     *
     * This must stay temporary because the canonical slug depends on
     * the current session locale and can legitimately change per request.
     */
    protected function renderResolvedPage(Page $page, string $requestedSlug): RedirectResponse|View
    {
        $locale = app()->getLocale();

        $canonicalSlug = $page->translation($locale)?->slug;
        if ($canonicalSlug && $canonicalSlug !== $requestedSlug) {
            return redirect()->to('/' . ltrim($canonicalSlug, '/'));
        }

        return view('front.pages.page', [
            'page'     => $page,
            'sections' => $page->sections,
        ]);
    }
}
