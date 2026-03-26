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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    /**
     * Display the template library.
     */
    public function index(Request $request)
    {
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
            'total' => Template::count(),
            'visible' => $templates->total(),
            'discounted' => Template::query()
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
            'categories' => $categories->count(),
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
        $categories = CategoryTemplate::with('translation')->get();
        $languages = Language::all();
        $plans = Plan::all();

        return view('dashboard.templates.create', compact('categories', 'languages', 'plans'));
    }

    /**
     * Persist a newly created template.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at' => ['nullable', 'date'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id' => ['required', 'exists:category_templates,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'image' => ['nullable', 'image'],
            'image_media_id' => ['nullable', 'integer', 'exists:media,id', 'required_without:image'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['required', 'string'],
            'translations.*.preview_url' => ['nullable', 'url'],
            'translations.*.details' => ['nullable'],
        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('templates', 'public');
            } elseif ($request->filled('image_media_id')) {
                $imagePath = Media::query()
                    ->whereKey((int) $request->input('image_media_id'))
                    ->value('file_path');
            }

            $template = Template::create([
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'discount_ends_at' => $request->discount_ends_at,
                'rating' => $request->rating ?? 0,
                'category_template_id' => $request->category_template_id,
                'plan_id' => $request->plan_id,
                'image' => $imagePath,
            ]);

            foreach ($request->translations as $translation) {
                $slug = $this->makeSlug($translation['slug'] ?? null, $translation['name'] ?? '');

                TemplateTranslation::create([
                    'template_id' => $template->id,
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'slug' => $slug,
                    'preview_url' => $translation['preview_url'] ?? null,
                    'description' => $translation['description'],
                    'details' => $translation['details'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard.templates.index')
                ->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();

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
        return redirect()->route('dashboard.templates.edit', $id);
    }

    /**
     * Show the template edit form.
     */
    public function edit($id)
    {
        $template = Template::with('translations')->findOrFail($id);
        $categories = CategoryTemplate::with('translation')->get();
        $plans = Plan::all();

        return view('dashboard.templates.edit', compact('template', 'categories', 'plans'));
    }

    /**
     * Update an existing template.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $template = Template::findOrFail($id);

        $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at' => ['nullable', 'date'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id' => ['required', 'exists:category_templates,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'image' => ['nullable', 'image'],
            'image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['required', 'string'],
            'translations.*.preview_url' => ['nullable', 'url'],
            'translations.*.details' => ['nullable'],
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                if ($template->image && Storage::disk('public')->exists($template->image)) {
                    Storage::disk('public')->delete($template->image);
                }

                $template->image = $request->file('image')->store('templates', 'public');
            } elseif ($request->filled('image_media_id')) {
                $selectedImagePath = Media::query()
                    ->whereKey((int) $request->input('image_media_id'))
                    ->value('file_path');

                if (! empty($selectedImagePath)) {
                    $template->image = ltrim((string) $selectedImagePath, '/');
                }
            }

            $template->update([
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'discount_ends_at' => $request->discount_ends_at,
                'rating' => $request->filled('rating') ? $request->rating : $template->rating,
                'category_template_id' => $request->category_template_id,
                'plan_id' => $request->plan_id,
                'image' => $template->image,
            ]);

            $template->translations()->delete();

            foreach ($request->translations as $translation) {
                $slug = $this->makeSlug($translation['slug'] ?? null, $translation['name'] ?? '');

                TemplateTranslation::create([
                    'template_id' => $template->id,
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'slug' => $slug,
                    'preview_url' => $translation['preview_url'] ?? null,
                    'description' => $translation['description'],
                    'details' => $translation['details'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard.templates.index')
                ->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();

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

        if ($template->image && Storage::disk('public')->exists($template->image)) {
            Storage::disk('public')->delete($template->image);
        }

        $template->delete();

        return redirect()
            ->route('dashboard.templates.index')
            ->with('success', 'تم حذف القالب بنجاح.');
    }

    /**
     * Display the controller-driven template category management screen.
     */
    public function categories()
    {
        return $this->renderCategoryManagement();
    }

    /**
     * Store a template category.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
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
        return $this->renderCategoryManagement($category);
    }

    /**
     * Update a template category.
     */
    public function updateCategory(Request $request, CategoryTemplate $category): RedirectResponse
    {
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
     * Delete a template category when it is no longer used by templates.
     */
    public function destroyCategory(CategoryTemplate $category): RedirectResponse
    {
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

    /**
     * Render the controller-driven template category management screen.
     */
    private function renderCategoryManagement(?CategoryTemplate $editingCategory = null)
    {
        $editingCategory?->loadMissing('translations');

        $languages = $this->templateCategoryLanguages();
        $categories = CategoryTemplate::with('translations')->latest()->get();

        return view('dashboard.templates.category-management', [
            'languages' => $languages,
            'categories' => $categories,
            'editingCategory' => $editingCategory,
            'formTranslations' => $this->templateCategoryFormTranslations($languages, $editingCategory),
            'activeLang' => old('active_lang', $languages->first()?->code ?? app()->getLocale()),
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
        $oldTranslations = old('translations');
        $existingTranslations = $editingCategory?->translations?->keyBy('locale') ?? collect();
        $formTranslations = [];

        foreach ($languages as $language) {
            $languageCode = (string) $language->code;

            if ($languageCode === '') {
                continue;
            }

            if (is_array($oldTranslations[$languageCode] ?? null)) {
                $formTranslations[$languageCode] = [
                    'name' => (string) ($oldTranslations[$languageCode]['name'] ?? ''),
                    'slug' => (string) ($oldTranslations[$languageCode]['slug'] ?? ''),
                    'description' => (string) ($oldTranslations[$languageCode]['description'] ?? ''),
                ];

                continue;
            }

            $translation = $existingTranslations->get($languageCode);

            $formTranslations[$languageCode] = [
                'name' => (string) ($translation?->name ?? ''),
                'slug' => (string) ($translation?->slug ?? ''),
                'description' => (string) ($translation?->description ?? ''),
            ];
        }

        return $formTranslations;
    }

    private function templateCategoryRules($languages, ?CategoryTemplate $editingCategory = null): array
    {
        $rules = [
            'active_lang' => ['nullable', 'string'],
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
            'translations.*.name.required' => 'ط§ظ„ط§ط³ظ… ظ…ط·ظ„ظˆط¨.',
            'translations.*.name.max' => 'ظٹط¬ط¨ ط£ظ„ط§ ظٹطھط¬ط§ظˆط² ط§ظ„ط§ط³ظ… 255 ط­ط±ظپظ‹ط§.',
            'translations.*.slug.required' => 'ط§ظ„ط±ط§ط¨ط· (slug) ظ…ط·ظ„ظˆط¨.',
            'translations.*.slug.alpha_dash' => 'ظٹط¬ط¨ ط£ظ† ظٹط­طھظˆظٹ ط§ظ„ط±ط§ط¨ط· ط¹ظ„ظ‰ ط£ط­ط±ظپ ظˆط£ط±ظ‚ط§ظ… ظˆط´ط±ط·ط§طھ ظپظ‚ط·.',
            'translations.*.slug.unique' => 'ظ‡ط°ط§ ط§ظ„ط±ط§ط¨ط· ظ…ط³طھط®ط¯ظ… ط¨ط§ظ„ظپط¹ظ„.',
            'translations.*.slug.max' => 'ط§ظ„ط±ط§ط¨ط· ط·ظˆظٹظ„ ط¬ط¯ظ‹ط§.',
            'translations.*.description.string' => 'ط§ظ„ظˆطµظپ ط؛ظٹط± طµط§ظ„ط­.',
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
                    'name' => (string) ($translation['name'] ?? ''),
                    'slug' => (string) ($translation['slug'] ?? ''),
                    'description' => filled($translation['description'] ?? null)
                        ? (string) $translation['description']
                        : null,
                ]
            );
        }
    }

    /**
     * Helper to generate a normalized slug value.
     */
    private function makeSlug(?string $slug, string $fallbackName): string
    {
        $value = $slug ?: $fallbackName;

        // ظ…ط³ط§ظپط§طھ ط£ظˆ _ â†’ -
        $value = preg_replace('/[\s_]+/u', '-', $value);

        // ط¥ط²ط§ظ„ط© ط£ظٹ ط´ظٹط، ط؛ظٹط± ط­ط±ظˆظپ/ط£ط±ظ‚ط§ظ…/ط´ط±ط·ط©
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);

        // ظ…ظ†ط¹ -- ظ…ظƒط±ط±ط©
        $value = preg_replace('/\-{2,}/u', '-', $value);

        // ظ‚طµ ط§ظ„ط´ط±ط·ط§طھ ظ…ظ† ط§ظ„ط¨ط¯ط§ظٹط© ظˆط§ظ„ظ†ظ‡ط§ظٹط©
        $value = trim($value, '-');

        return $value !== '' ? $value : 'template-' . uniqid();
    }
}


