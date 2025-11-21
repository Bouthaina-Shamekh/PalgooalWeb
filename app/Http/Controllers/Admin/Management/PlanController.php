<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Server;
use App\Models\PlanCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::latest()->paginate(20);
        return view('dashboard.management.plans.index', compact('plans'));
    }

    public function create()
    {
        $servers = Server::all();
        // eager load translations to avoid N+1 when rendering labels
        $categories = PlanCategory::with('translations')->get();
        return view('dashboard.management.plans.create', compact('servers', 'categories'));
    }

    private function parsePrice(Request $r, string $field): ?int
    {
        // expects calls like $this->parsePrice($r, 'monthly_price') and fields monthly_price_cents / monthly_price_ui
        $value = $r->input($field . '_cents');
        if ($value === null || $value === '') {
            $uiValue = $r->input($field . '_ui');
            $value = ($uiValue !== null && $uiValue !== '') ? (int) round(floatval($uiValue) * 100) : null;
        }
        return $value;
    }

    private function validateTranslation(Request $r, string $locale): array
    {
        $input = [
            'name' => $r->input("name.$locale"),
            'description' => $r->input("description.$locale"),
            'featured_label' => $r->input("featured_label.$locale"),
            'features' => $r->input("features.$locale"),
        ];

        $validator = Validator::make($input, [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'featured_label' => 'nullable|string|max:120',
            'features' => 'nullable|array',
            'features.*' => 'nullable|array',
            'features.*.*' => 'nullable|array',
            'features.*.text' => 'nullable|string|max:255',
            'features.*.available' => 'nullable',
            'features.*.*.text' => 'nullable|string|max:255',
            'features.*.*.available' => 'nullable',
        ], [
            'name.required' => '�?�?�?�?�? �?���?�?�?',
        ]);

        $translation = $validator->validate();

        $rawFeatures = $translation['features'] ?? [];
        $normalized = [
            'monthly' => [],
            'annual' => [],
        ];

        if (is_array($rawFeatures)) {
            if ($this->featuresContainBillingBuckets($rawFeatures)) {
                $normalized['monthly'] = $this->normalizeFeatureItems($rawFeatures['monthly'] ?? []);
                $normalized['annual'] = $this->normalizeFeatureItems($rawFeatures['annual'] ?? []);
            } else {
                $normalized['monthly'] = $this->normalizeFeatureItems($rawFeatures);
            }
        }

        if (empty($normalized['monthly']) && empty($normalized['annual'])) {
            $translation['features'] = [];
        } else {
            $translation['features'] = $normalized;
        }

        $label = isset($translation['featured_label'])
            ? trim((string) $translation['featured_label'])
            : null;
        $translation['featured_label'] = $label !== '' ? $label : null;

        return $translation;
    }

    private function normalizeFeatureItems($items): array
    {
        $normalized = [];
        foreach ((array) $items as $item) {
            if (is_array($item)) {
                $text = isset($item['text']) ? trim((string) $item['text']) : '';
                if ($text === '') {
                    continue;
                }
                $availableRaw = $item['available'] ?? true;
                $available = filter_var($availableRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $normalized[] = [
                    'text' => $text,
                    'available' => $available === null ? (bool) $availableRaw : (bool) $available,
                ];
                continue;
            }

            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }
            $normalized[] = [
                'text' => $text,
                'available' => true,
            ];
        }

        return $normalized;
    }

    private function featuresContainBillingBuckets(array $features): bool
    {
        foreach (['monthly', 'annual'] as $bucket) {
            if (array_key_exists($bucket, $features)) {
                return true;
            }
        }

        return false;
    }

    public function store(Request $r)
    {
        // normalize booleans
        $r->merge([
            'is_active' => $r->boolean('is_active'),
            'is_featured' => $r->boolean('is_featured'),
        ]);

        // parse prices (will read either *_cents or *_ui)
        $monthly = $this->parsePrice($r, 'monthly_price');
        $annual = $this->parsePrice($r, 'annual_price');

        $r->merge([
            'monthly_price_cents' => $monthly,
            'annual_price_cents' => $annual,
        ]);

        $data = $r->validate([
            'slug' => 'nullable|string|max:140|unique:plans,slug',
            'monthly_price_cents' => 'nullable|integer|min:0',
            'annual_price_cents' => 'nullable|integer|min:0',
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'server_package' => ['nullable', 'string', 'max:255'],
            'plan_category_id' => ['nullable', 'integer', 'exists:plan_categories,id'],
            'plan_type' => ['required', Rule::in([Plan::TYPE_MULTI_TENANT, Plan::TYPE_HOSTING])],
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($monthly === null && $annual === null) {
            return back()->withErrors(['monthly_price_cents' => 'ظٹط¬ط¨ ط¥ط¯ط®ط§ظ„ ط³ط¹ط± ط´ظ‡ط±ظٹ ط£ظˆ ط³ظ†ظˆظٹ ط¹ظ„ظ‰ ط§ظ„ط£ظ‚ظ„'])->withInput();
        }

        $locale = app()->getLocale();
        $translation = $this->validateTranslation($r, $locale);

        // slug and top-level name
        $data['slug'] = $data['slug'] ?: Str::slug($translation['name']);
        $data['name'] = $translation['name'];
        $data['plan_type'] = $data['plan_type'] ?? Plan::TYPE_MULTI_TENANT;

        // If server_package is not provided, default it to the plan name so provisioning can use it
        if (empty($data['server_package'])) {
            $data['server_package'] = $data['name'];
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $primaryFeaturedLabel = $translation['featured_label'] ?? null;
        $data['featured_label'] = $data['is_featured']
            ? ($primaryFeaturedLabel ?: null)
            : null;

        // Important: do NOT convert plan_category_id -> category_id.
        // We expect the DB column to be plan_category_id and the Plan model to have it fillable.

        $plan = Plan::create($data);

        $plan->translations()->create([
            'locale' => $locale,
            'title' => $translation['name'],
            'description' => $translation['description'] ?? '',
            'features' => $translation['features'],
            'featured_label' => $translation['featured_label'],
        ]);

        return redirect()->route('dashboard.plans.index')->with('ok', 'طھظ… ط¥ظ†ط´ط§ط، ط§ظ„ط®ط·ط© ط¨ظ†ط¬ط§ط­');
    }

    public function edit(Plan $plan)
    {
        $servers = Server::all();
        $categories = PlanCategory::with('translations')->get();
        $translation = $plan->translations()->where('locale', app()->getLocale())->first();
        return view('dashboard.management.plans.edit', compact('plan', 'servers', 'categories', 'translation'));
    }

    public function update(Request $r, Plan $plan)
    {
        $r->merge([
            'is_active' => $r->boolean('is_active'),
            'is_featured' => $r->boolean('is_featured'),
        ]);

        $monthly = $this->parsePrice($r, 'monthly_price');
        $annual = $this->parsePrice($r, 'annual_price');

        $r->merge([
            'monthly_price_cents' => $monthly,
            'annual_price_cents' => $annual,
        ]);

        $data = $r->validate([
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'monthly_price_cents' => 'nullable|integer|min:0',
            'annual_price_cents' => 'nullable|integer|min:0',
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'server_package' => ['nullable', 'string', 'max:255'],
            'plan_category_id' => ['nullable', 'integer', 'exists:plan_categories,id'],
            'plan_type' => ['required', Rule::in([Plan::TYPE_MULTI_TENANT, Plan::TYPE_HOSTING])],
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        if ($monthly === null && $annual === null) {
            return back()->withErrors(['monthly_price_cents' => 'ظٹط¬ط¨ ط¥ط¯ط®ط§ظ„ ط³ط¹ط± ط´ظ‡ط±ظٹ ط£ظˆ ط³ظ†ظˆظٹ ط¹ظ„ظ‰ ط§ظ„ط£ظ‚ظ„'])->withInput();
        }

        $locale = app()->getLocale();
        $translation = $this->validateTranslation($r, $locale);

        $data['slug'] = $data['slug'] ?: Str::slug($translation['name']);
        $data['name'] = $translation['name'];
        $data['plan_type'] = $data['plan_type'] ?? $plan->plan_type ?? Plan::TYPE_MULTI_TENANT;

        // Ensure server_package defaults to the human-readable name when left empty
        if (empty($data['server_package'])) {
            $data['server_package'] = $data['name'];
        }

        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $primaryFeaturedLabel = $translation['featured_label'] ?? null;
        $existingFeaturedLabel = $plan->getOriginal('featured_label');
        $data['featured_label'] = $data['is_featured']
            ? ($primaryFeaturedLabel ?? $existingFeaturedLabel ?? null)
            : null;

        // Keep plan_category_id as-is (no renaming)
        $plan->update($data);

        $planTranslation = $plan->translations()->where('locale', $locale)->first();
        if ($planTranslation) {
            $planTranslation->update([
                'title' => $translation['name'],
                'description' => $translation['description'] ?? '',
                'features' => $translation['features'],
                'featured_label' => $translation['featured_label'],
            ]);
        } else {
            $plan->translations()->create([
                'locale' => $locale,
                'title' => $translation['name'],
                'description' => $translation['description'] ?? '',
                'features' => $translation['features'],
                'featured_label' => $translation['featured_label'],
            ]);
        }

        return redirect()->route('dashboard.plans.index')->with('ok', 'طھظ… طھط­ط¯ظٹط« ط§ظ„ط®ط·ط©');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return back()->with('ok', 'طھظ… ط­ط°ظپ ط§ظ„ط®ط·ط©');
    }

    /**
     * Toggle the is_active flag for the plan.
     */
    public function toggle(Plan $plan)
    {
        $plan->is_active = ! (bool) $plan->is_active;
        $plan->save();

        return back()->with('ok', $plan->is_active ? 'طھظ… طھظپط¹ظٹظ„ ط§ظ„ط®ط·ط©' : 'طھظ… ط¥ظٹظ‚ط§ظپ ط§ظ„ط®ط·ط©');
    }
}

