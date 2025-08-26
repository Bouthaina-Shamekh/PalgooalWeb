<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'order_id',
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

    // الفاتورة تحتوي على عدة بنود
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
