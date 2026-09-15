<?php

namespace App\Enums;

/**
 * Workflow states for a PaymentRequest, plus the single source of truth
 * for which transitions are legal (allowedTransitions()). Every method on
 * PaymentRequestWorkflowService checks canTransitionTo() before writing
 * anything, so an invalid transition throws instead of silently
 * corrupting the request's history.
 *
 *   DRAFT -> SUBMITTED -> ASSIGNED/UNDER_REVIEW -> RETURNED -> (resubmit) SUBMITTED
 *                                                -> APPROVED -> READY_FOR_PAYMENT -> PAID
 *                                                -> REJECTED (terminal)
 *   any non-terminal state -> CANCELLED (terminal)
 */
enum PaymentRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Assigned = 'assigned';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case ReadyForPayment = 'ready_for_payment';
    case Paid = 'paid';
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
            self::ReadyForPayment => 'Ready for Payment',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Maps onto the .status-badge variants already defined in styles.css
     * (shared across every portal on this design system) rather than
     * introducing new badge colours just for this feature.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'status-draft',
            self::Submitted => 'status-info',
            self::Assigned => 'status-secondary',
            self::UnderReview => 'status-warning',
            self::Returned => 'status-warning',
            self::Approved => 'status-success',
            self::ReadyForPayment => 'status-info',
            self::Paid => 'status-completed',
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
            self::ReadyForPayment => 'bi-hourglass-split',
            self::Paid => 'bi-cash-stack',
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
            self::Submitted => [self::Assigned, self::UnderReview, self::Approved, self::Returned, self::Rejected, self::Cancelled],
            self::Assigned => [self::Assigned, self::UnderReview, self::Approved, self::Returned, self::Rejected],
            self::UnderReview => [self::Assigned, self::Approved, self::Returned, self::Rejected],
            self::Returned => [self::Submitted, self::Cancelled],
            self::Approved => [self::ReadyForPayment, self::Cancelled],
            self::ReadyForPayment => [self::Paid, self::Cancelled],
            self::Paid => [],
            self::Rejected => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Paid/Rejected/Cancelled: no further workflow action is possible.
     * Kept in the system for audit (never deleted), just inert.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Rejected, self::Cancelled], true);
    }

    /**
     * Only a Draft or a Returned request may have its financial fields
     * edited by the creator - everything else requires a formal workflow
     * action (return-for-correction) before edits are allowed again.
     */
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
