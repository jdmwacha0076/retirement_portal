<?php

namespace App\Models;

use App\Enums\ActivityBudgetStatus;
use App\Enums\ActivityRetirementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One VERSION of an Activity's budget. unique(activity_id, version) is
 * enforced at the DB level (see migration). is_current marks the version
 * in effect - a later phase's ActivityBudgetWorkflowService is
 * responsible for only flipping is_current when a new version is
 * actually Approved (never when merely created), per the approved
 * architecture. totals are derived from items and re-persisted here;
 * never independently editable (no business logic here yet - Phase 1
 * scope).
 */
class ActivityBudget extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ActivityBudgetStatus::class,
            'is_current' => 'boolean',
            'version' => 'integer',
            'total_cash' => 'decimal:2',
            'total_invoice' => 'decimal:2',
            'total_overall' => 'decimal:2',
            'requested_advance_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ActivityBudgetItem::class)->orderBy('sort_order');
    }

    public function advanceDisbursements(): HasMany
    {
        return $this->hasMany(ActivityAdvanceDisbursement::class);
    }

    public function retirements(): HasMany
    {
        return $this->hasMany(ActivityRetirement::class);
    }

    /**
     * The one non-cancelled retirement in progress against this budget
     * version, if any - there is no is_current-style column for
     * retirements (unlike Activity::currentBudget()), since "one non-
     * cancelled retirement per budget version" is an application-layer
     * rule (ActivityRetirementService::start()), not a DB constraint -
     * see ActivityRetirement's own class docblock for why. latestOfMany()
     * picks the newest by id, which is only ever ambiguous with more than
     * one non-cancelled row at once, and that's exactly what start()
     * guards against.
     */
    public function currentRetirement(): HasOne
    {
        return $this->hasOne(ActivityRetirement::class)
            ->where('status', '!=', ActivityRetirementStatus::Cancelled->value)
            ->latestOfMany();
    }

    /**
     * Payment Requests generated FROM this budget (Activity Advance
     * purpose) - see payment_requests.source_activity_budget_id.
     */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'source_activity_budget_id');
    }

    public function history(): MorphMany
    {
        return $this->morphMany(ActivityHistory::class, 'historyable')->latest('created_at');
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(ActivityAssignment::class, 'assignmentable')->latest('created_at');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ActivityComment::class, 'commentable')->latest('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('current_assignee_id', $userId);
    }

    public function scopeStatus(Builder $query, ActivityBudgetStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ActivityBudgetStatus ? $status->value : $status);
    }

    // ── Authorization helpers ──────────────────────────────────────

    /**
     * Whether $user - assumed to be checked as this budget's creator, not
     * an admin (callers handle that separately) - may edit its line items
     * and resubmit right now. Draft/Rejected always qualify by status
     * alone. Once it's been submitted and forwarded, editability instead
     * follows current_assignee_id: being assigned the budget back to its
     * own creator IS "returned for correction" now that Assign is the
     * single, only forward/return action (see
     * ActivityBudgetWorkflowService::assign() and ActivityBudgetStatus's
     * own class docblock) - there's no dedicated Returned status to check
     * anymore for anything created going forward.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($this->created_by !== $user->id) {
            return false;
        }

        if (in_array($this->status, [ActivityBudgetStatus::Draft, ActivityBudgetStatus::Rejected], true)) {
            return true;
        }

        return $this->status === ActivityBudgetStatus::Assigned && $this->current_assignee_id === $user->id;
    }
}
