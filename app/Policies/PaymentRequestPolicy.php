<?php

namespace App\Policies;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestAssignment;
use App\Models\User;

/**
 * Backend authorization for every Payment Request action - checked from
 * controllers/the workflow service via $this->authorize(...), never
 * relied on as a UI-only (hide-the-button) guard.
 *
 * Praxis currently has two roles (admin/staff) and no dedicated
 * "Approver" or "Finance" role, so approve/reject/markPaid are admin-only
 * for now. If Praxis later wants those split out, that's a one-line
 * change per method here plus a role addition - nothing else in the
 * workflow service depends on this policy's specific rules.
 */
class PaymentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated admin/staff - the 'role:admin,staff' route middleware already gates this
    }

    /**
     * Admin sees everything. Staff sees requests they created, currently
     * hold, or have ever been assigned/forwarded (so a request doesn't
     * vanish from someone's view the moment it's forwarded onward).
     */
    public function view(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($paymentRequest->created_by === $user->id || $paymentRequest->current_assignee_id === $user->id) {
            return true;
        }

        return PaymentRequestAssignment::where('payment_request_id', $paymentRequest->id)
            ->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return true; // both roles may originate a request
    }

    /**
     * Only the creator, and only while it's a Draft or has been Returned
     * for correction - submitted/in-review/approved/paid requests can't
     * be freely edited.
     */
    public function update(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->created_by === $user->id
            && $paymentRequest->status->isEditableByCreator();
    }

    public function submit(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->created_by === $user->id
            && in_array($paymentRequest->status, [PaymentRequestStatus::Draft, PaymentRequestStatus::Returned], true);
    }

    /**
     * Admin can always (re)assign. The creator can assign it onward right
     * after submitting (before anyone else has picked it up). The current
     * assignee can forward it further along the chain.
     */
    public function assign(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($paymentRequest->current_assignee_id === $user->id) {
            return true;
        }

        return $paymentRequest->created_by === $user->id
            && $paymentRequest->status === PaymentRequestStatus::Submitted
            && $paymentRequest->current_assignee_id === null;
    }

    public function review(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin() || $paymentRequest->current_assignee_id === $user->id;
    }

    public function returnForCorrection(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin() || $paymentRequest->current_assignee_id === $user->id;
    }

    public function approve(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin();
    }

    public function markReadyForPayment(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin();
    }

    public function markPaid(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($paymentRequest->status->isTerminal()) {
            return false;
        }

        return $user->isAdmin() || $paymentRequest->created_by === $user->id;
    }

    public function comment(User $user, PaymentRequest $paymentRequest): bool
    {
        return $this->view($user, $paymentRequest);
    }

    public function uploadDocument(User $user, PaymentRequest $paymentRequest): bool
    {
        return $this->view($user, $paymentRequest) && ! $paymentRequest->status->isTerminal();
    }

    public function print(User $user, PaymentRequest $paymentRequest): bool
    {
        return $this->view($user, $paymentRequest);
    }
}
