# ADR-003: Integer Cents as Canonical Money Storage

**Status:** Proposed  
**Date:** 2026-06-16  
**Author:** Engineering (documented from code audit)  
**Related:** `docs/25-billing-system.md § TD-1, TD-2`, `docs/03-database-architecture.md`

---

## Context

The platform handles monetary values across five distinct subsystems: Plan pricing, Subscriptions, Invoices, Orders, and Coupons. A code audit of all financial migrations and models reveals that the project is **partially migrated** to an integer-cents storage convention — fully applied in the invoicing and ordering subsystems, but not yet applied to subscriptions and coupons.

### Current State by Table

| Table | Column(s) | DB Type | Model Cast | Convention |
|---|---|---|---|---|
| `plans` | `monthly_price_cents`, `annual_price_cents` | `unsignedInteger` | `integer` | ✅ Cents |
| `invoices` | `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents` | `integer` | *(none)* | ✅ Cents |
| `invoice_items` | `unit_price_cents`, `total_cents` | `integer` | *(none)* | ✅ Cents |
| `order_items` | `price_cents` | `unsignedBigInteger` | `integer` | ✅ Cents |
| `subscriptions` | `price` | `decimal(10,2)` | `float` | ❌ Decimal |
| `coupons` | `discount_value` | `decimal(10,2)` | *(none)* | ❌ Decimal |
| `domain_tld_prices` | `cost`, `sale` | `decimal(10,2)` | *(none)* | ❌ Decimal |
| `templates` | `price`, `discount_price` | `decimal(10,2)` | *(none)* | ❌ Decimal |

### Code Evidence

**`subscriptions` table (migration `2025_08_17_132647_create_subscriptions_table.php`):**
```php
$table->decimal('price', 10, 2)->default(0); // السعر وقت الاشتراك
```

**`Subscription` model cast:**
```php
protected $casts = [
    'price' => 'float',  // stored as 29.99, not 2999
    // ...
];
```

**`SubscriptionController` validation:**
```php
'price' => ['required', 'numeric', 'min:0'],
// Admin submits "29.99" — stored directly as decimal
```

**`coupons` table (migration `2025_05_03_132914_create_coupons_table.php`):**
```php
$table->enum('discount_type', ['fixed', 'percent']);
$table->decimal('discount_value', 10, 2); // 50.00 (dollars) or 20.00 (%)
```

**`plans` table (migration `2025_05_03_132312_create_plans_table.php`):**
```php
$table->unsignedInteger('monthly_price_cents')->nullable();
$table->unsignedInteger('annual_price_cents')->nullable();
```

**`Plan` model accessors (conversion happens at display time):**
```php
public function getMonthlyPriceAttribute(): ?float
{
    return $this->attributes['monthly_price_cents'] / 100; // cents → dollars for display only
}
```

**`invoices` table (migration `2025_08_23_114349_rebuild_invoices_tables.php`):**
```php
$table->integer('subtotal_cents')->default(0);
$table->integer('discount_cents')->default(0);
$table->integer('tax_cents')->default(0);
$table->integer('total_cents')->default(0);
```

The comment in the migration title itself — "rebuild_invoices_tables" — indicates a deliberate redesign to adopt cents. Subscriptions and coupons were not refactored at the same time.

---

## Problem

### 1. Floating-Point Rounding Risk

PHP floats follow IEEE 754 double-precision binary. Storing and manipulating decimal prices as `float` introduces rounding errors that are invisible in typical cases but manifest in edge cases:

```php
// Benign-looking but wrong:
$price = 10.005;
$cents = (int)($price * 100); // → 1000, should be 1001

// Aggregation risk:
$total = 0.1 + 0.2;  // → 0.30000000000000004
$cents = (int)($total * 100); // → 30 ✅ (happens to work here)

// But at scale, SUM(price) in SQL returns a float aggregate
// that accumulates error across thousands of rows.
```

