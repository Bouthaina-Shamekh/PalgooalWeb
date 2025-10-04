<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    // Ensure the computed 'url' accessor is included when serialized to JSON
    protected $appends = ['url'];

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
        // Return a relative path to avoid APP_URL mismatches in development
        return '/storage/' . ltrim($this->file_path, '/');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
