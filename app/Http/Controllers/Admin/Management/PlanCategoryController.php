<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\PlanCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class PlanCategoryController extends Controller
{
    // إذا أحببت، أضف middleware للأذونات هنا
    // public function __construct()
    // {
    //     $this->middleware('can:manage-plan-categories');
    // }

    /**
     * توليد slug فريد لكل locale
     */
    protected function generateUniqueSlug(?string $slug, string $title, string $locale, int $categoryId = 0): string
    {
        $slug = $slug ?: Str::slug($title);
        $base = $slug ?: "cat-{$categoryId}-{$locale}";
        $candidate = $base;
        $counter = 1;

        while (DB::table('plan_category_translations')
            ->where('slug', $candidate)
            ->where('locale', $locale)
            ->when($categoryId, fn($q) => $q->where('plan_category_id', '!=', $categoryId))
            ->exists()
        ) {
            $candidate = $base . '-' . $counter++;
        }

        return $candidate;
    }

    /**
     * تبديل الحالة النشطة للتصنيف
     */
    public function toggle(PlanCategory $plan_category)
    {
        $this->authorize('update', $plan_category);
        try {
            $plan_category->is_active = !$plan_category->is_active;
            $plan_category->save();

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'is_active' => $plan_category->is_active]);
            }

            return back()->with('ok', t('dashboard.Category_Status_Updated', 'Category status updated.'));
        } catch (\Throwable $e) {
            Log::error('PlanCategory toggle failed: ' . $e->getMessage(), ['id' => $plan_category->id]);
            return back()->with('error', t('dashboard.Category_Status_Failed', 'Failed to update category status.'));
        }
    }

    /**
     * عرض قائمة التصنيفات
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PlanCategory::class);

        $search  = $request->get('search');
        $perPage = in_array((int) $request->get('per_page'), [10, 20, 50])
            ? (int) $request->get('per_page') : 20;

        $categories = PlanCategory::with('translations')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('translations', function ($t) use ($search) {
                    $t->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%');
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.management.plan_categories.index', compact('categories', 'search', 'perPage'));
    }

    public function create()
    {
        $this->authorize('create', PlanCategory::class);
        return view('dashboard.management.plan_categories.create');
    }

    /**
     * حفظ تصنيف جديد مع الترجمات
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PlanCategory::class);
        $validated = $request->validate([
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.slug' => 'nullable|string|max:140',
        ]);

        DB::beginTransaction();
        try {
            $category = PlanCategory::create([
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($request->input('translations', []) as $locale => $trans) {
                $slug = $this->generateUniqueSlug($trans['slug'] ?? null, $trans['title'] ?? '', $locale, $category->id);

                $category->translations()->create([
                    'locale' => $locale,
                    'title' => $trans['title'] ?? '',
                    'description' => $trans['description'] ?? '',
                    'slug' => $slug,
                ]);
            }

            DB::commit();
            return redirect()->route('dashboard.plan_categories.index')
                ->with('ok', t('dashboard.Category_Created', 'Category created successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanCategory store failed: ' . $e->getMessage(), ['input' => $request->all()]);
            return back()->withInput()->with('error', t('dashboard.Category_Create_Failed', 'Failed to create category. Please try again.'));
        }
    }

    public function edit(PlanCategory $plan_category)
    {
        $this->authorize('update', $plan_category);
        $plan_category->load('translations');
        return view('dashboard.management.plan_categories.edit', ['category' => $plan_category]);
    }

    /**
     * تحديث تصنيف وترجماته
     */
    public function update(Request $request, PlanCategory $plan_category): RedirectResponse
    {
        $this->authorize('update', $plan_category);
        $validated = $request->validate([
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.slug' => 'nullable|string|max:140',
        ]);

        DB::beginTransaction();
        try {
            $plan_category->is_active = $request->boolean('is_active', false);
            $plan_category->touch();

            foreach ($request->input('translations', []) as $locale => $trans) {
                $slug = $this->generateUniqueSlug($trans['slug'] ?? null, $trans['title'] ?? '', $locale, $plan_category->id);

                $plan_category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $trans['title'] ?? '',
                        'description' => $trans['description'] ?? '',
                        'slug' => $slug
                    ]
                );
            }

            DB::commit();
            return redirect()->route('dashboard.plan_categories.index')
                ->with('ok', t('dashboard.Category_Updated', 'Category updated successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanCategory update failed: ' . $e->getMessage(), ['id' => $plan_category->id, 'input' => $request->all()]);
            return back()->withInput()->with('error', t('dashboard.Category_Update_Failed', 'Failed to update category. Please try again.'));
        }
    }

    /**
     * حذف تصنيف إذا لم يكن مرتبط بخطط
     */
    public function destroy(PlanCategory $plan_category): RedirectResponse
    {
        $this->authorize('delete', $plan_category);
        if ($plan_category->plans()->count() > 0) {
            return back()->with('error', t('dashboard.Category_Has_Plans', 'Cannot delete: this category is linked to hosting plans. Remove or re-assign the plans first.'));
        }

        try {
            $plan_category->delete();
            return back()->with('ok', t('dashboard.Category_Deleted', 'Category deleted successfully.'));
        } catch (\Throwable $e) {
            Log::error('PlanCategory delete failed: ' . $e->getMessage(), ['id' => $plan_category->id]);
            return back()->with('error', t('dashboard.Category_Delete_Failed', 'Failed to delete category: it may be linked to other records.'));
        }
    }
}

