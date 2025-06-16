<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'mime_type',
        'size',
        'uploader_id',
        'alt',
        'title',
        'caption',
        'description',
    ];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
