<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackTranslation extends Model
{
    protected $fillable = ['feedback_id', 'locale', 'feedback', 'name', 'major'];
}
