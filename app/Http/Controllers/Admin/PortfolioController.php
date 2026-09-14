<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\PortfolioTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    private const STATUS_SUGGESTIONS = [
        'ar' => ['مفعل', 'غير مفعل', 'مكتمل'],
        'en' => ['Active', 'Inactive', 'Completed'],
    ];

    private const STATUS_CLASSES = [
        0 => 'portfolio-status-active bg-emerald-50 text-emerald-700',
        1 => 'portfolio-status-inactive bg-gray-100 text-gray-600',
        2 => 'portfolio-status-completed bg-blue-50 text-blue-700',
    ];

    // -------------------------------------------------------------------------
    // Shared state — loaded lazily only when needed (P5 fix)
    // -------------------------------------------------------------------------

    protected ?object $languages       = null;
    protected ?array  $typeSuggestions = null;
    protected ?array  $statusSuggestions = null;

    /**
     * Load languages once and cache on the instance.
     * Called only by actions that actually need the language list (create/edit).
     */
    protected function loadLanguages(): void
    {
        if ($this->languages !== null) {
            return;
        }

        $this->languages = Language::get();

        // P10 fix: use the already-loaded $this->languages instead of a second DB query.
        $activeCodes = $this->languages->where('is_active', 1)->pluck('code')->all();

        // Build type suggestions per language (one query per language — acceptable for small sets)
        $this->typeSuggestions = $this->languages->mapWithKeys(function ($lang) {
            $types = PortfolioTranslation::where('locale', $lang->code)
                ->whereNotNull('type')
                ->pluck('type')
                ->flatMap(fn($str) => collect(preg_split('/[,،]/u', $str))->map('trim')->filter())
                ->unique()
                ->values();
            return [$lang->code => $types];
        })->toArray();

        $this->statusSuggestions = self::STATUS_SUGGESTIONS;

        foreach ($this->languages as $lang) {
            if (! isset($this->statusSuggestions[$lang->code])) {
                $this->statusSuggestions[$lang->code] = $this->statusSuggestions['en'] ?? [];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------------------------

    /**
     * Build per-translation validation rules based on active languages.
     * P10 fix: uses already-loaded $this->languages instead of extra DB query.
     */
    protected function buildTranslationRules(Request $request, ?Portfolio $portfolio = null): array
    {
        // P10: reuse loaded collection — no extra DB round-trip
        $activeCodes = $this->languages->where('is_active', 1)->pluck('code')->all();
        $allCodes    = $this->languages->pluck('code')->all();

        $translations = $request->input('translations', []);
        $translations = is_array($translations) ? $translations : [];
        $usedActiveCodes = collect($translations)
            ->filter(fn ($translation) => is_array($translation)
                && in_array($translation['locale'] ?? null, $activeCodes, true)
                && $this->translationHasMeaningfulContent($translation))
            ->pluck('locale')
            ->all();
        $hasUsedActiveLanguage = $usedActiveCodes !== [];
        $firstActiveCode = $activeCodes[0] ?? null;

        $rules = [
            'translations' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($hasUsedActiveLanguage): void {
                    if (! $hasUsedActiveLanguage) {
                        $fail(t(
                            'dashboard.Portfolio_At_Least_One_Language',
                            'Complete the required fields in at least one active language.'
                        ));
                    }
                },
            ],
        ];

        foreach ($translations as $i => $t) {
            $locale    = is_array($t) ? ($t['locale'] ?? null) : null;
            $isActive  = in_array($locale, $activeCodes, true);
            $isUsed    = $isActive && $this->translationHasMeaningfulContent(is_array($t) ? $t : []);
            $requireTitle = $isUsed || (! $hasUsedActiveLanguage && $locale === $firstActiveCode);

            $rules["translations.$i.locale"]      = 'required|string|in:' . implode(',', $allCodes);
            $rules["translations.$i.title"]       = ($requireTitle ? 'required' : 'nullable') . '|string|max:500';
            $rules["translations.$i.type"]        = ($isUsed ? 'required' : 'nullable') . '|string|max:255';
            $rules["translations.$i.materials"]   = ($isUsed ? 'required' : 'nullable') . '|string|max:500';
            $rules["translations.$i.link"]        = 'nullable|string|max:2048';
            $allowedStatuses = $this->statusSuggestions[$locale]
                ?? ($this->statusSuggestions['en'] ?? []);
            $storedStatus = $portfolio?->translations
                ->firstWhere('locale', $locale)?->status;
            if (is_string($storedStatus) && $storedStatus !== '') {
                $allowedStatuses[] = $storedStatus;
            }
            $rules["translations.$i.status"] = [
                'nullable', 'string', 'max:100', Rule::in(array_values(array_unique($allowedStatuses))),
            ];
            $rules["translations.$i.description"] = 'nullable|string';
        }

        return $rules;
    }

    /** A language is used when any user-editable translation value is non-empty. */
    private function translationHasMeaningfulContent(array $translation): bool
    {
        foreach (['title', 'type', 'materials', 'link', 'status', 'description'] as $field) {
            $value = $translation[$field] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a unique slug by checking existing DB rows.
     * Callers (store/update) wrap the actual insert/update in a retry loop that
     * catches QueryException SQLSTATE 23000 for the rare concurrent collision case.
     */
    public function generateUniqueSlug(string $string, ?int $excludeId = null): string
    {
        $original = Str::slug($string) ?: 'portfolio';
        $slug     = $original;
        $counter  = 1;

        while (
            Portfolio::where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Convert a single Media ID or comma-separated Media IDs to a stored path string.
     * Used for the `default_image` (single-image) field only (ADR-005 Wave 1 dual-write).
     */
    private function resolveMediaIdsToPaths(mixed $input): ?string
    {
        if (! $input) {
            return null;
        }

        if (is_numeric($input)) {
            $media = \App\Models\Media::find((int) $input);
            return $media?->file_path;
        }

        if (is_string($input)) {
            // P13 fix: cast to int and filter non-positive values to prevent arbitrary strings
            $ids = array_values(array_filter(array_map('intval', explode(',', $input))));
            if (! empty($ids)) {
                $paths = \App\Models\Media::whereIn('id', $ids)
                    ->pluck('file_path')
                    ->filter()
                    ->values()
                    ->toArray();
                return ! empty($paths) ? json_encode($paths) : null;
            }
        }

        return null;
    }

    /**
     * Return ordered, unique IDs; the model's array cast owns JSON serialization.
     * ADR-005 Wave 3: portfolios.images now stores IDs, not paths.
     */
    private function resolveImagesToIds(mixed $input): array
    {
        if (! is_string($input) || $input === '') {
            return [];
        }

        $ids = array_filter(array_map('intval', explode(',', $input)), fn ($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    // -------------------------------------------------------------------------
    // CRUD actions
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('viewAny', Portfolio::class);

        $search  = trim((string) $request->get('search', ''));
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page') : 10;

        $portfolios = Portfolio::with(['translations', 'defaultImageMedia'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('translations', function ($t) use ($search) {
                    $t->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%')
                      ->orWhere('type', 'like', '%' . addcslashes($search, '%_\\') . '%');
                })->orWhere('client', 'like', '%' . addcslashes($search, '%_\\') . '%');
            })
            ->orderBy('order')
            ->paginate($perPage)
            ->withQueryString();

        $statusStyles = $this->statusStyles();

        $returnContext = ['return_page' => $portfolios->currentPage(), 'return_per_page' => $perPage];
        if ($search !== '') $returnContext['return_search'] = $search;

        return view('dashboard.portfolios.index', compact('portfolios', 'search', 'perPage', 'statusStyles', 'returnContext'));
    }

    public function create()
    {
        $this->authorize('create', Portfolio::class);

        $this->loadLanguages();

        $portfolio            = new Portfolio();
        $portfolioTranslations = [];
        $languages            = $this->languages;
        $typeSuggestions      = $this->typeSuggestions;
        $statusSuggestions    = $this->statusSuggestions;
        $portfolioMedia       = $this->portfolioPreviewMedia($portfolio);
        $returnContext        = $this->portfolioReturnContext(request());

        return view('dashboard.portfolios.create',
            compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions', 'portfolioMedia', 'returnContext'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Portfolio::class);

        $this->loadLanguages();

        // P13 fix: validate images as comma-separated integers
        $baseRules = [
            'order'                      => 'required|integer|min:0',
            'delivery_date'              => 'required|date',
            'implementation_period_days' => 'nullable|integer|min:0',
            'client'                     => 'nullable|string|max:255',
            'default_image'              => ['bail', 'nullable', 'integer', $this->imageMediaRule()],
            'remove_default_image'       => 'sometimes|boolean',
            'images'                     => ['bail', 'nullable', 'string', 'regex:/^(\d+)(,\d+)*$/', $this->imageMediaRule(true)],
            'return_search'              => 'nullable|string|max:255',
            'return_page'                => 'nullable|integer|min:1',
            'return_per_page'            => 'nullable|integer|in:10,25,50',
        ];

        $translationRules = $this->buildTranslationRules($request);
        $validated        = $request->validate($baseRules + $translationRules);

        DB::beginTransaction();

        try {
            $translations  = $request->input('translations', []);
            $activeCodes   = $this->languages->where('is_active', 1)->pluck('code')->all();
            $titleForSlug  = collect($translations)
                ->first(fn($t) => in_array($t['locale'] ?? '', $activeCodes, true) && ! empty($t['title']))
                ['title']
                ?? (collect($translations)->firstWhere('title')['title'] ?? 'portfolio');

            // P11 fix: build from $validated (explicit fields only — not $request->except())
            $rawDefaultImageId = $validated['default_image'] ?? null;
            $portfolioData = [
                'order'                      => $validated['order'],
                'delivery_date'              => $validated['delivery_date'],
                'implementation_period_days' => $validated['implementation_period_days'] ?? null,
                'client'                     => $validated['client'] ?? null,
                // ADR-005 Wave 1 dual-write: keep path for old column, save ID in new FK column
                'default_image'              => $this->resolveMediaIdsToPaths($rawDefaultImageId),
                'default_image_media_id'     => $rawDefaultImageId ? (int) $rawDefaultImageId : null,
                // ADR-005 Wave 3: store gallery IDs directly (no path conversion)
                'images'                     => $this->resolveImagesToIds($validated['images'] ?? null),
            ];

            // P8 fix: retry on rare concurrent slug collision (SQLSTATE 23000)
            $portfolio = null;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $portfolioData['slug'] = $this->generateUniqueSlug($titleForSlug);
                    $portfolio = Portfolio::create($portfolioData);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempt < 2 && str_contains($e->getMessage(), '23000')) {
                        continue;
                    }
                    throw $e;
                }
            }

            // P6 fix: use null-safe access on every translation key
            foreach ($translations as $translation) {
                if (! is_array($translation) || ! $this->translationHasMeaningfulContent($translation)) {
                    continue;
                }
                PortfolioTranslation::create([
                    'portfolio_id' => $portfolio->id,
                    'locale'       => $translation['locale']       ?? '',
                    'title'        => $translation['title']        ?? null,
                    'type'         => $translation['type']         ?? null,
                    'materials'    => $translation['materials']    ?? null,
                    'link'         => $translation['link']         ?? null,
                    'status'       => $translation['status']       ?? null,
                    'description'  => $translation['description']  ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('dashboard.portfolios.index', $this->portfolioReturnContext($request))
                ->with('ok', t('dashboard.Portfolio_Created', 'Portfolio created successfully.'));

        } catch (\Exception $e) {
            DB::rollBack();
            // P12 fix: log internally, show generic message to user
            Log::error('Portfolio store failed: ' . $e->getMessage(), ['exception' => $e]);
            return back()->withInput($this->restorableFormInput($validated))
                ->with('error', t('dashboard.Portfolio_Error', 'An error occurred while saving. Please try again.'));
        }
    }

    public function edit($id)
    {
        $portfolio = Portfolio::with('translations')->findOrFail($id);
        $this->authorize('update', $portfolio);

        $this->loadLanguages();

        $portfolioTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $portfolio->translations->firstWhere('locale', $lang->code);
            $portfolioTranslations[$lang->code] = [
                'locale'      => $lang->code,
                'title'       => $trans?->title       ?? '',
                'type'        => $trans?->type        ?? '',
                'materials'   => $trans?->materials   ?? '',
                'link'        => $trans?->link        ?? '',
                'status'      => $trans?->status      ?? '',
                'description' => $trans?->description ?? '',
            ];
        }

        $languages         = $this->languages;
        $typeSuggestions   = $this->typeSuggestions;
        $statusSuggestions = $this->statusSuggestions;
        $portfolioMedia     = $this->portfolioPreviewMedia($portfolio);
        $returnContext      = $this->portfolioReturnContext(request());

        return view('dashboard.portfolios.edit',
            compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions', 'portfolioMedia', 'returnContext'));
    }

    public function update(Request $request, $id)
    {
        $portfolio = Portfolio::with('translations')->findOrFail($id);
        $this->authorize('update', $portfolio);

        $this->loadLanguages();

        $baseRules = [
            'order'                      => 'required|integer|min:0',
            'delivery_date'              => 'required|date',
            'implementation_period_days' => 'nullable|integer|min:0',
            'client'                     => 'nullable|string|max:255',
            'default_image'              => ['bail', 'nullable', 'integer', $this->imageMediaRule()],
            'remove_default_image'       => 'sometimes|boolean',
            'images'                     => ['bail', 'nullable', 'string', 'regex:/^(\d+)(,\d+)*$/', $this->imageMediaRule(true)],
            'return_search'              => 'nullable|string|max:255',
            'return_page'                => 'nullable|integer|min:1',
            'return_per_page'            => 'nullable|integer|in:10,25,50',
        ];

        $translationRules = $this->buildTranslationRules($request, $portfolio);
        $validated        = $request->validate($baseRules + $translationRules);

        DB::beginTransaction();

        try {
            $translations = $request->input('translations', []);
            $activeCodes  = $this->languages->where('is_active', 1)->pluck('code')->all();
            $titleForSlug = collect($translations)
                ->first(fn($t) => in_array($t['locale'] ?? '', $activeCodes, true) && ! empty($t['title']))
                ['title']
                ?? (collect($translations)->firstWhere('title')['title'] ?? 'portfolio');

            // P11 fix: explicit field list from $validated
            $rawDefaultImageId = $validated['default_image'] ?? null;
            // A legacy path-only image has no ID to submit. Keep it until a replacement is selected.
            $preserveLegacyDefaultImage = ! $rawDefaultImageId && ! $portfolio->default_image_media_id
                && ! ($validated['remove_default_image'] ?? false);
            $portfolioData = [
                'order'                      => $validated['order'],
                'delivery_date'              => $validated['delivery_date'],
                'implementation_period_days' => $validated['implementation_period_days'] ?? null,
                'client'                     => $validated['client'] ?? null,
                // ADR-005 Wave 1 dual-write: keep path for old column, save ID in new FK column
                'default_image'              => $preserveLegacyDefaultImage
                    ? $portfolio->default_image : $this->resolveMediaIdsToPaths($rawDefaultImageId),
                'default_image_media_id'     => $rawDefaultImageId ? (int) $rawDefaultImageId : null,
                // ADR-005 Wave 3: store gallery IDs directly (no path conversion)
                'images'                     => $this->resolveImagesToIds($validated['images'] ?? null),
            ];

            // An unrelated update must not overwrite a gallery that was not submitted.
            if (! array_key_exists('images', $validated)) {
                unset($portfolioData['images']);
            }

            // P8 fix: retry on rare concurrent slug collision (SQLSTATE 23000)
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $portfolioData['slug'] = $this->generateUniqueSlug($titleForSlug, (int) $id);
                    $portfolio->update($portfolioData);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempt < 2 && str_contains($e->getMessage(), '23000')) {
                        continue;
                    }
                    throw $e;
                }
            }

            // P6 fix: null-safe on every translation key
            foreach ($translations as $translation) {
                // A blank language means "unused", not "delete this translation".
                if (! is_array($translation) || ! $this->translationHasMeaningfulContent($translation)) {
                    continue;
                }
                PortfolioTranslation::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'locale' => $translation['locale'] ?? ''],
                    [
                        'title'       => $translation['title']       ?? null,
                        'description' => $translation['description'] ?? null,
                        'type'        => $translation['type']        ?? null,
                        'materials'   => $translation['materials']   ?? null,
                        'link'        => $translation['link']        ?? null,
                        'status'      => array_key_exists('status', $translation)
                            ? $translation['status']
                            : $portfolio->translations->firstWhere('locale', $translation['locale'] ?? '')?->status,
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.portfolios.index', $this->portfolioReturnContext($request))
                ->with('ok', t('dashboard.Portfolio_Updated', 'Portfolio updated successfully.'));

        } catch (\Exception $e) {
            DB::rollBack();
            // P12 fix: log internally, show generic message
            Log::error('Portfolio update failed for id=' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return back()->withInput($this->restorableFormInput($validated))
                ->with('error', t('dashboard.Portfolio_Error', 'An error occurred while saving. Please try again.'));
        }
    }

    private function statusStyles(): array
    {
        $styles = [];
        foreach (self::STATUS_SUGGESTIONS as $statuses) {
            foreach ($statuses as $index => $status) {
                $styles[$status] = self::STATUS_CLASSES[$index];
            }
        }

        return $styles;
    }

    /** Whitelisted portfolio-list state; never accepts a return URL. */
    private function portfolioReturnContext(Request $request): array
    {
        $context = [];
        $returnValue = fn (string $key) => $request->input($key, $request->hasSession() ? $request->session()->getOldInput($key) : null);
        $search = trim((string) $returnValue('return_search'));
        if ($search !== '' && mb_strlen($search) <= 255) $context['search'] = $search;

        $page = filter_var($returnValue('return_page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($page !== false) $context['page'] = $page;

        $perPage = filter_var($returnValue('return_per_page'), FILTER_VALIDATE_INT);
        if (in_array($perPage, [10, 25, 50], true)) $context['per_page'] = $perPage;

        return $context;
    }

    /** Fetch all media needed by the form once; the ordered ID list remains authoritative. */
    private function portfolioPreviewMedia(Portfolio $portfolio)
    {
        $ids = [];
        $default = old('default_image', $portfolio->default_image_media_id);
        if (is_scalar($default) && ctype_digit((string) $default) && (int) $default > 0) {
            $ids[] = (int) $default;
        }

        $gallery = old('images', $portfolio->images);
        if (is_string($gallery)) {
            $trimmed = trim($gallery);
            $decoded = str_starts_with($trimmed, '[') ? json_decode($trimmed, true) : explode(',', $trimmed);
            $gallery = is_array($decoded) ? $decoded : [];
        }
        if (is_array($gallery)) {
            foreach ($gallery as $id) {
                if ((is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0) {
                    $ids[] = (int) $id;
                }
            }
        }

        $ids = array_values(array_unique($ids));

        return $ids === [] ? collect() : Media::whereIn('id', $ids)->get()->keyBy('id');
    }

    private function imageMediaRule(bool $multiple = false): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($multiple): void {
            $ids = [];
            foreach ($multiple ? explode(',', (string) $value) : [$value] as $rawId) {
                $id = filter_var(ltrim((string) $rawId, '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false) {
                    $fail(t('dashboard.Portfolio_Invalid_Image', 'Choose existing image media only.'));
                    return;
                }
                $ids[$id] = $id;
            }
            if (Media::images()->whereIn('id', array_values($ids))->count() !== count($ids)) {
                $fail(t('dashboard.Portfolio_Invalid_Image', 'Choose existing image media only.'));
            }
        };
    }

    private function restorableFormInput(array $validated): array
    {
        // Explicitly exclude tokens, uploads and unknown keys, including nested keys
        // retained by Laravel's parent translations array validation rule.
        $input = Arr::only($validated, [
            'order', 'delivery_date', 'implementation_period_days', 'client', 'default_image', 'images', 'remove_default_image',
            'return_search', 'return_page', 'return_per_page',
        ]);
        $input['translations'] = array_map(fn (array $translation) => Arr::only($translation, [
            'locale', 'title', 'type', 'materials', 'link', 'status', 'description',
        ]), $validated['translations'] ?? []);

        return $input;
    }

    public function destroy($id)
    {
        $portfolio = Portfolio::findOrFail($id);
        $this->authorize('delete', $portfolio);

        // P9 fix: soft-delete (requires SoftDeletes on the model + deleted_at migration)
        $portfolio->delete();

        return redirect()->route('dashboard.portfolios.index')
            ->with('ok', t('dashboard.Portfolio_Deleted', 'Portfolio deleted successfully.'));
    }
}
