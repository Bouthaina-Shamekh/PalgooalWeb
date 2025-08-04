<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;

class Client extends User
{
    use Notifiable;
    protected $guard = 'client';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'company_name',
        'phone',
        'zip_code',
        'can_login',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
