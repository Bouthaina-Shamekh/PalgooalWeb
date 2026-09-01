<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    // ADR — Provisioning Idempotency Phase 1 (Register Domain فقط). راجع
    // RegistrarProvisioningService::provisionOrderDomain() ومigration
    // 2026_07_25_000001_add_provisioning_status_to_order_items_table.
    public const PROVISIONING_NOT_STARTED = 'not_started';
    public const PROVISIONING_IN_PROGRESS = 'in_progress';
    public const PROVISIONING_COMPLETED   = 'completed';
    public const PROVISIONING_FAILED      = 'failed';

    protected $fillable = [
        'order_id',
        'domain',
        'item_option',   // بدل "option"
        'price_cents',
        'meta',
        'provisioning_status',
        'provisioning_started_at',
        'provisioning_completed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'price_cents' => 'integer',
        'provisioning_status' => 'string',
        'provisioning_started_at' => 'datetime',
        'provisioning_completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
