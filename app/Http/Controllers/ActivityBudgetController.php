<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activities\ApproveActivityBudgetRequest;
use App\Http\Requests\Activities\AssignActivityBudgetRequest;
use App\Http\Requests\Activities\RejectActivityBudgetRequest;
use App\Http\Requests\Activities\SaveActivityBudgetItemsRequest;
use App\Models\Activity;
use App\Models\ActivityBudget;
use App\Models\BudgetCategory;
use App\Models\BudgetComponent;
use App\Models\User;
use App\Services\ActivityBudgetService;
use App\Services\ActivityBudgetWorkflowService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Activity Budget builder (Phase 4) plus its full workflow: Submit/
 * Assign/Review/Return/Reject/Cancel (Phase 6) and Approve (Phase 7).
 * One GET route is the whole entry point - it renders either a
 * "Start Budget" prompt or the builder (with its action bar) depending
 * on whether a current budget already exists, so nothing ever links to
 * a dead page.
 */
class ActivityBudgetController extends Controller
{
    public function __construct(
        private readonly ActivityBudgetService $budgets,
        private readonly ActivityBudgetWorkflowService $workflow
    ) {
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $budget = $activity->currentBudget()->first();

        if (! $budget) {
            $canStart = Gate::allows('create', [ActivityBudget::class, $activity]);

            return view('activities.budget.edit', [
                'activity' => $activity,
                'budget' => null,
                'canStart' => $canStart,
                'canEdit' => false,
                'rows' => [],
                'categories' => collect(),
                'componentsByCategory' => collect(),
            ]);
        }

        $this->authorize('view', $budget);

        $canEdit = Gate::allows('update', $budget);

        $budget->load(['history.performer', 'currentAssignee', 'assignments']);

        $assignableUsers = User::whereIn('role', ['admin', 'staff'])
            ->where('status', 'active')
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get();

        $rows = old('items') ?? $budget->items()->orderBy('sort_order')->get()->map(fn ($item) => [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'budget_category_id' => $item->budget_category_id,
            'budget_component_id' => $item->budget_component_id,
            'description' => $item->description,
            'unit' => $item->unit,
            'qty' => $item->qty,
            'frequency' => $item->frequency,
            'unit_cost' => $item->unit_cost,
            'payment_mode' => $item->payment_mode->value,
            'cash_amount' => $item->cash_amount,
            'invoice_amount' => $item->invoice_amount,
            'notes' => $item->notes,
        ])->values()->all();

        // Active master data, plus whatever this budget's existing lines
        // already reference even if since deactivated - otherwise saving
        // an untouched row would silently null out or reject a category/
        // component that simply isn't offered to NEW lines anymore
        // (same defensive pattern as ActivityController::edit()'s
        // activity_type_id handling).
        $usedCategoryIds = $budget->items()->pluck('budget_category_id')->unique()->all();
        $usedComponentIds = $budget->items()->pluck('budget_component_id')->filter()->unique()->all();

        $categories = BudgetCategory::query()
            ->where('is_active', true)
            ->orWhereIn('id', $usedCategoryIds)
            ->orderBy('sort_order')->orderBy('name')->get();

        $componentsByCategory = BudgetComponent::query()
            ->where('is_active', true)
            ->orWhereIn('id', $usedComponentIds)
            ->orderBy('name')->get()->groupBy('budget_category_id');

        // A current budget already exists, so this view is always the
        // builder, never the "Start Budget" prompt.
        $canStart = false;

        return view('activities.budget.edit', compact(
            'activity', 'budget', 'canStart', 'canEdit', 'rows', 'categories', 'componentsByCategory', 'assignableUsers'
        ));
    }

    public function store(Activity $activity): RedirectResponse
    {
        $this->authorize('create', [ActivityBudget::class, $activity]);

        try {
            $this->budgets->startDraft($activity, request()->user());
        } catch (DomainException $e) {
            return redirect()->route('activities.budget.edit', $activity)->with('error', $e->getMessage());
        }

        return redirect()->route('activities.budget.edit', $activity)
            ->with('success', 'Budget started — add your line items below, then save.');
    }

    public function update(SaveActivityBudgetItemsRequest $request, Activity $activity): RedirectResponse
    {
        $budget = $activity->currentBudget()->first();
        abort_if(! $budget, 404);

        $data = $request->validated();

        try {
            $this->budgets->saveItems(
                $budget,
                $data['items'],
                $request->user(),
                $data['budget_code'] ?? null,
                isset($data['requested_advance_amount']) ? (float) $data['requested_advance_amount'] : null
            );
        } catch (DomainException $e) {
            return redirect()->route('activities.budget.edit', $activity)->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('activities.budget.edit', $activity)->with('success', 'Budget saved.');
    }

    public function submit(Request $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        $this->authorize('submit', $budget);

        return $this->transition(
            fn () => $this->workflow->submit($budget, $request->user()),
            $activity,
            'Budget submitted for review.'
        );
    }

    /**
     * Forwards the budget to another staff member, OR - by assigning it
     * back to its own creator - returns it for correction. One action
     * either way, always carrying a reason (see
     * ActivityBudgetWorkflowService::assign()'s docblock).
     */
    public function assign(AssignActivityBudgetRequest $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);
        $assignTo = User::findOrFail($request->validated('assigned_to'));

        return $this->transition(
            fn () => $this->workflow->assign($budget, $request->user(), $assignTo, $request->validated('reason')),
            $activity,
            "Budget assigned to {$assignTo->name}."
        );
    }

