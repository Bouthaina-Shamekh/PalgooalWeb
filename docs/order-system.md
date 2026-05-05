# Order System

## Overview

The order system manages the full lifecycle of customer orders — from creation through
activation, status changes, and soft-deletion. It is tightly coupled with the billing
layer (`Invoice`, `InvoiceItem`) and the `OrderActivationService`.

---

## Core Files

| File | Role |
|------|------|
| `app/Models/Order.php` | Eloquent model, order_number generation, status constants |
| `app/Models/OrderItem.php` | Line items belonging to an order |
| `app/Http/Controllers/Admin/Management/OrderController.php` | Admin CRUD + bulk actions |
| `app/Policies/OrderPolicy.php` | Authorization (extends `ModelPolicy`) |
| `app/Services/Billing/OrderActivationService.php` | Business logic for activating an order |
| `database/migrations/*_create_orders_table.php` | Schema |
| `database/migrations/*_add_soft_deletes_to_orders_tables.php` | Adds `deleted_at` to orders + order_items |
| `resources/views/dashboard/management/orders/` | Admin Blade views |

---

## Data Model

### `orders` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint unsigned PK | Auto-increment |
| `order_number` | varchar(32), unique | Format: `ORD-YYYYMMDD-XXXXXXXX`. Generated in `booted()->creating()`. Immutable after creation. |
| `client_id` | bigint FK nullable | Nullable so order history is preserved if client is deleted |
| `status` | enum/varchar | `pending`, `active`, `cancelled`, `fraud` |
| `type` | varchar | e.g. `subscription`, `domain` |
| `notes` | text nullable | Free-text admin notes |
| `deleted_at` | timestamp nullable | Soft-delete column |
| `created_at` / `updated_at` | timestamps | |

### `order_items` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint unsigned PK | |
| `order_id` | bigint FK | |
| `domain` | varchar | The domain this item relates to |
| `item_option` | varchar | Product option (e.g. plan slug) |
| `price_cents` | integer | Amount in cents |
| `meta` | json nullable | Arbitrary metadata; cast to `array` in model |
| `deleted_at` | timestamp nullable | Soft-delete column |

---

## Status Machine

```
pending ──► active
pending ──► cancelled
pending ──► fraud
active  ──► cancelled
active  ──► fraud
```

Status changes are performed via `OrderController::updateStatus()` (single order) or
`OrderController::bulk()` (batch). Both methods are wrapped in `DB::transaction()` so
a failed activation rolls back the status update.

When transitioning to `active`, `OrderActivationService::activate()` is called to
create or update related invoices and provision the service.

---

## Order Number Generation

Order numbers follow the pattern `ORD-YYYYMMDD-XXXXXXXX` (8 random uppercase chars).
This gives ~208 billion unique combinations per calendar day.

**Race-condition-safe creation**: use `Order::createWithUniqueNumber($attributes)` instead
of `Order::create()`. It retries up to 5 times on `SQLSTATE 23000` (unique constraint
violation). The `order_number` column must have a `UNIQUE` index.

```php
// Correct — collision-safe
$order = Order::createWithUniqueNumber([
    'client_id' => $client->id,
    'type'      => 'subscription',
]);

// Wrong — may collide under concurrent load
$order = Order::create([...]);
```

`Order::generateCandidateNumber()` is also public so it can be used in tests or seeders
to generate numbers without persisting.

**Immutability**: `order_number` is excluded from `$fillable` and cannot be changed after
creation. Both the Eloquent `updating` hook and the `setOrderNumberAttribute()` mutator
enforce this.

---

## Authorization

Authorization uses the project's `ModelPolicy` base class with role-slug pattern. Each
action requires the authenticated user to have the corresponding role assigned.

| Controller method | Required role slug |
|-------------------|--------------------|
| `index` | `orders.viewAny` |
| `show` | `orders.view` |
| `updateStatus` | `orders.update` |
| `bulk` | `orders.bulk` (custom method in `OrderPolicy`) |

`OrderPolicy::bulk()` is defined explicitly because `ModelPolicy.__call()` does not cover
custom method names.

---

## Soft Deletes

Both `orders` and `order_items` use Laravel's `SoftDeletes` trait. Calling
`Order::whereIn('id', $ids)->delete()` marks records with a `deleted_at` timestamp rather
than physically removing them.

To recover a soft-deleted order from Tinker:

```php
Order::withTrashed()->find($id)->restore();
```

To permanently purge (use with caution):

```php
Order::withTrashed()->find($id)->forceDelete();
```

---

## N+1 Prevention

The `show()` controller action eager-loads all required relations in a single query:

```php
Order::with(['client', 'items', 'invoices.items'])->findOrFail($id)
```

The `getTotalCentsAttribute` accessor also avoids a redundant DB query when `items` is
already loaded in memory:

```php
public function getTotalCentsAttribute(): int
{
    if ($this->relationLoaded('items')) {
        return (int) $this->items->sum('price_cents');
    }
    return (int) $this->items()->sum('price_cents');
}
```

---

## Nullable `client_id`

`client_id` is nullable. When a client account is deleted, the FK is set to `NULL` so
that historical order and invoice records are not lost.

To prevent null-pointer errors in views, the `client()` relation uses `withDefault()`:

```php
public function client(): BelongsTo
{
    return $this->belongsTo(Client::class)->withDefault([
        'first_name' => '—',
        'last_name'  => '',
        'email'      => '—',
        'phone'      => '—',
    ]);
}
```

Views should access `$order->client->first_name` directly — no `?? '-'` guard needed.

---

## Bulk Actions

`OrderController::bulk()` handles batch status changes and soft-deletes. Key design
decisions:

- Each bulk activation wraps `OrderActivationService::activate()` in its **own**
  `DB::transaction()` so one failure does not prevent the remaining orders from being
  activated.
- Failed activations are logged via `Log::error()` and do not surface to the user.
- `LIKE` search wildcards (`%`, `_`, `\`) in the search query are escaped with
  `addcslashes()` to prevent wildcard injection.

---

## Known Gaps / Future Work

- `extra` and `server_id` columns referenced in some activation logic are **not** present
  in the current migration. If needed, add a migration:
  ```php
  $table->string('server_id')->nullable();
  $table->json('extra')->nullable();
  ```
- There is currently no `restore` endpoint — recovering soft-deleted orders requires
  Tinker or a future admin UI action.
- `OrderActivationService` does not publish domain events; consider adding
  `OrderActivated` / `OrderCancelled` events for downstream listeners.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-05 | Added `SoftDeletes` to `orders` + `order_items`; collision-safe `createWithUniqueNumber()`; `withDefault()` on `client()` relation; atomic `updateStatus()` and `bulk()`; N+1 fix for `invoices.items`; XSS fix in `show.blade.php`; LIKE wildcard escaping; swapped column fix in index table; hardcoded currency `$` removed from invoices view. |
