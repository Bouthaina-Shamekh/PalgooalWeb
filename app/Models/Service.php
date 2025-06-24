<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
     protected $fillable = [
        'icon',
        'order',
    ];

     public function servicetranslations()
    {
        return $this->hasMany( ServiceTranslation::class);
    }
}
