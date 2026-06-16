<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentAttempt — audit record for every payment gateway interaction.
 *
 * ADR-007 Phase 2: Every settlement now has an optional audit trail.
 * In Phase 1, settlement was anonymous (no PaymentAttempt created).
 * Phase 2 makes the linkage optional and backward-compatible.
 * Phase 3 (Webhook Stub) will create PaymentAttempt records from webhook events.
 * Phase 4 will create PaymentAttempt records from createSession() calls.
 *
 * @property int                             $id
 * @property int|null                        $invoice_id
 * @property int|null                        $order_id
 * @property int|null                        $client_id
 * @property string                          $gateway
 * @property string                          $idempotency_key
 * @property string|null                     $gateway_session_id
 * @property string|null                     $gateway_transaction_id
 * @property int|null                        $gateway_amount_cents
 * @property string                          $currency
 * @property string                          $status
 * @property string|null                     $gateway_status_raw
 * @property array|null                      $gateway_response
 * @property \Illuminate\Support\Carbon|null $webhook_verified_at
 * @property \Illuminate\Support\Carbon|null $settled_at
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property int|null                        $refund_amount_cents
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class PaymentAttempt extends Model
{
    // ── Status constants ──────────────────────────────────────────────────

    /** Session created, client redirected to gateway hosted checkout. */
    public const STATUS_INITIATED = 'initiated';

    /** Webhook received, settlement in progress. */
    public const STATUS_PENDING = 'pending';

    /** markPaid() completed — invoice.status = paid, subscription activated. */
    public const STATUS_SUCCEEDED = 'succeeded';

    /** Gateway declined the payment, or webhook reported failure. */
    public const STATUS_FAILED = 'failed';

    /** Client cancelled on the gateway page before completing payment. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Full or partial refund confirmed by the gateway. */
    public const STATUS_REFUNDED = 'refunded';

    // ── Model configuration ───────────────────────────────────────────────

    protected $fillable = [
        'invoice_id',
        'order_id',
        'client_id',
        'gateway',
        'idempotency_key',
        'gateway_session_id',
        'gateway_transaction_id',
        'gateway_amount_cents',
        'currency',
        'status',
        'gateway_status_raw',
        'gateway_response',
        'webhook_verified_at',
        'settled_at',
        'refunded_at',
        'refund_amount_cents',
    ];

    protected $casts = [
        'gateway_response'      => 'array',
        'gateway_amount_cents'  => 'integer',
        'refund_amount_cents'   => 'integer',
        'webhook_verified_at'   => 'datetime',
        'settled_at'            => 'datetime',
        'refunded_at'           => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ── Status helpers ────────────────────────────────────────────────────

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeSucceeded($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
