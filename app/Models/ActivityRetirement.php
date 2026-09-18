<?php

namespace App\Models;

use App\Enums\ActivityRetirementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Created FROM one specific approved ActivityBudget version. `reference`
 * (RET/2026/00001) is its own permanent system identifier - a retirement
 * is never identified only by its activity's reference. "One
 * non-cancelled retirement per budget version" is an application-layer
 * rule (a later phase's ActivityRetirementWorkflowService::start(),
 * under DB::transaction()+lockForUpdate()) - deliberately NOT a DB unique
 * constraint, since MySQL has no partial/filtered unique index and a
 * plain unique(activity_budget_id) would wrongly block a fresh retirement
 * after a prior one was cancelled.
 */
class ActivityRetirement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ActivityRetirementStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'closed_at' => 'datetime',
            'total_actual_cash' => 'decimal:2',
            'total_actual_invoice' => 'decimal:2',
            'total_actual_overall' => 'decimal:2',
            'total_advanced' => 'decimal:2',
            'unspent_advance_amount' => 'decimal:2',
            'reimbursement_due_amount' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function activityBudget(): BelongsTo
    {
        return $this->belongsTo(ActivityBudget::class);
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

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Ordered by id (insertion order) rather than a dedicated sort_order
     * column - ActivityRetirementService::start() seeds these rows in the
     * same order as the approved budget's own items()->orderBy('sort_order'),
     * so insertion order already matches the item_code sequence the
     * creator expects to see.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ActivityRetirementItem::class)->orderBy('id');
    }

    /**
     * General, retirement-level documents (attendance sheet, activity
     * report, ...) - not tied to a specific line. Item-level receipts
     * live on ActivityRetirementItem::documents() instead.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RetirementDocument::class)->whereNull('activity_retirement_item_id')->latest('created_at');
    }

    public function allDocuments(): HasMany
    {
        return $this->hasMany(RetirementDocument::class)->latest('created_at');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(ActivityReconciliation::class)->latest('transaction_date');
    }

    /**
     * Payment Requests generated FROM this retirement (Activity
     * Reimbursement purpose) - see
     * payment_requests.source_activity_retirement_id.
     */
    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'source_activity_retirement_id');
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

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('current_assignee_id', $userId);
    }

    public function scopeStatus(Builder $query, ActivityRetirementStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ActivityRetirementStatus ? $status->value : $status);
    }
}
