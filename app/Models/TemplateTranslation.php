<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'locale',
        'name',
        'slug',
        'preview_url',
        'description',
    ];

    protected $hidden = [
    'template_id',
    ];

    /**
     * العلاقة مع القالب
     */
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
