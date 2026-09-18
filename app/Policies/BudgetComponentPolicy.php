<?php

namespace App\Policies;

use App\Models\BudgetComponent;
use App\Models\User;

/**
 * Admin-only master data - see ActivityTypePolicy's docblock for the
 * reasoning (identical here).
 */
class BudgetComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BudgetComponent $budgetComponent): bool
    {
        return $user->isAdmin();
    }

    public function toggleActive(User $user, BudgetComponent $budgetComponent): bool
    {
        return $user->isAdmin();
    }
}
