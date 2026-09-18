<?php

namespace App\Policies;

use App\Models\BudgetCategory;
use App\Models\User;

/**
 * Admin-only master data - see ActivityTypePolicy's docblock for the
 * reasoning (identical here).
 */
class BudgetCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BudgetCategory $budgetCategory): bool
    {
        return $user->isAdmin();
    }

    public function toggleActive(User $user, BudgetCategory $budgetCategory): bool
    {
        return $user->isAdmin();
    }
}
