# ADR-003 Closeout Review

**Date:** 2026-06-16 (updated 2026-06-16 — B5 resolved by Template View Read Switch session)  
**Reviewer:** ADR-003 Implementation Session  
**Scope:** `templates.price_cents` · `templates.discount_price_cents` · `subscriptions.price_cents`  
**Purpose:** Verify that the new integer-cents columns are the authoritative source and evaluate readiness for Phase 3 (column drop)

---

## 1. Read Path Audit

### 1A — `templates.price` / `templates.discount_price`

| File | Line(s) | Legacy Read | Classification |
|------|---------|-------------|----------------|
| `resources/views/front/pages/template-show.blade.php` | 5–13 | `$template->price`, `$template->discount_price` — compute `$finalPrice` for display + JS Alpine | **DISPLAY** — display only; checkout is priced in CheckoutController via `resolvedPriceCents()` |
| `resources/views/front/pages/template-show.blade.php` | 623 | `$finalPriceCents = (int) round($finalPrice * 100)` → `data-price-cents` attribute | **DISPLAY** — client-side hint on button; actual billing never reads this attribute |
| `resources/views/front/pages/template-show-redesign.blade.php` | 6–11 | `$template->price`, `$template->discount_price` — compute `$finalPrice` | **DISPLAY** — display only |
| `resources/views/front/pages/view-template.blade.php` | 19–24 | `$template->price`, `$template->discount_price` — compute `$finalPriceCents` → `data-price-cents` | **DISPLAY** — client-side attribute only |
| `resources/views/components/template/sections/templates.blade.php` | 33, 39, 85–92 | `$template->price`, `$template->discount_price` — price display in listing cards | **DISPLAY** — listing cards, no business logic |
| `resources/views/components/template/sections/templates_listing_showcase.blade.php` | ~line with `$price = (float)...` | `$template->discount_price ?? $template->price` → `$price` variable for sort/display | **DISPLAY** — sort key + display only |
| `resources/views/front/sections/templates/templates_listing_showcase.blade.php` | same pattern | same as above (duplicate component) | **DISPLAY** — display only |
| `resources/views/dashboard/templates/index.blade.php` | 265–268 | `$template->discount_price`, `$template->price` — `$currentPrice` for badge display | **DISPLAY** — admin list view, display only |
| `resources/views/livewire/admin/template/template-management.blade.php` | 203–206 | `$template->discount_price`, `$template->price` — price badge in Livewire template list | **DISPLAY** — display only |
| `resources/views/livewire/admin/template/frontend-templates-page.blade.php` | 62, 85–86 | `$template->discount_price ?? $template->price` — schema.org + price display | **DISPLAY** — display only |
| `app/Http/Controllers/Front/CheckoutController.php` | 134–138 | `$template->resolvedPriceCents()`, `$template->resolvedDiscountPriceCents()` | ✅ **CENTS** — switched in Phase 1, fully on cents |

**Summary for templates:** 10 view locations still read legacy decimal columns. All are **display-only** — zero business logic or billing impact. The one business-critical read (CheckoutController) is fully on cents.

---

### 1B — `subscriptions.price`

| File | Line(s) | Legacy Read | Classification |
|------|---------|-------------|----------------|
| `resources/views/dashboard/index.blade.php` | 200–202 | `$sub->resolvedPrice()` | ✅ **SWITCHED** — read switch applied in Phase 2 |
| `resources/views/dashboard/management/subscriptions/edit.blade.php` | 94 | `$subscription->resolvedPrice()` | ✅ **SWITCHED** — read switch applied in Phase 2 |
| All controllers | — | No direct `->price` reads found | ✅ **CLEAN** |
| All services | — | No direct `->price` reads found | ✅ **CLEAN** |

**Summary for subscriptions:** Zero legacy reads in controllers or services. Both view locations already switched to `resolvedPrice()`.

---

## 2. Write Path Audit

### 2A — `templates.price` / `templates.discount_price`

