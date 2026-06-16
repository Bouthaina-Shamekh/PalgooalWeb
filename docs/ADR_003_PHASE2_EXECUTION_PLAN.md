# ADR-003 Phase 2 — Execution Plan: `subscriptions.price_cents`

**Date:** 2026-06-16  
**Status:** 📋 PLAN — Not yet implemented  
**Scope:** `subscriptions.price` (decimal) → `subscriptions.price_cents` (BIGINT UNSIGNED)  
**Prerequisite:** Phase 1 (`templates.price_cents`) must be stable for ≥1 week before Phase 2 begins  
**Hard constraints:** No migrations created · No models edited · No controllers edited · No commands created · Planning only

---

## 1. Re-Audit: Write Paths for `subscriptions.price`

Full fresh audit of every PHP location that writes the `price` column on the `subscriptions` table.

| # | File | Method | Line | Write Pattern | Notes |
|---|------|--------|------|---------------|-------|
| W1 | `app/Http/Controllers/Admin/Management/SubscriptionController.php` | `store()` | ~131–160 | `Subscription::create($data)` where `$data['price']` comes from `$request->validate(['price' => ['required','numeric','min:0']])` | Admin creates subscription manually |
| W2 | `app/Http/Controllers/Admin/Management/SubscriptionController.php` | `update()` | ~177–196 | `$subscription->update($data)` same validation | Admin edits subscription price manually |
| W3 | `app/Http/Controllers/Front/CheckoutController.php` | _(checkout flow)_ | ~355 | `Subscription::create(['price' => $config['unit_cents'] / 100, ...])` | Client checkout — converts from cents then stores as decimal |
| W4 | `app/Http/Controllers/Admin/Management/ServerController.php` | _(accounts)_ | ~127 | `Subscription::create(['price' => 0, ...])` | Hardcoded zero — admin "add existing account" tool |
| W5 | `app/Services/Billing/OrderActivationService.php` | `activate()` | ~212 | `Subscription::create(['price' => $template->resolvedPrice(), ...])` | Order activation — already reads from Phase 1 cents helper |

**Non-writes confirmed (no action needed):**
- `SubscriptionSyncService` — updates `status`, `username`, `last_sync_message` only
- `SyncSubscriptionToProvider` Job — updates `last_sync_message` only
- `SubscriptionThemeController` (admin + client) — updates `theme_settings` only

---

## 2. Re-Audit: Read Paths for `subscriptions.price`

Full fresh audit of every location that reads the `price` column from a subscription.

| # | File | Line | Read Pattern | Context |
|---|------|------|--------------|---------|
| R1 | `resources/views/dashboard/index.blade.php` | 200–201 | `$sub->price` → `number_format($sub->price, 2)` | Admin dashboard home — secondary price label under plan name in "Recent Subscriptions" table |
| R2 | `resources/views/dashboard/management/subscriptions/edit.blade.php` | 94 | `old('price', $subscription->price)` | Admin edit form — pre-fills the price `<input>` field |
| R3 | `resources/views/dashboard/management/subscriptions/edit.blade.php` | 4 | `$plan->price ?? 0` → `$plansArray` → JS auto-fill | ⚠️ Pre-existing bug: `Plan` model has no `price` column (uses `monthly_price_cents`); always returns 0. ADR-003 will not fix this bug but should not worsen it. |

**Confirmed zero-impact reads (no subscription->price read):**
- `resources/views/dashboard/management/invoices/` — all amounts come from `invoice_items.unit_price_cents`; subscription selected by ID only, price not read
- `app/Services/Billing/InvoiceSettlementService.php` — no `subscription->price` reads found
- `app/Services/Tenancy/TenantProvisioningService.php` — no price reads
- `app/Jobs/ProvisionSubscription.php` — no price reads
- All client-side views (`resources/views/client/`) — no subscription price reads found

---

## 3. Subscription Lifecycle Map

