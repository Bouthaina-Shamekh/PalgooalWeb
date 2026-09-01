<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRegistrationClaim extends Model
{
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'domain_name_normalized',
        'order_item_id',
        'status',
        'claimed_at',
        'released_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
