<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ADR-008 Phase 2 — Server-side Coupon Validation
 *
 * POST /checkout/coupon/validate
 *
 * This endpoint is READ-ONLY:
 *   - It validates a coupon code against the database.
 *   - It computes the discount for the given subtotal.
 *   - It does NOT increment used_count.
 *   - It does NOT attach the coupon to any invoice or order.
 *   - It does NOT create any session/state.
 *
 * Actual coupon application (increment + FK assignment) happens in
 * CheckoutController::process() in ADR-008 Phase 3.
 *
 * Always returns HTTP 200:
 *   { valid: true,  code: "SUMMER20", discount_cents: 1000, message: "..." }
 *   { valid: false, discount_cents: 0, message: "..." }
 *
 * 200 for invalid coupons is intentional — keeps frontend UX simple
 * (no try/catch needed for 422 on the JS side).
 */
class CouponValidationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'code'           => ['required', 'string', 'max:100'],
            'subtotal_cents' => ['required', 'integer', 'min:0'],
        ]);

        $code          = strtoupper(trim($request->input('code')));
        $subtotalCents = (int) $request->input('subtotal_cents');

        // -----------------------------------------------------------------------
        // 1. Find the coupon by code
        // -----------------------------------------------------------------------
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon === null) {
            return $this->invalid(t('dashboard.Coupon_Not_Found', 'Coupon code not found.'));
        }

        // -----------------------------------------------------------------------
        // 2. Check usability (active, not expired, usage limit, minimum amount)
        // -----------------------------------------------------------------------
        if (! $coupon->isUsableForSubtotal($subtotalCents)) {
            $message = $this->unusableMessage($coupon, $subtotalCents);
            return $this->invalid($message);
        }

        // -----------------------------------------------------------------------
        // 3. Compute discount
        // -----------------------------------------------------------------------
        $discountCents = $coupon->computeDiscountCents($subtotalCents);

        return response()->json([
            'valid'          => true,
            'code'           => $coupon->code,
            'discount_cents' => $discountCents,
            'message'        => t('dashboard.Coupon_Applied', 'Coupon applied successfully.'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function invalid(string $message): JsonResponse
    {
        return response()->json([
            'valid'          => false,
            'discount_cents' => 0,
            'message'        => $message,
        ]);
    }

    /**
     * Return a specific reason message for an unusable coupon.
     * Deliberately does not reveal max_uses or internal counts to the client.
     */
    private function unusableMessage(Coupon $coupon, int $subtotalCents): string
    {
        if (! $coupon->is_active) {
            return t('dashboard.Coupon_Inactive', 'This coupon is no longer active.');
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            return t('dashboard.Coupon_Expired', 'This coupon has expired.');
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return t('dashboard.Coupon_Exhausted', 'This coupon has reached its usage limit.');
        }

        if ($coupon->minimum_amount_cents !== null && $subtotalCents < $coupon->minimum_amount_cents) {
            $minAmount = number_format($coupon->minimum_amount_cents / 100, 2);
            return t('dashboard.Coupon_Minimum_Amount', "A minimum order of \${$minAmount} is required for this coupon.");
        }

        return t('dashboard.Coupon_Invalid', 'This coupon is invalid.');
    }
}