MySQL `decimal(10,2)` is exact for two decimal places, but once the value crosses into PHP as a `float` (via Eloquent's cast `'price' => 'float'`), exactness is lost.

### 2. Unit Ambiguity

When reading `$subscription->price = 29.99`, it is unclear whether this is:
- $29.99 USD (dollars), or
- 29.99 cents ($0.30)

The codebase uses both conventions simultaneously. Any developer reading `subscriptions.price` without knowing the convention will guess wrong. A developer reading `invoices.total_cents = 2999` has no ambiguity.

For `coupons.discount_value = 50.00` with `discount_type = 'fixed'`, the value is $50.00. For `discount_type = 'percent'`, it is 50%. The same column name carries two different semantic units depending on a sibling column value.

### 3. Aggregation Complexity

Billing reports that join subscriptions with invoices must convert between units in SQL:

```sql
-- Mixing units in a report query:
SELECT
    s.price          AS subscription_price_dollars,  -- decimal
    i.total_cents    AS invoice_total_cents,          -- integer
    s.price * 100    AS subscription_price_cents      -- computed, risky
FROM subscriptions s
JOIN invoices i ON i.order_id = s.id
```

This forces conversion arithmetic into every aggregation query. A mistake (`price / 100` instead of `price * 100`) produces values that are numerically valid but financially wrong — and may not be caught by type-checking.

### 4. Implicit Conversion at Invoice Creation

Currently, when an invoice is created from a subscription, the billing layer must convert `subscriptions.price` (decimal dollars) to `invoices.total_cents` (integer cents). This conversion exists implicitly in `SubscriptionController`. If the conversion is omitted or doubled, the invoice amount will be wrong by a factor of 100 — and the error may not surface until payment reconciliation.

### 5. Coupon Application is Blocked by This Inconsistency

The `invoices.discount_cents` column expects integer cents. The `coupons.discount_value` is stored as `decimal(10,2)`. For a `fixed` coupon, applying it requires:

```php
$discountCents = (int)($coupon->discount_value * 100); // fragile
```

This conversion ambiguity is one contributing factor to coupon application currently being hard-coded to `$discount = 0` in the billing calculation (documented as TD-3 in `docs/25-billing-system.md`).

---

## Decision

**All monetary values stored by the platform must use integer cents as the canonical storage format.**

The unit is always: **1 cent = 1/100 of the billing currency (USD by default).**

### Canonical Storage Format

| Use Case | Storage | Example |
|---|---|---|
| Invoice totals | `integer` cents | `2999` = $29.99 |
| Order totals | `integer` cents | `5000` = $50.00 |
| Subscription price (snapshot) | `integer` cents | `2999` = $29.99 |
| Plan base price | `integer` cents | `2999` = $29.99 |
| Discount — fixed amount | `integer` cents | `500` = $5.00 |
| Discount — percentage | `integer` basis points OR separate `percent` column | `2000` = 20.00% |
| Tax | `integer` cents | `150` = $1.50 |
| Domain TLD pricing | `integer` cents | `1200` = $12.00 |
| Template price | `integer` cents | `4900` = $49.00 |

### Naming Convention

All integer-cents columns must follow the `*_cents` suffix convention:

```
price_cents          ✅
monthly_price_cents  ✅
total_cents          ✅
discount_cents       ✅
price                ❌  (ambiguous)
discount_value       ❌  (ambiguous)
```

### Conversion Rule

Conversion between storage and display happens **only at the boundary** (form display, API response, PDF rendering):

```php
// Storage → Display (accessor on model):
$plan->monthly_price = $plan->monthly_price_cents / 100;  // 2999 → 29.99

// Display → Storage (on form submit or API receive):
$priceCents = (int) round($request->price * 100);  // 29.99 → 2999
// Use round(), not (int)($value * 100), to avoid float truncation
```

---

## Exceptions

The following are **not** stored as integer cents and are excluded from this ADR:

**Display-only formatting** — Formatted price strings such as `"$29.99"` or `"USD 29.99"` used in Blade views or PDF templates. These are computed at render time from cents and never persisted.

**Percentage discount values** — When `coupons.discount_type = 'percent'`, the value `20.00` represents 20% — not a monetary amount. Percentage values are dimensionless ratios. Post-migration, these should be stored as `integer` basis points (`2000` = 20.00%) in a separate column, or as a `tinyint` percentage (0–100). The current `discount_value` column conflates both types.

**External API payloads** — Payment gateway responses (Stripe, PayPal) may return amounts in their own format (Stripe uses cents natively; others use decimals). Normalize to cents immediately upon receipt before any storage.

---

## Migration Strategy

The migration is **non-destructive** and follows a four-phase dual-write pattern. No production data is deleted until the new column is verified correct.

### Phase 1 — Add New Columns (No Breaking Changes)

Add `price_cents` alongside the existing `price` column. No application code changes required yet. Old and new columns coexist.

```php
// Migration: add_price_cents_to_subscriptions_table
Schema::table('subscriptions', function (Blueprint $table) {
    $table->unsignedInteger('price_cents')->nullable()->after('price');
});

// Migration: add_discount_cents_to_coupons_table
Schema::table('coupons', function (Blueprint $table) {
    $table->unsignedInteger('discount_value_cents')->nullable()->after('discount_value');
    // separate column for percent to resolve the type ambiguity:
    $table->unsignedTinyInteger('discount_percent')->nullable()->after('discount_value_cents');
});
```

### Phase 2 — Dual Write

Update all write paths (controllers, seeders, commands) to write both the old decimal column and the new cents column simultaneously. The old column remains the read source during this phase.

```php
// SubscriptionController::store() and update()
$subscription->price       = $request->price;                       // old path (keep)
$subscription->price_cents = (int) round($request->price * 100);   // new path (add)
$subscription->save();
```

**Duration:** Deploy and monitor for one billing cycle (minimum 30 days) to confirm `price_cents` is populated and correct.

### Phase 3 — Backfill and Switch Reads

Backfill any historical rows where `price_cents IS NULL`:

```php
// Artisan command or one-time migration:
Subscription::whereNull('price_cents')->chunkById(500, function ($subs) {
    foreach ($subs as $sub) {
        $sub->price_cents = (int) round($sub->price * 100);
        $sub->saveQuietly();
    }
});
```

Switch all **read paths** to use `price_cents`. Update the model cast:

```php
// Subscription model — after switching reads:
protected $casts = [
    'price_cents' => 'integer',  // ← new read source
    'price'       => 'float',    // ← kept temporarily, no longer the source of truth
];
```

**Verification:** Run a reconciliation report confirming `price_cents = round(price * 100)` for 100% of rows.

### Phase 4 — Drop Legacy Decimal Columns

After confirming Phase 3 is correct and stable across at least one billing period:

```php
// Migration: drop_price_from_subscriptions_table
Schema::table('subscriptions', function (Blueprint $table) {
    $table->dropColumn('price');
});

// Migration: drop_discount_value_from_coupons_table
Schema::table('coupons', function (Blueprint $table) {
    $table->dropColumn('discount_value');
});
```

Remove the `'price' => 'float'` cast from the Subscription model.

---

## Consequences

### Positive

**Eliminates float rounding at storage.** `integer` columns in MySQL are exact. No floating-point arithmetic is performed between write and read.

**Unambiguous column names.** `price_cents = 2999` means exactly $29.99. No developer will interpret it as $2999.00.

**Consistent aggregation.** `SUM(total_cents)`, `SUM(price_cents)`, `SUM(discount_cents)` all return exact integers. No conversion needed across joined tables.

**Unlocks coupon application.** The `invoice.discount_cents` column is already an integer. Once `coupon.discount_value_cents` is an integer, the application is a direct subtraction with no conversion risk.

**Simplifies billing reports.** All monetary columns share one unit. Revenue, refund, and discount reports can join and aggregate without unit conversion.

**Consistent with invoicing subsystem.** Invoices and OrderItems already use cents. Subscriptions and Coupons will match the established pattern.

### Negative

**Migration complexity.** Four-phase migration with dual-write period requires careful coordination across deployments. A mistake in the backfill step (`price` vs `price / 100`) would corrupt financial data.

**Breaking change for any external integrations.** If any external service reads `subscriptions.price` directly from the database (bypassing the API), it will see `price_cents` instead after Phase 4. Requires audit of any direct DB consumers.

**Percentage coupons require a separate solution.** The `discount_value` column currently handles two types. Splitting into `discount_value_cents` (fixed) and `discount_percent` (percentage) requires updating every place that reads `coupons.discount_value`.

**Form input changes.** Admin forms that currently accept `"29.99"` for `price` will need to continue accepting decimal input but convert to cents on submit. The UX stays the same; only the write path changes.

---

## Alternatives Considered

### Decimal(10,2) Across All Tables

**Rejected.** MySQL `decimal(10,2)` is exact in the database, but:
1. PHP casts it to `float` unless explicitly handled, reintroducing IEEE 754 risk.
2. The invoicing subsystem has already committed to integer cents. Reverting invoices to decimal would require a larger migration than completing the cents migration.
3. Decimal aggregation in PHP (`bcadd`, `bcsub`) requires using BC Math extension everywhere — more complexity than integer arithmetic.
4. Naming ambiguity (`price` = dollars or cents?) is not resolved.

### Money Value Object (PHP Layer Only)

**Insufficient alone.** A `Money` value object wrapping `int cents` could enforce correct arithmetic in PHP. However:
1. It does not fix the database schema. `subscriptions.price` as `decimal(10,2)` would still require conversion when writing/reading.
2. Query-level aggregations (`SUM`, `AVG`, reporting queries) bypass PHP entirely and would still produce decimal sums.
3. It is a complementary tool, not a substitute for correct storage format.

The two approaches are not mutually exclusive. A Money value object may be introduced later as a PHP-layer safeguard, but it does not replace the schema migration described here.

---

## Impacted Systems

### Billing (`docs/25-billing-system.md`)

- `invoices.*_cents` — already compliant, no change
- `invoice_items.*_cents` — already compliant, no change
- Invoice creation logic that reads `subscriptions.price` must be updated to read `price_cents` after Phase 3

### Orders (`docs/25-billing-system.md`)

- `order_items.price_cents` — already compliant, no change
- `Order::subtotalCents()` — already returns integer, no change

### Subscriptions

- `subscriptions.price` → **migrate to `price_cents`** (Phases 1–4)
- `SubscriptionController` validation rule `'price' => 'numeric'` → change to `'price_cents' => 'integer'`
- Form inputs: continue accepting decimal from admin, convert `round($input * 100)` on submit

### Coupons

- `coupons.discount_value` → **migrate to `discount_value_cents`** (for `fixed` type) and `discount_percent` (for `percent` type)
- Coupon application logic (currently `$discount = 0`) must be implemented **after** this migration completes

### Domain Pricing (`domain_tld_prices`)

- `cost` and `sale` columns are `decimal(10,2)` — **outside the core billing migration scope** but should follow the same convention
- Proposed: add `cost_cents` and `sale_cents` as a Phase 1 extension
- Blocked by: no current code reads these for invoice generation (domain orders use `order_items.price_cents` directly)

### Templates (`templates`)

- `price` and `discount_price` are `decimal(10,2)` — template catalog pricing
- Template purchase flows through `order_items.price_cents` (correct), so `templates.price` is only used at cart-build time
- Proposed: rename to `price_cents` and `discount_price_cents` in the next template pricing migration

### Reporting

- Any reporting query that joins `subscriptions.price` with `invoices.total_cents` must be updated to use `price_cents` after Phase 3
- HomeController revenue calculations use `Invoice::paid()->sum('total_cents')` — already correct

---

## Technical Debt Closed

This ADR directly addresses:

**TD-1 — `subscriptions.price` is `decimal(10,2)` instead of integer cents**  
Documented in `docs/25-billing-system.md § Technical Debt`:
> `subscriptions.price` | `decimal(10,2)` cast to `float` | **❌ TD-1** — should be integer cents

Closed by: completing Phases 1–4 for the `subscriptions` table.

**TD-2 — `coupons.discount_value` is `decimal(10,2)` for fixed discounts**  
Documented in `docs/25-billing-system.md § Technical Debt`:
> `coupons.discount_value` | `decimal(10,2)` | ⚠️ **TD-2** — should be integer cents for `fixed` type

Closed by: completing the coupons migration and splitting `fixed` vs `percent` into separate columns.

---

## References

- `docs/25-billing-system.md` — full billing domain documentation, TD-1 through TD-4
- `docs/03-database-architecture.md § Money Storage Convention`
- `database/migrations/2025_08_17_132647_create_subscriptions_table.php`
- `database/migrations/2025_08_23_114349_rebuild_invoices_tables.php` — the pivot point where invoices adopted cents
- `database/migrations/2025_05_03_132914_create_coupons_table.php`
- `app/Models/Tenancy/Subscription.php`
- `app/Models/Coupon.php`
- `app/Models/Plan.php` — reference implementation of correct cents + display accessor pattern
