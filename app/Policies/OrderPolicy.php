<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends ModelPolicy
{
    /**
     * Bulk operations (status changes, delete).
     * Resolved to the permission slug "orders.bulk" in the roles table.
     */
    public function bulk(User $user): bool
    {
        return $user->roles->where('role_name', 'orders.bulk')->isNotEmpty();
    }
}

