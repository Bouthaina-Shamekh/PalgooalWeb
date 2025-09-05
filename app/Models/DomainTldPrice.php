<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTldPrice extends Model
{
    protected $fillable = [
        'domain_tld_id',
        'action',
        'years',
        'cost',
        'sale'
    ];

    public function tld()
    {
        return $this->belongsTo(DomainTld::class, 'domain_tld_id');
    }
}
