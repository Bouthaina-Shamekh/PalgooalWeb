<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Str;
class ModelPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    public function __call($name, $arguments){
        $class_name = str_replace('Policy', '', class_basename($this));
        $customMap = [
            'feedback' => 'feedbacks',
            'media' => 'medias',
        ];

        $class_name = Str::lower($class_name);
        $class_name = $customMap[$class_name] ?? Str::plural($class_name);

        if ($name == 'viewAny') {
            $name = 'view';
        }

        $ability = $class_name . '.' . Str::kebab($name);
        $user = $arguments[0];
        if ($user instanceof User) {
            return ($user->roles->where('role_name',$ability)->first() == null) ? false : true;
        }
    }
}
