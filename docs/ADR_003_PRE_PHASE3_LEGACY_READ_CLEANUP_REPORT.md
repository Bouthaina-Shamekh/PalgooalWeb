# ADR-003 Pre-Phase-3 Legacy Read Cleanup Report

**Date:** 2026-06-16  
**Status:** ✅ Complete — D1, D2, D3 resolved  
**Scope:** Remove all remaining legacy decimal column reads from Blade views before Phase 3 column drop  
**Files modified:** 2  
**Constraints honored:** No Migration · No column drops · No Model edits · No Controller edits · No Phase 3 · No `__()` · No `with('success')`

---

## 1. Files Modified

| File | Debt(s) Resolved | Lines Changed |
|------|-----------------|---------------|
| `resources/views/front/pages/checkout.blade.php` | D1, D2 | Lines 17–32 (price block), line 1045 (JS constant) |
| `resources/views/dashboard/templates/edit.blade.php` | D3 | Lines 153, 160 (form prefill) |

---

## 2. Legacy Reads Removed

### D1 — `checkout.blade.php` price display block (lines 18–29)

**Before:**
```php
$basePrice = (float) ($template?->price ?? 0);
$discRaw = $template?->discount_price ?? null;
$discPrice = is_null($discRaw) ? null : (float) $discRaw;
$hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
$endsAt = $hasDiscount && !empty($template?->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;
$discountExpired = false;
if ($hasDiscount && $endsAt) {
    $discountExpired = $endsAt->isPast();
}
$showDiscount = $hasDiscount && !$discountExpired;
$finalPrice = $showDiscount ? $discPrice : $basePrice;
$discountPerc = $showDiscount && $basePrice > 0 ? (int) round((($basePrice - $discPrice) / $basePrice) * 100) : 0;
```

**After:**
```php
// ADR-003 Read Switch (D1): use helpers instead of legacy decimal columns
$priceCents    = $template ? $template->resolvedPriceCents() : 0;
$discountCents = $template ? $template->resolvedDiscountPriceCents() : null;
$hasDiscount   = $discountCents !== null && $discountCents > 0 && $discountCents < $priceCents;
$endsAt = $hasDiscount && !empty($template?->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;
$discountExpired = false;
if ($hasDiscount && $endsAt) {
    $discountExpired = $endsAt->isPast();
}
$showDiscount  = $hasDiscount && !$discountExpired;
$finalCents    = $showDiscount ? $discountCents : $priceCents;
$basePrice     = $priceCents / 100;
$discPrice     = $showDiscount ? $discountCents / 100 : null;
$finalPrice    = $finalCents / 100;
$discountPerc  = $showDiscount && $priceCents > 0 ? (int) round((($priceCents - $discountCents) / $priceCents) * 100) : 0;
```

**What changed:**
- Source: `->price` (float column) → `resolvedPriceCents()` (integer cents)
- Source: `->discount_price` (float column) → `resolvedDiscountPriceCents()` (integer cents or null)
- `$finalCents` introduced as the canonical integer cents variable
- `$finalPrice`, `$basePrice`, `$discPrice` preserved as `/100` derivatives for display
- `$discountPerc` now computes from cents (no float multiplication risk)
- `$endsAt` / `$discountExpired` / `$showDiscount` logic preserved exactly

---

### D2 — `checkout.blade.php` JS constant (line 1045)

**Before:**
```js
const TEMPLATE_FINAL_CENTS = {{ (int) (($finalPrice ?? 0) * 100) }};
```

**After:**
```js
const TEMPLATE_FINAL_CENTS = {{ $finalCents }}; // ADR-003 D2: was (int)(($finalPrice??0)*100)
```

**What changed:**
- Eliminated float-to-cents multiplication for JS injection
- `$finalCents` is already an integer from `resolvedPriceCents()` — no rounding error possible
- Variable `TEMPLATE_FINAL_CENTS` name unchanged; no JS downstream changes required

---

### D3 — `templates/edit.blade.php` form prefill (lines 153, 160)

**Before:**
```blade
value="{{ old('price', $template->price) }}"
value="{{ old('discount_price', $template->discount_price) }}"
```

