<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Header extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'location_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HeaderItem::class)->orderBy('order');
    }
}
