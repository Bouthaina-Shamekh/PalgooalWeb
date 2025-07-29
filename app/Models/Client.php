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
        'can_login',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

}
