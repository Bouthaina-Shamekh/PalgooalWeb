# Pricing Plans — Dynamic Billing Toggle & Discount Support

**Date:** 2026-06-21  
**Section key:** `pricing_plans_dynamic`  
**Blade file:** `resources/views/front/sections/pricing/pricing_plans_dynamic.blade.php`  
**Header partial:** `resources/views/front/sections/pricing/_plan_header_dynamic.blade.php`

---

## 1. Data Source

### Model: `App\Models\Plan`

| Column | DB column | Accessor | Type |
|--------|-----------|----------|------|
| Monthly price | `monthly_price_cents` | `$plan->monthly_price` | `float\|null` (dollars) |
| Annual price | `annual_price_cents` | `$plan->annual_price` | `float\|null` (total per year, dollars) |
| Is featured | `is_featured` | `$plan->is_featured` | `bool` |
| Featured label | `featured_label` | `$plan->featured_label` | `string\|null` |

**Note:** Both price columns are stored in **cents** (integer) in the database.  
The accessors `getMonthlyPriceAttribute()` and `getAnnualPriceAttribute()` divide by 100 to return dollars.

### Query used in `pricing_plans_dynamic.blade.php`

```php
$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

Translations are eager-loaded to avoid N+1. Ordering by ascending monthly price ensures cheapest plan appears first.

---

## 2. Discount Formula

### Per-plan discount percentage

```php
$monthlyYearTotal = $monthly_price * 12;

$discountPercent = ($monthlyYearTotal > 0)
    ? (int) round((($monthlyYearTotal - $annual_price) / $monthlyYearTotal) * 100)
    : 0;
```

**Protection against edge cases:**
- `$monthly_price === null` → returns `0` (no badge shown)
- `$annual_price === null` → returns `0` (no badge shown)
- `$monthly_price <= 0` → returns `0` (division-by-zero guard)
- `$discountPercent <= 0` → returns `0` (no badge if annual ≥ monthly × 12)

### Example calculations

| Monthly | Annual | Monthly × 12 | Discount % | Save / year |
|---------|--------|-------------|------------|-------------|
| $10.00  | $120.00 | $120.00 | **0%** — badge NOT shown | $0 |
| $19.99  | $191.88 | $239.88 | **20%** — badge shown | $48 |
| $20.00  | $192.00 | $240.00 | **20%** — badge shown | $48 |
| $29.99  | $287.88 | $359.88 | **20%** — badge shown | $72 |
| $15.00  | $162.00 | $180.00 | **10%** — badge shown | $18 |

### Yearly saving in dollars

```php
$yearlySaving = max(0, (int) round(($monthly_price * 12) - $annual_price));
```

---

## 3. Toggle Behaviour

The toggle is powered by Alpine.js: `x-data="{ annual: false }"` on the `<section>` element.  
No page reload. All switching is client-side.

### Monthly mode (`annual === false`)
- Price displayed: `$monthlyPrice /mo`  (e.g. `$19.99`)
- Annual billing sub-line: **hidden**
- Discount badge per card: **hidden** (`x-show="annual"`)

### Annual mode (`annual === true`)
- Price displayed: `$annualPrice ÷ 12 /mo`  (e.g. `$15.99`)
- Annual billing sub-line: **visible** — e.g. `Billed $191.88 / year — save $48`
- Discount badge per card: **visible** — e.g. `Save 20%`

### Alpine.js x-text expression for price

```html
<span x-text="annual ? '{{ $monthlyEquiv ?? number_format($monthlyPrice, 2) }}' : '{{ number_format($monthlyPrice, 2) }}'">
    {{ number_format($monthlyPrice, 2) }}
</span>
```

The fallback text inside the `<span>` is the static monthly price (shown before Alpine initialises / if JS is disabled).

---

## 4. Discount Badge Display Logic

### Toggle-area badge (Annual button chip)

Shows the **maximum discount across all active plans**.

```php
$maxDiscountPercent = (int) $allPlans->map($computeDiscountPercent)->max();
```

```blade
@if ($maxDiscountPercent > 0)
    <span class="bg-red-brand text-white ...">−{{ $maxDiscountPercent }}%</span>
@endif
```

If **no plan has a discount** (all annual prices equal monthly × 12), the badge is **not rendered**.

### Per-plan Save badge (inside each card)

```blade
@if ($hasDiscount)
    <div x-show="annual" x-transition.opacity.duration.300ms>
        <span>{{ strtr(t('site.Save_Percent', 'Save :percent%'), [':percent' => $discountPercent]) }}</span>
    </div>
@endif
```

- Rendered in PHP only if `$discountPercent > 0` — prevents empty DOM nodes.
- Shown/hidden via Alpine.js `x-show="annual"` — no flash.
- Smooth opacity transition (`300ms`).

---

## 5. Savings Message

The hint line below the toggle shows the **highest dollar saving** across all plans:

```php
$maxSavingDollars = (int) $allPlans->map(function ($plan) {
    if (! $plan->monthly_price || ! $plan->annual_price) return 0;
    return max(0, (int) round(($plan->monthly_price * 12) - $plan->annual_price));
})->max();
```

```blade
@if ($maxSavingDollars > 0)
    <p :class="annual ? 'opacity-100' : 'opacity-0 pointer-events-none'">
        💡 {{ t('site.You_Save_Up_To') }}
        <strong>${{ $maxSavingDollars }}/year</strong>
        {{ t('site.With_Annual_Billing') }}
    </p>