**After:**
```blade
value="{{ old('price', number_format($template->resolvedPrice(), 2, '.', '')) }}"
value="{{ old('discount_price', $template->resolvedDiscountPrice() !== null ? number_format($template->resolvedDiscountPrice(), 2, '.', '') : '') }}"
```

**What changed:**
- Source: `->price` (decimal column) → `resolvedPrice()` (cents/100, float)
- Source: `->discount_price` (decimal column) → `resolvedDiscountPrice()` (cents/100 or null)
- `number_format(..., 2, '.', '')` preserves existing 2-decimal display format
- Null guard on discount: if no discount set, field renders empty (same behavior as before)
- `type="number" step="0.01"` input remains unchanged — the controller dual-writes on save

---

## 3. Remaining Legacy Reads?

**Zero.**

| Column | Active reads remaining | Notes |
|--------|----------------------|-------|
| `templates.price` | **0** | All removed in this session |
| `templates.discount_price` | **0** | All removed in this session |
| `subscriptions.price` | **0** | Already cleaned in Phase 2 |

The two previously-classified DEAD CODE entries (`plan->discount_price` in CheckoutController:164 and `plan->price` in subscriptions/edit.blade:4) remain but are harmless — the `plans` table never had those columns, so they always return null/0.

---

## 4. Grep Audit Results

Run after all edits applied — 2026-06-16:

```
=== checkout.blade.php — ->price ===
(no output)  ← CLEAN

=== checkout.blade.php — ->discount_price ===
(no output)  ← CLEAN

=== checkout.blade.php — round(.*\* 100) or float * 100 ===
32:  $discountPerc = ... round((($priceCents - $discountCents) / $priceCents) * 100) ...
     ← LEGITIMATE: computing a percentage (0-100), not a float-to-cents conversion
983: if (n <= 1000) return Math.round(n * 100);
     ← UNRELATED: domain pricing JavaScript, not template pricing
1048: const TEMPLATE_FINAL_CENTS = {{ $finalCents }};
     ← FIXED: shows the resolved variable, no float multiply

=== templates/edit.blade.php — ->price / ->discount_price ===
(no output)  ← CLEAN
```

**Result: Zero legacy decimal reads remain in either file.**

---

## 5. Phase 3 Status — Is it waiting only for the stability window?

**Yes.**

All code-side blockers are resolved:

| Blocker | Status |
|---------|--------|
| B1 — `php artisan migrate` | ✅ Done (user confirmed) |
| B2 — `adr003:backfill-template-prices` | ✅ Done (user confirmed) |
| B3 — `adr003:backfill-subscription-prices` | ✅ Done (user confirmed) |
| B4 — SQL zero-checks | ✅ Passed (user confirmed: 0 NULLs, 0 mismatches) |
| B5 — Template view read switch | ✅ Resolved (previous session) |
| B6 — Stability window (≥7 days) | ⏳ Pending — clock started 2026-06-16 |
| D1/D2/D3 — Pre-Phase-3 view cleanup | ✅ Resolved (this session) |

**The only remaining blocker is time (B6).** No developer action is required before 2026-06-23.

Readiness Gates:

| Gate | Status |
|------|--------|
| G1 — No NULL cents | ✅ PASSED |
| G2 — No mismatches | ✅ PASSED |
| G3 — No active legacy reads anywhere | ✅ FULLY MET |
| G4 — 7+ days observation | ⏳ PENDING (review 2026-06-23) |
| G5 — No production pricing errors | ⏳ MONITOR daily |

---

## 6. May the legacy columns be dropped now?

**No.**

The stability window (G4) requires a minimum of 7 calendar days of observation with zero pricing incidents since the backfill was applied (2026-06-16). The purpose of this window is to confirm that:

1. No edge-case write path was missed by the dual-write audit
2. No production user encounter a wrong price that wasn't caught by the SQL checks
3. The `resolvedPriceCents()` helpers behave identically to the decimal columns in all real traffic scenarios

Dropping the columns before 2026-06-23 would skip this observation period and eliminate the ability to roll back if an unexpected edge case surfaces.

**Earliest permitted date for Phase 3 column drop: 2026-06-23**

If G4 and G5 are both GREEN on that date, Phase 3 may proceed.
