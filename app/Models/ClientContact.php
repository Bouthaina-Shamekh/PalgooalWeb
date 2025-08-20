<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'role', 'can_login', 'password_hash'
    ];

    protected $hidden = [
        'password_hash', 'remember_token'
    ];

    protected $casts = [
        'can_login' => 'boolean',
    ];

    //Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
