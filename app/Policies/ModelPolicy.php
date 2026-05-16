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
            'testimonial' => 'testimonials',
            'media' => 'medias',
        ];

        $class_name = Str::lower($class_name);
        $class_name = $customMap[$class_name] ?? Str::plural($class_name);

        // Normalize ability name: map Laravel's conventional method names to the
        // keys stored in role_user.role_name (which the UI generates from abilities.php).
        $nameAliases = [
            'viewAny' => 'view',
            'update'  => 'edit',  // abilities.php uses 'edit'; controllers use 'update'
        ];
        $name = $nameAliases[$name] ?? $name;

        $ability = $class_name . '.' . Str::kebab($name);
        $user = $arguments[0];
        if ($user instanceof User) {
            return ($user->roles->where('role_name',$ability)->first() == null) ? false : true;
        }
    }
}
