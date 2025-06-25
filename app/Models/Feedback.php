<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['image', 'star', 'order'];
    protected $table = 'feedbacks';


    public function translations()
    {
        return $this->hasMany(FeedbackTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->where('locale', $locale)->first();
    }
}
