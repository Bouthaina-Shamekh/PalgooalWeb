<?php

namespace App\Models;

use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
// Domain is in the App\Models namespace (same as this class), no import needed.

class InvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'item_type',
        'reference_id',
        'description',
        'qty',
        'unit_price_cents',
        'total_cents',
    ];

    // البند مرتبط بفاتورة
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Raw Eloquent relation to the related Subscription row.
     *
     * Use the type-guarded accessor ($item->subscription) instead of calling
     * this method directly; it returns null when item_type !== 'subscription'.
     */
    public function subscriptionRelation(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'reference_id')
            ->withDefault();
    }

    /**
     * Raw Eloquent relation to the related Domain row.
     *
     * Use the type-guarded accessor ($item->domain) instead of calling
     * this method directly; it returns null when item_type !== 'domain'.
     */
    public function domainRelation(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'reference_id')
            ->withDefault();
    }

    /**
     * Type-guarded accessor: returns the Subscription only when item_type === 'subscription',
     * preventing phantom FK matches on mixed-type item collections.
     */
    public function getSubscriptionAttribute(): ?Subscription
    {
        if ($this->item_type !== 'subscription') {
            return null;
        }

        if (! $this->relationLoaded('subscriptionRelation')) {
            $this->load('subscriptionRelation');
        }

        $related = $this->getRelation('subscriptionRelation');

        // withDefault() returns an empty model instance on no-match; treat as null.
        return ($related instanceof Subscription && $related->exists) ? $related : null;
    }

    /**
     * Type-guarded accessor: returns the Domain only when item_type === 'domain'.
     */
    public function getDomainAttribute(): ?Domain
    {
        if ($this->item_type !== 'domain') {
            return null;
        }

        if (! $this->relationLoaded('domainRelation')) {
            $this->load('domainRelation');
        }

        $related = $this->getRelation('domainRelation');

        return ($related instanceof Domain && $related->exists) ? $related : null;
    }
}
