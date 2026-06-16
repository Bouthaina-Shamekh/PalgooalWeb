# ADR-003 Readiness Audit

**Date:** 2026-06-16  
**Auditor:** Engineering  
**Scope:** Read-only audit — no code changes, no migrations, no model edits  
**Related:** `docs/adr/003-integer-cents-money-storage.md`, `docs/ADR_IMPLEMENTATION_PLAN.md`

---

## Audit Constraints

This audit is **read-only**. Nothing was created or modified:
- ❌ No migrations created
- ❌ No models modified
- ❌ No controllers modified
- ❌ No code written
- ❌ ADR-003 not started

---

## 1. Money Columns Audit

### 1A. Non-Compliant Columns (DECIMAL / FLOAT — ADR-003 targets)

| Table | Column | Schema Type | Model Cast | Status |
|-------|--------|-------------|------------|--------|
| `subscriptions` | `price` | `decimal(10,2)` | `'float'` | ❌ PRIMARY TARGET |
| `templates` | `price` | `decimal(10,2)` | `'float'` | ❌ UPSTREAM DEPENDENCY |
| `templates` | `discount_price` | `decimal(10,2)` | `'float'` | ❌ DISPLAY ONLY (no invoice impact) |
| `coupons` | `discount_value` | `decimal(10,2)` | *(none)* | ⚠ STUB — see §5 |
| `domain_tld_prices` | `cost` | `decimal(10,2)` | *(none)* | ⚠ DEFERRED — see §6 |
| `domain_tld_prices` | `sale` | `decimal(10,2)` | *(none)* | ⚠ DEFERRED — see §6 |

### 1B. Compliant Columns (INTEGER_CENTS — already correct)

| Table | Column(s) | Schema Type | Model Cast | Status |
|-------|-----------|-------------|------------|--------|
| `plans` | `monthly_price_cents` | `unsignedInteger` | `'integer'` | ✅ |
| `plans` | `annual_price_cents` | `unsignedInteger` | `'integer'` | ✅ |
| `invoices` | `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents` | `unsignedBigInteger` | *(none needed)* | ✅ |
| `invoice_items` | `unit_price_cents`, `total_cents` | `unsignedBigInteger` | *(none needed)* | ✅ |
| `order_items` | `price_cents` | `unsignedBigInteger` | `'integer'` | ✅ |

**Summary:** 6 non-compliant columns (1 primary target, 2 templates, 1 stub coupon, 2 domain prices). 9 compliant columns. The entire invoice and order subsystem is already correct — zero rework needed there.

---

## 2. Read Paths Audit

### 2A. subscriptions.price

| Location | Usage | Impact on ADR-003 |
|----------|-------|-------------------|
| `resources/views/dashboard/index.blade.php:200-201` | `$sub->price` + `number_format($sub->price, 2)` (display only) | Must switch to `$sub->price_cents / 100` after migration |
| `resources/views/dashboard/management/subscriptions/edit.blade.php:94` | Form prefill `old('price', $subscription->price)` | Must use `price_cents / 100` after migration |

### 2B. templates.price / discount_price

| Location | Usage | Impact on ADR-003 |
|----------|-------|-------------------|
| `app/Http/Controllers/Front/CheckoutController.php:132` | `$basePrice = (float) ($template->price ?? 0)` — **display only** in checkout page rendering | Switch to `$template->price_cents / 100` after migration |
| `app/Services/Billing/OrderActivationService.php:210` | `'price' => $template->price` — **writes decimal into subscriptions.price** | Critical: must use `'price_cents' => (int) round($template->price * 100)` or `$template->price_cents` |

### 2C. domain_tld_prices.cost / sale

| Location | Usage | Impact on ADR-003 |
|----------|-------|-------------------|
| `app/Http/Controllers/Client/DomainController.php:459,599` | `(int) round(((float) $quote['price']) * 100)` — correctly converts to cents at call site | None (already safe) |
| `app/Services/Domains/DomainRenewalService.php:276,303` | `$quote['price_cents']` used for invoice (passes via API result object) | None (already uses cents) |
| `app/Http/Controllers/Admin/Management/DomainSearchController.php:118-354` | Display / API response only | None |
| `app/Http/Controllers/Admin/Management/DomainTldController.php:206-436` | Admin CRUD display | None |

