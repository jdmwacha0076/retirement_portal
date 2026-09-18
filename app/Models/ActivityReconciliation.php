<?php

namespace App\Models;

use App\Enums\ReconciliationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One unspent-advance-return or reimbursement transaction against a
 * retirement - append-only ledger, supports multiple partial
 * transactions. Outstanding balance (target minus SUM of matching-type
 * rows) is computed in a later phase's service, never stored here as a
 * single "resolved" flag. payment_request_id is unique when present so a
 * paid, linked reimbursement Payment Request can only ever produce one
 * reconciliation row.
 */
class ActivityReconciliation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ReconciliationType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function activityRetirement(): BelongsTo
    {
        return $this->belongsTo(ActivityRetirement::class);
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
