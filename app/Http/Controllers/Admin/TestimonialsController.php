<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TestimonialsController extends Controller
{
    // -------------------------------------------------------------------------
    // Shared state — loaded lazily only when needed (P4 fix)
    // -------------------------------------------------------------------------

    protected ?object $languages = null;

    /**
     * Load languages once and cache on the instance.
     * Called only by actions that actually need the language list (create/edit).
     * P4 fix: avoid querying languages on every request (e.g. destroy).
     */
    protected function loadLanguages(): void
    {
        if ($this->languages !== null) {
            return;
        }

        $this->languages = Language::all();
    }

    // -------------------------------------------------------------------------
    // CRUD actions
    // -------------------------------------------------------------------------

    /**
     * عرض قائمة التقييمات.
     */
    public function index()
    {
        // P1 fix: authorize before returning data
        $this->authorize('viewAny', Testimonial::class);

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
        // P1 fix
        $this->authorize('create', Testimonial::class);

        $this->loadLanguages();

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
        // P1 fix
        $this->authorize('create', Testimonial::class);

        $this->loadLanguages();

        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            $testimonialData = [
                'order'       => $validated['order'],
                'star'        => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : true,
                'image_id'    => $validated['featured_image_id'] ?? null,
            ];

            $testimonial = Testimonial::create($testimonialData);

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
            // P2 fix: log internally, show generic message to user
            Log::error('Testimonial store failed: ' . $th->getMessage(), ['exception' => $th]);

            return back()
                ->withErrors(['error' => __('حدث خطأ أثناء الحفظ، يرجى المحاولة مجدداً.')])
                ->withInput();
        }
    }

    /**
     * صفحة تعديل تقييم.
     */
    public function edit($id)
    {
        $testimonial = Testimonial::with(['translations', 'image'])->findOrFail($id);

        // P1 fix
        $this->authorize('update', $testimonial);

        $this->loadLanguages();

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

        // P1 fix
        $this->authorize('update', $testimonial);

        $this->loadLanguages();

        $validated = $this->validateTestimonialRequest($request);

        DB::beginTransaction();

        try {
            // P5 fix: single assignment from $validated only — no duplicate raw $request->input() block
            $testimonialData = [
                'order'       => $validated['order'],
                'star'        => $validated['star'] ?? null,
                'is_approved' => array_key_exists('is_approved', $validated)
                    ? (bool) $validated['is_approved']
                    : $testimonial->is_approved,
            ];

            // Only update image_id if the field was submitted (allows clearing to null)
            if (array_key_exists('featured_image_id', $validated)) {
                $testimonialData['image_id'] = $validated['featured_image_id'] ?: null;
            }

            $testimonial->update($testimonialData);

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

            // Remove translations that were not submitted
            if (! empty($providedLocales)) {
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
            // P2 fix: log internally, show generic message to user
            Log::error('Testimonial update failed for id=' . $id . ': ' . $th->getMessage(), ['exception' => $th]);

            return back()
                ->withErrors(['error' => __('حدث خطأ أثناء الحفظ، يرجى المحاولة مجدداً.')])
                ->withInput();
        }
    }

    /**
     * حذف تقييم.
     */
    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        // P1 fix
        $this->authorize('delete', $testimonial);

        // P7 fix: soft-delete — translations cascade via DB FK onDelete('cascade')
        $testimonial->delete();

        return redirect()
            ->route('dashboard.testimonials.index')
            ->with('success', 'تم حذف التقييم بنجاح.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * التحقق من الطلب (مع دعم الترجمة المتعددة + الميديا).
     */
    protected function validateTestimonialRequest(Request $request): array
    {
        // P4 fix: $this->languages already loaded by caller
        $localeCodes = $this->languages
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $translationLocaleRules = ['required', 'string'];

        if (! empty($localeCodes)) {
            $translationLocaleRules[] = Rule::in($localeCodes);
        }

        $rules = [
            'order'             => 'required|integer|min:1',
            'star'              => 'nullable|integer|min:1|max:5',
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

        // Custom rule: at least one complete translation required
        $validator->after(function ($validator) use ($request, $languageLabels) {
            $translations        = $request->input('testimonialTranslations', []);
            $hasCompleteLanguage = false;

            foreach ($translations as $code => $translation) {
                $feedback = trim((string) ($translation['feedback'] ?? ''));
                $name     = trim((string) ($translation['name'] ?? ''));
                $major    = trim((string) ($translation['major'] ?? ''));

                $hasAny = $feedback !== '' || $name !== '' || $major !== '';

                if ($feedback !== '' && $name !== '' && $major !== '') {
                    $hasCompleteLanguage = true;
                    continue;
                }

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

            if (! $hasCompleteLanguage) {
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
