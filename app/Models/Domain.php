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
        // $response = Http::withHeaders([
        //     'x-rapidapi-host' => 'domainr.p.rapidapi.com',
        //     'x-rapidapi-key' => env('RAPIDAPI_KEY'), // 👈 الأفضل تضعها في .env
        // ])->get('https://domainr.p.rapidapi.com/v2/status', [
        //     'domain' => $domain,
        // ]);

        // if ($response->successful()) {
        //     $result = $response->json();
        //     $status = $result['status'][0]['status'] ?? null;
        //     return $status !== 'active';
        // }

        // return false;

        return true;
    }

}