---

## 3. Write Paths Audit — subscriptions.price

All 5 locations confirmed writing to `subscriptions.price`:

| # | File | Line | Value Written | Source |
|---|------|------|---------------|--------|
| 1 | `app/Http/Controllers/Admin/Management/SubscriptionController.php` | ~154 | `$validated['price']` (numeric, from form input) | Admin manual create |
| 2 | `app/Http/Controllers/Admin/Management/SubscriptionController.php` | ~195 | `$validated['price']` (numeric, from form input) | Admin manual edit |
| 3 | `app/Http/Controllers/Front/CheckoutController.php` | 352 | `$config['unit_cents'] / 100` | Frontend checkout — converts cents BACK to decimal |
| 4 | `app/Services/Billing/OrderActivationService.php` | 210 | `$template->price` | Order activation fallback — reads template decimal |
| 5 | `app/Http/Controllers/Admin/Management/ServerController.php` | 127 | `0` (hardcoded zero) | Server-provisioned subscription |

**Critical observation on #3:** `CheckoutController` correctly uses integer cents throughout (`unit_cents`, `invoice_items.unit_price_cents`) but then converts back to decimal (`/ 100`) when writing `subscriptions.price`. This is the exact anti-pattern ADR-003 targets.

**Critical observation on #4:** `OrderActivationService` propagates `$template->price` (decimal) directly into `subscriptions.price` without any cents conversion. A `templates.price_cents` column (upstream migration) is required to break this chain cleanly.

---

## 4. Invoice Creation Audit

All 6 invoice creation sites — **100% compliant**. No changes required to the invoice subsystem.

| Location | Columns Written | Compliant? |
|----------|----------------|------------|
| `CheckoutController` (domain-only path) L258 | `subtotal_cents`, `total_cents` | ✅ |
| `CheckoutController` (mixed path) L314 | `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents` | ✅ |
| `Admin DomainController` L66 | `subtotal_cents`, `total_cents`; item: `unit_price_cents`, `total_cents` | ✅ |
| `Client DomainController` L80 | `subtotal_cents`, `total_cents`; item: `unit_price_cents`, `total_cents` | ✅ |
| `Client DomainController` L381 | `subtotal_cents`, `total_cents` from `$priceCents`; item: `unit_price_cents`, `total_cents` | ✅ |
| `DomainRenewalService` L71 | `subtotal_cents`, `total_cents` from `$quote['price_cents']`; item: `unit_price_cents`, `total_cents` | ✅ |
| `Admin InvoiceController` (manual) L425 | `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents` via `$totals[]`; items: `unit_price_cents`, `total_cents` | ✅ |
| `OrderActivationService` L41 | Does NOT create invoices — only flips draft→unpaid on existing invoices | ✅ (N/A) |

`InvoiceItem` is always written with `unit_price_cents` × `qty` → `total_cents`. Integer arithmetic throughout. The invoice subsystem is a **zero-impact zone** for ADR-003.

---

## 5. Coupon Audit

### Finding: Coupons are schema-only stubs

| Check | Result |
|-------|--------|
| `CouponController` exists? | **No** — not found anywhere in `app/Http/Controllers/` |
| Coupon routes registered? | **No** — no coupon routes in `routes/web.php` or `routes/api.php` |
| PHP reads `coupons.discount_value`? | **No** — zero PHP reads found in `app/` |
| Coupon discount applied in PHP? | **No** — `discount_cents` in invoice is computed from pricing logic, not from DB coupons |
| Frontend coupon logic? | `computeDiscount()` function in `front/pages/checkout.blade.php:2274` — **hardcoded** strings: `'PROMO10'` → 10%, `'WELCOME20'` → 2000 cents, `'FREE'` → 100% |
| `coupon_subscription` pivot written to? | **Never** — the BelongsToMany relation exists in `Subscription::coupons()` and `Coupon::subscriptions()` but no code calls `->attach()` or `->sync()` |

