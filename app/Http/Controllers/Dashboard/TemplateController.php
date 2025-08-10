<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\TemplateTranslation;
use App\Models\CategoryTemplate;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::with(['categoryTemplate.translation', 'translations'])->latest()->paginate(10);
        return view('dashboard.templates.index', compact('templates'));
    }

    public function create()
    {
        $categories = CategoryTemplate::with('translation')->get();
        $languages = Language::all();
        return view('dashboard.templates.create', compact('categories', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'category_template_id' => 'required|exists:category_templates,id',
            'image' => 'required|image',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.slug' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // رفع الصورة
            $imagePath = $request->file('image')->store('templates', 'public');

            // إنشاء القالب
            $template = Template::create([
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'discount_ends_at' => $request->discount_ends_at,
                'rating' => $request->rating ?? 0,
                'category_template_id' => $request->category_template_id,
                'image' => $imagePath,
            ]);

            // إنشاء الترجمات
            foreach ($request->translations as $translation) {
                $slug = $translation['slug'] ?? '';
                $slug = $slug ?: $translation['name'];
                $slug = preg_replace('/[\s_]+/u', '-', $slug);
                $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $slug);
                $slug = preg_replace('/\-{2,}/u', '-', $slug);
                $slug = trim($slug, '-');
                
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

            return redirect()->route('dashboard.templates.index')->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $template = Template::with('translations')->findOrFail($id);
        $categories = CategoryTemplate::with('translation')->get();
        return view('dashboard.templates.edit', compact('template', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $request->validate([
            'price' => 'required|numeric',
            'category_template_id' => 'required|exists:category_templates,id',
            'image' => 'nullable|image',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.slug' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
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

            $template->update([
                'price' => $request->price,
                'discount_price' => $request->discount_price,
                'discount_ends_at' => $request->discount_ends_at,
                'rating' => $request->rating ?? 0,
                'category_template_id' => $request->category_template_id,
            ]);

            // حذف الترجمات القديمة
            $template->translations()->delete();

            // إعادة إدخال الترجمات
            foreach ($request->translations as $translation) {
                $slug = $translation['slug'] ?? '';
                $slug = $slug ?: $translation['name'];
                $slug = preg_replace('/[\s_]+/u', '-', $slug);
                $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $slug);
                $slug = preg_replace('/\-{2,}/u', '-', $slug);
                $slug = trim($slug, '-');
                
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

            return redirect()->route('dashboard.templates.index')->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    
    public function destroy($id)
    {
        $template = Template::findOrFail($id);

        if ($template->image && Storage::disk('public')->exists($template->image)) {
            Storage::disk('public')->delete($template->image);
        }

        $template->delete();

        return redirect()->route('dashboard.templates.index')->with('success', 'تم حذف القالب بنجاح.');
    }
}