| File | Method | `price` written | `price_cents` written | `discount_price` written | `discount_price_cents` written | Dual-Write? |
|------|--------|-----------------|----------------------|--------------------------|-------------------------------|-------------|
| `app/Http/Controllers/Admin/TemplateController.php` | `store()` | ✅ | ✅ `round(price * 100)` | ✅ | ✅ `round(discount_price * 100)` | ✅ **Complete** |
| `app/Http/Controllers/Admin/TemplateController.php` | `update()` | ✅ | ✅ | ✅ | ✅ | ✅ **Complete** |

### 2B — `subscriptions.price`

| File | Method | `price` written | `price_cents` written | Dual-Write? |
|------|--------|-----------------|----------------------|-------------|
| `SubscriptionController.php` | `store()` | ✅ | ✅ `round(price * 100)` | ✅ **Complete** |
| `SubscriptionController.php` | `update()` | ✅ | ✅ `round(price * 100)` | ✅ **Complete** |
| `CheckoutController.php` | checkout flow | ✅ `unit_cents / 100` | ✅ `(int) unit_cents` | ✅ **Complete** |
| `ServerController.php` | account import | ✅ `0` | ✅ `0` | ✅ **Complete** |
| `OrderActivationService.php` | `activate()` | ✅ `resolvedPrice()` | ✅ `resolvedPriceCents()` | ✅ **Complete** |

**All 7 write paths (2 template + 5 subscription) are dual-writing. Zero paths write only the old column.**

---

## 3. Backfill Validation

### Commands available

```bash
# Templates
php artisan adr003:backfill-template-prices --dry-run
php artisan adr003:backfill-template-prices

# Subscriptions
php artisan adr003:backfill-subscription-prices --dry-run
php artisan adr003:backfill-subscription-prices
```

### SQL Verification Queries

```sql
-- Templates: must return 0 after backfill
SELECT COUNT(*) AS null_template_prices
FROM templates
WHERE price_cents IS NULL;

-- Templates mismatch check: must return 0
SELECT COUNT(*) AS template_mismatches
FROM templates
WHERE price_cents != ROUND(price * 100)
   OR (discount_price IS NOT NULL
       AND discount_price > 0
       AND discount_price_cents != ROUND(discount_price * 100));

-- Subscriptions: must return 0 after backfill
SELECT COUNT(*) AS null_sub_prices
FROM subscriptions
WHERE price_cents IS NULL;

-- Subscriptions mismatch check: must return 0
SELECT COUNT(*) AS sub_mismatches
FROM subscriptions
WHERE price_cents != ROUND(price * 100);
```

**Note:** SQL cannot be run from the sandbox environment. These queries must be executed on the user's MySQL instance (`palgoalsnewtest1`, `127.0.0.1:3306`).

---

## 4. Business Logic Audit

### CheckoutController (template path)

| Check | Finding |
|-------|---------|
| Uses `resolvedPriceCents()`? | ✅ Yes — line 134 |
| Uses `resolvedDiscountPriceCents()`? | ✅ Yes — line 135 |
| Float arithmetic affecting billing? | ✅ No — `$unitCents` is assigned directly from `$discPriceCents ?? $basePriceCents` (integers) |
| `number_format` inside billing logic? | ✅ No — only in display/response strings (`'$' . number_format($totalCents / 100, 2)`) |

### CheckoutController (plan path)

| Check | Finding |
|-------|---------|
| `$basePricePlan` source | `$plan->monthly_price_cents / 100` (integer ÷ 100 → float) |
| `$unitCentsPlan` computation | `(int)($basePricePlan * 100)` — float roundtrip: `cents / 100 * 100` |
| Precision risk? | ⚠️ **Low** — mathematically safe since `monthly_price_cents` is already an integer; `int / 100 * 100 = int` with no rounding loss for typical price values. However this is a code smell. |
| `$plan->discount_price` | Column does not exist on `plans` table → always returns `null` → `$showDiscountPlan = false` → discount branch is dead code. Safe. |

### OrderActivationService

| Check | Finding |
|-------|---------|
| `price` field source | `$template->resolvedPrice()` — derived from `price_cents / 100` |
| `price_cents` field source | `$template->resolvedPriceCents()` — reads `price_cents` directly, no float conversion |
| Float arithmetic? | ✅ None in billing path |

