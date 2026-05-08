<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Header extends Model
{
    use SoftDeletes;
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

    protected static function booted(): void
    {
        static::deleting(function (Header $header): void {
            // Cascade soft-delete to items so they are restored when the menu is restored
            $header->items()->each(fn (HeaderItem $item) => $item->delete());
        });

        static::restoring(function (Header $header): void {
            $header->items()->withTrashed()->each(fn (HeaderItem $item) => $item->restore());
        });
    }
}
