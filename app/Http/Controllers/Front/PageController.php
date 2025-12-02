<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the frontend homepage.
     *
     * This replicates the old closure:
     * - Find the page marked as `is_home` and `is_active`
     * - Eager load translations + sections.translations
     * - Share `currentPage` with all views
     * - Render `front.pages.page`
     */
    public function home(Request $request)
    {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_home', true)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            abort(404, 'لم يتم تحديد الصفحة الرئيسية بعد.');
        }

        // Make the current page globally available to the views
        view()->share('currentPage', $page);

        return view('front.pages.page', [
            'page' => $page,
        ]);
    }

    /**
     * Display a dynamic CMS page by slug.
     *
     * This replicates the old closure for /{slug}.
     */
    public function show(Request $request, string $slug)
    {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_active', true)
            ->whereSlug($slug) // uses your existing scopeWhereSlug on Page
            ->firstOrFail();

        view()->share('currentPage', $page);

        return view('front.pages.page', [
            'page' => $page,
        ]);
    }
}
