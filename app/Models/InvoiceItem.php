<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_type',
        'reference_id',
        'description',
        'qty',
        'unit_price_cents',
        'total_cents',
    ];

    // البند مرتبط بفاتورة
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // البند ممكن يكون مرتبط باشتراك
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'reference_id');
    }

    // أو مرتبط بدومين
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'reference_id');
    }
}