```
ADMIN MANUAL CREATE
  SubscriptionController::store()
    ↓ validates 'price' (decimal input)
    ↓ Subscription::create($data) → writes price (decimal)
    ↓ Phase 2: also writes price_cents (integer)

CLIENT CHECKOUT
  CheckoutController (checkout flow)
    ↓ $config['unit_cents'] ← already an integer
    ↓ 'price' => $config['unit_cents'] / 100 → decimal written to DB
    ↓ Phase 2: also 'price_cents' => $config['unit_cents']

ORDER ACTIVATION (from payment confirmation)
  OrderActivationService::activate()
    ↓ 'price' => $template->resolvedPrice() ← already from Phase 1 cents helper
    ↓ Phase 2: also 'price_cents' => $template->resolvedPriceCents()

SERVER ACCOUNT IMPORT
  ServerController (admin)
    ↓ 'price' => 0 (hardcoded)
    ↓ Phase 2: also 'price_cents' => 0

ADMIN EDIT
  SubscriptionController::update()
    ↓ validates 'price' (decimal input)
    ↓ $subscription->update($data) → writes price (decimal)
    ↓ Phase 2: also writes price_cents from round(price * 100)

READ → DISPLAY
  dashboard/index.blade: $sub->price → display only
  subscriptions/edit.blade: $subscription->price → pre-fill input
  Phase 2 (read switch): both use resolvedPrice() / resolvedPriceCents()
```

---

## 4. Dual-Write Strategy

**Principle:** Every write to `subscriptions.price` simultaneously writes `subscriptions.price_cents`. Old column kept. No reads switch until all writes are dual-writing.

| Write Path | Old Code | Phase 2 Code |
|-----------|---------|--------------|
| W1 `SubscriptionController::store()` | `$data['price']` | Add: `$data['price_cents'] = (int) round((float) $data['price'] * 100);` before `Subscription::create($data)` |
| W2 `SubscriptionController::update()` | `$subscription->update($data)` | Same: add `price_cents` to `$data` before update |
| W3 `CheckoutController` checkout | `'price' => $config['unit_cents'] / 100` | Add: `'price_cents' => $config['unit_cents']` (already integer — no conversion needed) |
| W4 `ServerController` | `'price' => 0` | Add: `'price_cents' => 0` |
| W5 `OrderActivationService` | `'price' => $template->resolvedPrice()` | Add: `'price_cents' => $template->resolvedPriceCents()` |

**Model changes:**
- Add `price_cents` to `$fillable`
- Add cast: `'price_cents' => 'integer'`
- Add four helpers: `resolvedPriceCents(): int`, `resolvedPrice(): float`, (and optionally) `resolvedPriceCentsOrNull(): ?int`

**Helper pattern (mirrors Phase 1):**
```php
public function resolvedPriceCents(): int
{
    $raw = $this->getRawOriginal('price_cents');
    if ($raw !== null) return (int) $raw;
    return (int) round((float) ($this->getRawOriginal('price') ?? 0) * 100);
}

public function resolvedPrice(): float
{
    return $this->resolvedPriceCents() / 100;
}
```
`getRawOriginal()` bypasses the `'price' => 'float'` cast to avoid double-rounding.

---

## 5. Read-Switch Strategy

After dual-write is live and the backfill has run (no mismatches), switch the two read locations:

| Read Path | Old Code | Phase 2 Code |
|----------|---------|--------------|
| R1 `dashboard/index.blade:201` | `$sub->price` | `$sub->resolvedPrice()` |
| R2 `subscriptions/edit.blade:94` | `old('price', $subscription->price)` | `old('price', $subscription->resolvedPrice())` |

**Note on R3 (`$plan->price ?? 0`):** This is a pre-existing bug (`Plan` has no `price` column). ADR-003 Phase 2 will **not** fix it — it returns 0 today and will continue to return 0. A separate fix (using `$plan->monthly_price ?? 0`) can be applied independently.

**Read-switch is safe only after:**
1. Migration ran → `price_cents` column exists
2. Dual-write deployed → all new rows have `price_cents` set
3. Backfill ran → all old rows have `price_cents` set
4. SQL check: `SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL;` → must return **0**

---

## 6. Backfill Command Design

**Command:** `php artisan adr003:backfill-subscription-prices [--dry-run]`

**Registration location:** `routes/console.php` — insert after the `adr003:backfill-template-prices` command block.

**Algorithm:**
```
foreach chunk(200) of subscriptions:
    $expectedCents = (int) round((float) $row->getRawOriginal('price') * 100)
    if $row->getRawOriginal('price_cents') === null:
        action = UPDATE → set price_cents = $expectedCents
    elif $row->getRawOriginal('price_cents') !== $expectedCents:
        action = MISMATCH — log warning, update to expected
    else:
        action = SKIP (already correct)
    if not dry_run: execute UPDATE
```

**Output summary table:**

| Column | Meaning |
|--------|---------|
| `processed` | Total rows examined |
| `updated` | Rows where `price_cents` was null → filled |
| `skipped` | Rows already correct |
| `mismatches` | Rows where existing `price_cents` ≠ `round(price × 100)` — warning |
| `dry_run` | true/false |

