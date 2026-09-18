<?php

namespace App\Models;

use App\Enums\ActivityRetirementStatus;
use App\Enums\ActivityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The umbrella project/travel/training record. `reference` (ACT/2026/
 * 00001) is the permanent system identifier; `accounting_code` (e.g.
 * LGH27/26) is a separate, optional, manually-entered donor/project code
 * used only for printed budget item numbering - see
 * ActivityBudgetItem.item_code and displayAccountingCode() below.
 *
 * `status` is deliberately coarse - the detailed lifecycle stage is
 * always computed from the current ActivityBudget/ActivityRetirement
 * (a later phase adds that computed accessor once the workflow services
 * exist); no business logic lives on this model yet (Phase 1 scope).
 */
class Activity extends Model
{
    use SoftDeletes;

    /**
     * Deliberately NOT mass-assignable from raw request input - every
     * write goes through a service in a later phase, the same convention
     * already used by PaymentRequest.
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'participant_count' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ActivityBudget::class)->orderByDesc('version');
    }

    public function currentBudget(): HasOne
    {
        return $this->hasOne(ActivityBudget::class)->where('is_current', true);
    }

    /**
     * Activity-level audit trail (registered, details updated,
     * cancelled, ...) - reuses the same polymorphic activity_history
     * table shared by ActivityBudget/ActivityRetirement (Phase 1), since
     * that table's whole point is being reusable by any historyable
     * record rather than duplicated per domain. Purely additive: nothing
     * about the approved Phase 1 schema changes, this just adds the
     * relationship method Phase 3 needs to actually use it for Activity
     * itself.
     */
    public function history(): MorphMany
    {
        return $this->morphMany(ActivityHistory::class, 'historyable')->latest('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopeStatus(Builder $query, ActivityStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ActivityStatus ? $status->value : $status);
    }

    /**
     * "Should this show up in a staff member's own activity list" - never
     * called for an admin, who sees everything regardless. Three-tier
     * shape mirrors PaymentRequestPolicy::view() (created / currently
     * holding / ever touched it), generalised across the current budget
     * and current retirement that live under this activity, since an
     * Activity has no assignment concept of its own - only its budget/
     * retirement workflow does. "Ever touched" (the activity_assignments
     * history, not just current_assignee_id) matters here for the same
     * reason it does for Payment Requests: a budget/retirement shouldn't
     * vanish from someone's activity list the moment it's forwarded
     * onward to somebody else.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhere('coordinator_id', $user->id)
                ->orWhereHas('currentBudget', function (Builder $budgetQuery) use ($user) {
                    $budgetQuery->where('current_assignee_id', $user->id)
                        ->orWhereHas('assignments', fn (Builder $aq) => $aq
                            ->where('assigned_to', $user->id)
                            ->orWhere('assigned_by', $user->id));
                })
                ->orWhereHas('currentBudget.retirements', function (Builder $retirementQuery) use ($user) {
                    // Plain retirements() (not the latestOfMany()-based
                    // currentRetirement()) deliberately, to avoid nesting
                    // an ofMany subquery inside this already-nested
                    // whereHas - filtering out Cancelled here is
                    // equivalent for visibility purposes, since the app
                    // never allows more than one non-cancelled retirement
                    // per budget version at a time anyway.
                    $retirementQuery->where('status', '!=', ActivityRetirementStatus::Cancelled->value)
                        ->where(function (Builder $q) use ($user) {
                            $q->where('current_assignee_id', $user->id)
                                ->orWhereHas('assignments', fn (Builder $aq) => $aq
                                    ->where('assigned_to', $user->id)
                                    ->orWhere('assigned_by', $user->id));
                        });
                });
        });
    }

    // ── Accessors / helpers ────────────────────────────────────────

    /**
     * The code used for printed budget item numbering - falls back to
     * the permanent system reference when no accounting_code has been
     * entered, per the approved architecture.
     */
    public function displayAccountingCode(): string
    {
        return $this->accounting_code ?: $this->reference;
    }

    /**
     * Who currently holds this activity's workflow, right now - distinct
     * from creator()/created_by, which never changes once set.
     *
     * Walks to whichever workflow object is furthest along (the current
     * retirement if one exists, otherwise the current budget), and returns
     * whoever it's presently assigned to (current_assignee_id). Before
     * anything has been formally assigned/forwarded, current_assignee_id
     * is still null, so this falls back to that workflow object's own
     * creator; if no budget exists yet at all, it falls back to the
     * Activity's own creator. Deliberately computed rather than stored -
     * same "derive, don't duplicate" approach as Activity::status (see
     * class docblock).
     *
     * Callers that loop over many activities (e.g. the index listing)
     * should eager-load currentBudget.currentAssignee, currentBudget.creator,
     * currentBudget.currentRetirement.currentAssignee and
     * currentBudget.currentRetirement.creator first, to avoid N+1s.
     */
    public function currentlyAssignedTo(): ?User
    {
        $workflowObject = $this->currentBudget?->currentRetirement ?? $this->currentBudget;

        if (! $workflowObject) {
            return $this->creator;
        }

        return $workflowObject->currentAssignee ?? $workflowObject->creator;
    }
}
