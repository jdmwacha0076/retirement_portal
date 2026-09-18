<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetSettings\BudgetCategoryRequest;
use App\Models\BudgetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin-only master data CRUD. No destroy route is ever exposed - see
 * ActivityTypeController's docblock for the reasoning (identical here).
 * The A/B/C/D letters shown on a printed budget are NOT assigned here -
 * they're computed per-budget in a later phase (see the migration's
 * docblock).
 */
class BudgetCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BudgetCategory::class);

        $budgetCategories = BudgetCategory::withCount('components')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('budget-settings.budget-categories.index', compact('budgetCategories'));
    }

    public function store(BudgetCategoryRequest $request): RedirectResponse
    {
        BudgetCategory::create($request->validated() + ['is_active' => true]);

        return back()->with('success', 'Budget category added.');
    }

    public function update(BudgetCategoryRequest $request, BudgetCategory $budgetCategory): RedirectResponse
    {
        $budgetCategory->update($request->validated());

        return back()->with('success', 'Budget category updated.');
    }

    public function toggleActive(BudgetCategory $budgetCategory): RedirectResponse
    {
        $this->authorize('toggleActive', $budgetCategory);

        $budgetCategory->update(['is_active' => ! $budgetCategory->is_active]);

        return back()->with('success', $budgetCategory->is_active
            ? 'Budget category activated.'
            : 'Budget category deactivated. It stays visible on historical budget lines but can no longer be picked for new ones.');
    }
}
