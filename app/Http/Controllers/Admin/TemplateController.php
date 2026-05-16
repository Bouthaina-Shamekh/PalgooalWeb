<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateTranslation;
use App\Models\CategoryTemplate;
use App\Models\Language;
use App\Models\Media;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    /**
     * Display the template library.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Template::class);

        $search = trim((string) $request->query('q', ''));
        $selectedCategory = $request->filled('category')
            ? (int) $request->query('category')
            : null;

        $templatesQuery = Template::query()
            ->with([
                'translations',
                'categoryTemplate.translations',
                'plan.translations',
            ]);

        if ($search !== '') {
            $templatesQuery->where(function ($query) use ($search) {
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }

                $query->orWhereHas('translations', function ($translationQuery) use ($search) {
                    $translationQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });

                $query->orWhereHas('categoryTemplate.translations', function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%");
                });

                $query->orWhereHas('plan', function ($planQuery) use ($search) {
                    $planQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });

                $query->orWhereHas('plan.translations', function ($planTranslationQuery) use ($search) {
                    $planTranslationQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        if ($selectedCategory) {
            $templatesQuery->where('category_template_id', $selectedCategory);
        }

        $templates = $templatesQuery
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = CategoryTemplate::with('translations')->get();

        $stats = [
            'total'        => Template::count(),
            'visible'      => $templates->total(),
            'discounted'   => Template::query()
                ->whereNotNull('discount_price')
                ->where('discount_price', '>', 0)
                ->whereColumn('discount_price', '<', 'price')
                ->count(),
            'with_preview' => Template::query()
                ->whereHas('translations', function ($query) {
                    $query
                        ->whereNotNull('preview_url')
                        ->where('preview_url', '!=', '');
                })
                ->count(),
            'categories'   => $categories->count(),
        ];

        return view('dashboard.templates.index', compact(
            'templates',
            'categories',
            'search',
            'selectedCategory',
            'stats',
        ));
    }

    /**
     * Show the template creation form.
     */
    public function create()
    {
        $this->authorize('create', Template::class);

        $categories = CategoryTemplate::with('translation')->get();
        $languages  = Language::where('is_active', true)->orderBy('id')->get();
        $plans      = Plan::all();

        return view('dashboard.templates.create', compact('categories', 'languages', 'plans'));
    }

    /**
     * Persist a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Template::class);

        $validated = $request->validate([
            'price'                      => ['required', 'numeric', 'min:0'],
            'discount_price'             => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at'           => ['nullable', 'date'],
            'rating'                     => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id'       => ['required', 'exists:category_templates,id'],
            'plan_id'                    => ['required', 'exists:plans,id'],
            'image'                      => ['nullable', 'image'],
            'image_media_id'             => ['nullable', 'integer', 'exists:media,id', 'required_without:image'],
            'translations'               => ['required', 'array', 'min:1'],
            'translations.*.locale'      => ['required', 'string'],
            'translations.*.name'        => ['required', 'string', 'max:255'],
            'translations.*.slug'        => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['required', 'string'],
            'translations.*.preview_url' => ['nullable', 'url'],
            'translations.*.details'     => ['nullable'],
        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('templates', 'public');
            } elseif (filled($validated['image_media_id'] ?? null)) {
                $rawPath   = Media::query()
                    ->whereKey((int) $validated['image_media_id'])
                    ->value('file_path');
                $imagePath = $rawPath ? ltrim((string) $rawPath, '/') : null;
            }

            $template = Template::create([
                'price'                => $validated['price'],
                'discount_price'       => $validated['discount_price'] ?? null,
                'discount_ends_at'     => $validated['discount_ends_at'] ?? null,
                'rating'               => $validated['rating'] ?? 0,
                'category_template_id' => $validated['category_template_id'],
                'plan_id'              => $validated['plan_id'],
                'image'                => $imagePath,
            ]);

            foreach ($validated['translations'] as $translation) {
                $slug = $this->makeSlug($translation['slug'] ?? null, $translation['name'] ?? '');

                TemplateTranslation::create([
                    'template_id' => $template->id,
                    'locale'      => $translation['locale'],
                    'name'        => $translation['name'],
                    'slug'        => $slug,
                    'preview_url' => $translation['preview_url'] ?? null,
                    'description' => $translation['description'],
                    'details'     => $translation['details'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard.templates.index')
                ->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('TemplateController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء القالب: ' . $e->getMessage()]);
        }
    }

    /**
     * Redirect the unused resource show route to edit.
     */
    public function show($id): RedirectResponse
    {
        $template = Template::findOrFail($id);
        $this->authorize('update', $template);

        return redirect()->route('dashboard.templates.edit', $id);
    }

    /**
     * Show the template edit form.
     */
    public function edit($id)
    {
        $template = Template::with('translations')->findOrFail($id);
        $this->authorize('update', $template);

        $categories = CategoryTemplate::with('translation')->get();
        $plans      = Plan::all();

        return view('dashboard.templates.edit', compact('template', 'categories', 'plans'));
    }

    /**
     * Update an existing template.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $template = Template::findOrFail($id);
        $this->authorize('update', $template);

        $validated = $request->validate([
            'price'                      => ['required', 'numeric', 'min:0'],
            'discount_price'             => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at'           => ['nullable', 'date'],
            'rating'                     => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id'       => ['required', 'exists:category_templates,id'],
            'plan_id'                    => ['required', 'exists:plans,id'],
            'image'                      => ['nullable', 'image'],
            'image_media_id'             => ['nullable', 'integer', 'exists:media,id'],
            'translations'               => ['required', 'array', 'min:1'],
            'translations.*.locale'      => ['required', 'string'],
            'translations.*.name'        => ['required', 'string', 'max:255'],
            'translations.*.slug'        => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['required', 'string'],
            'translations.*.preview_url' => ['nullable', 'url'],
            'translations.*.details'     => ['nullable'],
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                if ($template->image && Storage::disk('public')->exists($template->image)) {
                    Storage::disk('public')->delete($template->image);
                }
                $template->image = $request->file('image')->store('templates', 'public');
            } elseif (filled($validated['image_media_id'] ?? null)) {
                $selectedImagePath = Media::query()
                    ->whereKey((int) $validated['image_media_id'])
                    ->value('file_path');

                if (! empty($selectedImagePath)) {
                    $template->image = ltrim((string) $selectedImagePath, '/');
                }
            }

            $template->update([
                'price'                => $validated['price'],
                'discount_price'       => $validated['discount_price'] ?? null,
                'discount_ends_at'     => $validated['discount_ends_at'] ?? null,
                'rating'               => $validated['rating'] ?? $template->rating,
                'category_template_id' => $validated['category_template_id'],
                'plan_id'              => $validated['plan_id'],
                'image'                => $template->image,
            ]);

            // updateOrCreate avoids the brief data-loss window of delete+recreate.
            $submittedLocales = [];

            foreach ($validated['translations'] as $translation) {
                $locale = $translation['locale'];
                $slug   = $this->makeSlug($translation['slug'] ?? null, $translation['name'] ?? '');

                $template->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name'        => $translation['name'],
                        'slug'        => $slug,
                        'preview_url' => $translation['preview_url'] ?? null,
                        'description' => $translation['description'],
                        'details'     => $translation['details'] ?? null,
                    ]
                );

                $submittedLocales[] = $locale;
            }

            if (! empty($submittedLocales)) {
                $template->translations()
                    ->whereNotIn('locale', $submittedLocales)
                    ->delete();
            }

            DB::commit();

            return redirect()
                ->route('dashboard.templates.index')
                ->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('TemplateController::update failed', ['template_id' => $id, 'error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تعديل القالب: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a template.
     */
    public function destroy($id): RedirectResponse
    {
        $template = Template::findOrFail($id);
        $this->authorize('delete', $template);

        if ($template->image && Storage::disk('public')->exists($template->image)) {
            Storage::disk('public')->delete($template->image);
        }

        $template->delete();

        return redirect()
            ->route('dashboard.templates.index')
            ->with('success', 'تم حذف القالب بنجاح.');
    }

    /**
     * Display the template category management screen.
     */
    public function categories()
    {
        $this->authorize('viewAny', CategoryTemplate::class);

        return $this->renderCategoryManagement();
    }

    /**
     * Store a template category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorize('create', CategoryTemplate::class);

        $languages = $this->templateCategoryLanguages();
        $validated = $request->validate(
            $this->templateCategoryRules($languages),
            $this->templateCategoryMessages(),
        );

        DB::transaction(function () use ($validated, $languages) {
            $category = new CategoryTemplate();
            $category->save();

            $this->syncTemplateCategoryTranslations(
                $category,
                is_array($validated['translations'] ?? null) ? $validated['translations'] : [],
                $languages,
            );
        });

        return redirect()
            ->route('dashboard.category')
            ->with('success', __('Category created successfully.'));
    }

    /**
     * Show the category edit form.
     */
    public function editCategory(CategoryTemplate $category)
    {
        $this->authorize('update', $category);

        return $this->renderCategoryManagement($category);
    }

    /**
     * Update a template category.
     */
    public function updateCategory(Request $request, CategoryTemplate $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->loadMissing('translations');

        $languages = $this->templateCategoryLanguages();
        $validated = $request->validate(
            $this->templateCategoryRules($languages, $category),
            $this->templateCategoryMessages(),
        );

        DB::transaction(function () use ($category, $validated, $languages) {
            $this->syncTemplateCategoryTranslations(
                $category,
                is_array($validated['translations'] ?? null) ? $validated['translations'] : [],
                $languages,
            );
        });

        return redirect()
            ->route('dashboard.category')
            ->with('success', __('Category updated successfully.'));
    }

    /**
     * Delete a category that has no templates.
     */
    public function destroyCategory(CategoryTemplate $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $isUsedByTemplates = Template::query()
            ->where('category_template_id', $category->id)
            ->exists();

        if ($isUsedByTemplates) {
            return redirect()
                ->route('dashboard.category')
                ->withErrors([
                    'error' => __('This category cannot be deleted while templates still belong to it.'),
                ]);
        }

        DB::transaction(function () use ($category) {
            $category->translations()->delete();
            $category->delete();
        });

        return redirect()
            ->route('dashboard.category')
            ->with('success', __('Category deleted successfully.'));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function renderCategoryManagement(?CategoryTemplate $editingCategory = null)
    {
        $editingCategory?->loadMissing('translations');

        $languages  = $this->templateCategoryLanguages();
        $categories = CategoryTemplate::with('translations')->latest()->get();

        return view('dashboard.templates.category-management', [
            'languages'        => $languages,
            'categories'       => $categories,
            'editingCategory'  => $editingCategory,
            'formTranslations' => $this->templateCategoryFormTranslations($languages, $editingCategory),
            'activeLang'       => old('active_lang', $languages->first()?->code ?? app()->getLocale()),
        ]);
    }

    private function templateCategoryLanguages()
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return $languages->isNotEmpty()
            ? $languages
            : Language::query()->orderBy('id')->get();
    }

    private function templateCategoryFormTranslations($languages, ?CategoryTemplate $editingCategory = null): array
    {
        $oldTranslations      = old('translations');
        $existingTranslations = $editingCategory?->translations?->keyBy('locale') ?? collect();
        $formTranslations     = [];

        foreach ($languages as $language) {
            $languageCode = (string) $language->code;

            if ($languageCode === '') {
                continue;
            }

            if (is_array($oldTranslations[$languageCode] ?? null)) {
                $formTranslations[$languageCode] = [
                    'name'        => (string) ($oldTranslations[$languageCode]['name'] ?? ''),
                    'slug'        => (string) ($oldTranslations[$languageCode]['slug'] ?? ''),
                    'description' => (string) ($oldTranslations[$languageCode]['description'] ?? ''),
                ];

                continue;
            }

            $translation = $existingTranslations->get($languageCode);

            $formTranslations[$languageCode] = [
                'name'        => (string) ($translation?->name ?? ''),
                'slug'        => (string) ($translation?->slug ?? ''),
                'description' => (string) ($translation?->description ?? ''),
            ];
        }

        return $formTranslations;
    }

    private function templateCategoryRules($languages, ?CategoryTemplate $editingCategory = null): array
    {
        $rules = [
            'active_lang'  => ['nullable', 'string'],
            'translations' => ['required', 'array', 'min:1'],
        ];

        foreach ($languages as $language) {
            $languageCode = (string) $language->code;

            if ($languageCode === '') {
                continue;
            }

            $translationId = $editingCategory?->translations?->firstWhere('locale', $languageCode)?->id;

            $rules["translations.{$languageCode}.name"] = ['required', 'string', 'max:255'];
            $rules["translations.{$languageCode}.slug"] = [
                'required',
                'string',
                'alpha_dash',
                'max:255',
                Rule::unique('category_template_translations', 'slug')
                    ->ignore($translationId)
                    ->where(fn ($query) => $query->where('locale', $languageCode)),
            ];
            $rules["translations.{$languageCode}.description"] = ['nullable', 'string'];
        }

        return $rules;
    }

    private function templateCategoryMessages(): array
    {
        return [
            'translations.*.name.required'      => __('The name field is required.'),
            'translations.*.name.max'            => __('The name may not be greater than 255 characters.'),
            'translations.*.slug.required'       => __('The slug field is required.'),
            'translations.*.slug.alpha_dash'     => __('The slug may only contain letters, numbers, dashes and underscores.'),
            'translations.*.slug.unique'         => __('This slug is already taken.'),
            'translations.*.slug.max'            => __('The slug may not be greater than 255 characters.'),
            'ranslations.*.description.string'  => __('The description must be a string.'),
        ];
    }

    private function syncTemplateCategoryTranslations(CategoryTemplate $category, array $translations, $languages): void
    {
        foreach ($languages as $language) {
            $languageCode = (string) $language->code;

            if ($languageCode === '') {
                continue;
            }

            $translation = is_array($translations[$languageCode] ?? null)
                ? $translations[$languageCode]
                : [];

            $category->translations()->updateOrCreate(
                ['locale' => $languageCode],
                [
                    'name'        => (string) ($translation['name'] ?? ''),
                    'slug'        => (string) ($translation['slug'] ?? ''),
                    'description' => filled($translation['description'] ?? null)
                        ? (string) $translation['description']
                        : null,
                ]
            );
        }
    }

    /**
     * Generate a URL-safe slug from the given value or fallback name.
     */
    private function makeSlug(?string $slug, string $fallbackName): string
    {
        $value = $slug ?: $fallbackName;

        // spaces / underscores -> hyphens
        $value = preg_replace('/[\s_]+/u', '-', $value);

        // strip anything that is not a letter, digit, or hyphen
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);

        // collapse consecutive hyphens
        $value = preg_replace('/\-{2,}/u', '-', $value);

        // trim leading/trailing hyphens
        $value = trim($value, '-');

        return $value !== '' ? $value : 'template-' . uniqid();
    }
}
