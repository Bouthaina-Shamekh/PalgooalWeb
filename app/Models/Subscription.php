<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subscription extends Model
{
    protected $fillable = [
        'client_id',
        'plan_id',
        'status',
        'last_sync_message',
        'price',
        'username',
        'server_id',
        'server_package',
        'next_due_date',
        'starts_at',
        'ends_at',
        'domain_option',
        'domain_name'
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Server::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'reference_id')
            ->where('item_type', 'subscription');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class)
            ->withTimestamps();
    }
}
