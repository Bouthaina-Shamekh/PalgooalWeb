<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TestimonialsController extends Controller
{
    /**
     * جميع اللغات المتاحة في النظام.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $languages;

    public function __construct()
    {
        $this->languages = Language::all();
    }

    /**
     * عرض قائمة التقييمات.
     */
    public function index()
    {
        $testimonials = Testimonial::with(['translations', 'image'])
            ->orderBy('order')
            ->orderBy('id')
            ->paginate(10);

        return view('dashboard.testimonials.index', compact('testimonials'));
    }

    /**
     * صفحة إنشاء تقييم جديد.
     */
    public function create()
    {
        $testimonial             = new Testimonial();
        $testimonialTranslations = [];
        $languages               = $this->languages;

        return view('dashboard.testimonials.create', compact(
            'testimonial',
            'testimonialTranslations',
            'languages'
        ));
    }

    /**
     * حفظ تقييم جديد.
     */
    public function store(Request $request)
    {
        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            // نقرأ الـ image id من الفورم مباشرة (hidden input من الـ media picker)
            $imageId = $request->input('featured_image_id');

            // ✅ حفظ البيانات الأساسية للتقييم
            $testimonialData = [
                'order'       => $validated['order'],
                'star'        => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : true,
                'image_id'    => $validated['featured_image_id'] ?? null, // ✅
            ];

            $testimonial = Testimonial::create($testimonialData);

            // ✅ حفظ الترجمات المتكاملة فقط (اسم + نص + تخصص)
            $translations = $this->extractCompleteTranslations(
                $validated['testimonialTranslations'] ?? []
            );

            foreach ($translations as $translation) {
                TestimonialTranslation::create([
                    'feedback_id' => $testimonial->id,
                    'locale'      => $translation['locale'],
                    'feedback'    => $translation['feedback'],
                    'name'        => $translation['name'],
                    'major'       => $translation['major'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard.testimonials.index')
                ->with('success', 'تمت إضافة التقييم بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => $th->getMessage()])
                ->withInput();
        }
    }

    /**
     * صفحة تعديل تقييم.
     */
    public function edit($id)
    {
        $testimonial = Testimonial::with(['translations', 'image'])->findOrFail($id);
        $testimonialTranslations = [];

        foreach ($this->languages as $lang) {
            $trans = $testimonial->translations->firstWhere('locale', $lang->code);

            $testimonialTranslations[$lang->code] = [
                'locale'   => $lang->code,
                'feedback' => $trans?->feedback ?? '',
                'name'     => $trans?->name ?? '',
                'major'    => $trans?->major ?? '',
            ];
        }

        $languages = $this->languages;

        return view('dashboard.testimonials.edit', compact(
            'testimonial',
            'testimonialTranslations',
            'languages'
        ));
    }

    /**
     * تحديث تقييم موجود.
     */
    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            $testimonialData = [
                'order'       => $validated['order'],
                'star'        => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : $testimonial->is_approved,
            ];

            if (array_key_exists('featured_image_id', $validated)) {
                $testimonialData['image_id'] = $validated['featured_image_id'] ?: null; // ✅
            }

            // ✅ نقرأ الـ image id من الفورم، لو موجود نحدّثه
            $imageId = $request->input('featured_image_id', null);
            if ($imageId !== null && $imageId !== '') {
                $testimonialData['image_id'] = $imageId;
            }

            $testimonial->update($testimonialData);

            // ✅ إدارة الترجمات (إضافة / تحديث / حذف)
            $translationsInput = $validated['testimonialTranslations'] ?? [];
            $translations      = $this->extractCompleteTranslations($translationsInput);
            $providedLocales   = array_column($translations, 'locale');

            foreach ($translations as $translation) {
                TestimonialTranslation::updateOrCreate(
                    [
                        'feedback_id' => $testimonial->id,
                        'locale'      => $translation['locale'],
                    ],
                    [
                        'feedback' => $translation['feedback'],
                        'name'     => $translation['name'],
                        'major'    => $translation['major'],
                    ]
                );
            }

            // حذف الترجمات التي لم تعد موجودة في الفورم
            if (!empty($providedLocales)) {
                $testimonial->translations()
                    ->whereNotIn('locale', $providedLocales)
                    ->delete();
            }

            DB::commit();

            return redirect()
                ->route('dashboard.testimonials.index')
                ->with('success', 'تم تحديث التقييم بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => $th->getMessage()])
                ->withInput();
        }
    }

    /**
     * حذف تقييم.
     */
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        DB::beginTransaction();

        try {
            // ✅ حذف الترجمات فقط، وترك ملف الميديا كما هو في مكتبة الوسائط
            $testimonial->translations()->delete();
            $testimonial->delete();

            DB::commit();

            return redirect()
                ->route('dashboard.testimonials.index')
                ->with('success', 'تم حذف التقييم بنجاح.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    /**
     * ✅ التحقق من الطلب (مع دعم الترجمة المتعددة + الميديا الجديدة).
     */
    protected function validateTestimonialRequest(Request $request): array
    {
        $localeCodes = $this->languages
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $translationLocaleRules = ['required', 'string'];

        if (!empty($localeCodes)) {
            $translationLocaleRules[] = Rule::in($localeCodes);
        }

        $rules = [
            'order'             => 'required|integer|min:1',
            'star'              => 'nullable|integer|min:1|max:5',

            // ✅ حقل الميديا الجديد (ID من جدول media)
            'featured_image_id' => 'nullable|integer|exists:media,id',

            'is_approved'       => 'nullable|boolean',

            'testimonialTranslations'            => 'required|array',
            'testimonialTranslations.*.locale'   => $translationLocaleRules,
            'testimonialTranslations.*.feedback' => 'nullable|string',
            'testimonialTranslations.*.name'     => 'nullable|string',
            'testimonialTranslations.*.major'    => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        $languageLabels = $this->languages->mapWithKeys(
            fn($language) => [
                $language->code => $language->name ?? strtoupper($language->code),
            ]
        );

        // ✅ التحقق المخصص: على الأقل لغة واحدة مكتملة
        $validator->after(function ($validator) use ($request, $languageLabels) {
            $translations        = $request->input('testimonialTranslations', []);
            $hasCompleteLanguage = false;

            foreach ($translations as $code => $translation) {
                $feedback = trim((string) ($translation['feedback'] ?? ''));
                $name     = trim((string) ($translation['name'] ?? ''));
                $major    = trim((string) ($translation['major'] ?? ''));

                $hasAny = $feedback !== '' || $name !== '' || $major !== '';

                // لغة مكتملة
                if ($feedback !== '' && $name !== '' && $major !== '') {
                    $hasCompleteLanguage = true;
                    continue;
                }

                // لغة فيها بيانات ناقصة → نظهر أخطاء لكل حقل ناقص
                if ($hasAny) {
                    $label = $languageLabels[$translation['locale'] ?? $code]
                        ?? ($translation['locale'] ?? $code);

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

    /**
     * إبقاء فقط الترجمات الكاملة (feedback + name + major).
     */
    protected function extractCompleteTranslations(array $translations): array
    {
        $complete = [];

        foreach ($translations as $translation) {
            $locale   = $translation['locale'] ?? null;
            $feedback = trim((string) ($translation['feedback'] ?? ''));
            $name     = trim((string) ($translation['name'] ?? ''));
            $major    = trim((string) ($translation['major'] ?? ''));

            if ($locale && $feedback !== '' && $name !== '' && $major !== '') {
                $complete[] = [
                    'locale'   => $locale,
                    'feedback' => $feedback,
                    'name'     => $name,
                    'major'    => $major,
                ];
            }
        }

        return $complete;
    }
}
