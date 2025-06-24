<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    protected $fillable = ['service_id', 'locale', 'title', 'description'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

