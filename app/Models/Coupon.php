<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * ADR-008 Phase 1 — Coupon Foundation
 *
 * @property int         $id
 * @property string      $code
 * @property string      $discount_type   'fixed' | 'percent'
 * @property float       $discount_value
 * @property Carbon|null $expires_at
 * @property int|null    $max_uses
 * @property int         $used_count
 * @property bool        $is_active
 * @property int|null    $minimum_amount_cents
 */
class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'expires_at',
        // ADR-008 Phase 1
        'max_uses',
        'used_count',
        'is_active',
        'minimum_amount_cents',
    ];

    protected $casts = [
        'expires_at'           => 'datetime',
        'is_active'            => 'boolean',
        'max_uses'             => 'integer',
        'used_count'           => 'integer',
        'minimum_amount_cents' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class)
                    ->withTimestamps();
    }

    /** Invoices that used this coupon (ADR-008 Phase 1). */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Usable coupons: active, not expired, not exhausted.
     * Does NOT filter by minimum_amount_cents — that requires context.
     *
     * @param  Builder  $query
     * @param  int|null $subtotalCents  When provided, also filters by minimum_amount_cents.
     */
    public function scopeUsable(Builder $query, ?int $subtotalCents = null): Builder
    {
        $query->where('is_active', true)
              ->where(function (Builder $q) {
                  $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
              })
              ->where(function (Builder $q) {
                  $q->whereNull('max_uses')
                    ->orWhereColumn('used_count', '<', 'max_uses');
              });

        if ($subtotalCents !== null) {
            $query->where(function (Builder $q) use ($subtotalCents) {
                $q->whereNull('minimum_amount_cents')
                  ->orWhere('minimum_amount_cents', '<=', $subtotalCents);
            });
        }

        return $query;
    }

    /** Alias convenience scope — active flag only. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /**
     * Check whether this coupon can be applied to a given subtotal.
     *
     * Checks: is_active, expires_at, max_uses vs used_count, minimum_amount_cents.
     *
     * @param  int  $subtotalCents  Cart subtotal in cents.
     */
    public function isUsableForSubtotal(int $subtotalCents): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->minimum_amount_cents !== null && $subtotalCents < $this->minimum_amount_cents) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount in cents for a given subtotal.
     *
     * Rules:
     *   - fixed:   discount_value is treated as a USD decimal (e.g. 10.00 → 1000 cents)
     *   - percent: discount_value is a percentage (e.g. 20 → 20%)
     *   - Result is capped at subtotalCents (never negative invoice)
     *   - Returns 0 if subtotalCents <= 0
     *
     * @param  int  $subtotalCents  Cart subtotal in cents.
     * @return int  Discount amount in cents (>= 0, <= subtotalCents).
     */
    public function computeDiscountCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discount = match ($this->discount_type) {
            'fixed'   => (int) round((float) $this->discount_value * 100),
            'percent' => (int) round($subtotalCents * ((float) $this->discount_value / 100)),
            default   => 0,
        };

        return min($discount, $subtotalCents);
    }
}