**Verdict on coupons:** The `coupons` table and `Coupon` model are schema scaffolding for a feature not yet implemented. `coupons.discount_value` is DECIMAL but has **zero PHP read paths**. Migration of this column is **not a blocker** for ADR-003 core. It can be migrated when the coupon feature is actually built.

---

## 6. domain_tld_prices Audit

| Check | Result |
|-------|--------|
| `cost` / `sale` columns | `decimal(10,2)` — no model cast |
| How consumed in billing path? | `Client\DomainController`: converts at call site → `(int) round(((float) $quote['price']) * 100)` → stored in `invoice_items.unit_price_cents` ✅ |
| How consumed in renewal path? | `DomainRenewalService` receives `$quote['price_cents']` (already cents from API result) → stored in `unit_price_cents` ✅ |
| Written to any subscription? | No |
| ADR-003 impact? | **None** — domain prices never flow into `subscriptions.price` directly |

**Verdict on domain prices:** The conversion from decimal to cents happens correctly at every call site. Migrating these columns to `_cents` would be clean but is **not required** for ADR-003 correctness. Deferred to a future cleanup.

---

## 7. Blocking Issues

### CRITICAL

| # | Issue | Location | Blocking |
|---|-------|----------|---------|
| C-1 | **5 write paths** must be updated simultaneously — if `price_cents` column is added but writes are not updated, dual-write is partial and data will be inconsistent | SubscriptionController ×2, CheckoutController, OrderActivationService, ServerController | Must update all 5 before or at the same time as migration |
| C-2 | **`OrderActivationService:210`** reads `$template->price` (decimal) and writes it to `subscriptions.price` — this path must switch to integer cents, which requires either converting at write time OR migrating `templates.price` first | `app/Services/Billing/OrderActivationService.php:210` | templates migration recommended before or alongside subscriptions |

### HIGH

| # | Issue | Location | Note |
|---|-------|----------|------|
| H-1 | **`CheckoutController:352`** converts `unit_cents / 100` back to decimal for `subscriptions.price` — must switch to writing `price_cents` directly | `app/Http/Controllers/Front/CheckoutController.php:352` | Easy fix once column exists |
| H-2 | `templates.price` is read as `(float)` in `CheckoutController:132` and `OrderActivationService:210` — display use of C2 above | Two files | Resolved when templates migrated |

### MEDIUM

| # | Issue | Note |
|---|-------|------|
| M-1 | `dashboard/index.blade.php` reads `$sub->price` for display — must switch to `price_cents / 100` post-migration | View-only, no data risk |
| M-2 | `subscriptions/edit.blade.php` prefills form with `$subscription->price` — must prefill from `price_cents / 100` | View-only |
| M-3 | `Admin SubscriptionController` validates `'price' => ['required', 'numeric', 'min:0']` — must be updated to validate integer `price_cents` | Low risk, easy |

### LOW

| # | Issue | Note |
|---|-------|------|
| L-1 | `coupons.discount_value` is DECIMAL but zero PHP reads exist — not a blocker, deferred to when coupon feature is built | |
| L-2 | `domain_tld_prices.cost/sale` are DECIMAL but all billing paths convert correctly at call site — deferred | |
| L-3 | `plan.discount_price` referenced in `CheckoutController:162` but no such column exists in `plans` table — returns null silently, dead code | |

---

## 8. Migration Scope

### Phase 1 (Required) — templates first

| Action | Target | Migration |
|--------|--------|-----------|
| Add column | `templates.price_cents` BIGINT UNSIGNED NULLABLE | New migration |
| Add column | `templates.discount_price_cents` BIGINT UNSIGNED NULLABLE | Same migration |
| Dual-write | `TemplateController::store()` + `update()` | Both columns populated |
| Backfill | `templates.price` → `(int) round($price * 100)` | Artisan command |
| Model | `Template::$casts += ['price_cents' => 'integer']` + accessor + `resolvedPrice()` helper | |

### Phase 2 (Required) — subscriptions core

