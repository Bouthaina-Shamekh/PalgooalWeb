<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        'name',
        'type',
        'ip',
        'hostname',
        'username',
        'password',
        'api_token',
        'is_active',
    ];

    // Mutator: تشفير كلمة المرور تلقائياً عند الحفظ
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? encrypt($value) : null;
    }

    // Accessor: فك التشفير عند القراءة (للاستخدام الداخلي فقط)
    public function getPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    // Mutator: تشفير API Token تلقائياً عند الحفظ
    public function setApiTokenAttribute($value)
    {
        $this->attributes['api_token'] = $value ? encrypt($value) : null;
    }

    // Accessor: فك التشفير عند القراءة (للاستخدام الداخلي فقط)
    public function getApiTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
}
