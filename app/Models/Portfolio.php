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
    ];

    public function translations()
    {
        return $this->hasMany(PortfolioTranslation::class);
    }
}
