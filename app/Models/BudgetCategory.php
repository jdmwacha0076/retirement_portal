<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-managed master data (Transport, Meals and Credit, Visa,
 * Accommodation, ...). The A/B/C/D letters on a printed budget are NOT
 * tied to this record - they're assigned per-budget by category order
 * within that specific budget (see ActivityBudgetItem.item_code, built in
 * a later phase). Never hard-deleted once used - see is_active.
 */
class BudgetCategory extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function components(): HasMany
    {
        return $this->hasMany(BudgetComponent::class);
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
