<?php

namespace App\Policies\Tenancy;

use App\Models\Tenancy\Subscription;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Str;

class SubscriptionPolicy extends ModelPolicy
{
    /**
     * Admin users are authorized via role slugs (delegated to ModelPolicy.__call()).
     * Client users are authorized only for their own subscription.
     * super_admin users bypass all checks.
     */
    public function view($actor, Subscription $subscription): bool
    {
        // Admin User: delegate to role-based check (subscriptions.view)
        if ($actor instanceof User) {
            return $this->adminCan($actor, 'view');
        }

        // Client: only their own subscription
        if ($actor instanceof Client) {
            return (int) $subscription->client_id === (int) $actor->id;
        }

        return false;
    }

    public function update($actor, Subscription $subscription): bool
    {
        if ($actor instanceof User) {
            return $this->adminCan($actor, 'update');
        }

        if ($actor instanceof Client) {
            return (int) $subscription->client_id === (int) $actor->id;
        }

        return false;
    }

    public function manage($actor, Subscription $subscription): bool
    {
        if ($actor instanceof User) {
            return $this->adminCan($actor, 'manage');
        }

        if ($actor instanceof Client) {
            return (int) $subscription->client_id === (int) $actor->id;
        }

        return false;
    }

    /**
     * Bulk actions — admin-only, never exposed to Client actors.
     */
    public function bulk(User $user): bool
    {
        return $this->adminCan($user, 'bulk');
    }

    /**
     * Check whether an admin User has the given ability via role slug.
     * super_admin bypasses all role checks.
     * Ability slug format: "subscriptions.{kebab-action}" — matches ModelPolicy.__call().
     */
    protected function adminCan(User $user, string $action): bool
    {
        if ($user->super_admin ?? false) {
            return true;
        }

        $ability = 'subscriptions.' . Str::kebab($action);

        return $user->roles->where('role_name', $ability)->isNotEmpty();
    }
}