**After backfill — verification SQL:**
```sql
-- Must return 0 (no nulls after backfill)
SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL;

-- Must return 0 (no mismatches)
SELECT id, price, price_cents, ROUND(price * 100) AS expected
FROM subscriptions
WHERE price_cents != ROUND(price * 100);
```

---

## 7. Risk Analysis

| Risk | Severity | Likelihood | Mitigation |
|------|----------|-----------|------------|
| **CheckoutController writes cents→decimal→cents, losing precision** | Critical | Low | Phase 2 stores `price_cents` directly from `$config['unit_cents']` (already integer) — no decimal conversion in the round-trip |
| **`SubscriptionController` float input rounding error** | High | Low | `round((float) $input * 100)` is the same pattern as Phase 1; identical risk profile, accepted |
| **Backfill produces mismatches due to legacy float storage** | High | Low–Medium | Backfill computes `round(price * 100)` and warns on mismatch; DBA reviews before read-switch |
| **`OrderActivationService` writes wrong cents** | High | Very Low | Already uses `$template->resolvedPriceCents()` (Phase 1) — exact integer, no conversion |
| **`ServerController` hardcoded 0 causes unexpected mismatch** | Low | Very Low | `price = 0`, `price_cents = 0` — perfectly consistent |
| **`$plan->price ?? 0` bug (R3) silently overwrites real price** | Medium | Medium | The edit form pre-fills price from `$subscription->price` (R2), not from JS auto-fill (R3 only triggers on plan change). User can still overwrite manually. Phase 2 does not worsen this. |
| **Migration takes lock on large subscriptions table** | Medium | Low | `price_cents` is nullable BIGINT → single fast ALTER, no data rewrite; test on staging first |
| **Backfill runs during peak traffic** | Medium | Low | Run with `--dry-run` first; schedule during low-traffic window |
| **Read-switch deployed before backfill** | Critical | Preventable | Sequence gate: deploy migration → deploy dual-write → run backfill → SQL zero-check → deploy read-switch |

---

## 8. Rollback Plan

**Trigger:** Any checkout 500 error, price display corruption, or mismatch count > 0 after read-switch.

**Steps:**

| Phase | Rollback Action | Impact |
|-------|----------------|--------|
| After migration only | Run `php artisan migrate:rollback` (down() drops `price_cents`) | Zero — column was nullable, no data lost |
| After dual-write deployed | Revert W1–W5 controller changes; column still exists but stays null | Zero — old `price` column still populated and used |
| After backfill ran | No rollback needed — backfill only fills nulls, does not change `price` | Zero |
| After read-switch | Revert R1–R2 view changes back to `$sub->price` / `$subscription->price` | Zero — old decimal column still accurate |
| Nuclear option | `ALTER TABLE subscriptions DROP COLUMN price_cents;` | Only after confirmed no reads of `price_cents` remain |

**Key guarantee:** The no-drop policy means `subscriptions.price` (decimal) is never removed during Phase 2. Even if `price_cents` is wrong, the old column is the authoritative fallback.

---

## 9. Implementation Order (7 Steps)

Execute in this exact sequence — each step is safe to deploy independently:

```
Step 1 — Migration
  Create: 2026_06_1X_add_price_cents_to_subscriptions_table.php
  ALTER TABLE subscriptions ADD COLUMN price_cents BIGINT UNSIGNED NULL
  → Run: php artisan migrate

Step 2 — Subscription Model
  Add price_cents to $fillable
  Add cast 'price_cents' => 'integer'
  Add helpers: resolvedPriceCents(), resolvedPrice()

Step 3 — Dual-Write W1: SubscriptionController::store()
  $data['price_cents'] = (int) round((float) $data['price'] * 100);

Step 4 — Dual-Write W2: SubscriptionController::update()
  Same as Step 3

Step 5 — Dual-Write W3: CheckoutController
  'price_cents' => $config['unit_cents']   // already integer

Step 6 — Dual-Write W4+W5: ServerController + OrderActivationService
  ServerController: 'price_cents' => 0
  OrderActivationService: 'price_cents' => $template->resolvedPriceCents()

Step 7 — Backfill Command
  php artisan adr003:backfill-subscription-prices --dry-run
  php artisan adr003:backfill-subscription-prices
  → Verify: SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL; → 0

  ↓ [STABILITY WINDOW: ≥1 week, zero incidents]

Step 8 — Read Switch
  R1: dashboard/index.blade → $sub->resolvedPrice()
  R2: subscriptions/edit.blade → $subscription->resolvedPrice()

Step 9 — Report
  Create: docs/ADR_003_PHASE2_SUBSCRIPTION_PRICES_REPORT.md
```

