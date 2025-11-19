<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPage extends Model
{
    protected $fillable = [
        'subscription_id',
        'slug',
        'is_active',
        'is_home',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_home' => 'bool',
        'published_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SubscriptionPageTranslation::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SubscriptionSection::class)->orderBy('sort_order');
    }
}
