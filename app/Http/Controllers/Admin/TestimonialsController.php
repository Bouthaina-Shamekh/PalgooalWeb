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
    public function index(Request $request)
    {
        // P1 fix: authorize before returning data
        $this->authorize('viewAny', Testimonial::class);

        $search  = trim((string) $request->get('search', ''));
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page') : 10;

        $testimonials = Testimonial::with(['translations', 'image'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('translations', function ($t) use ($search) {
                    $t->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                      ->orWhere('major', 'like', '%' . addcslashes($search, '%_\\') . '%');
                });
            })
            ->orderBy('order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.testimonials.index', compact('testimonials', 'search', 'perPage'));
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
                    'testimonial_id' => $testimonial->id,
                    'locale'         => $translation['locale'],
                    'text'           => $translation['text'],
                    'name'           => $translation['name'],
                    'major'          => $translation['major'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard.testimonials.index')
                ->with('ok', t('dashboard.Testimonial_Created', 'Testimonial added successfully.'));

        } catch (\Throwable $th) {
            DB::rollBack();
            // P2 fix: log internally, show generic message to user
            Log::error('Testimonial store failed: ' . $th->getMessage(), ['exception' => $th]);

            return back()
                ->with('error', t('dashboard.Testimonial_Error', 'An error occurred while saving. Please try again.'))
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
                'locale' => $lang->code,
                'text'   => $trans?->text ?? '',
                'name'   => $trans?->name ?? '',
                'major'  => $trans?->major ?? '',
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
                        'testimonial_id' => $testimonial->id,
                        'locale'         => $translation['locale'],
                    ],
                    [
                        'text'  => $translation['text'],
                        'name'  => $translation['name'],
                        'major' => $translation['major'],
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
                ->with('ok', t('dashboard.Testimonial_Updated', 'Testimonial updated successfully.'));

        } catch (\Throwable $th) {
            DB::rollBack();
            // P2 fix: log internally, show generic message to user
            Log::error('Testimonial update failed for id=' . $id . ': ' . $th->getMessage(), ['exception' => $th]);

            return back()
                ->with('error', t('dashboard.Testimonial_Error', 'An error occurred while saving. Please try again.'))
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
            ->with('ok', t('dashboard.Testimonial_Deleted', 'Testimonial deleted successfully.'));
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
            'testimonialTranslations.*.text'     => 'nullable|string',
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
                $text  = trim((string) ($translation['text'] ?? ''));
                $name  = trim((string) ($translation['name'] ?? ''));
                $major = trim((string) ($translation['major'] ?? ''));

                $hasAny = $text !== '' || $name !== '' || $major !== '';

                if ($text !== '' && $name !== '' && $major !== '') {
                    $hasCompleteLanguage = true;
                    continue;
                }

                if ($hasAny) {
                    $label = $languageLabels[$translation['locale'] ?? $code]
                        ?? ($translation['locale'] ?? $code);

                    if ($text === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.text",
                            strtr(t('dashboard.Field_Required_For_Lang', 'This field is required for :lang.'), [':lang' => $label])
                        );
                    }
                    if ($name === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.name",
                            strtr(t('dashboard.Field_Required_For_Lang', 'This field is required for :lang.'), [':lang' => $label])
                        );
                    }
                    if ($major === '') {
                        $validator->errors()->add(
                            "testimonialTranslations.$code.major",
                            strtr(t('dashboard.Field_Required_For_Lang', 'This field is required for :lang.'), [':lang' => $label])
                        );
                    }
                }
            }

            if (! $hasCompleteLanguage) {
                $validator->errors()->add(
                    'testimonialTranslations',
                    t('dashboard.Translation_Required', 'Please fill in all fields for at least one language.')
                );
            }
        });

        return $validator->validate();
    }

    /**
     * إبقاء فقط الترجمات الكاملة (text + name + major).
     */
    protected function extractCompleteTranslations(array $translations): array
    {
        $complete = [];

        foreach ($translations as $translation) {
            $locale = $translation['locale'] ?? null;
            $text   = trim((string) ($translation['text'] ?? ''));
            $name   = trim((string) ($translation['name'] ?? ''));
            $major  = trim((string) ($translation['major'] ?? ''));

            if ($locale && $text !== '' && $name !== '' && $major !== '') {
                $complete[] = [
                    'locale' => $locale,
                    'text'   => $text,
                    'name'   => $name,
                    'major'  => $major,
                ];
            }
        }

        return $complete;
    }
}
