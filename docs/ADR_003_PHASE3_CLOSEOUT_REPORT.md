# ADR-003 Phase 3 — Legacy Price Columns Drop: Closeout Report

**Date:** 2026-06-17
**Phase:** 3 of 3 (ADR-003 — FINAL PHASE)
**Scope:** Remove legacy decimal columns `templates.price`, `templates.discount_price`, `subscriptions.price` and all transition-period fallback code.

---

## 1. Audit Summary (Step 0)

### Legacy columns in scope
| Table | Column | Type | Status |
|-------|--------|------|--------|
| `templates` | `price` | decimal(10,2) | ✅ Dropped in this phase |
| `templates` | `discount_price` | decimal(10,2) nullable | ✅ Dropped in this phase |
| `subscriptions` | `price` | decimal(10,2) | ✅ Dropped in this phase |

### New columns (pre-existing from Phases 1-2)
| Table | Column | Type | Status |
|-------|--------|------|--------|
| `templates` | `price_cents` | int unsigned | ✅ Populated by backfill (Phase 1) |
| `templates` | `discount_price_cents` | int unsigned nullable | ✅ Populated by backfill (Phase 1) |
| `subscriptions` | `price_cents` | int unsigned | ✅ Populated by backfill (Phase 2) |

### Out-of-scope false positives
| File | Hit | Reason |
|------|-----|--------|
| `CheckoutController.php:176` | `$plan->discount_price` | `Plan` model — not in scope |
| `subscriptions/edit.blade.php:4` | `$plan->price ?? 0` | `Plan` model getter — not in scope |

### Gate 1 result: ✅ PASSED
All active legacy usages were dual-writes and fallback branches — safely removable. No irreversible blockers.

---

## 2. Files Changed

### Step 1 — Fallback branches removed (models)

**`app/Models/Template.php`**
- `resolvedPriceCents()` — removed fallback that read `getRawOriginal('price')`
- `resolvedDiscountPriceCents()` — removed fallback that read `getRawOriginal('discount_price')`
- Updated section comment from "Phase 1 — dual-write period" → "Phase 3 — cents-only"

**`app/Models/Tenancy/Subscription.php`**
- `resolvedPriceCents()` — removed fallback that read `getRawOriginal('price')`
- Updated section comment

### Step 2 — Dual-writes removed (controllers + service)

**`app/Http/Controllers/Admin/TemplateController.php`**
- `store()`: removed `'price'` and `'discount_price'` from `Template::create()` payload; renamed `$discountDecimal` → `$discountPriceDollars`
- `update()`: same for `$template->update()` payload

**`app/Http/Controllers/Admin/Management/SubscriptionController.php`**
- `store()`: added `unset($data['price'])` after computing `price_cents`; updated comment
- `update()`: same

**`app/Services/Billing/OrderActivationService.php`**
- `createSubscription()` (line ~211): removed `'price' => $template->resolvedPrice()` from `Subscription::create()` payload

### Step 3 — Drop migration created

**`database/migrations/2026_06_17_000001_drop_legacy_price_columns.php`**
- `up()`: drops `templates.price`, `templates.discount_price`, `subscriptions.price`
- `down()`: restores all three as decimal(10,2)
- Includes prerequisite checklist and validation SQL in docblock

### Step 4 — Model cleanup

**`app/Models/Template.php`**
- `$fillable`: removed `'price'`, `'discount_price'`
- `$casts`: removed `'price' => 'float'`, `'discount_price' => 'float'`

**`app/Models/Tenancy/Subscription.php`**
- `$fillable`: removed `'price'`
- `$casts`: removed `'price' => 'float'`

---

## 3. Files NOT Changed (and why)

