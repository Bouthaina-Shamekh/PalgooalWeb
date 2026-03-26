<?php

namespace App\Models\Tenancy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRuntimeMetric extends Model
{
    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'source',
        'page_model',
        'page_id',
        'path',
        'resolved_slug',
        'locale',
        'bucket_key',
        'hits',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'hits' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
