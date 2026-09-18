<?php

namespace App\Models;

use App\Enums\BudgetItemPaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * activity_budget_item_id is MANDATORY - every retirement line must
 * answer "what was approved?" as well as "what was actually spent?". The
 * approved payment_mode/cash_amount/invoice_amount/total are read live
 * via activityBudgetItem() rather than snapshotted, since the parent
 * budget version is already immutable once Approved.
 */
class ActivityRetirementItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'actual_qty' => 'decimal:2',
            'actual_unit_cost' => 'decimal:2',
            'actual_frequency' => 'integer',
            'actual_cash_amount' => 'decimal:2',
            'actual_invoice_amount' => 'decimal:2',
            'actual_total' => 'decimal:2',
            'actual_payment_mode' => BudgetItemPaymentMode::class,
        ];
    }

    public function activityRetirement(): BelongsTo
    {
        return $this->belongsTo(ActivityRetirement::class);
    }

    public function activityBudgetItem(): BelongsTo
    {
        return $this->belongsTo(ActivityBudgetItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RetirementDocument::class)->latest('created_at');
    }
}
