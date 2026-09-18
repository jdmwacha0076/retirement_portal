<?php

namespace App\Services;

use App\Enums\ActivityBudgetStatus;
use App\Models\ActivityBudget;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every state-changing ActivityBudget operation goes through here, never
 * a generic ->update() in a controller - mirrors
 * PaymentRequestWorkflowService almost line for line, per the approved
 * architecture. Each public method row-locks the budget inside a
 * transaction, checks the transition is legal
 * (ActivityBudgetStatus::canTransitionTo), applies the change, and writes
 * an activity_history row atomically.
 *
 * approve() (Phase 7) is the one method that reaches outside this budget
 * row: per the user's own framing of the lifecycle, an approved budget is
 * what makes the activity itself genuinely under way, so approve() also
 * activates the parent Activity (idempotently - a no-op once it's past
 * Draft). Recording the actual advance disbursement against a real
 * Payment Request is a separate later concern (Phase 8) - approving a
 * budget authorizes the advance, it doesn't itself move any money.
 * Notifications are Phase 16's - no ->notify() calls exist yet anywhere
 * in this class.
 *
 * reject() is deliberately NOT terminal here (unlike
 * PaymentRequestWorkflowService's reject()) - per the user's own
 * clarification, a rejected budget should still be revisable and
 * resubmittable, not a dead end. submit()'s own resubmission logic above
 * already handles this for free: it only checks whether submitted_at is
 * already set to decide "submitted" vs "resubmitted" wording, so a
 * Rejected -> Submitted resubmission needed no code change once
 * ActivityBudgetStatus allowed that transition.
 */
class ActivityBudgetWorkflowService
{
    public function __construct(private readonly ActivityService $activities)
    {
    }

    public function submit(ActivityBudget $budget, User $user): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $user) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, ActivityBudgetStatus::Submitted);

            $isResubmission = $locked->submitted_at !== null;

            $from = $locked->status;
            $locked->status = ActivityBudgetStatus::Submitted;
            $locked->submitted_at = now();
            $locked->save();

            $this->recordHistory($locked, $isResubmission ? 'resubmitted' : 'submitted', $user, $from, ActivityBudgetStatus::Submitted);

            return $locked->fresh();
        });
    }

    /**
     * Forward/assign to another staff member - OR, by assigning it back
     * to the budget's own creator, the "return for correction" of the
     * old two-button design: a single action either way, always ending
     * in the Assigned status, always carrying a $reason. Never overwrites
     * the previous assignment - assigned_from captures who had it before,
     * and a full row is inserted into activity_assignments every time
     * (history), while activity_budgets.current_assignee_id is the only
     * column that gets overwritten (a pointer to "who has it now").
     * ActivityBudget::canBeEditedBy() is what turns "assigned back to the
     * creator" into actual edit/resubmit rights for them.
     */
    public function assign(ActivityBudget $budget, User $assigner, User $assignTo, string $reason): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $assigner, $assignTo, $reason) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, ActivityBudgetStatus::Assigned, allowSameStatus: true);

            $previousAssigneeId = $locked->current_assignee_id;
            $from = $locked->status;

            $locked->current_assignee_id = $assignTo->id;
            $locked->status = ActivityBudgetStatus::Assigned;
            $locked->save();

            $locked->assignments()->create([
                'assigned_by' => $assigner->id,
                'assigned_to' => $assignTo->id,
                'assigned_from' => $previousAssigneeId,
                'comment' => $reason,
                'status_at_assignment' => $locked->status->value,
                'created_at' => now(),
            ]);

            $this->recordHistory($locked, 'assigned', $assigner, $from, ActivityBudgetStatus::Assigned, $reason, [
                'assigned_to' => $assignTo->id,
                'assigned_to_name' => $assignTo->name,
            ]);

            return $locked->fresh();
        });
    }

    public function review(ActivityBudget $budget, User $user, ?string $comment = null): ActivityBudget
    {
        return $this->simpleTransition($budget, $user, ActivityBudgetStatus::UnderReview, 'reviewed', $comment);
    }

    /**
     * Terminal, and the version-boundary case this whole phase exists
     * for: an approval on anything but version 1 must supersede whatever
     * version is currently marked is_current for this activity, in the
     * SAME transaction as this row's own approval - "only that new
     * version's own Approval" flips is_current, never mere creation of a
     * new Draft version (see the activity_budgets migration's own
     * docblock). Version 1 is a no-op here since is_current is already
     * true from creation; nothing else in this codebase can yet create a
     * version 2+ (that's the amendment flow, still unbuilt), so this
     * branch is exercised only once that exists - but is correct today
     * regardless.
     */
    public function approve(ActivityBudget $budget, User $user, ?string $comment = null): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $user, $comment) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, ActivityBudgetStatus::Approved);

            if ($locked->version > 1) {
                ActivityBudget::where('activity_id', $locked->activity_id)
                    ->where('id', '!=', $locked->id)
                    ->where('is_current', true)
                    ->lockForUpdate()
                    ->update(['is_current' => false]);
            }

            $from = $locked->status;
            $locked->status = ActivityBudgetStatus::Approved;
            $locked->approved_by = $user->id;
            $locked->approved_at = now();
            $locked->approval_comment = $comment;
            $locked->is_current = true;
            $locked->save();

            $this->recordHistory($locked, 'approved', $user, $from, ActivityBudgetStatus::Approved, $comment);

            $this->activities->activate($locked->activity, $user);

            return $locked->fresh();
        });
    }

    /**
     * NOT terminal, unlike PaymentRequestWorkflowService's reject() - a
     * rejected budget can be revised and resubmitted by its creator
     * exactly like being assigned it back for correction
     * (ActivityBudget::canBeEditedBy() / canTransitionTo() both cover
     * Rejected -> Submitted). No dedicated
     * rejected_by/rejected_at columns exist on activity_budgets (unlike
     * payment_requests) - who/when is captured by this action's own
     * activity_history row instead, which is sufficient since nothing
     * else needs to query "who rejected this" outside the audit trail.
     */
    public function reject(ActivityBudget $budget, User $user, string $reason): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $user, $reason) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, ActivityBudgetStatus::Rejected);

            $from = $locked->status;
            $locked->status = ActivityBudgetStatus::Rejected;
            $locked->rejection_reason = $reason;
            $locked->save();

            $this->recordHistory($locked, 'rejected', $user, $from, ActivityBudgetStatus::Rejected, $reason);

            return $locked->fresh();
        });
    }

    /**
     * Terminal, and only reachable from Draft/Submitted/Returned/Rejected
     * - the enum deliberately excludes Cancelled from Assigned/
     * UnderReview's allowedTransitions() (a Phase 1 decision), so
     * guardTransition() rejects those regardless of who's asking.
     */
    public function cancel(ActivityBudget $budget, User $user, ?string $reason = null): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $user, $reason) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, ActivityBudgetStatus::Cancelled);

            $from = $locked->status;
            $locked->status = ActivityBudgetStatus::Cancelled;
            $locked->cancelled_by = $user->id;
            $locked->cancelled_at = now();
            $locked->cancellation_reason = $reason;
            $locked->save();

            $this->recordHistory($locked, 'cancelled', $user, $from, ActivityBudgetStatus::Cancelled, $reason);

            return $locked->fresh();
        });
    }

    // ── Internals ──────────────────────────────────────────────────

    private function simpleTransition(
        ActivityBudget $budget,
        User $user,
        ActivityBudgetStatus $to,
        string $action,
        ?string $comment = null
    ): ActivityBudget {
        return DB::transaction(function () use ($budget, $user, $to, $action, $comment) {
            $locked = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, $to);

            $from = $locked->status;
            $locked->status = $to;
            $locked->save();

            $this->recordHistory($locked, $action, $user, $from, $to, $comment);

            return $locked->fresh();
        });
    }

    private function guardTransition(ActivityBudget $budget, ActivityBudgetStatus $to, bool $allowSameStatus = false): void
    {
        if ($allowSameStatus && $budget->status === $to) {
            return;
        }

        if (! $budget->status->canTransitionTo($to)) {
            throw new DomainException(sprintf(
                'Cannot move a %s budget to %s.',
                $budget->status->label(),
                $to->label()
            ));
        }
    }

    private function recordHistory(
        ActivityBudget $budget,
        string $action,
        User $performer,
        ?ActivityBudgetStatus $from,
        ?ActivityBudgetStatus $to,
        ?string $comment = null,
        ?array $metadata = null
    ): void {
        $budget->history()->create([
            'action' => $action,
            'performed_by' => $performer->id,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'comment' => $comment,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
