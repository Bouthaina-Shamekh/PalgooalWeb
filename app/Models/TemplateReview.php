<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateReview extends Model
{
    protected $fillable = [
        'template_id','user_id','client_id',
        'author_name','author_email','rating','comment','approved',
    ];

    // سكوب الاعتماد
    public function scopeApproved($q)
    {
        return $q->where('approved', true);
    }

    // علاقات
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withDefault();
    }
    

}
