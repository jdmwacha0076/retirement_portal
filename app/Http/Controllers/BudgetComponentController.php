<?php

namespace App\Http\Controllers;

use App\Enums\BudgetItemPaymentMode;
use App\Http\Requests\BudgetSettings\BudgetComponentRequest;
use App\Models\BudgetCategory;
use App\Models\BudgetComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin-only master data CRUD. No destroy route is ever exposed - see
 * ActivityTypeController's docblock for the reasoning (identical here).
 */
class BudgetComponentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BudgetComponent::class);

        $budgetComponents = BudgetComponent::with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Every category (not just active ones) so a component that was
        // filed under a category later deactivated still shows correctly
        // in its edit modal - only the CREATE dropdown needs to be
        // limited to active categories.
        $allCategories = BudgetCategory::orderBy('sort_order')->orderBy('name')->get();
        $activeCategories = $allCategories->where('is_active', true);

        $paymentModes = BudgetItemPaymentMode::options();

        return view('budget-settings.budget-components.index', compact(
            'budgetComponents', 'allCategories', 'activeCategories', 'paymentModes'
        ));
    }

    public function store(BudgetComponentRequest $request): RedirectResponse
    {
        BudgetComponent::create($request->validated() + ['is_active' => true]);

        return back()->with('success', 'Budget component added.');
    }

    public function update(BudgetComponentRequest $request, BudgetComponent $budgetComponent): RedirectResponse
    {
        $budgetComponent->update($request->validated());

        return back()->with('success', 'Budget component updated.');
    }

    public function toggleActive(BudgetComponent $budgetComponent): RedirectResponse
    {
        $this->authorize('toggleActive', $budgetComponent);

        $budgetComponent->update(['is_active' => ! $budgetComponent->is_active]);

        return back()->with('success', $budgetComponent->is_active
            ? 'Budget component activated.'
            : 'Budget component deactivated. It stays visible on historical budget lines but can no longer be picked for new ones.');
    }
}
