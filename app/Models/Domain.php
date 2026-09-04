<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    protected $fillable = [
        'client_id',
        'template_id',
        'domain_name',
        'registrar',
        'provider_id',
        'registration_date',
        'renewal_date',
        'auto_renew',
        'status',
        'payment_method',
        'nameservers',
        'dns_last_note',
        'dns_last_synced_at',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'nameservers' => 'array',
        'dns_last_synced_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * TLD-3D — Hybrid Provider Identity. Source of Truth لهوية المزوّد عندما تكون موجودة
     * (provider_id ليس null). null يمثل نطاقاً خارجياً/غير مُدار (manual/quick-add)، وهذا
     * مسموح ومقصود في هذه المرحلة — راجع تدقيق TLD-3D.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class, 'provider_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class)->withDefault();
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'reference_id')
                    ->where('item_type', 'domain');
    }


    public static function checkAvailability($domain)
    {

        return true;
    }

}
