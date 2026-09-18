<?php

namespace App\Policies;

use App\Enums\ActivityBudgetStatus;
use App\Models\ActivityBudget;
use App\Models\ActivityRetirement;
use App\Models\User;

/**
 * Backend authorization for ActivityRetirement. `create` is checked
 * against the parent ActivityBudget (Gate::authorize('create',
 * [ActivityRetirement::class, $budget])) since a retirement doesn't exist
 * yet to authorize against - same nested-resource pattern
 * ActivityBudgetPolicy::create() already uses against Activity. Shape
 * mirrors ActivityBudgetPolicy; submit/assign/review/approve/reject/
 * cancel methods will be added here alongside that later workflow phase.
 */
class ActivityRetirementPolicy
{
    /**
     * Admin sees everything. Otherwise: the activity's creator/
     * coordinator, plus - per PaymentRequestPolicy::view()'s same three-
     * tier shape - whoever currently holds this retirement or has ever
     * been assigned/forwarded it. Without the assignment check here,
     * someone a retirement was forwarded to (but who didn't create or
     * coordinate the activity) would get a 403 trying to open it.
     */
    public function view(User $user, ActivityRetirement $retirement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $activity = $retirement->activityBudget->activity;

        if ($activity->created_by === $user->id || $activity->coordinator_id === $user->id) {
            return true;
        }

        if ($retirement->current_assignee_id === $user->id) {
            return true;
        }

        return $retirement->assignments()
            ->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id))
            ->exists();
    }

    /**
     * Only an Approved budget can be retired, as long as it doesn't
     * already have a non-cancelled retirement in progress (also enforced
     * by ActivityRetirementService::start() at write time - this is the
     * UI-facing half of the same rule, mirroring ActivityBudgetPolicy::
     * create()'s shape against Activity).
     */
    public function create(User $user, ActivityBudget $budget): bool
    {
        if ($budget->status !== ActivityBudgetStatus::Approved) {
            return false;
        }

        $activity = $budget->activity;

        if (! $user->isAdmin() && $activity->created_by !== $user->id && $activity->coordinator_id !== $user->id) {
            return false;
        }

        return ! $budget->currentRetirement;
    }

    /**
     * Only the retirement's creator, and only while it's Draft/Returned
     * (ActivityRetirementStatus::isEditableByCreator()). Admin can always
     * edit, matching ActivityBudgetPolicy's own convention.
     */
    public function update(User $user, ActivityRetirement $retirement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $retirement->created_by === $user->id && $retirement->status->isEditableByCreator();
    }

    /**
     * Same shape as PaymentRequestPolicy::uploadDocument() - allowed
     * throughout the active lifecycle, not just the editable-by-creator
     * states, since a reviewer may ask for a missing receipt while the
     * retirement is Assigned/UnderReview, not only while it's Draft.
     */
    public function uploadDocument(User $user, ActivityRetirement $retirement): bool
    {
        return $this->view($user, $retirement) && ! $retirement->status->isTerminal();
    }

    /**
     * The print/export report is just a read-only rendering of the same
     * data the builder page already shows, so it's gated the same way -
     * mirrors PaymentRequestPolicy::print() delegating straight to view().
     */
    public function print(User $user, ActivityRetirement $retirement): bool
    {
        return $this->view($user, $retirement);
    }
}