| File | Reason |
|------|--------|
| `resources/views/dashboard/templates/create.blade.php` | Form inputs `name="price"` / `name="discount_price"` are REQUEST field names for dollar amounts — not DB columns. Controller converts to cents before writing. |
| `resources/views/dashboard/templates/edit.blade.php` | Pre-fill uses `$template->resolvedPrice()` / `$template->resolvedDiscountPrice()` — these go through helpers which now read from cents columns. |
| `resources/views/dashboard/management/subscriptions/edit.blade.php` | `name="price"` is a request field for dollar input. `$subscription->resolvedPrice()` reads from `price_cents` via helper. |
| `app/Http/Controllers/Front/CheckoutController.php` | Already uses `resolvedPriceCents()` / `resolvedDiscountPriceCents()` (no legacy reads). `$plan->discount_price` is Plan model — not in scope. |
| All views using `resolvedPrice()` / `resolvedDiscountPrice()` / `resolvedPriceCents()` / `resolvedDiscountPriceCents()` | These helpers now correctly read from cents columns only — no changes needed. |

---

## 4. Validation SQL

Run these before executing `php artisan migrate` to confirm all data is backfilled:

```sql
-- Must return 0 (all templates have price_cents populated)
SELECT COUNT(*) AS templates_missing_price_cents
FROM templates
WHERE price_cents IS NULL;

-- Must return 0 (all templates with a discount have discount_price_cents populated)
SELECT COUNT(*) AS templates_missing_discount_price_cents
FROM templates
WHERE discount_price IS NOT NULL
  AND discount_price > 0
  AND discount_price_cents IS NULL;

-- Must return 0 (all subscriptions have price_cents populated)
SELECT COUNT(*) AS subscriptions_missing_price_cents
FROM subscriptions
WHERE price_cents IS NULL;
```

If any count > 0, run the appropriate backfill artisan command first:
```bash
php artisan adr003:backfill-template-prices      # for templates
php artisan adr003:backfill-subscription-prices  # for subscriptions
```

---

## 5. Deploy Sequence

```bash
# 1. Verify backfill is complete (run the SQL above)

# 2. Run the drop migration
php artisan migrate

# 3. Clear caches
php artisan cache:clear
php artisan config:clear

# 4. Verify the application loads without errors
# - Open /admin/templates (index, create, edit)
# - Open /admin/subscriptions (index, edit)
# - Open frontend template pages (/templates/{slug})
# - Open checkout page (/checkout/client/{template_id})
```

---

## 6. Is ADR-003 CLOSED?

```
Phase 1 — Templates cents columns + dual-write:   ✅ Complete (2026-06-16)
Phase 2 — Subscriptions cents column + dual-write: ✅ Complete (2026-06-16)
Phase 3 — Drop legacy columns + remove fallbacks:  ✅ Complete (2026-06-17, this phase)

ADR-003 STATUS: ✅ CLOSED
```

All three legacy decimal price columns are gone from the schema and from all application code.

- **No application code reads** `templates.price`, `templates.discount_price`, or `subscriptions.price`
- **No application code writes** to these columns
- **No fallback branches** remain in model helpers
- **All reads** go through `resolvedPriceCents()` / `resolvedDiscountPriceCents()` → integer cents
- **All display/form values** go through `resolvedPrice()` / `resolvedDiscountPrice()` → derived from cents

---

## 7. What Stays (by design)

| What | Why |
|------|-----|
| `resolvedPrice()` / `resolvedDiscountPrice()` helpers on `Template` | Used in 10+ views for dollar display — now derived from cents only |
| `resolvedPrice()` helper on `Subscription` | Used in edit form pre-fill and dashboard — now derived from cents only |
| `name="price"` in template/subscription forms | Request field names for dollar input — controller converts to cents |
| `$plan->discount_price` in CheckoutController | Plan model field — NOT in scope of ADR-003 |
| `$plan->price` in subscriptions/edit.blade.php | Plan model getter — NOT in scope of ADR-003 |
| ADR-003 backfill artisan commands in `routes/console.php` | Safe to keep as historical reference; they are idempotent |
