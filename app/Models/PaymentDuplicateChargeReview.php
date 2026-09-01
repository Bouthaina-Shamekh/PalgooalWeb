<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDuplicateChargeReview extends Model
{
    public const STATUS_NEEDS_FOLLOW_UP = 'needs_follow_up';

    public const STATUS_RESOLVED = 'resolved';

    public const RESOLUTION_CONFIRMED_DUPLICATE = 'confirmed_duplicate';

    public const RESOLUTION_NOT_DUPLICATE = 'not_duplicate';

    public const STATUSES = [
        self::STATUS_NEEDS_FOLLOW_UP,
        self::STATUS_RESOLVED,
    ];

    public const RESOLUTIONS = [
        self::RESOLUTION_CONFIRMED_DUPLICATE,
        self::RESOLUTION_NOT_DUPLICATE,
    ];

    protected $fillable = [
        'payment_attempt_id',
        'review_status',
        'resolution',
        'needs_follow_up',
        'reviewed_by',
        'reviewer_name',
        'reviewed_at',
        'note',
        'detection_classification',
        'verification_result',
        'verification_checked_at',
        'evidence_snapshot',
    ];

    protected $casts = [
        'needs_follow_up' => 'boolean',
        'reviewed_at' => 'datetime',
        'verification_checked_at' => 'datetime',
        'evidence_snapshot' => 'array',
    ];

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
