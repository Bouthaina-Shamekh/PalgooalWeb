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

    public function translations()
    {
        return $this->hasMany(PortfolioTranslation::class);
    }
}
