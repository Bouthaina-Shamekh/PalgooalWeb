<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::latest()->paginate(20);
        return view('dashboard.management.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('dashboard.management.plans.create');
    }

    public function store(Request $r)
    {
        // توحيد الحقول
        $r->merge([
            'is_active' => $r->boolean('is_active'),
            'features'  => array_values(array_filter((array) $r->input('features', []), fn($v) => trim((string)$v) !== '')),
        ]);

        // تحويل السعر من الدولار إلى سنتات (int)
        $monthly = $r->input('monthly_price_cents');
        $annual = $r->input('annual_price_cents');
        $r->merge([
            'monthly_price_cents' => $monthly !== null && $monthly !== '' ? (int) round(floatval($monthly) * 100) : null,
            'annual_price_cents' => $annual !== null && $annual !== '' ? (int) round(floatval($annual) * 100) : null,
        ]);

        // فاليديشن: أحد السعرين مطلوب على الأقل
        $data = $r->validate([
            'name'                => 'required|string|max:120',
            'slug'                => 'nullable|string|max:140|unique:plans,slug',
            'monthly_price_cents' => 'nullable|integer|min:0',
            'annual_price_cents'  => 'nullable|integer|min:0',
            'features'            => 'nullable|array',
            'is_active'           => 'boolean',
        ], [
            'name.required'                => 'الاسم مطلوب',
            'slug.unique'                  => 'المُعرّف (Slug) مستخدم من قبل',
            'monthly_price_cents.integer'  => 'السعر الشهري يجب أن يكون رقمًا عشريًا أو صحيحًا',
            'annual_price_cents.integer'   => 'السعر السنوي يجب أن يكون رقمًا عشريًا أو صحيحًا',
        ]);

        if (empty($data['monthly_price_cents']) && empty($data['annual_price_cents'])) {
            return back()->withErrors(['monthly_price_cents' => 'يجب إدخال سعر شهري أو سنوي على الأقل'])->withInput();
        }

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        Plan::create($data);

        return redirect()
            ->route('dashboard.plans.index')
            ->with('ok', 'تم إنشاء الخطة بنجاح');
    }

    public function edit(Plan $plan)
    {
        return view('dashboard.management.plans.edit', compact('plan'));
    }

    public function update(Request $r, Plan $plan)
    {
        // توحيد الحقول
        $features = $r->input('features');
        if (is_string($features)) {
            // لو جاي JSON كنص
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : [];
        }
        $features = array_values(array_filter((array)$features, fn($v) => trim((string)$v) !== ''));

        $r->merge([
            'is_active' => $r->boolean('is_active'),
            'features'  => $features,
        ]);

        // تحويل السعر من الدولار إلى سنتات (int)
        $monthly = $r->input('monthly_price_cents');
        $annual = $r->input('annual_price_cents');
        $r->merge([
            'monthly_price_cents' => $monthly !== null && $monthly !== '' ? (int) round(floatval($monthly) * 100) : null,
            'annual_price_cents' => $annual !== null && $annual !== '' ? (int) round(floatval($annual) * 100) : null,
        ]);

        // فاليديشن: أحد السعرين مطلوب على الأقل
        $data = $r->validate([
            'name'                => 'required|string|max:120',
            'slug'                => ['nullable','string','max:140', Rule::unique('plans','slug')->ignore($plan->id)],
            'monthly_price_cents' => 'nullable|integer|min:0',
            'annual_price_cents'  => 'nullable|integer|min:0',
            'features'            => 'nullable|array',
            'is_active'           => 'boolean',
        ], [
            'name.required'                => 'الاسم مطلوب',
            'slug.unique'                  => 'المُعرّف (Slug) مستخدم من قبل',
            'monthly_price_cents.integer'  => 'السعر الشهري يجب أن يكون رقمًا عشريًا أو صحيحًا',
            'annual_price_cents.integer'   => 'السعر السنوي يجب أن يكون رقمًا عشريًا أو صحيحًا',
        ]);

        if (empty($data['monthly_price_cents']) && empty($data['annual_price_cents'])) {
            return back()->withErrors(['monthly_price_cents' => 'يجب إدخال سعر شهري أو سنوي على الأقل'])->withInput();
        }

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $plan->update($data);

        return redirect()
            ->route('dashboard.plans.index')
            ->with('ok', 'تم تحديث الخطة');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return back()->with('ok', 'تم حذف الخطة');
    }
}