---

## 10. Effort Estimate

| Step | Files | Complexity | Estimated Time |
|------|-------|------------|----------------|
| Migration | 1 file | Trivial | 5 min |
| Subscription Model | 1 file | Low | 15 min |
| Dual-Write W1+W2 (SubscriptionController) | 1 file | Low | 20 min |
| Dual-Write W3 (CheckoutController) | 1 file | Low | 15 min |
| Dual-Write W4+W5 (ServerController + OrderActivationService) | 2 files | Trivial | 10 min |
| Backfill Command (console.php) | 1 file | Medium | 30 min |
| Read Switch (2 views) | 2 files | Trivial | 10 min |
| Report Document | 1 file | Low | 20 min |
| **Total** | **9 files** | | **~2 hours** |

---

## 11. Readiness Gate

Phase 2 implementation may begin when **all** of the following are true:

| Gate | Criterion | Check |
|------|-----------|-------|
| G1 | Phase 1 migration ran (`templates.price_cents` column exists) | `SHOW COLUMNS FROM templates LIKE 'price_cents';` |
| G2 | Phase 1 backfill ran with zero mismatches | `SELECT COUNT(*) FROM templates WHERE price_cents IS NULL;` → 0 |
| G3 | Phase 1 stable ≥1 week in production | No price-related 500 errors or checkout regressions |
| G4 | `CheckoutController` uses `$basePriceCents` / `$discPriceCents` (Phase 1 read-switch done) | Confirmed in code |
| G5 | `OrderActivationService` uses `$template->resolvedPrice()` (Phase 1 update done) | Confirmed in code |

**Current gate status (as of 2026-06-16):**

| Gate | Status |
|------|--------|
| G1 | ⏳ Pending user runs `php artisan migrate` |
| G2 | ⏳ Pending user runs `php artisan adr003:backfill-template-prices` |
| G3 | ⏳ Stability window not yet started |
| G4 | ✅ Done (Phase 1 complete) |
| G5 | ✅ Done (Phase 1 complete) |

**Phase 2 is BLOCKED pending G1–G3.**

---

## 12. Files Affected in Phase 2 (Summary)

| File | Change Type |
|------|------------|
| `database/migrations/2026_06_1X_add_price_cents_to_subscriptions_table.php` | **New** |
| `app/Models/Tenancy/Subscription.php` | **Edit** — fillable + cast + 2 helpers |
| `app/Http/Controllers/Admin/Management/SubscriptionController.php` | **Edit** — dual-write in store() + update() |
| `app/Http/Controllers/Front/CheckoutController.php` | **Edit** — dual-write in checkout create |
| `app/Http/Controllers/Admin/Management/ServerController.php` | **Edit** — dual-write `price_cents => 0` |
| `app/Services/Billing/OrderActivationService.php` | **Edit** — dual-write `price_cents` |
| `routes/console.php` | **Edit** — add `adr003:backfill-subscription-prices` command |
| `resources/views/dashboard/index.blade.php` | **Edit** — read switch R1 (after backfill) |
| `resources/views/dashboard/management/subscriptions/edit.blade.php` | **Edit** — read switch R2 (after backfill) |
| `docs/ADR_003_PHASE2_SUBSCRIPTION_PRICES_REPORT.md` | **New** (final report) |

---

## Appendix A: Pre-existing Bug (Out of Scope)

**Location:** `resources/views/dashboard/management/subscriptions/edit.blade.php:4`

```php
$plansArray[$plan->id] = $plan->price ?? 0;
// ↑ Plan model has no 'price' column → always returns 0
// Correct code: $plan->monthly_price ?? 0  (computed from monthly_price_cents / 100)
```

This causes the JS auto-fill to always set price to `0` when the admin changes the plan dropdown. The admin must then manually enter the price. This bug pre-dates ADR-003 and is out of scope for Phase 2 — but it should be fixed as part of a separate subscription form improvement.

---

## Appendix B: Constraints Honored

| Constraint | Status |
|-----------|--------|
| No Migration created | ✅ Planning only |
| No Models edited | ✅ Planning only |
| No Controllers edited | ✅ Planning only |
| No Commands created | ✅ Planning only |
| ADR-003 Phase 2 not executed | ✅ Planning only |
| `subscriptions.price` not dropped | ✅ No-drop policy confirmed |
| `coupons` not touched | ✅ Out of scope |
| `domain_tld_prices` not touched | ✅ Out of scope |
| `invoices` / `orders` not touched | ✅ Out of scope |
| No `__()` used | ✅ |
| No `with('success', ...)` used | ✅ |
