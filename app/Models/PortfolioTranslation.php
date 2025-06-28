<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioTranslation extends Model
{
    protected $table = 'portfolio_translations';

    protected $fillable = [
        'portfolio_id',
        'locale',
        'title',
        'type',
        'materials',
        'link',
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }
}
