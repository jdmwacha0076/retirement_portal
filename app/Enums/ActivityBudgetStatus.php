<?php

namespace App\Enums;

/**
 * Workflow states for an ActivityBudget (one specific version of an
 * Activity's budget). Same shape as PaymentRequestStatus by design, with
 * two deliberate divergences: REJECTED is NOT terminal here (rejection is
 * corrective, not final - the creator can revise and resubmit exactly
 * like after being assigned it back), and there is no dedicated RETURNED
 * step in the live workflow - "return for correction" and "forward to
 * someone else" were merged into a single Assign action (per the user's
 * own request): assigning the budget back to its own creator IS a
 * "return for correction" now, it just stays in the ASSIGNED status with
 * current_assignee_id pointing at the creator, carrying the reason as
 * the assignment's own comment (see
 * ActivityBudgetWorkflowService::assign() and
 * ActivityBudget::canBeEditedBy()). The RETURNED case/value is kept
 * defined only so any pre-existing row still stored with that status
 * doesn't break - nothing transitions INTO it anymore.
 *
 *   DRAFT -> SUBMITTED -> ASSIGNED/UNDER_REVIEW -> (assigned back to creator) -> (resubmit) SUBMITTED
 *                                                -> APPROVED (locks this version; see is_current)
 *                                                -> REJECTED -> (revise + resubmit) SUBMITTED
 *   any non-terminal state -> CANCELLED (terminal)
 */
enum ActivityBudgetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Assigned = 'assigned';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Assigned => 'Assigned',
            self::UnderReview => 'Under Review',
            self::Returned => 'Returned',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'status-draft',
            self::Submitted => 'status-info',
            self::Assigned => 'status-secondary',
            self::UnderReview => 'status-warning',
            self::Returned => 'status-warning',
            self::Approved => 'status-success',
            self::Rejected => 'status-danger',
            self::Cancelled => 'status-archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'bi-file-earmark-text',
            self::Submitted => 'bi-send-check-fill',
            self::Assigned => 'bi-person-arms-up',
            self::UnderReview => 'bi-search',
            self::Returned => 'bi-reply-fill',
            self::Approved => 'bi-check-circle-fill',
            self::Rejected => 'bi-x-circle-fill',
            self::Cancelled => 'bi-slash-circle-fill',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::Assigned, self::UnderReview, self::Approved, self::Rejected, self::Cancelled],
            // Assigned -> Submitted is the creator resubmitting after
            // being assigned the budget back to themselves (see class
            // docblock) - the merged replacement for the old
            // Returned -> Submitted transition.
            self::Assigned => [self::Assigned, self::UnderReview, self::Approved, self::Submitted, self::Rejected],
            self::UnderReview => [self::Assigned, self::Approved, self::Rejected],
            // Legacy only - nothing transitions into Returned anymore,
            // this just lets any pre-existing Returned row still be
            // submitted/cancelled normally.
            self::Returned => [self::Submitted, self::Cancelled],
            self::Approved => [],
            self::Rejected => [self::Submitted, self::Cancelled],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Approved/Cancelled only: no further workflow action on THIS version.
     * Approved is not editable (immutable per version), but an amendment
     * (a new version) may still be created from it later. Rejected is
     * deliberately NOT terminal - see this enum's class docblock.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Cancelled], true);
    }

    /**
     * Draft, Returned, or Rejected may have their items edited by the
     * creator - Rejected behaves exactly like Returned (revise, then
     * resubmit) rather than being a dead end.
     */
    public function isEditableByCreator(): bool
    {
        return in_array($this, [self::Draft, self::Returned, self::Rejected], true);
    }

    /**
     * @return array<string,string> value => label, for filter dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
