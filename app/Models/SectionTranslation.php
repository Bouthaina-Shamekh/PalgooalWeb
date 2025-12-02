<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionTranslation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * Each row represents a localized version of a section:
     * - locale  : the language code (ar, en, ...)
     * - title   : optional section title (can be duplicated inside JSON if needed)
     * - content : JSON payload that stores the actual fields for the section
     */
    protected $fillable = [
        'section_id',
        'locale',
        'title',
        'content',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'content' => 'array', // Automatically decode/encode JSON to array
    ];

    /**
     * Relationship: parent section.
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