    public function review(Request $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        $this->authorize('review', $budget);

        return $this->transition(
            fn () => $this->workflow->review($budget, $request->user(), $request->input('comment')),
            $activity,
            'Budget marked as under review.'
        );
    }

    public function approve(ApproveActivityBudgetRequest $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        return $this->transition(
            fn () => $this->workflow->approve($budget, $request->user(), $request->validated('comment')),
            $activity,
            'Budget approved.'
        );
    }

    public function reject(RejectActivityBudgetRequest $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        return $this->transition(
            fn () => $this->workflow->reject($budget, $request->user(), $request->validated('reason')),
            $activity,
            'Budget rejected.'
        );
    }

    /**
     * Optional reason, no dedicated Form Request - mirrors
     * PaymentRequestController::cancel() exactly.
     */
    public function cancel(Request $request, Activity $activity): RedirectResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        $this->authorize('cancel', $budget);

        return $this->transition(
            fn () => $this->workflow->cancel($budget, $request->user(), $request->input('reason')),
            $activity,
            'Budget cancelled.'
        );
    }

    /**
     * A4-landscape, browser-print-to-PDF report of the current budget -
     * same convention already used for Payment Request vouchers
     * (resources/views/payment-requests/print/*), just laid out for a
     * wide line-item table instead of a single voucher. "Save as PDF" in
     * the browser's own print dialog is the actual PDF output; no
     * server-side PDF library involved.
     */
    public function print(Activity $activity): View
    {
        $budget = $this->currentBudgetOrAbort($activity);

        $this->authorize('print', $budget);

        $budget->load(['items.category', 'items.component', 'creator', 'approver']);

        return view('activities.budget.print', compact('activity', 'budget'));
    }

    /**
     * Plain CSV (not a real .xlsx) so it needs no new composer package -
     * opens directly in Excel/Sheets. A UTF-8 BOM is written first so
     * Excel renders the £/currency-adjacent characters correctly rather
     * than mangling them, a well-known Excel-specific quirk with plain
     * UTF-8 CSVs.
     */
    public function exportCsv(Activity $activity): StreamedResponse
    {
        $budget = $this->currentBudgetOrAbort($activity);

        $this->authorize('print', $budget);

        $budget->load(['items.category', 'items.component']);

        $filename = Str::slug($budget->budget_code ?: $activity->displayAccountingCode()).'-budget.csv';

        return response()->streamDownload(function () use ($activity, $budget) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Activity', $activity->reference]);
            fputcsv($out, ['Title', $activity->title]);
            fputcsv($out, ['Budget', $budget->budget_code ?: $activity->displayAccountingCode()]);
            fputcsv($out, ['Version', $budget->version]);
            fputcsv($out, ['Status', $budget->status->label()]);
            fputcsv($out, ['Currency', $budget->currency]);
            fputcsv($out, ['Requested Advance', $budget->requested_advance_amount]);
            fputcsv($out, []);

            fputcsv($out, ['Item Code', 'Description', 'Category', 'Component', 'Qty', 'Frequency', 'Unit Cost', 'Total', 'Payment Mode', 'Cash Amount', 'Invoice Amount', 'Notes']);

            foreach ($budget->items as $item) {
                fputcsv($out, [
                    $item->item_code,
                    $item->description,
                    $item->category->name ?? '',
                    $item->component->name ?? '',
                    $item->qty,
                    $item->frequency,
                    $item->unit_cost,
                    $item->total,
                    $item->payment_mode->label(),
                    $item->cash_amount,
                    $item->invoice_amount,
                    $item->notes,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', '', '', 'Totals', $budget->total_overall, '', $budget->total_cash, $budget->total_invoice, '']);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── Internals ──────────────────────────────────────────────────

    private function currentBudgetOrAbort(Activity $activity): ActivityBudget
    {
        $budget = $activity->currentBudget()->first();
        abort_if(! $budget, 404);

        return $budget;
    }

    /**
     * Every transition action shares the same shape: run the workflow
     * call, catch an illegal-transition DomainException as a flash error
     * instead of a 500, otherwise flash success - mirrors
     * PaymentRequestController::transition().
     */
    private function transition(callable $action, Activity $activity, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('activities.budget.edit', $activity)->with('error', $e->getMessage());
        }

        return redirect()->route('activities.budget.edit', $activity)->with('success', $successMessage);
    }
}
