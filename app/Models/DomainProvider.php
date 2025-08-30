<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainProvider extends Model
{
    protected $fillable = [
        'name',
        'type', // enom, namecheap, ...
        'endpoint',
        'username',
        'password',
        'api_token',
        'is_active',
        'mode', // live أو test
    ];

    // تشفير كلمة المرور
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? encrypt($value) : null;
    }
    public function getPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
    public function setApiTokenAttribute($value)
    {
        $this->attributes['api_token'] = $value ? encrypt($value) : null;
    }
    public function getApiTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
}
