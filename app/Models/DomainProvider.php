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
        'api_key',
        'client_ip',
        'is_active',
        'mode',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'api_key',
    ];

    // ✅ تعريف واحد فقط للـ casts
    protected $casts = [
        'is_active' => 'boolean',

        // يتطلب Laravel 9.2+ (يخزن مشفّر ويعيد مفكوك تلقائيًا في التطبيق)
        'password'  => 'encrypted',
        'api_token' => 'encrypted',
        'api_key'   => 'encrypted',
    ];

    // قيم افتراضية
    protected $attributes = [
        'is_active' => true,
        'mode'      => 'test',
    ];

    // ثابتات
    public const MODES = ['live', 'test'];
    public const TYPES = ['enom', 'namecheap', 'cloudflare'];

    /* =========================
     *         Scopes
     * ========================= */
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

    /* =========================
     *     Normalizers (Mutators)
     * ========================= */
    public function setEndpointAttribute($v)
    {
        $this->attributes['endpoint'] = $v ? trim($v) : null;
    }

    public function setTypeAttribute($v)
    {
        $this->attributes['type'] = $v ? strtolower(trim($v)) : null;
    }

    public function setModeAttribute($v)
    {
        $this->attributes['mode'] = $v ? strtolower(trim($v)) : null;
    }

    public function setClientIpAttribute($v)
    {
        $v = $v ? trim((string) $v) : null;
        $this->attributes['client_ip'] = $v ?: null;
    }

    /* =========================
     *   Helpers (اختياري مفيد)
     * ========================= */

    // Endpoint محسوب إن أحببت تستخدمه في أي مكان (لا يستبدل الحقل)
    public function getResolvedEndpointAttribute(): ?string
    {
        if (!empty($this->endpoint)) {
            return rtrim($this->endpoint, '/');
        }
        if ($this->mode === 'test') return 'https://api.sandbox.namecheap.com/xml.response';
        if ($this->mode === 'live') return 'https://api.namecheap.com/xml.response';
        return null;
    }

    public function isTest(): bool
    {
        return $this->mode === 'test' || str_contains((string) $this->endpoint, 'sandbox');
    }

    public function isLive(): bool
    {
        return $this->mode === 'live' || str_contains((string) $this->endpoint, 'api.namecheap.com');
    }

    public function domainTlds()
    {
        return $this->hasMany(DomainTld::class, 'provider_id');
    }
}
