<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            $testimonialData = [
                'order' => $validated['order'],
                'star' => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : true,
            ];
            $imagePath = trim((string) ($validated['image_path'] ?? ''));

            if ($request->hasFile('image')) {
                $testimonialData['image'] = $request->file('image')->store('testimonials', 'public');
            } elseif ($imagePath !== '') {
                $testimonialData['image'] = $imagePath;
            }

            $testimonial = Testimonial::create($testimonialData);

            $translations = $this->extractCompleteTranslations($validated['testimonialTranslations'] ?? []);
            foreach ($translations as $translation) {
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

        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            $testimonialData = [
                'order' => $validated['order'],
                'star' => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : $testimonial->is_approved,
            ];
            $imagePath = trim((string) ($validated['image_path'] ?? ''));

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

            $translationsInput = $validated['testimonialTranslations'] ?? [];
            $translations = $this->extractCompleteTranslations($translationsInput);
            $providedLocales = array_column($translations, 'locale');

            foreach ($translations as $translation) {
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

    protected function validateTestimonialRequest(Request $request): array
    {
        $localeCodes = $this->languages->pluck('code')->filter()->values()->all();

        $translationLocaleRules = ['required', 'string'];
        if (!empty($localeCodes)) {
            $translationLocaleRules[] = Rule::in($localeCodes);
        }

        $rules = [
            'order' => 'required|integer|min:1',
            'star' => 'nullable|integer|min:1|max:5',
            'image' => 'nullable|image',
            'image_path' => 'nullable|string',
            'is_approved' => 'nullable|boolean',
            'testimonialTranslations' => 'required|array',
            'testimonialTranslations.*.locale' => $translationLocaleRules,
            'testimonialTranslations.*.feedback' => 'nullable|string',
            'testimonialTranslations.*.name' => 'nullable|string',
            'testimonialTranslations.*.major' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        $languageLabels = $this->languages->mapWithKeys(
            fn($language) => [$language->code => $language->name ?? strtoupper($language->code)]
        );

        $validator->after(function ($validator) use ($request, $languageLabels) {
            $translations = $request->input('testimonialTranslations', []);
            $hasCompleteLanguage = false;

            foreach ($translations as $code => $translation) {
                $feedback = trim((string) ($translation['feedback'] ?? ''));
                $name = trim((string) ($translation['name'] ?? ''));
                $major = trim((string) ($translation['major'] ?? ''));
                $hasAny = $feedback !== '' || $name !== '' || $major !== '';

                if ($feedback !== '' && $name !== '' && $major !== '') {
                    $hasCompleteLanguage = true;
                    continue;
                }

                if ($hasAny) {
                    $label = $languageLabels[$translation['locale'] ?? $code] ?? ($translation['locale'] ?? $code);

                    if ($feedback === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.feedback",
                            __('هذا الحقل مطلوب للغة :lang.', ['lang' => $label])
                        );
                    }
                    if ($name === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.name",
                            __('هذا الحقل مطلوب للغة :lang.', ['lang' => $label])
                        );
                    }
                    if ($major === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.major",
                            __('هذا الحقل مطلوب للغة :lang.', ['lang' => $label])
                        );
                    }
                }
            }

            if (!$hasCompleteLanguage) {
                $validator->errors()->add(
                    'testimonialTranslations',
                    __('يرجى تعبئة جميع الحقول للغة واحدة على الأقل.')
                );
            }
        });

        return $validator->validate();
    }

    protected function extractCompleteTranslations(array $translations): array
    {
        $complete = [];

        foreach ($translations as $translation) {
            $locale = $translation['locale'] ?? null;
            $feedback = trim((string) ($translation['feedback'] ?? ''));
            $name = trim((string) ($translation['name'] ?? ''));
            $major = trim((string) ($translation['major'] ?? ''));

            if ($locale && $feedback !== '' && $name !== '' && $major !== '') {
                $complete[] = [
                    'locale' => $locale,
                    'feedback' => $feedback,
                    'name' => $name,
                    'major' => $major,
                ];
            }
        }

        return $complete;
    }
}
