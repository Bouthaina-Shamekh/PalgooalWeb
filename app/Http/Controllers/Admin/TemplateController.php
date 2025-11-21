<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateTranslation;
use App\Models\CategoryTemplate;
use App\Models\Language;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * قائمة القوالب مع الترجمة والفئة.
     */
    public function index()
    {
        $templates = Template::with(['categoryTemplate.translation', 'translations'])
            ->latest()
            ->paginate(10);

        return view('dashboard.templates.index', compact('templates'));
    }

    /**
     * نموذج إنشاء قالب جديد.
     */
    public function create()
    {
        $categories = CategoryTemplate::with('translation')->get();
        $languages  = Language::all();
        $plans      = Plan::all();

        return view('dashboard.templates.create', compact('categories', 'languages', 'plans'));
    }

    /**
     * تخزين قالب جديد.
     */
    public function store(Request $request)
    {
        $request->validate([
            'price'                => ['required', 'numeric', 'min:0'],
            'discount_price'       => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at'     => ['nullable', 'date'],
            'rating'               => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id' => ['required', 'exists:category_templates,id'],
            'plan_id'              => ['required', 'exists:plans,id'],
            'image'                => ['required', 'image'],

            'translations'                 => ['required', 'array', 'min:1'],
            'translations.*.locale'        => ['required', 'string'],
            'translations.*.name'          => ['required', 'string', 'max:255'],
            'translations.*.slug'          => ['nullable', 'string', 'max:255'],
            'translations.*.description'   => ['required', 'string'],
            'translations.*.preview_url'   => ['nullable', 'url'],
            'translations.*.details'       => ['nullable'], // JSON أو array حسب الفورم
        ]);

        DB::beginTransaction();

        try {
            // رفع الصورة
            $imagePath = $request->file('image')->store('templates', 'public');

            // إنشاء القالب
            $template = Template::create([
                'price'                => $request->price,
                'discount_price'       => $request->discount_price,
                'discount_ends_at'     => $request->discount_ends_at,
                'rating'               => $request->rating ?? 0, // حاليًا موجود في الجدول
                'category_template_id' => $request->category_template_id,
                'plan_id'              => $request->plan_id,
                'image'                => $imagePath,
            ]);

            // إنشاء الترجمات
            foreach ($request->translations as $translation) {
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

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء القالب: ' . $e->getMessage()]);
        }
    }

    /**
     * نموذج تعديل قالب.
     */
    public function edit($id)
    {
        $template   = Template::with('translations')->findOrFail($id);
        $categories = CategoryTemplate::with('translation')->get();
        $plans      = Plan::all();

        return view('dashboard.templates.edit', compact('template', 'categories', 'plans'));
    }

    /**
     * تحديث بيانات قالب موجود.
     */
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $request->validate([
            'price'                => ['required', 'numeric', 'min:0'],
            'discount_price'       => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount_ends_at'     => ['nullable', 'date'],
            'rating'               => ['nullable', 'numeric', 'min:0', 'max:5'],
            'category_template_id' => ['required', 'exists:category_templates,id'],
            'plan_id'              => ['required', 'exists:plans,id'],
            'image'                => ['nullable', 'image'],

            'translations'                 => ['required', 'array', 'min:1'],
            'translations.*.locale'        => ['required', 'string'],
            'translations.*.name'          => ['required', 'string', 'max:255'],
            'translations.*.slug'          => ['nullable', 'string', 'max:255'],
            'translations.*.description'   => ['required', 'string'],
            'translations.*.preview_url'   => ['nullable', 'url'],
            'translations.*.details'       => ['nullable'],
        ]);

        DB::beginTransaction();

        try {
            // رفع صورة جديدة إن وُجدت
            if ($request->hasFile('image')) {
                if ($template->image && Storage::disk('public')->exists($template->image)) {
                    Storage::disk('public')->delete($template->image);
                }

                $template->image = $request->file('image')->store('templates', 'public');
            }

            // تحديث بيانات القالب الأساسية
            $template->update([
                'price'                => $request->price,
                'discount_price'       => $request->discount_price,
                'discount_ends_at'     => $request->discount_ends_at,
                'rating'               => $request->rating ?? 0,
                'category_template_id' => $request->category_template_id,
                'plan_id'              => $request->plan_id,
                'image'                => $template->image, // في حال تم تغييرها بالأعلى
            ]);

            // حذف الترجمات القديمة بالكامل
            $template->translations()->delete();

            // إعادة إنشاء الترجمات من الفورم
            foreach ($request->translations as $translation) {
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
                ->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تعديل القالب: ' . $e->getMessage()]);
        }
    }

    /**
     * حذف قالب.
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);

        // حذف الصورة من التخزين إن وجدت
        if ($template->image && Storage::disk('public')->exists($template->image)) {
            Storage::disk('public')->delete($template->image);
        }

        $template->delete();

        return redirect()
            ->route('dashboard.templates.index')
            ->with('success', 'تم حذف القالب بنجاح.');
    }

    /**
     * دالة مساعدة لتوليد الـ slug بشكل موحّد.
     *
     * - لو لم يُرسل slug نستخدم الاسم.
     * - نحول المسافات/الـ _ إلى -.
     * - نحذف أي حروف غير أرقام/حروف/شرطة.
     * - نمنع التكرار المتتالي للـ -.
     */
    private function makeSlug(?string $slug, string $fallbackName): string
    {
        $value = $slug ?: $fallbackName;

        // مسافات أو _ → -
        $value = preg_replace('/[\s_]+/u', '-', $value);

        // إزالة أي شيء غير حروف/أرقام/شرطة
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);

        // منع -- مكررة
        $value = preg_replace('/\-{2,}/u', '-', $value);

        // قص الشرطات من البداية والنهاية
        $value = trim($value, '-');

        return $value !== '' ? $value : 'template-' . uniqid();
    }
}

