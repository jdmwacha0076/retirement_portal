<?php

namespace App\Policies;

use App\Enums\ActivityBudgetStatus;
use App\Models\Activity;
use App\Models\ActivityBudget;
use App\Models\User;

/**
 * Backend authorization for ActivityBudget. `create` is checked against
 * the parent Activity (Gate::authorize('create', [ActivityBudget::class,
 * $activity])) since a budget doesn't exist yet to authorize against -
 * same nested-resource pattern Laravel's own docs recommend. Shape
 * mirrors ActivityPolicy/PaymentRequestPolicy.
 */
class ActivityBudgetPolicy
{
    /**
     * Admin sees everything. Otherwise: the activity's creator/
     * coordinator, plus - per PaymentRequestPolicy::view()'s same three-
     * tier shape - whoever currently holds this budget or has ever been
     * assigned/forwarded it. Without the assignment check here, someone
     * a budget was forwarded to (but who didn't create or coordinate the
     * activity) would get a 403 trying to open it for review.
     */
    public function view(User $user, ActivityBudget $budget): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($budget->activity->created_by === $user->id || $budget->activity->coordinator_id === $user->id) {
            return true;
        }

        if ($budget->current_assignee_id === $user->id) {
            return true;
        }

        return $budget->assignments()
            ->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id))
            ->exists();
    }

    /**
     * Anyone who could view/register the activity may start its budget,
     * as long as the activity isn't cancelled and doesn't already have
     * one in progress (the second check is also enforced by
     * ActivityBudgetService::startDraft() at write time - this is the
     * UI-facing half of the same rule).
     */
    public function create(User $user, Activity $activity): bool
    {
        if ($activity->status->isTerminal()) {
            return false;
        }

        if (! $user->isAdmin() && $activity->created_by !== $user->id && $activity->coordinator_id !== $user->id) {
            return false;
        }

        return ! $activity->currentBudget()->exists();
    }

    /**
     * Only the budget's creator, and only while ActivityBudget::canBeEditedBy()
     * says so (Draft/Rejected always, or Assigned while it's currently
     * pointed back at them - the merged replacement for the old dedicated
     * Returned status). Admin can always edit, matching ActivityPolicy's
     * own convention.
     */
    public function update(User $user, ActivityBudget $budget): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $budget->canBeEditedBy($user);
    }

    /**
     * Submitting is just "I'm done editing, send it on" - so it's legal
     * exactly when editing is (ActivityBudget::canBeEditedBy()): Draft/
     * Rejected always (Rejected is deliberately non-terminal here, unlike
     * PaymentRequestStatus's own terminal Rejected - see
     * ActivityBudgetStatus's class docblock), or Assigned while it's
     * currently pointed back at the creator themselves.
     */
    public function submit(User $user, ActivityBudget $budget): bool
    {
        return $budget->canBeEditedBy($user);
    }

    /**
     * Admin can always (re)assign. The creator can assign it onward right
     * after submitting (before anyone else has picked it up). The current
     * assignee can forward it further along the chain.
     */
    public function assign(User $user, ActivityBudget $budget): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($budget->current_assignee_id === $user->id) {
            return true;
        }

        return $budget->created_by === $user->id
            && $budget->status === ActivityBudgetStatus::Submitted
            && $budget->current_assignee_id === null;
    }

    public function review(User $user, ActivityBudget $budget): bool
    {
        return $user->isAdmin() || $budget->current_assignee_id === $user->id;
    }

    /**
     * Same as Payment Requests: no dedicated "Approver"/"Finance" role
     * exists yet, so approve/reject stay admin-only for now.
     */
    public function approve(User $user, ActivityBudget $budget): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, ActivityBudget $budget): bool
    {
        return $user->isAdmin();
    }

    /**
     * The print/export report is just a read-only rendering of the same
     * data the builder page already shows, so it's gated the same way -
     * mirrors PaymentRequestPolicy::print() delegating straight to view().
     */
    public function print(User $user, ActivityBudget $budget): bool
    {
        return $this->view($user, $budget);
    }

    /**
     * Unlike PaymentRequestPolicy::cancel()'s plain "not terminal" check,
     * this also respects the enum's own narrower rule that Cancelled is
     * only reachable from Draft/Submitted/Returned/Rejected (Assigned/
     * UnderReview are deliberately excluded) - so the Cancel button never
     * appears somewhere the workflow service would just reject it anyway.
     */
    public function cancel(User $user, ActivityBudget $budget): bool
    {
        if (! $budget->status->canTransitionTo(ActivityBudgetStatus::Cancelled)) {
            return false;
        }

        return $user->isAdmin() || $budget->created_by === $user->id;
    }
}
