<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $table = 'portfolios';

    protected $fillable = [
        'default_image',
        'images',
        'delivery_date',
        'order',
        'implementation_period_days',
        'slug',
        'client',
    ];

    protected $casts = [
        'images' => 'array',
        'delivery_date' => 'date',
    ];

    public function translations()
    {
        return $this->hasMany(PortfolioTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $this->translations->firstWhere('locale', $locale);
    }
}

