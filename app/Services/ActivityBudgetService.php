<?php

namespace App\Services;

use App\Enums\ActivityBudgetStatus;
use App\Models\Activity;
use App\Models\ActivityBudget;
use App\Models\ActivityBudgetItem;
use App\Models\User;
use App\Support\ActivityBudgetCalculator;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every write to an ActivityBudget or its items goes through here -
 * mirrors ActivityService/PaymentRequestWorkflowService's shape.
 * Phase 4 scope only: create the version-1 Draft shell, and let the
 * creator build/edit its line items while it's editable
 * (ActivityBudget::canBeEditedBy() - Draft/Rejected always, or Assigned
 * while current_assignee_id points back at the creator, i.e. it was
 * assigned back to them for correction). Submit/assign/review/
 * approve/reject - and the version-2+ amendment flow - belong to Phases 6
 * and 7, which is why this class never touches status beyond the initial
 * 'draft'.
 */
class ActivityBudgetService
{
    /**
     * Creates the activity's first budget version. Guarded against
     * double-creation (a double form submit, or two tabs) with
     * lockForUpdate() inside a transaction - the unique(activity_id,
     * version) DB constraint is the last line of defence either way.
     *
     * @throws DomainException if a current budget already exists.
     */
    public function startDraft(Activity $activity, User $creator): ActivityBudget
    {
        return DB::transaction(function () use ($activity, $creator) {
            $existing = ActivityBudget::where('activity_id', $activity->id)
                ->current()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new DomainException('This activity already has a budget in progress.');
            }

            $nextVersion = (int) (ActivityBudget::where('activity_id', $activity->id)->max('version') ?? 0) + 1;

            $budget = ActivityBudget::create([
                'activity_id' => $activity->id,
                'version' => $nextVersion,
                'is_current' => true,
                'status' => ActivityBudgetStatus::Draft,
                'currency' => $activity->currency,
                'created_by' => $creator->id,
            ]);

            $budget->history()->create([
                'action' => 'started',
                'performed_by' => $creator->id,
                'to_status' => ActivityBudgetStatus::Draft->value,
                'comment' => "Budget v{$nextVersion} started.",
            ]);

            return $budget;
        });
    }

    /**
     * Replaces the budget's line items wholesale from the builder's
     * submitted array (a "diff and replace" write: rows missing from
     * $itemsData are deleted, the rest are updated/created), recomputes
     * every line's total/cash/invoice split and every line's item_code,
     * then re-persists the three budget-level totals. All inside one
     * transaction so a mid-save failure never leaves stale totals next
     * to a changed item set.
     *
     * @param  array<int, array{id?: int, budget_category_id: int, budget_component_id: ?int, description: string, unit: ?string, qty: float, frequency: int, unit_cost: float, payment_mode: string, cash_amount: ?float, invoice_amount: ?float, notes: ?string}>  $itemsData
     *
     * @throws DomainException if the budget is no longer in an
     *         editable-by-creator state (e.g. someone submitted it in
     *         another tab while this one was open).
     */
    public function saveItems(ActivityBudget $budget, array $itemsData, User $actor, ?string $budgetCode, ?float $requestedAdvanceAmount): ActivityBudget
    {
        return DB::transaction(function () use ($budget, $itemsData, $actor, $budgetCode, $requestedAdvanceAmount) {
            $budget = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            if (! $budget->canBeEditedBy($actor) && ! $actor->isAdmin()) {
                throw new DomainException('This budget can no longer be edited in its current status.');
            }

            $keepIds = [];

            foreach (array_values($itemsData) as $index => $row) {
                $mode = \App\Enums\BudgetItemPaymentMode::from($row['payment_mode']);
                $total = ActivityBudgetCalculator::itemTotal((float) $row['qty'], (float) $row['unit_cost'], (int) $row['frequency']);
                [$cash, $invoice] = ActivityBudgetCalculator::splitAmounts(
                    $mode,
                    $total,
                    (float) ($row['cash_amount'] ?? 0),
                    (float) ($row['invoice_amount'] ?? 0)
                );

                $attributes = [
                    'activity_budget_id' => $budget->id,
                    'budget_category_id' => $row['budget_category_id'],
                    'budget_component_id' => $row['budget_component_id'] ?: null,
                    'description' => $row['description'],
                    'unit' => $row['unit'] ?: null,
                    'qty' => $row['qty'],
                    'frequency' => $row['frequency'],
                    'unit_cost' => $row['unit_cost'],
                    'total' => $total,
                    'payment_mode' => $mode,
                    'cash_amount' => $cash,
                    'invoice_amount' => $invoice,
                    'notes' => $row['notes'] ?: null,
                    'sort_order' => $index,
                ];

                if (! empty($row['id'])) {
                    $item = ActivityBudgetItem::where('activity_budget_id', $budget->id)->find($row['id']);
                    if ($item) {
                        $item->update($attributes);
                        $keepIds[] = $item->id;

                        continue;
                    }
                }

                $item = ActivityBudgetItem::create($attributes);
                $keepIds[] = $item->id;
            }

            ActivityBudgetItem::where('activity_budget_id', $budget->id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            $this->assignItemCodes($budget);

            $items = $budget->items()->get();
            $totals = ActivityBudgetCalculator::budgetTotals($items);

            $budget->update($totals + [
                'budget_code' => $budgetCode ?: null,
                'requested_advance_amount' => $requestedAdvanceAmount ?? 0,
            ]);

            $budget->history()->create([
                'action' => 'items_updated',
                'performed_by' => $actor->id,
                'to_status' => $budget->status->value,
                'comment' => 'Budget items saved ('.$items->count().' line'.($items->count() === 1 ? '' : 's').').',
            ]);

            return $budget->fresh(['items']);
        });
    }

    /**
     * Assigns each line's item_code ("A1", "A2", "B1", ...) purely from
     * the order categories first appear while scanning this budget's
     * items in their own sort_order - per the approved architecture and
     * the budget_categories migration's own docblock ("assigned per-
     * budget based on the order categories appear inside that specific
     * budget"). A category's letter is NOT its master-data sort_order or
     * any fixed mapping - it's compacted to whichever categories are
     * actually used here, in the order a preparer happened to enter them.
     */
    private function assignItemCodes(ActivityBudget $budget): void
    {
        $items = $budget->items()->orderBy('sort_order')->get();

        $categoryLetters = [];
        $categoryCounters = [];

        foreach ($items as $item) {
            $categoryId = $item->budget_category_id;

            if (! array_key_exists($categoryId, $categoryLetters)) {
                $categoryLetters[$categoryId] = $this->letterFor(count($categoryLetters));
                $categoryCounters[$categoryId] = 0;
            }

            $categoryCounters[$categoryId]++;

            $item->update([
                'item_code' => $categoryLetters[$categoryId].$categoryCounters[$categoryId],
            ]);
        }
    }

    /**
     * 0-indexed position -> spreadsheet-style letter (A, B, ... Z, AA,
     * AB, ...) - a budget with more than 26 distinct categories is not a
     * realistic scenario, but this stays correct if it ever happens.
     */
    private function letterFor(int $position): string
    {
        $letter = '';
        $position++;

        while ($position > 0) {
            $position--;
            $letter = chr(65 + ($position % 26)).$letter;
            $position = intdiv($position, 26);
        }

        return $letter;
    }
}
