<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'billing_cycle',
        'features',
        'is_active',
        'created_by',
        'updated_by'
    ];
    protected $casts = [
        'features' => 'array',
        'is_active' => 'bool'
    ];
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
