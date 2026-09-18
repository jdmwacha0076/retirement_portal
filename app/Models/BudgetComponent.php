<?php

namespace App\Models;

use App\Enums\BudgetItemPaymentMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-managed master data belonging to a category (e.g. "Airport
 * Transfer" under Transport). requires_supporting_document drives a
 * later phase's retirement-submission guard - a retirement line whose
 * component requires a document but has none attached blocks submission.
 * Never hard-deleted once used - see is_active.
 */
class BudgetComponent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'default_payment_mode' => BudgetItemPaymentMode::class,
            'requires_supporting_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ActivityBudgetItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
