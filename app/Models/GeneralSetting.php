<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';
    protected $fillable = [
        'site_title',
        'site_discretion',
        'logo',
        'dark_logo',
        'sticky_logo',
        'dark_sticky_logo',
        'admin_logo',
        'admin_dark_logo',
        'favicon',
        'default_language',
    ];

    public function language()
    {
        return $this->belongsTo(Language::class, 'default_language', 'id');
    }
}
