<?php

namespace App\Enums;

/**
 * Workflow states for an ActivityRetirement.
 *
 *   DRAFT -> SUBMITTED -> ASSIGNED/UNDER_REVIEW -> RETURNED -> (resubmit) SUBMITTED
 *                                                -> APPROVED -> RECONCILIATION_PENDING (auto) -> CLOSED
 *                                                -> REJECTED (terminal)
 *   any non-terminal state -> CANCELLED (terminal)
 */
enum ActivityRetirementStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Assigned = 'assigned';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ReconciliationPending = 'reconciliation_pending';
    case Closed = 'closed';
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
            self::ReconciliationPending => 'Reconciliation Pending',
            self::Closed => 'Closed',
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
            self::ReconciliationPending => 'status-warning',
            self::Closed => 'status-completed',
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
            self::ReconciliationPending => 'bi-hourglass-split',
            self::Closed => 'bi-lock-fill',
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
            self::Submitted => [self::Assigned, self::UnderReview, self::Approved, self::Returned, self::Rejected, self::Cancelled],
            self::Assigned => [self::Assigned, self::UnderReview, self::Approved, self::Returned, self::Rejected],
            self::UnderReview => [self::Assigned, self::Approved, self::Returned, self::Rejected],
            self::Returned => [self::Submitted, self::Cancelled],
            self::Approved => [self::ReconciliationPending],
            self::ReconciliationPending => [self::Closed],
            self::Closed => [],
            self::Rejected => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Rejected, self::Cancelled], true);
    }

    public function isEditableByCreator(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    /**
     * @return array<string,string> value => label, for filter dropdowns.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