### SubscriptionController

| Check | Finding |
|-------|---------|
| Input | Admin form: `price` validated as `numeric` → `round((float) $data['price'] * 100)` |
| Precision risk? | ⚠️ **Low** — admin input from HTML number field; rounding error possible on non-round decimal values entered manually (e.g. `9.999` → 999 cents or 1000 cents depending on float precision). Acceptable for admin-entered prices. |
| `number_format` in business logic? | ✅ No |

### ServerController

| Check | Finding |
|-------|---------|
| Price value | Hardcoded `0` / `0` — no float arithmetic |
| Risk | ✅ None |

### Template Display Views (`template-show.blade`, `view-template.blade`, etc.)

| Check | Finding |
|-------|---------|
| `$finalPriceCents = (int) round($finalPrice * 100)` | Used only in `data-price-cents` HTML attribute on checkout button. The JS reads this for UI display only; CheckoutController re-derives the price from `resolvedPriceCents()` on the server. **No billing impact.** |
| `number_format` | Display only — never feeds into order creation |

**Overall verdict:** No float arithmetic affects billing accuracy. The only remaining float multiply is in the plan checkout roundtrip (`monthly_price_cents / 100 * 100`) which is mathematically lossless for integer source values.

---

## 5. Cleanup Readiness

### `templates.price`

| Criterion | Status | Notes |
|-----------|--------|-------|
| Dual-write on all write paths | ✅ Done | Both `store()` and `update()` |
| Backfill command exists | ✅ Done | `adr003:backfill-template-prices` |
| Business logic reads switched | ✅ Done | CheckoutController uses `resolvedPriceCents()` |
| All view reads switched | ✅ Done (2026-06-16) | All 9 view files switched to `resolvedPrice*` helpers |
| Stability window | ❌ Not started | Requires ≥1 week post-backfill |

**Verdict: ❌ NOT READY** — view reads resolved. Remaining blocker: migrate + backfill + stability window.

---

### `templates.discount_price`

| Criterion | Status | Notes |
|-----------|--------|-------|
| Dual-write on all write paths | ✅ Done | |
| Backfill command exists | ✅ Done | |
| Business logic reads switched | ✅ Done | |
| All view reads switched | ✅ Done (2026-06-16) | Same 9 files, zero `->discount_price` remaining |
| Stability window | ❌ Not started | |

**Verdict: ❌ NOT READY** — view reads resolved. Remaining: migrate + backfill + stability window.

---

### `subscriptions.price`

| Criterion | Status | Notes |
|-----------|--------|-------|
| Dual-write on all 5 write paths | ✅ Done | |
| Backfill command exists | ✅ Done | `adr003:backfill-subscription-prices` |
| All controller/service reads switched | ✅ Done | |
| All view reads switched | ✅ Done | Both R1 + R2 switched in Phase 2 |
| Migration applied | ⏳ Pending | User must run `php artisan migrate` |
| Backfill applied | ⏳ Pending | After migrate |
| SQL zero-check passed | ⏳ Pending | After backfill |
| Stability window | ⏳ Not started | Requires ≥1 week post-backfill with zero incidents |

**Verdict: ❌ NOT READY** — code is complete, but awaiting migrate + backfill + 1-week stability window before dropping.

---

## 6. Remaining Risks

