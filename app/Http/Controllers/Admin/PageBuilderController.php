<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBuilderStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageBuilderController extends Controller
{
    /**
     * Render the GrapesJS builder view for a given page.
     */
    public function edit(Page $page)
    {
        $page->loadMissing('translations');

        return view('dashboard.pages.builder', [
            'page' => $page,
        ]);
    }

    /**
     * Return stored GrapesJS project data for this page.
     */
    public function loadData(Page $page): JsonResponse
    {
        $builder = $page->builderStructure;

        return response()->json([
            'structure' => $builder?->structure ?? new \stdClass(),
        ]);
    }

    /**
     * Save GrapesJS project data (components JSON) for this page.
     */
    public function saveData(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'structure' => 'required|array',
        ]);

        $builder = PageBuilderStructure::updateOrCreate(
            ['page_id' => $page->id],
            ['structure' => $validated['structure']]
        );

        return response()->json([
            'status'    => 'ok',
            'structure' => $builder->structure,
        ]);
    }
}
