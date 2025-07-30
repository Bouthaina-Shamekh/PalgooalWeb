<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTemplateTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_template_id',
        'locale',
        'name',
        'slug',
        'description',
    ];

    /**
     * العلاقة مع الفئة
     */
    public function categoryTemplate()
    {
        return $this->belongsTo(CategoryTemplate::class);
    }
}
