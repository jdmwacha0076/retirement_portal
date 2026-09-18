<?php

namespace App\Models;

use App\Enums\BudgetItemPaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single budget line. total = qty * unit_cost * frequency - a later
 * phase's ActivityBudgetCalculator support class is the single place
 * that computes this server-side (never trusted from the client); no
 * calculation logic lives on the model itself yet (Phase 1 scope).
 */
class ActivityBudgetItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payment_mode' => BudgetItemPaymentMode::class,
            'qty' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'frequency' => 'integer',
            'total' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'invoice_amount' => 'decimal:2',
        ];
    }

    public function activityBudget(): BelongsTo
    {
        return $this->belongsTo(ActivityBudget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(BudgetComponent::class, 'budget_component_id');
    }

    public function retirementItems(): HasMany
    {
        return $this->hasMany(ActivityRetirementItem::class);
    }
}
