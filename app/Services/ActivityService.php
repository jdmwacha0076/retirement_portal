<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use App\Support\ReferenceGenerator;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every write to Activity goes through here rather than the controller
 * touching the model directly - mirrors PaymentRequestWorkflowService's
 * shape (authorize is the controller/Form Request's job via Policies;
 * this class does: validate current state, mutate inside a transaction,
 * record history). Phase 3 scope: register/update/cancel. activate() was
 * added in Phase 7 - it's called by ActivityBudgetWorkflowService::
 * approve() the moment an activity's budget is first approved, per the
 * user's own clarification of the lifecycle ("approved budget means the
 * requested advance has been provided" - i.e. the activity is genuinely
 * under way from that point, not merely from submission as originally
 * sketched in Phase 3's plan).
 */
class ActivityService
{
    public function register(array $data, User $creator): Activity
    {
        return DB::transaction(function () use ($data, $creator) {
            $reference = ReferenceGenerator::next(
                'activity',
                config('activity_budgets.activity_reference_prefix'),
                config('activity_budgets.reference_seq_length')
            );

            $activity = Activity::create($data + [
                'reference' => $reference,
                'status' => ActivityStatus::Draft,
                'created_by' => $creator->id,
            ]);

            $this->recordHistory($activity, $creator, 'registered', null, ActivityStatus::Draft, 'Activity registered.');

            return $activity;
        });
    }

    public function update(Activity $activity, array $data, User $actor): Activity
    {
        return DB::transaction(function () use ($activity, $data, $actor) {
            $activity->update($data);

            $this->recordHistory($activity, $actor, 'updated', $activity->status, $activity->status, 'Activity details updated.');

            return $activity->fresh();
        });
    }

    /**
     * @throws DomainException when Cancelled isn't a legal move from the
     *         activity's current status (e.g. it's already terminal) -
     *         the controller catches this and turns it into a flash
     *         'error' message, never a 500, matching the Payment Request
     *         module's convention.
     */
    public function cancel(Activity $activity, User $actor, string $reason): Activity
    {
        return DB::transaction(function () use ($activity, $actor, $reason) {
            $this->guardTransition($activity, ActivityStatus::Cancelled);

            $fromStatus = $activity->status;

            $activity->update([
                'status' => ActivityStatus::Cancelled,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->recordHistory($activity, $actor, 'cancelled', $fromStatus, ActivityStatus::Cancelled, $reason);

            return $activity->fresh();
        });
    }

    /**
     * Draft -> Active, triggered by the activity's budget being approved
     * for the first time. Idempotent by design: called unconditionally
     * from ActivityBudgetWorkflowService::approve() (including a future
     * amendment's re-approval), it silently no-ops once the activity is
     * already Active or beyond rather than throwing - approving a second
     * budget version is not an error against the activity itself.
     */
    public function activate(Activity $activity, User $actor): Activity
    {
        return DB::transaction(function () use ($activity, $actor) {
            $locked = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ActivityStatus::Draft) {
                return $locked;
            }

            $this->guardTransition($locked, ActivityStatus::Active);

            $fromStatus = $locked->status;
            $locked->status = ActivityStatus::Active;
            $locked->save();

            $this->recordHistory($locked, $actor, 'activated', $fromStatus, ActivityStatus::Active, 'Activated by budget approval.');

            return $locked->fresh();
        });
    }

    private function guardTransition(Activity $activity, ActivityStatus $to): void
    {
        if (! $activity->status->canTransitionTo($to)) {
            throw new DomainException(
                "This activity can't be moved from \"{$activity->status->label()}\" to \"{$to->label()}\"."
            );
        }
    }

    private function recordHistory(Activity $activity, User $actor, string $action, ?ActivityStatus $from, ?ActivityStatus $to, ?string $comment): void
    {
        $activity->history()->create([
            'action' => $action,
            'performed_by' => $actor->id,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'comment' => $comment,
        ]);
    }
}
