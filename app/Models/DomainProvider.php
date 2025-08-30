<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainProvider extends Model
{
    protected $table = 'domain_providers';

    protected $fillable = [
        'name',
        'type',
        'endpoint',
        'username',
        'password',
        'api_token',
        'is_active',
        'mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password'  => 'encrypted',   // احذف لو إصدارك لا يدعم
        'api_token' => 'encrypted',   // احذف لو إصدارك لا يدعم
    ];

    protected $hidden = ['password', 'api_token'];

    // قيم افتراضيّة
    protected $attributes = [
        'is_active' => true,
        'mode'      => 'test',
    ];

    // Constants
    public const MODES = ['live', 'test'];
    public const TYPES = ['enom', 'namecheap', 'cloudflare'];

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeOfType($q, $type)
    {
        return $q->where('type', strtolower($type));
    }
    public function scopeMode($q, $mode)
    {
        return $q->where('mode', strtolower($mode));
    }

    // Normalizers
    public function setEndpointAttribute($v)
    {
        $this->attributes['endpoint'] = $v ? trim($v) : null;
    }
    public function setTypeAttribute($v)
    {
        $this->attributes['type']     = $v ? strtolower(trim($v)) : null;
    }
    public function setModeAttribute($v)
    {
        $this->attributes['mode']     = $v ? strtolower(trim($v)) : null;
    }
}
