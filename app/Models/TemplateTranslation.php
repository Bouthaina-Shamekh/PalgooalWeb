<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateTranslation extends Model
{
    use HasFactory;

    // إذا كنت تستخدم جدولًا باسم مخصص، أضف السطر التالي:
    // protected $table = 'template_translations';

    protected $fillable = [
        'template_id',
        'locale',
        'name',
        'slug',
        'preview_url',
        'description',
        'details',
    ];

    protected $hidden = [
        'template_id',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    // إذا لم يكن هناك created_at و updated_at في الجدول
    // public $timestamps = false;

    /**
     * العلاقة مع القالب
     */
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Accessors مساعدة للحصول على المميزات والمواصفات من details
     */
    public function getFeaturesAttribute()
    {
        return $this->details['features'] ?? [];
    }

    public function getSpecificationsAttribute()
    {
        return $this->details['specifications'] ?? [];
    }
}