@endif
```

- If `$maxSavingDollars === 0`: the paragraph is **not rendered** at all.
- The message fades in/out using CSS `opacity` transition (Alpine class binding).

---

## 6. Featured Plan

The `Most Popular` badge only appears when `$plan->is_featured === true`:

```php
$isFeatured = (bool) $plan->is_featured;
```

```blade
@if ($isFeatured)
    {{-- badge rendered --}}
    <span>{{ $featuredLabel }}</span>  {{-- from $plan->featured_label or t('site.Most_Popular') --}}
@endif
```

No hardcoded text. `$plan->featured_label` falls back to `t('site.Most_Popular', 'Most Popular')`.

---

## 7. Translation Keys

All keys are seeded via `SiteTranslationsSeeder` (Arabic locale).

| Key | Arabic | English fallback |
|-----|--------|-----------------|
| `site.Monthly` | شهري | Monthly |
| `site.Annual` | سنوي | Annual |
| `site.You_Save_Up_To` | توفّر ما يصل إلى | You save up to |
| `site.With_Annual_Billing` | مع الاشتراك السنوي | with annual billing |
| `site.Save_Percent` | وفّر :percent% | Save :percent% |
| `site.Per_Month` | /شهر | /mo |
| `site.Billed_Per_Year` | يُحسب $:amount / سنة | Billed $:amount / year |
| `site.Save_Per_Year` | توفير $:amount | save $:amount |
| `site.No_Plans_Available` | لا توجد خطط متاحة حالياً. | No plans available. |
| `site.No_Features_Listed` | لا توجد مميزات محددة. | No features listed. |
| `site.Choose_Now` | اشترك الآن | Choose now |
| `site.Most_Popular` | الأكثر طلباً | Most Popular |

### Variable replacement in `t()`

`t()` does **not** support `:param` replacement. Use `strtr()` externally:

```php
// ✅ Correct
strtr(t('site.Save_Percent', 'Save :percent%'), [':percent' => $discountPercent])
// → "وفّر 20%"

// ✅ Correct
strtr(t('site.Billed_Per_Year', 'Billed $:amount / year'), [':amount' => '191.88'])
// → "يُحسب $191.88 / سنة"
```

---

## 8. Files Changed / Created

| File | Action |
|------|--------|
| `resources/views/front/sections/pricing/pricing_plans_dynamic.blade.php` | **Created** — new standalone section |
| `resources/views/front/sections/pricing/_plan_header_dynamic.blade.php` | **Created** — new header partial with `t()` + annual billing line |
| `resources/views/front/sections/pricing/_plan_header.blade.php` | **Updated** — `/mo` and billing strings now use `t()` |
| `database/seeders/SiteTranslationsSeeder.php` | **Updated** — 12 new `site.*` keys added |

### Unchanged files (backward-compatible)
- `pricing_plans.blade.php` — existing section unchanged, continues to use old partials
- `_plan_features.blade.php` — reused as-is by both sections
- `_plan_cta.blade.php` — reused as-is by both sections

---

## 9. Validation Scenarios

### Scenario A — Plan with NO discount (`monthly = $10, annual = $120`)

| Element | Expected result |
|---------|----------------|
| Toggle Annual badge | **Not rendered** (`$maxDiscountPercent = 0`) |
| Per-card discount badge | **Not rendered** (`$hasDiscount = false`) |
| Annual price display | `$10.00/mo` (same as monthly ÷ 12 = $10) |
| Billing sub-line | Shown: `Billed $120.00 / year` (no "save" part) |
| Savings hint | **Not rendered** (`$maxSavingDollars = 0`) |

### Scenario B — Plan with 20% discount (`monthly = $20, annual = $192`)

| Element | Expected result |
|---------|----------------|
| Toggle Annual badge | `−20%` chip visible |
| Per-card discount badge | `Save 20%` visible in annual mode |
| Monthly mode price | `$20.00/mo` |
| Annual mode price | `$16.00/mo` ($192 ÷ 12) |
| Billing sub-line | `Billed $192.00 / year — save $48` |
| Savings hint | `You save up to $48/year with annual billing` |

### Scenario C — Mixed plans (some with, some without discount)

- Toggle Annual badge shows **highest** discount % across all plans
- Per-card badges show each plan's **own** discount %
- Savings hint shows **highest** dollar saving across all plans
- Plans with zero discount: no badge, same price in both modes

---

## 10. How to Activate

Run after deployment:

```bash
php artisan db:seed --class=SiteTranslationsSeeder
php artisan cache:clear
```

To create the section definition in the admin (if not already present):

1. Go to **Admin → Section Definitions → Create From Template**
2. Or create manually with `section_key = pricing_plans_dynamic`, category = `pricing`
3. The system resolves Blade via convention: `front.sections.pricing.pricing_plans_dynamic`
