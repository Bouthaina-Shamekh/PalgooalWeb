<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subscription extends Model
{
    use HasFactory;

    public const PROVISIONING_PENDING = 'pending';
    public const PROVISIONING_IN_PROGRESS = 'provisioning';
    public const PROVISIONING_ACTIVE = 'active';
    public const PROVISIONING_FAILED = 'failed';

    protected $fillable = [
        'client_id',
        'plan_id',
        'template_id',
        'status',
        'provisioning_status',
        'provisioned_at',
        'last_sync_message',
        'price',
        'billing_cycle',
        'engine',
        'username',
        'cpanel_username',
        'cpanel_password',
        'cpanel_url',
        'server_id',
        'server_package',
        'next_due_date',
        'last_synced_at',
        'starts_at',
        'ends_at',
        'domain_option',
        'domain_name',
        'subdomain',
        'domain_id',
        'settings',
    ];

    protected $casts = [
        'price' => 'float',
        'next_due_date' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'provisioned_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'settings' => 'array',
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

    public function pages(): HasMany
    {
        return $this->hasMany(SubscriptionPage::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
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
