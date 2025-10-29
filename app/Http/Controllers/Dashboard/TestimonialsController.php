<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $localeCodes = $this->languages->pluck('code')->filter()->values()->all();

        $translationLocaleRules = ['required', 'string'];
        if (!empty($localeCodes)) {
            $translationLocaleRules[] = Rule::in($localeCodes);
        }

        $request->validate([
            'order' => 'required|integer|min:1',
            'star' => 'nullable|integer|min:1|max:5',
            'image' => 'nullable|image',
            'image_path' => 'nullable|string',
            'testimonialTranslations' => 'required|array',
            'testimonialTranslations.*.locale' => $translationLocaleRules,
            'testimonialTranslations.*.feedback' => 'required|string',
            'testimonialTranslations.*.name' => 'required|string',
            'testimonialTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $testimonialData = $request->only(['star', 'order']);
            $imagePath = trim((string) $request->input('image_path', ''));

            if ($request->hasFile('image')) {
                $testimonialData['image'] = $request->file('image')->store('testimonials', 'public');
            } elseif ($imagePath !== '') {
                $testimonialData['image'] = $imagePath;
            }

            $testimonial = Testimonial::create($testimonialData);

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

            return redirect()->route('dashboard.testimonials.index')->with('success', 'تمت إضافة التقييم بنجاح.');
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
        $localeCodes = $this->languages->pluck('code')->filter()->values()->all();

        $translationLocaleRules = ['required', 'string'];
        if (!empty($localeCodes)) {
            $translationLocaleRules[] = Rule::in($localeCodes);
        }

        $request->validate([
            'order' => 'required|integer|min:1',
            'star' => 'nullable|integer|min:1|max:5',
            'image' => 'nullable|image',
            'image_path' => 'nullable|string',
            'testimonialTranslations' => 'required|array',
            'testimonialTranslations.*.locale' => $translationLocaleRules,
            'testimonialTranslations.*.feedback' => 'required|string',
            'testimonialTranslations.*.name' => 'required|string',
            'testimonialTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $testimonialData = $request->only(['star', 'order']);
            $imagePath = trim((string) $request->input('image_path', ''));

            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('testimonials', 'public');

                if (!empty($testimonial->image)) {
                    Storage::disk('public')->delete($testimonial->image);
                }

                $testimonialData['image'] = $newImagePath;
            } elseif ($imagePath !== '') {
                $testimonialData['image'] = $imagePath;
            }

            $testimonial->update($testimonialData);

            $translationsInput = $request->input('testimonialTranslations', []);
            $providedLocales = collect($translationsInput)->pluck('locale')->filter()->all();

            foreach ($translationsInput as $translation) {
                TestimonialTranslation::updateOrCreate(
                    ['feedback_id' => $testimonial->id, 'locale' => $translation['locale']],
                    [
                        'feedback' => $translation['feedback'],
                        'name' => $translation['name'],
                        'major' => $translation['major'],
                    ]
                );
            }

            if (!empty($providedLocales)) {
                $testimonial->translations()->whereNotIn('locale', $providedLocales)->delete();
            }

            DB::commit();

            return redirect()->route('dashboard.testimonials.index')->with('success', 'تم تحديث التقييم بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $imagePath = $testimonial->image;

        DB::beginTransaction();

        try {
            $testimonial->translations()->delete();
            $testimonial->delete();

            if (!empty($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            DB::commit();

            return redirect()->route('dashboard.testimonials.index')->with('success', 'تم حذف التقييم بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }
}
