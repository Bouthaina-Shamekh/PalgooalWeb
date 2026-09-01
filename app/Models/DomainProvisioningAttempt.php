<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainProvisioningAttempt extends Model
{
    public const OPERATION_REGISTER = 'register';

    public const STATUS_INITIATED = 'initiated';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CONFIRMED_FAILED = 'confirmed_failed';
    public const STATUS_INDETERMINATE = 'indeterminate';

    protected $fillable = [
        'order_item_id',
        'domain_id',
        'provider_id',
        'attempt_uuid',
        'operation',
        'provider_type',
        'provider_mode',
        'status',
        'provider_reference',
        'provider_domain_id',
        'started_at',
        'finished_at',
        'response_payload',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'response_payload' => 'array',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class, 'provider_id');
    }
}
