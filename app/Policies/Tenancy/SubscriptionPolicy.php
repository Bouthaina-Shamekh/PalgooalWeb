<?php

namespace App\Policies\Tenancy;

use App\Models\Tenancy\Subscription;
use App\Models\User;
use App\Models\Client;

class SubscriptionPolicy extends ModelPolicy
{
    /**
     * Allow admins (User) or the owning client to view/manage the subscription.
     */
    public function view($actor, Subscription $subscription): bool
    {
        return $this->isOwner($actor, $subscription);
    }

    public function update($actor, Subscription $subscription): bool
    {
        return $this->isOwner($actor, $subscription);
    }

    public function manage($actor, Subscription $subscription): bool
    {
        return $this->isOwner($actor, $subscription);
    }

    protected function isOwner($actor, Subscription $subscription): bool
    {
        if ($actor instanceof User && ($actor->super_admin ?? false)) {
            return true;
        }

        if ($actor instanceof Client) {
            return (int) $subscription->client_id === (int) $actor->id;
        }

        return false;
    }
}
