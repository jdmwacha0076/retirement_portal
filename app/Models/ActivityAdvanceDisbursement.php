<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One actual advance disbursement transaction against an approved
 * ActivityBudget - a budget's total advance is always SUM(amount) across
 * these rows (see activity_retirements.total_advanced, computed in a
 * later phase), never a single field. payment_request_id is unique when
 * present so a paid, linked Payment Request can only ever produce one
 * disbursement row.
 */
class ActivityAdvanceDisbursement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'disbursed_at' => 'datetime',
        ];
    }

    public function activityBudget(): BelongsTo
    {
        return $this->belongsTo(ActivityBudget::class);
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
