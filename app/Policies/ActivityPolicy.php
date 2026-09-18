<?php

namespace App\Policies;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\ActivityBudget;
use App\Models\ActivityRetirement;
use App\Models\User;

/**
 * Backend authorization for Activity - checked from the controller via
 * $this->authorize()/Form Request authorize(), never relied on as a
 * UI-only (hide-the-button) guard. Shape mirrors PaymentRequestPolicy.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated admin/staff - route middleware already gates this
    }

    /**
     * Admin sees everything. Staff sees activities they registered,
     * coordinate, or are (or were ever) assigned on via the current
     * budget/retirement - same three-tier "created / holding / ever
     * touched" shape as PaymentRequestPolicy::view(), generalised across
     * the workflow objects that live under an Activity (it has no
     * assignment concept of its own). This is also what actually lets an
     * assigned reviewer open the activity to reach the budget/retirement
     * they were forwarded, not just the "Mine" list filter.
     */
    public function view(User $user, Activity $activity): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($activity->created_by === $user->id || $activity->coordinator_id === $user->id) {
            return true;
        }

        $budget = $activity->currentBudget;

        if (! $budget) {
            return false;
        }

        if ($this->isRelevantAssignee($budget, $user)) {
            return true;
        }

        $retirement = $budget->currentRetirement;

        return $retirement !== null && $this->isRelevantAssignee($retirement, $user);
    }

    /**
     * Shared by view() above for both ActivityBudget and ActivityRetirement
     * - both expose current_assignee_id plus the polymorphic assignments()
     * history relation, so the same two-part check applies to either.
     */
    private function isRelevantAssignee(ActivityBudget|ActivityRetirement $workflowObject, User $user): bool
    {
        if ($workflowObject->current_assignee_id === $user->id) {
            return true;
        }

        return $workflowObject->assignments()
            ->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return true; // both roles may register an activity
    }

    /**
     * Only the creator, and only while still Draft - once a budget
     * exists and moves the activity to Active, details are no longer
     * freely editable this way. Admin can always edit.
     */
    public function update(User $user, Activity $activity): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $activity->created_by === $user->id && $activity->status === ActivityStatus::Draft;
    }

    public function cancel(User $user, Activity $activity): bool
    {
        if ($activity->status->isTerminal()) {
            return false;
        }

        return $user->isAdmin() || $activity->created_by === $user->id;
    }
}
