<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Portfolio;
use App\Models\PortfolioTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
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

        $this->statusSuggestions = [
            'ar' => ['مفعل', 'غير مفعل', 'مكتمل'],
            'en' => ['Active', 'Inactive', 'Completed'],
        ];

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
    protected function buildTranslationRules(Request $request): array
    {
        // P10: reuse loaded collection — no extra DB round-trip
        $activeCodes = $this->languages->where('is_active', 1)->pluck('code')->all();
        $allCodes    = $this->languages->pluck('code')->all();

        $rules = ['translations' => 'required|array'];

        foreach ($request->input('translations', []) as $i => $t) {
            $locale    = $t['locale'] ?? null;
            $isActive  = in_array($locale, $activeCodes, true);
            $reqOrNull = $isActive ? 'required' : 'nullable';

            $rules["translations.$i.locale"]      = 'required|string|in:' . implode(',', $allCodes);
            $rules["translations.$i.title"]       = "{$reqOrNull}|string|max:500";
            $rules["translations.$i.type"]        = "{$reqOrNull}|string|max:255";
            $rules["translations.$i.materials"]   = "{$reqOrNull}|string|max:500";
            $rules["translations.$i.link"]        = 'nullable|string|max:2048';
            $rules["translations.$i.status"]      = 'nullable|string|max:100';
            $rules["translations.$i.description"] = 'nullable|string';
        }

        return $rules;
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
     * Convert a single Media ID or comma-separated Media IDs to stored path(s).
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

    // -------------------------------------------------------------------------
    // CRUD actions
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('viewAny', Portfolio::class);

        $search  = trim((string) $request->get('search', ''));
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page') : 10;

        $portfolios = Portfolio::with('translations')
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('translations', function ($t) use ($search) {
                    $t->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%')
                      ->orWhere('type', 'like', '%' . addcslashes($search, '%_\\') . '%');
                })->orWhere('client', 'like', '%' . addcslashes($search, '%_\\') . '%');
            })
            ->orderBy('order')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.portfolios.index', compact('portfolios', 'search', 'perPage'));
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

        return view('dashboard.portfolios.create',
            compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions'));
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
            'default_image'              => 'nullable|integer|exists:media,id',
            'images'                     => ['nullable', 'string', 'regex:/^(\d+)(,\d+)*$/'],
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
            $portfolioData = [
                'order'                      => $validated['order'],
                'delivery_date'              => $validated['delivery_date'],
                'implementation_period_days' => $validated['implementation_period_days'] ?? null,
                'client'                     => $validated['client'] ?? null,
                'default_image'              => $this->resolveMediaIdsToPaths($validated['default_image'] ?? null),
                'images'                     => $this->resolveMediaIdsToPaths($validated['images'] ?? null),
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

            return redirect()->route('dashboard.portfolios.index')
                ->with('ok', t('dashboard.Portfolio_Created', 'Portfolio created successfully.'));

        } catch (\Exception $e) {
            DB::rollBack();
            // P12 fix: log internally, show generic message to user
            Log::error('Portfolio store failed: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', t('dashboard.Portfolio_Error', 'An error occurred while saving. Please try again.'));
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

        return view('dashboard.portfolios.edit',
            compact('portfolio', 'portfolioTranslations', 'languages', 'typeSuggestions', 'statusSuggestions'));
    }

    public function update(Request $request, $id)
    {
        $portfolio = Portfolio::findOrFail($id);
        $this->authorize('update', $portfolio);

        $this->loadLanguages();

        $baseRules = [
            'order'                      => 'required|integer|min:0',
            'delivery_date'              => 'required|date',
            'implementation_period_days' => 'nullable|integer|min:0',
            'client'                     => 'nullable|string|max:255',
            'default_image'              => 'nullable|integer|exists:media,id',
            'images'                     => ['nullable', 'string', 'regex:/^(\d+)(,\d+)*$/'],
        ];

        $translationRules = $this->buildTranslationRules($request);
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
            $portfolioData = [
                'order'                      => $validated['order'],
                'delivery_date'              => $validated['delivery_date'],
                'implementation_period_days' => $validated['implementation_period_days'] ?? null,
                'client'                     => $validated['client'] ?? null,
                'default_image'              => $this->resolveMediaIdsToPaths($validated['default_image'] ?? null),
                'images'                     => $this->resolveMediaIdsToPaths($validated['images'] ?? null),
            ];

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
                PortfolioTranslation::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'locale' => $translation['locale'] ?? ''],
                    [
                        'title'       => $translation['title']       ?? null,
                        'description' => $translation['description'] ?? null,
                        'type'        => $translation['type']        ?? null,
                        'materials'   => $translation['materials']   ?? null,
                        'link'        => $translation['link']        ?? null,
                        'status'      => $translation['status']      ?? null,
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.portfolios.index')
                ->with('ok', t('dashboard.Portfolio_Updated', 'Portfolio updated successfully.'));

        } catch (\Exception $e) {
            DB::rollBack();
            // P12 fix: log internally, show generic message
            Log::error('Portfolio update failed for id=' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', t('dashboard.Portfolio_Error', 'An error occurred while saving. Please try again.'));
        }
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
