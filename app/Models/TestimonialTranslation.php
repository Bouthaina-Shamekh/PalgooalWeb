<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestimonialTranslation extends Model
{
    protected $fillable = ['feedback_id', 'locale', 'feedback', 'name', 'major'];
    protected $table = 'feedback_translations';
}
