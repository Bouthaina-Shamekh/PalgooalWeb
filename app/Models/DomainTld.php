<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = [
        'provider_id',
        'provider',
        'tld',
        'currency',
        'enabled',
        'supports_premium',
        'synced_at',
        'in_catalog'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'supports_premium' => 'boolean',
        'in_catalog' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(DomainProvider::class, 'provider_id');
    }

    public function prices()
    {
        return $this->hasMany(DomainTldPrice::class, 'domain_tld_id');
    }

    public function price(string $action = 'register', int $years = 1): ?DomainTldPrice
    {
        return $this->prices()->where(compact('action', 'years'))->first();
    }
}
