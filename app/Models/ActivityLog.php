<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'meta'
    ];

    //Relationships
    public function actor()
    {
        return $this->morphTo();
    }

    // هذا المهم - تحويل تلقائي للـ JSON
    protected $casts = [
        'meta' => 'array', // ← Laravel هيعمل json_encode/decode تلقائياً
        'created_at' => 'datetime',
    ];

    // Mutator للتأكد من التحويل
    public function setMetaAttribute($value)
    {
        $this->attributes['meta'] = is_array($value) ? json_encode($value) : $value;
    }

    // Accessor للتأكد من التحويل العكسي
    public function getMetaAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }
}
