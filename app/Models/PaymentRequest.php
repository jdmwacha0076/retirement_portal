<?php

namespace App\Models;

use App\Enums\PaymentRequestStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PaymentRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Deliberately NOT mass-assignable from raw request input - every
     * write to this model goes through PaymentRequestWorkflowService,
     * which builds the attribute array itself. created_by, status,
     * approved_by/at, processed_by, paid_at, rejected_by/at etc. must
     * never be settable from a form field.
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'status' => PaymentRequestStatus::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function consultancyDetails(): HasOne
    {
        return $this->hasOne(PaymentRequestConsultancyDetail::class);
    }

    /**
     * Set only when this Payment Request was generated from the Activity
     * Budget module - "Activity Advance" purpose. Mutually exclusive with
     * sourceActivityRetirement() (enforced in the service layer, not at
     * the DB level). Both null (every Payment Request that predates this
     * module, and any created independently of it) behaves exactly as
     * before - this relationship is purely additive.
     */
    public function sourceActivityBudget(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ActivityBudget::class, 'source_activity_budget_id');
    }

    /**
     * Set only when this Payment Request was generated from the Activity
     * Budget module - "Activity Reimbursement" purpose. Mutually
     * exclusive with sourceActivityBudget().
     */
    public function sourceActivityRetirement(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ActivityRetirement::class, 'source_activity_retirement_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(PaymentRequestHistory::class)->latest('created_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PaymentRequestAssignment::class)->latest('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PaymentRequestComment::class)->latest('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PaymentRequestDocument::class)->latest('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('current_assignee_id', $userId);
    }

    public function scopeStatus(Builder $query, PaymentRequestStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof PaymentRequestStatus ? $status->value : $status);
    }

    /**
     * "Needs this user's attention" - assigned to them and not yet in a
     * terminal state. Backs the My Tasks page.
     */
    public function scopeAwaitingActionFrom(Builder $query, int $userId): Builder
    {
        return $query->where('current_assignee_id', $userId)
            ->whereNotIn('status', [
                PaymentRequestStatus::Paid->value,
                PaymentRequestStatus::Rejected->value,
                PaymentRequestStatus::Cancelled->value,
            ]);
    }

    // ── Accessors / helpers ────────────────────────────────────────

    public function isEditableBy(User $user): bool
    {
        return $this->created_by === $user->id
            && $this->status->isEditableByCreator();
    }

    public function isDraft(): bool
    {
        return $this->status === PaymentRequestStatus::Draft;
    }

    public function displayReference(): string
    {
        return $this->reference_number ?? 'DRAFT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function formattedAmount(): string
    {
        return $this->currency.' '.number_format((float) $this->amount, 2);
    }
}
