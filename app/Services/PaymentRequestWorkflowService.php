<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestConsultancyDetail;
use App\Models\PaymentRequestCounter;
use App\Models\User;
use App\Notifications\PaymentRequestUpdated;
use App\Support\AmountToWords;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Every state-changing Payment Request operation goes through here, never
 * through a generic ->update() in a controller. Each public method:
 *
 *   1. Row-locks the request (lockForUpdate) inside a DB transaction,
 *      preventing double-approval / double-payment under concurrent clicks.
 *   2. Checks the transition is legal (PaymentRequestStatus::canTransitionTo).
 *   3. Applies the change, writes a payment_request_history row (and a
 *      payment_request_assignments row for assign()), and commits
 *      atomically - a partial write (status changed but no history row)
 *      is not possible.
 *
 * Notifications (Stage 10) hook in at the bottom of each method, after
 * the transaction commits successfully.
 */
class PaymentRequestWorkflowService
{
    /**
     * Creates a Draft. Accepts already-validated data (see
     * StorePaymentRequestRequest) - this method does not re-validate
     * business rules beyond what the DB/enum layer enforces.
     */
    public function create(User $creator, PaymentType $type, array $data): PaymentRequest
    {
        return DB::transaction(function () use ($creator, $type, $data) {
            $payeeName = $type->requiresConsultancyDetails()
                ? $data['consultant_name']
                : $data['payee_name'];

            $paymentRequest = PaymentRequest::create([
                'payment_type' => $type,
                'status' => PaymentRequestStatus::Draft,
                'created_by' => $creator->id,
                'payee_name' => $payeeName,
                'payment_date' => $data['payment_date'],
                'description' => $data['description'],
                'currency' => $data['currency'],
                'amount' => $type->requiresConsultancyDetails() ? 0 : $data['amount'],
                'amount_in_words' => '',
                'cheque_number' => $data['cheque_number'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'cashier_name' => $data['cashier_name'] ?? null,
            ]);

            if ($type->requiresConsultancyDetails()) {
                $this->syncConsultancyDetails($paymentRequest, $data);
            }

            $paymentRequest->amount_in_words = AmountToWords::convert($paymentRequest->amount, $paymentRequest->currency);
            $paymentRequest->save();

            $this->recordHistory($paymentRequest, 'created', $creator, null, PaymentRequestStatus::Draft);

            return $paymentRequest->fresh(['consultancyDetails']);
        });
    }

    /**
     * Updates a Draft or Returned request's content. Financial fields are
     * only reachable here because PaymentRequestPolicy::update() already
     * confirmed the request is in an editable state - this method still
     * re-checks, since it's the last line of defence against a stale
     * form submission racing a status change.
     */
    public function update(PaymentRequest $paymentRequest, User $editor, array $data): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $editor, $data) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isEditableByCreator() || $locked->created_by !== $editor->id) {
                throw new DomainException('This request can no longer be edited.');
            }

            $type = $locked->payment_type;
            $payeeName = $type->requiresConsultancyDetails() ? $data['consultant_name'] : $data['payee_name'];

            $locked->fill([
                'payee_name' => $payeeName,
                'payment_date' => $data['payment_date'],
                'description' => $data['description'],
                'currency' => $data['currency'],
                'amount' => $type->requiresConsultancyDetails() ? $locked->amount : $data['amount'],
                'cheque_number' => $data['cheque_number'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'cashier_name' => $data['cashier_name'] ?? null,
            ]);

            if ($type->requiresConsultancyDetails()) {
                $this->syncConsultancyDetails($locked, $data);
            }

            $locked->amount_in_words = AmountToWords::convert($locked->amount, $locked->currency);
            $locked->save();

            $this->recordHistory($locked, 'edited', $editor, $locked->status, $locked->status);

            $this->notifyOthers(
                $locked,
                'edited',
                "{$locked->displayReference()} was edited by {$editor->name}.",
                exclude: [$editor],
                candidates: [$locked->currentAssignee]
            );

            return $locked->fresh(['consultancyDetails']);
        });
    }

    /**
     * First submission generates the permanent reference number; a
     * resubmission (Returned -> Submitted) reuses the one it already has
     * - "once submitted the official reference number must never
     * change" holds either way, since generateReferenceNumber() is only
     * called when reference_number is still null.
     */
    public function submit(PaymentRequest $paymentRequest, User $user): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Submitted);

            $isResubmission = $locked->reference_number !== null;

            if (! $isResubmission) {
                $locked->reference_number = $this->generateReferenceNumber();
            }

            $from = $locked->status;
            $locked->status = PaymentRequestStatus::Submitted;
            $locked->submitted_at = now();
            $locked->save();

            $this->recordHistory(
                $locked,
                $isResubmission ? 'resubmitted' : 'submitted',
                $user,
                $from,
                PaymentRequestStatus::Submitted
            );

            $verb = $isResubmission ? 're-submitted' : 'submitted';
            $this->notifyOthers(
                $locked,
                $isResubmission ? 'resubmitted' : 'submitted',
                "{$locked->displayReference()} was {$verb} by {$user->name} and needs assignment.",
                exclude: [$user],
                candidates: User::where('role', 'admin')->where('status', 'active')->get()
            );

            return $locked->fresh();
        });
    }

    /**
     * Forward/assign to another staff member. Never overwrites the
     * previous assignment - assigned_from captures who had it before, and
     * a full row is inserted into payment_request_assignments every time
     * (history), while payment_requests.current_assignee_id is the only
     * column that gets overwritten (it's a pointer to "who has it now",
     * not a record of who has ever had it).
     */
    public function assign(PaymentRequest $paymentRequest, User $assigner, User $assignTo, ?string $comment = null): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $assigner, $assignTo, $comment) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Assigned, allowSameStatus: true);

            $previousAssigneeId = $locked->current_assignee_id;
            $from = $locked->status;

            $locked->current_assignee_id = $assignTo->id;
            $locked->status = PaymentRequestStatus::Assigned;
            $locked->save();

            $locked->assignments()->create([
                'assigned_by' => $assigner->id,
                'assigned_to' => $assignTo->id,
                'assigned_from' => $previousAssigneeId,
                'comment' => $comment,
                'status_at_assignment' => $locked->status->value,
                'created_at' => now(),
            ]);

            $this->recordHistory($locked, 'assigned', $assigner, $from, PaymentRequestStatus::Assigned, $comment, [
                'assigned_to' => $assignTo->id,
                'assigned_to_name' => $assignTo->name,
            ]);

            $assignTo->notify(new PaymentRequestUpdated(
                $locked,
                'assigned',
                "{$locked->displayReference()} was assigned to you by {$assigner->name}."
            ));

            $previousAssignee = $previousAssigneeId ? User::find($previousAssigneeId) : null;
            $this->notifyOthers(
                $locked,
                'assigned',
                "{$locked->displayReference()} was assigned to {$assignTo->name} by {$assigner->name}.",
                exclude: [$assigner, $assignTo],
                candidates: [$locked->creator, $previousAssignee]
            );

            return $locked->fresh();
        });
    }

    public function review(PaymentRequest $paymentRequest, User $user, ?string $comment = null): PaymentRequest
    {
        return $this->simpleTransition(
            $paymentRequest,
            $user,
            PaymentRequestStatus::UnderReview,
            'reviewed',
            $comment,
            notifyMessage: "{$paymentRequest->displayReference()} is now under review."
        );
    }

    public function returnForCorrection(PaymentRequest $paymentRequest, User $user, string $reason): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user, $reason) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Returned);

            $from = $locked->status;
            $locked->status = PaymentRequestStatus::Returned;
            $locked->returned_reason = $reason;
            $locked->save();

            $this->recordHistory($locked, 'returned', $user, $from, PaymentRequestStatus::Returned, $reason);

            $locked->creator?->notify(new PaymentRequestUpdated(
                $locked,
                'returned',
                "{$locked->displayReference()} was returned for correction: {$reason}"
            ));

            return $locked->fresh();
        });
    }

    /**
     * Approving twice is impossible by construction: guardTransition()
     * rejects Approved -> Approved because Approved isn't in its own
     * allowedTransitions() list, and the row lock means a second
     * concurrent click sees the already-updated status once it gets the
     * lock.
     */
    public function approve(PaymentRequest $paymentRequest, User $user, ?string $comment = null): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user, $comment) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Approved);

            $from = $locked->status;
            $locked->status = PaymentRequestStatus::Approved;
            $locked->approved_by = $user->id;
            $locked->approved_at = now();
            $locked->approval_comment = $comment;
            $locked->save();

            $this->recordHistory($locked, 'approved', $user, $from, PaymentRequestStatus::Approved, $comment);

            $locked->creator?->notify(new PaymentRequestUpdated(
                $locked,
                'approved',
                "{$locked->displayReference()} was approved by {$user->name}."
            ));

            return $locked->fresh();
        });
    }

    public function reject(PaymentRequest $paymentRequest, User $user, string $reason): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user, $reason) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Rejected);

            $from = $locked->status;
            $locked->status = PaymentRequestStatus::Rejected;
            $locked->rejected_by = $user->id;
            $locked->rejected_at = now();
            $locked->rejection_reason = $reason;
            $locked->save();

            $this->recordHistory($locked, 'rejected', $user, $from, PaymentRequestStatus::Rejected, $reason);

            $locked->creator?->notify(new PaymentRequestUpdated(
                $locked,
                'rejected',
                "{$locked->displayReference()} was rejected: {$reason}"
            ));

            return $locked->fresh();
        });
    }

    public function markReadyForPayment(PaymentRequest $paymentRequest, User $user): PaymentRequest
    {
        return $this->simpleTransition(
            $paymentRequest,
            $user,
            PaymentRequestStatus::ReadyForPayment,
            'ready_for_payment',
            notifyMessage: "{$paymentRequest->displayReference()} is approved and ready for payment processing."
        );
    }

    /**
     * Records the actual payment detail (cheque number / transaction
     * reference / cashier - whichever the request's type uses) and moves
     * to Paid, which is treated as effectively immutable everywhere else
     * in the app from this point on (no update route matches a Paid
     * request's policy).
     */
    public function markPaid(PaymentRequest $paymentRequest, User $user, array $paymentDetails): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user, $paymentDetails) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Paid);

            $from = $locked->status;

            if (! empty($paymentDetails['cheque_number']) && $locked->payment_type->usesChequeNumber()) {
                $locked->cheque_number = $paymentDetails['cheque_number'];
            }

            if (! empty($paymentDetails['transaction_reference']) && $locked->payment_type->usesTransactionReference()) {
                $locked->transaction_reference = $paymentDetails['transaction_reference'];
            }

            if (! empty($paymentDetails['cashier_name']) && $locked->payment_type->usesCashier()) {
                $locked->cashier_name = $paymentDetails['cashier_name'];
            }

            $locked->status = PaymentRequestStatus::Paid;
            $locked->processed_by = $user->id;
            $locked->paid_at = $paymentDetails['payment_date'] ?? now();
            $locked->save();

            $this->recordHistory($locked, 'paid', $user, $from, PaymentRequestStatus::Paid);

            $locked->creator?->notify(new PaymentRequestUpdated(
                $locked,
                'paid',
                "Payment for {$locked->displayReference()} has been completed."
            ));

            return $locked->fresh();
        });
    }

    public function cancel(PaymentRequest $paymentRequest, User $user, ?string $reason = null): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest, $user, $reason) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, PaymentRequestStatus::Cancelled);

            $from = $locked->status;
            $locked->status = PaymentRequestStatus::Cancelled;
            $locked->save();

            $this->recordHistory($locked, 'cancelled', $user, $from, PaymentRequestStatus::Cancelled, $reason);

            $this->notifyOthers(
                $locked,
                'cancelled',
                "{$locked->displayReference()} was cancelled by {$user->name}.".($reason ? " Reason: {$reason}" : ''),
                exclude: [$user],
                candidates: [$locked->creator, $locked->currentAssignee]
            );

            return $locked->fresh();
        });
    }

    // ── Internals ──────────────────────────────────────────────────

    private function simpleTransition(
        PaymentRequest $paymentRequest,
        User $user,
        PaymentRequestStatus $to,
        string $action,
        ?string $comment = null,
        ?string $notifyMessage = null
    ): PaymentRequest {
        return DB::transaction(function () use ($paymentRequest, $user, $to, $action, $comment, $notifyMessage) {
            $locked = PaymentRequest::whereKey($paymentRequest->id)->lockForUpdate()->firstOrFail();

            $this->guardTransition($locked, $to);

            $from = $locked->status;
            $locked->status = $to;
            $locked->save();

            $this->recordHistory($locked, $action, $user, $from, $to, $comment);

            if ($notifyMessage) {
                $this->notifyOthers($locked, $action, $notifyMessage, exclude: [$user], candidates: [$locked->creator]);
            }

            return $locked->fresh();
        });
    }

    /**
     * Shared fan-out for every workflow notification: dedupes candidates
     * by id, drops nulls (e.g. a request with no current assignee yet),
     * and never notifies whoever just performed the action or anyone
     * else already excluded (e.g. the new assignee, who already got
     * their own more personal "assigned to you" message). Silently does
     * nothing when nobody is left to notify.
     *
     * @param  array<int, \App\Models\User|null>  $exclude
     * @param  iterable<\App\Models\User|null>  $candidates
     */
    private function notifyOthers(PaymentRequest $paymentRequest, string $event, string $message, array $exclude, iterable $candidates): void
    {
        $excludeIds = collect($exclude)->filter()->map(fn (User $u) => $u->id)->all();

        $recipients = collect($candidates)
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => in_array($u->id, $excludeIds, true));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PaymentRequestUpdated($paymentRequest, $event, $message));
    }

    private function guardTransition(PaymentRequest $paymentRequest, PaymentRequestStatus $to, bool $allowSameStatus = false): void
    {
        if ($allowSameStatus && $paymentRequest->status === $to) {
            return;
        }

        if (! $paymentRequest->status->canTransitionTo($to)) {
            throw new DomainException(sprintf(
                'Cannot move a %s request to %s.',
                $paymentRequest->status->label(),
                $to->label()
            ));
        }
    }

    private function recordHistory(
        PaymentRequest $paymentRequest,
        string $action,
        User $performer,
        ?PaymentRequestStatus $from,
        ?PaymentRequestStatus $to,
        ?string $comment = null,
        ?array $metadata = null
    ): void {
        $paymentRequest->history()->create([
            'action' => $action,
            'performed_by' => $performer->id,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * WHT amount = fee x percentage; amount due = fee - WHT amount. The
     * core request's 'amount' column always mirrors amount_due, since
     * that's what actually gets disbursed and printed as "the" amount.
     */
    private function syncConsultancyDetails(PaymentRequest $paymentRequest, array $data): void
    {
        $fee = (float) $data['consultancy_fee'];
        $percentage = (float) $data['withholding_tax_percentage'];
        $calculated = PaymentRequestConsultancyDetail::calculate($fee, $percentage);

        $paymentRequest->consultancyDetails()->updateOrCreate(
            ['payment_request_id' => $paymentRequest->id],
            [
                'consultant_name' => $data['consultant_name'],
                'tin_number' => $data['tin_number'],
                'client_name' => $data['client_name'],
                'consultancy_fee' => $fee,
                'withholding_tax_percentage' => $percentage,
                'withholding_tax_amount' => $calculated['withholding_tax_amount'],
                'amount_due' => $calculated['amount_due'],
            ]
        );

        $paymentRequest->amount = $calculated['amount_due'];
    }

    private function generateReferenceNumber(): string
    {
        $year = (int) now()->format('Y');

        $counter = PaymentRequestCounter::where('year', $year)->lockForUpdate()->first();

        if (! $counter) {
            // firstOrCreate isn't safe here under concurrency on some
            // drivers - explicit lock-then-insert-if-missing, still
            // inside the caller's transaction, is what makes this safe.
            $counter = PaymentRequestCounter::create(['year' => $year, 'next_number' => 1]);
            $counter = PaymentRequestCounter::where('year', $year)->lockForUpdate()->first();
        }

        $sequence = $counter->next_number;
        $counter->update(['next_number' => $sequence + 1]);

        $prefix = config('payment_requests.reference_prefix');
        $length = config('payment_requests.reference_seq_length');

        return sprintf('%s/%d/%s', $prefix, $year, str_pad((string) $sequence, $length, '0', STR_PAD_LEFT));
    }
}
