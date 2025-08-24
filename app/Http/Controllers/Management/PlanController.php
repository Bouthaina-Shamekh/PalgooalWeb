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
        // تحويل السعر من واجهة الدولار إلى السنت إن لزم
        if ($r->missing('price_cents') && $r->filled('price_ui')) {
            $ui = (float) $r->input('price_ui');
            $r->merge(['price_cents' => (int) round($ui * 100)]);
        }

        // توحيد الحقول
        $r->merge([
            'is_active' => $r->boolean('is_active'),
            'features'  => array_values(array_filter((array) $r->input('features', []), fn($v) => trim((string)$v) !== '')),
        ]);

        // فاليديشن
        $data = $r->validate([
            'name'          => 'required|string|max:120',
            'slug'          => 'nullable|string|max:140|unique:plans,slug',
            'price_cents'   => 'required|integer|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly','annually'])],
            'features'      => 'nullable|array',
            'is_active'     => 'boolean',
        ], [
            'name.required'          => 'الاسم مطلوب',
            'price_cents.required'   => 'السعر مطلوب',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة',
            'billing_cycle.in'       => 'قيمة دورة الفوترة غير صحيحة',
            'slug.unique'            => 'المُعرّف (Slug) مستخدم من قبل',
        ]);

        $data['slug']       = $data['slug'] ?: Str::slug($data['name']);
        $data['created_by'] = auth()->id();

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
        // تحويل السعر من واجهة الدولار إلى السنت إن لزم
        if ($r->missing('price_cents') && $r->filled('price_ui')) {
            $ui = (float) $r->input('price_ui');
            $r->merge(['price_cents' => (int) round($ui * 100)]);
        }

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

        // فاليديشن
        $data = $r->validate([
            'name'          => 'required|string|max:120',
            'slug'          => ['nullable','string','max:140', Rule::unique('plans','slug')->ignore($plan->id)],
            'price_cents'   => 'required|integer|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly','annually'])],
            'features'      => 'nullable|array',
            'is_active'     => 'boolean',
        ], [
            'name.required'          => 'الاسم مطلوب',
            'price_cents.required'   => 'السعر مطلوب',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة',
            'billing_cycle.in'       => 'قيمة دورة الفوترة غير صحيحة',
            'slug.unique'            => 'المُعرّف (Slug) مستخدم من قبل',
        ]);

        $data['slug']       = $data['slug'] ?: Str::slug($data['name']);
        $data['updated_by'] = auth()->id();

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
