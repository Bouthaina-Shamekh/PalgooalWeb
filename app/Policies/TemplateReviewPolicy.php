<?php

namespace App\Policies;

use App\Models\TemplateReview;
use App\Models\User;

class TemplateReviewPolicy extends ModelPolicy
{
    /**
     * Approve / reject are update-level operations — delegate to the
     * same role ability that guards standard update access.
     */
    public function approve(User $user, TemplateReview $review): bool
    {
        return $user->roles->where('role_name', 'templatereviews.update')->isNotEmpty();
    }

    public function reject(User $user, TemplateReview $review): bool
    {
        return $user->roles->where('role_name', 'templatereviews.update')->isNotEmpty();
    }

    /**
     * Bulk actions can be either update (approve/reject) or delete.
     * Require at least one of the two abilities.
     */
    public function bulk(User $user): bool
    {
        return $user->roles
            ->whereIn('role_name', ['templatereviews.update', 'templatereviews.delete'])
            ->isNotEmpty();
    }
}
