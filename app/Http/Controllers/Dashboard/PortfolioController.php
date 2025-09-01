<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Portfolio;
use App\Models\PortfolioTranslation;
use App\Models\CategoryPortfolio;
use App\Models\Client;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PortfolioController extends Controller
{
    public function generateUniqueSlug($string, $id = null)
    {
        // نحول النص إلى slug
        $slug = Str::slug($string);

        // نبدأ بالـ slug الأصلي
        $originalSlug = $slug;
        $counter = 1;

        // نتحقق إذا موجود مسبقاً
        while (
            Portfolio::where('slug', $slug)
                ->when($id, fn($q) => $q->where('id', '!=', $id)) // استثناء السجل نفسه وقت التعديل
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    public $languages;
    public $typeSuggestions;
    public $statusSuggestions;

    public function __construct()
    {
        $this->languages = Language::get();

        $this->typeSuggestions = collect($this->languages)->mapWithKeys(function ($lang) {
            $types = PortfolioTranslation::where('locale', $lang->code)
                ->whereNotNull('type')
                ->pluck('type')
                ->flatMap(fn($str) => collect(preg_split('/[,،]/u', $str))->map('trim')->filter())
                ->unique()
                ->values();
            return [$lang->code => $types];
        })->toArray();

        $this->statusSuggestions = [
            'ar' => ['مفعل', 'غير مفعل', 'مكتمل'],
            'en' => ['Active', 'Inactive', 'Completed'],
        ];
    }
    public function index()
    {
        $portfolios = Portfolio::with('translations')->paginate(10);
        return view('dashboard.portfolios.index', compact('portfolios'));
    }

    public function create()
    {
        $portfolio = new Portfolio();
        $portfolioTranslations = [];
        $languages = $this->languages;
        $typeSuggestions = $this->typeSuggestions;
        $statusSuggestions = $this->statusSuggestions;
        return view('dashboard.portfolios.create', compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
            'delivery_date' => 'required|date',
            'implementation_period_days' => 'required|integer',
            'client' => 'nullable|string',
            'description' => 'nullable|string',
            'translations.*.title' => 'required|string',
            'translations.*.type' => 'required|string',
            'translations.*.materials' => 'required|string',
            'translations.*.link' => 'nullable|string',
            'translations.*.status' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $slug = $this->generateUniqueSlug($request->translations[0]['title']);
            $request->merge(['slug' => $slug]);
            // حفظ البيانات
            $portfolio = Portfolio::create($request->all());

            // حفظ الترجمات
            foreach ($request->translations as $translation) {
                PortfolioTranslation::create(
                    [
                        'portfolio_id' => $portfolio->id,
                        'locale' => $translation['locale'],
                        'title' => $translation['title'],
                        'type' => $translation['type'],
                        'materials' => $translation['materials'],
                        'link' => $translation['link'],
                        'status' => $translation['status'],
                        'description' => $translation['description'],
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.portfolios.index')->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $portfolio = Portfolio::with('translations')->findOrFail($id);
        $portfolioTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $portfolio->translations->firstWhere('locale', $lang->code);
            $portfolioTranslations[$lang->code] = [
                'locale' => $lang->code,
                'title' => $trans?->title ?? '',
                'type' => $trans?->type ?? '',
                'materials' => $trans?->materials ?? '',
                'link' => $trans?->link ?? '',
                'status' => $trans?->status ?? '',
                'description' => $trans?->description ?? '',
            ];
        }
        $languages = $this->languages;
        $typeSuggestions = $this->typeSuggestions;
        $statusSuggestions = $this->statusSuggestions;
        return view('dashboard.portfolios.edit', compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions'));
    }

    public function update(Request $request, $id)
    {
        $portfolio = Portfolio::findOrFail($id);
        $request->validate([
            'order' => 'required|integer',
            'delivery_date' => 'required|date',
            'implementation_period_days' => 'required|integer',
            'client' => 'nullable|string',
            'description' => 'nullable|string',
            'translations.*.title' => 'required|string',
            'translations.*.type' => 'required|string',
            'translations.*.materials' => 'required|string',
            'translations.*.link' => 'nullable|string',
            'translations.*.status' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $request->merge(['slug' => Str::slug($request->translations[0]['title'])]);
            $portfolio->update($request->all());

            // إعادة إدخال الترجمات
            foreach ($request->translations as $translation) {
                PortfolioTranslation::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'locale' => $translation['locale']],
                    [
                        'title' => $translation['title'],
                        'description' => $translation['description'],
                        'type' => $translation['type'],
                        'materials' => $translation['materials'],
                        'link' => $translation['link'],
                        'status' => $translation['status'],
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.portfolios.index')->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function destroy($id)
    {
        $portfolio = Portfolio::findOrFail($id);

        $portfolio->delete();

        return redirect()->route('dashboard.portfolios.index')->with('success', 'تم حذف القالب بنجاح.');
    }
}
