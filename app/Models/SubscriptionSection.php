<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionSection extends Model
{
    protected $fillable = [
        'subscription_page_id',
        'key',
        'type',
        'variant',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'int',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPage::class, 'subscription_page_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SubscriptionSectionTranslation::class, 'subscription_section_id');
    }
}
