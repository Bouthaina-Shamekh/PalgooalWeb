<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    protected $fillable = [
        'client_id',
        'domain_name',
        'registrar',
        'registration_date',
        'renewal_date',
        'status',
        'payment_method',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
