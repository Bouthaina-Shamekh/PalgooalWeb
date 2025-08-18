<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'expires_at',
    ];

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class)
                    ->withTimestamps();
    }
}
