<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionTranslation extends Model
{
    protected $fillable = ['section_id', 'locale', 'title', 'content'];

    protected $casts = [
        'content' => 'array', // لفك JSON تلقائياً
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        // جلب الترجمة الحالية أو أقرب ترجمة متوفرة
        return $this->translations->where('locale', $locale)->first()
        ?? $this->translations->first(); // fallback
    }
}
