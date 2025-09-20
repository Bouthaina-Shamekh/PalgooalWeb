<?php

namespace App\Http\Controllers\Dashboard\Management;

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
            'features' => $r->input("features.$locale"),
        ];

        $validator = Validator::make($input, [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
        ], [
            'name.required' => 'الاسم مطلوب',
        ]);

        $translation = $validator->validate();
        $translation['features'] = array_values(array_filter((array) $translation['features'], fn($v) => trim((string)$v) !== ''));

        return $translation;
    }

    public function store(Request $r)
    {
        // normalize boolean
        $r->merge(['is_active' => $r->boolean('is_active')]);

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
            'plan_category_id' => ['nullable', 'integer', 'exists:plan_categories,id'],
            'is_active' => 'boolean',
        ]);

        if ($monthly === null && $annual === null) {
            return back()->withErrors(['monthly_price_cents' => 'يجب إدخال سعر شهري أو سنوي على الأقل'])->withInput();
        }

        $locale = app()->getLocale();
        $translation = $this->validateTranslation($r, $locale);

        // slug and top-level name
        $data['slug'] = $data['slug'] ?: Str::slug($translation['name']);
        $data['name'] = $translation['name'];

        // Important: do NOT convert plan_category_id -> category_id.
        // We expect the DB column to be plan_category_id and the Plan model to have it fillable.

        $plan = Plan::create($data);

        $plan->translations()->create([
            'locale' => $locale,
            'title' => $translation['name'],
            'description' => $translation['description'] ?? '',
            'features' => $translation['features'],
        ]);

        return redirect()->route('dashboard.plans.index')->with('ok', 'تم إنشاء الخطة بنجاح');
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
        $r->merge(['is_active' => $r->boolean('is_active')]);

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
            'plan_category_id' => ['nullable', 'integer', 'exists:plan_categories,id'],
            'is_active' => 'boolean',
        ]);

        if ($monthly === null && $annual === null) {
            return back()->withErrors(['monthly_price_cents' => 'يجب إدخال سعر شهري أو سنوي على الأقل'])->withInput();
        }

        $locale = app()->getLocale();
        $translation = $this->validateTranslation($r, $locale);

        $data['slug'] = $data['slug'] ?: Str::slug($translation['name']);
        $data['name'] = $translation['name'];

        // Keep plan_category_id as-is (no renaming)
        $plan->update($data);

        $planTranslation = $plan->translations()->where('locale', $locale)->first();
        if ($planTranslation) {
            $planTranslation->update([
                'title' => $translation['name'],
                'description' => $translation['description'] ?? '',
                'features' => $translation['features'],
            ]);
        } else {
            $plan->translations()->create([
                'locale' => $locale,
                'title' => $translation['name'],
                'description' => $translation['description'] ?? '',
                'features' => $translation['features'],
            ]);
        }

        return redirect()->route('dashboard.plans.index')->with('ok', 'تم تحديث الخطة');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return back()->with('ok', 'تم حذف الخطة');
    }
}