| Action | Target | Migration |
|--------|--------|-----------|
| Add column | `subscriptions.price_cents` BIGINT UNSIGNED DEFAULT 0 | New migration |
| Dual-write #1 | `SubscriptionController::store()` + `update()` — read `price` input × 100 | Validate as integer cents |
| Dual-write #2 | `CheckoutController:352` — write `price_cents` = `$config['unit_cents']` directly | Remove `/ 100` |
| Dual-write #3 | `OrderActivationService:210` — write `price_cents` = `$template->price_cents` | Requires Phase 1 |
| Dual-write #4 | `ServerController:127` — write `price_cents` = 0 | Trivial |
| Backfill | `subscriptions.price` → `(int) round($price * 100)` | Artisan command |
| Model | `Subscription::$casts += ['price_cents' => 'integer']` + `resolvedPrice()` helper | |
| Views | `dashboard/index.blade.php`, `subscriptions/edit.blade.php` — use `price_cents / 100` | |

### Phase 3 (Deferred) — column drops

After ≥1 week production stability:
- Drop `subscriptions.price` (after all reads confirmed migrated)
- Drop `templates.price`, `templates.discount_price` (after all reads migrated)

### Out of Scope

- `coupons.discount_value` — deferred, coupon feature not built
- `domain_tld_prices.cost/sale` — deferred, call sites already convert correctly

---

## 9. Final Verdict

### ✅ READY FOR ADR-003

All paths are fully mapped. There are no unknown code locations or surprise consumers. The invoice and order subsystems are already 100% compliant — no rework required there.

**What makes this READY:**
- All 5 write paths for `subscriptions.price` are identified and understood
- All read paths are identified — 2 are view-only, 1 is a write (OrderActivationService)
- Invoice creation is already integer cents everywhere — zero impact zone
- Coupons are stub-only — zero PHP reads of `discount_value`
- Domain prices convert correctly at every call site — not a blocker
- Migration is two-phase and well-defined (templates → subscriptions)

**Implementation order required:**
1. Migrate `templates.price` → `price_cents` first (breaks the OrderActivationService dependency chain)
2. Migrate `subscriptions.price` → `price_cents` with dual-write on all 5 paths simultaneously
3. Stability window, then drop old decimal columns

**Estimated file changes for full ADR-003 core (Phases 1+2):**
- 2 new migrations
- 2 Artisan backfill commands
- 2 model files (`Template`, `Subscription`)
- 4 controller files (`TemplateController`, `SubscriptionController`, `CheckoutController`, `ServerController`)
- 1 service file (`OrderActivationService`)
- 2 view files (`dashboard/index.blade.php`, `subscriptions/edit.blade.php`)

**Total: ~11 files. No hidden complexity. Proceed with implementation.**

---

## Appendix — Key File References

| File | Role in ADR-003 |
|------|----------------|
| `app/Models/Tenancy/Subscription.php` | Primary target model — has `'price' => 'float'` cast |
| `app/Models/Template.php` | Upstream dependency — `'price' => 'float'`, `'discount_price' => 'float'` |
| `app/Models/Plan.php` | Already compliant — `monthly_price_cents`, `annual_price_cents` with integer casts |
| `app/Models/Invoice.php` | Zero impact — all cents columns, no money casts needed |
| `app/Http/Controllers/Admin/Management/SubscriptionController.php` | Writes 1+2 |
| `app/Http/Controllers/Front/CheckoutController.php` | Write 3 + template display reads |
| `app/Services/Billing/OrderActivationService.php` | Write 4 — blocked on templates migration |
| `app/Http/Controllers/Admin/Management/ServerController.php` | Write 5 (trivial) |
| `resources/views/dashboard/index.blade.php` | Display read — `$sub->price` |
| `resources/views/dashboard/management/subscriptions/edit.blade.php` | Form prefill — `$subscription->price` |
| `database/migrations/2025_05_03_132312_create_plans_table.php` | Reference: correct integer cents pattern to follow |
| `database/migrations/2025_08_23_114349_rebuild_invoices_tables.php` | Reference: correct integer cents pattern to follow |
