<?php

namespace App\Http\Controllers\Dashboard\Management;

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
        try {
            $plan_category->is_active = !$plan_category->is_active;
            $plan_category->save();

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'is_active' => $plan_category->is_active]);
            }

            return back()->with('ok', 'تم تحديث حالة التصنيف بنجاح');
        } catch (\Throwable $e) {
            Log::error('PlanCategory toggle failed: ' . $e->getMessage(), ['id' => $plan_category->id]);
            return back()->with('error', 'تعذر تحديث حالة التصنيف.');
        }
    }

    /**
     * عرض قائمة التصنيفات
     */
    public function index()
    {
        $categories = PlanCategory::with('translations')->latest()->paginate(20);
        return view('dashboard.management.plan_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('dashboard.management.plan_categories.create');
    }

    /**
     * حفظ تصنيف جديد مع الترجمات
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.slug' => 'nullable|string|max:140',
        ]);

        DB::beginTransaction();
        try {
            $category = PlanCategory::create([]);

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
            return redirect()->route('dashboard.plan_categories.index')->with('ok', 'تم إضافة التصنيف بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanCategory store failed: ' . $e->getMessage(), ['input' => $request->all()]);
            return back()->withInput()->with('error', 'تعذر إنشاء التصنيف. حاول مرة أخرى.');
        }
    }

    public function edit(PlanCategory $plan_category)
    {
        $plan_category->load('translations');
        return view('dashboard.management.plan_categories.edit', ['category' => $plan_category]);
    }

    /**
     * تحديث تصنيف وترجماته
     */
    public function update(Request $request, PlanCategory $plan_category): RedirectResponse
    {
        $validated = $request->validate([
            'translations' => 'nullable|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.slug' => 'nullable|string|max:140',
        ]);

        DB::beginTransaction();
        try {
            $plan_category->touch(); // تحديث timestamps

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
            return redirect()->route('dashboard.plan_categories.index')->with('ok', 'تم تحديث التصنيف بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PlanCategory update failed: ' . $e->getMessage(), ['id' => $plan_category->id, 'input' => $request->all()]);
            return back()->withInput()->with('error', 'تعذر تحديث التصنيف. حاول مرة أخرى.');
        }
    }

    /**
     * حذف تصنيف إذا لم يكن مرتبط بخطط
     */
    public function destroy(PlanCategory $plan_category): RedirectResponse
    {
        if ($plan_category->plans()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف التصنيف لأنه مرتبط بخطط استضافة. يرجى حذف أو تعديل الخطط أولاً.');
        }

        try {
            $plan_category->delete();
            return back()->with('ok', 'تم حذف التصنيف بنجاح');
        } catch (\Throwable $e) {
            Log::error('PlanCategory delete failed: ' . $e->getMessage(), ['id' => $plan_category->id]);
            return back()->with('error', 'تعذر حذف التصنيف: قد يكون مرتبطًا بسجلات أخرى أو هناك خطأ.');
        }
    }
}
