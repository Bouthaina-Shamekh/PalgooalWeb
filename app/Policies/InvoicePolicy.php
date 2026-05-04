<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends ModelPolicy
{
    /**
     * Bulk operations (status changes, duplicate, reminder, delete).
     * Resolved to the permission slug  "invoices.bulk" in the roles table.
     */
    public function bulk(User $user): bool
    {
        return $user->roles->where('role_name', 'invoices.bulk')->isNotEmpty();
    }
}