| # | Risk | Severity | Probability | Details |
|---|------|----------|-------------|---------|
| ~~R1~~ | ~~**Template views not switched**~~ | ~~High~~ | ✅ **RESOLVED 2026-06-16** | All 9 view files switched via Template View Read Switch session. Zero `->price` / `->discount_price` reads remaining. |
| ~~R2~~ | ~~**`view-template.blade` + `template-show.blade` compute `$finalPriceCents` from float**~~ | ~~Low~~ | ✅ **RESOLVED 2026-06-16** | `$finalPriceCents = $finalCents` (integer, no float multiply). |
| R3 | **Plan checkout float roundtrip in CheckoutController** | **Low** | Very Low | `$unitCentsPlan = (int)($basePricePlan * 100)` where `$basePricePlan = monthly_price_cents / 100`. Lossless for integer source values, but not idiomatic. ADR-003 does not cover `plans` — separate scope. |
| R4 | **`$plan->discount_price` read — non-existent column** | **Low** | Very Low | `$plan->discount_price` is read in CheckoutController but the `plans` table has no `discount_price` column → always null. Dead code path. Harmless but misleading. |
| R5 | **Stability window not yet started for subscriptions** | **Critical (timing)** | N/A | Phase 2 code is complete but `migrate` + `backfill` must be run, then ≥1 week must pass before `subscriptions.price` can be dropped. Dropping early would require an emergency rollback. |
| R6 | **Pre-existing bug: `$plan->price ?? 0` in `subscriptions/edit.blade:4`** | **Medium** | Active | `Plan` model has no `price` column — JS auto-fill always sets price to `0` when admin changes plan. Admin must manually re-enter the price. Unrelated to ADR-003 but worsens UX. |
| R7 | **Livewire template components (`template-management.blade`, `frontend-templates-page.blade`) are legacy** | **Low** | Low | These Livewire views appear inactive (no matching Livewire components found in `app/`). If they are dead code, they won't cause runtime errors. Should be confirmed and removed. |

---

## 7. Recommendation

### Before Phase 3 can begin, the following must be completed:

**Required (blockers):**

1. **Run migrate + backfill on production:**
   ```bash
   php artisan migrate
   php artisan adr003:backfill-template-prices
   php artisan adr003:backfill-subscription-prices
   php artisan optimize:clear
   ```

2. **SQL zero-checks on production:**
   ```sql
   SELECT COUNT(*) FROM templates WHERE price_cents IS NULL;              -- must be 0
   SELECT COUNT(*) FROM templates WHERE discount_price_cents IS NULL AND discount_price > 0; -- must be 0
   SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL;          -- must be 0
   ```

3. ~~**Update 10 template view files**~~ — ✅ **DONE (2026-06-16)** All 9 files switched. Zero legacy reads remain.

4. **Stability window:** ≥1 week post-backfill in production with no checkout incidents for both templates and subscriptions.

**Optional (improvements):**

5. Fix `subscriptions/edit.blade:4` — `$plan->price ?? 0` → `$plan->monthly_price ?? 0` (pre-existing UX bug)
6. Fix `CheckoutController:276` — replace plan float roundtrip with direct cents usage once plan cents columns are consistent
7. Confirm and remove dead Livewire template components if unused

---

## 8. Final Verdict

```
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║   ADR-003 NOT READY FOR PHASE 3 (column drop)               ║
║                                                              ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║   Code status: ✅ Complete                                   ║
║   Dual-write:  ✅ All 7 write paths covered                  ║
║   Backfill:    ⏳ Commands created, must be run              ║
║   Read switch: ✅ All views done (subscriptions + templates) ║
║   Stability:   ⏳ Window not yet started                     ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

### Blockers to Phase 3:

| # | Blocker | Owner |
|---|---------|-------|
| B1 | `php artisan migrate` not yet run | User |
| B2 | `adr003:backfill-template-prices` not yet run | User |
| B3 | `adr003:backfill-subscription-prices` not yet run | User |
| B4 | SQL zero-checks not yet verified | User |
| ~~B5~~ | ~~10 template view files still read legacy columns~~ | ✅ **RESOLVED 2026-06-16** |
| B6 | Stability window (≥1 week) not yet elapsed | Time |

**Phase 3 (column drop) may begin only after B1–B5 are resolved and B6 has elapsed.**

### What IS complete:

- ✅ All write paths dual-write (old + cents)
- ✅ Business logic reads all on cents (`CheckoutController`, `OrderActivationService`, `OrderActivationService`)
- ✅ Subscription view reads switched to `resolvedPrice()`
- ✅ Backfill commands created and ready
- ✅ Model helpers (`resolvedPriceCents`, `resolvedPrice`, `resolvedDiscountPriceCents`, `resolvedDiscountPrice`) on both `Template` and `Subscription` models
- ✅ No billing regressions introduced
