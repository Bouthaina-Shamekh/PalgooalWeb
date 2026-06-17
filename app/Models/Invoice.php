<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Coupon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'order_id',
        'payment_attempt_id',
        'coupon_id',          // ADR-008 Phase 1
        'number',
        'status',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'due_date',
        'paid_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    // الفاتورة مرتبطة بعميل
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // الفاتورة تحتوي على عدة بنود
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // ADR-007 Phase 2 — Payment attempt relationships

    /**
     * The winning PaymentAttempt that settled this invoice.
     * Set by InvoiceSettlementService::markPaid() when a PaymentAttempt is provided.
     * Null for invoices settled before Phase 2 or via admin bulk-mark-paid.
     */
    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    /**
     * All PaymentAttempts that reference this invoice (includes failed/cancelled attempts).
     * An invoice may have multiple attempts before one succeeds.
     */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    // ADR-008 Phase 1 — Coupon relationship
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    // Scope: فواتير مدفوعة
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Scope: فواتير غير مدفوعة
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'draft']);
    }
}
