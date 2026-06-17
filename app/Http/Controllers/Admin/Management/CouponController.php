<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;

/**
 * ADR-008 Phase 4 — Admin Coupon CRUD
 *
 * Provides create / read / update / soft-deactivate / delete for coupons.
 *
 * Soft-delete policy:
 *   If a coupon has invoices attached (i.e., was actually redeemed), we deactivate
 *   instead of deleting to preserve the audit trail (invoice.coupon_id → coupon.id FK,
 *   nullOnDelete). If no invoices are attached, hard-delete is safe.
 */
class CouponController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorize('viewAny', Coupon::class);

        $search  = $request->get('search');
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page')
            : 20;

        $coupons = Coupon::query()
            ->withCount('invoices')
            ->latest()
            ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%"))
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.management.coupons.index', compact('coupons', 'search', 'perPage'));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create()
    {
        $this->authorize('create', Coupon::class);

        return view('dashboard.management.coupons.create');
    }

    public function store(StoreCouponRequest $request)
    {
        $data = $this->buildPayload($request);

        Coupon::create($data);

        return redirect()
            ->route('dashboard.coupons.index')
            ->with('ok', t('dashboard.Coupon_Created', 'تم إنشاء الكوبون بنجاح.'));
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(Coupon $coupon)
    {
        $this->authorize('update', $coupon);

        return view('dashboard.management.coupons.edit', compact('coupon'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $data = $this->buildPayload($request);

        $coupon->update($data);

        return redirect()
            ->route('dashboard.coupons.index')
            ->with('ok', t('dashboard.Coupon_Updated', 'تم تحديث الكوبون بنجاح.'));
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete', $coupon);

        // Soft-delete policy: if the coupon is attached to any invoice, we
        // deactivate it instead of deleting to preserve billing audit trail.
        if ($coupon->invoices()->exists()) {
            $coupon->update(['is_active' => false]);

            return redirect()
                ->route('dashboard.coupons.index')
                ->with('ok', t('dashboard.Coupon_Deactivated', 'تم تعطيل الكوبون بدلاً من حذفه لأنه مرتبط بفواتير.'));
        }

        $coupon->delete();

        return redirect()
            ->route('dashboard.coupons.index')
            ->with('ok', t('dashboard.Coupon_Deleted', 'تم حذف الكوبون بنجاح.'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the validated payload array from a store or update request.
     *
     * Converts minimum_amount input (in major currency units / dollars) to cents.
     */
    private function buildPayload(StoreCouponRequest|UpdateCouponRequest $request): array
    {
        $minAmountCents = null;
        if ($request->filled('minimum_amount')) {
            $minAmountCents = (int) round((float) $request->input('minimum_amount') * 100);
        }

        return [
            'code'                 => strtoupper(trim($request->input('code'))),
            'discount_type'        => $request->input('discount_type'),
            'discount_value'       => (float) $request->input('discount_value'),
            'expires_at'           => $request->input('expires_at') ?: null,
            'max_uses'             => $request->filled('max_uses')
                ? (int) $request->input('max_uses')
                : null,
            'minimum_amount_cents' => $minAmountCents,
            'is_active'            => $request->boolean('is_active', true),
        ];
    }
}
