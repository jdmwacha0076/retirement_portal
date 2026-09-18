<?php

namespace App\Policies;

use App\Models\ActivityType;
use App\Models\User;

/**
 * Admin-only master data (approved architecture decision - "Manage
 * master data" is an Admin ability, not a Staff one). The
 * budget-settings.* route group is already gated 'role:admin' at the
 * route level; this Policy is the real, centralized authorization
 * source that controllers actually check via $this->authorize(), so
 * nothing here duplicates a raw $user->role === 'admin' check anywhere
 * else.
 */
class ActivityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ActivityType $activityType): bool
    {
        return $user->isAdmin();
    }

    public function toggleActive(User $user, ActivityType $activityType): bool
    {
        return $user->isAdmin();
    }
}
