<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestimonialsController extends Controller
{
    protected $languages;

    public function __construct()
    {
        $this->languages = Language::all();
    }

    public function index()
    {
        $testimonials = Testimonial::with('translations')->paginate(10);
        return view('dashboard.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        $testimonial = new Testimonial();
        $testimonialTranslations = [];
        $languages = $this->languages;

        return view('dashboard.testimonials.create', compact('testimonial', 'testimonialTranslations', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
            'image' => 'nullable',
            'testimonialTranslations.*.feedback' => 'required|string',
            'testimonialTranslations.*.name' => 'required|string',
            'testimonialTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $testimonial = Testimonial::create($request->only(['image', 'star', 'order']));

            foreach ($request->input('testimonialTranslations', []) as $translation) {
                TestimonialTranslation::create([
                    'feedback_id' => $testimonial->id,
                    'locale' => $translation['locale'],
                    'feedback' => $translation['feedback'],
                    'name' => $translation['name'],
                    'major' => $translation['major'],
                ]);
            }

            DB::commit();

            return redirect()->route('dashboard.testimonials.index')->with('success', 'تم إنشاء الشهادة بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function edit($id)
    {
        $testimonial = Testimonial::with('translations')->findOrFail($id);
        $testimonialTranslations = [];

        foreach ($this->languages as $lang) {
            $trans = $testimonial->translations->firstWhere('locale', $lang->code);
            $testimonialTranslations[$lang->code] = [
                'locale' => $lang->code,
                'feedback' => $trans?->feedback ?? '',
                'name' => $trans?->name ?? '',
                'major' => $trans?->major ?? '',
            ];
        }

        $languages = $this->languages;

        return view('dashboard.testimonials.edit', compact('testimonial', 'testimonialTranslations', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $request->validate([
            'order' => 'required|integer',
            'image' => 'nullable',
            'testimonialTranslations.*.feedback' => 'required|string',
            'testimonialTranslations.*.name' => 'required|string',
            'testimonialTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $testimonial->update($request->only(['image', 'star', 'order']));

            foreach ($request->input('testimonialTranslations', []) as $translation) {
                TestimonialTranslation::updateOrCreate(
                    ['feedback_id' => $testimonial->id, 'locale' => $translation['locale']],
                    [
                        'feedback' => $translation['feedback'],
                        'name' => $translation['name'],
                        'major' => $translation['major'],
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.testimonials.index')->with('success', 'تم تحديث الشهادة بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();

        return redirect()->route('dashboard.testimonials.index')->with('success', 'تم حذف الشهادة بنجاح.');
    }
}
