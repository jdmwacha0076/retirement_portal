<?php

namespace App\Services;

use App\Enums\ActivityBudgetStatus;
use App\Enums\ActivityRetirementStatus;
use App\Enums\BudgetItemPaymentMode;
use App\Models\ActivityBudget;
use App\Models\ActivityRetirement;
use App\Models\ActivityRetirementItem;
use App\Models\User;
use App\Support\ActivityBudgetCalculator;
use App\Support\ReferenceGenerator;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Every write to an ActivityRetirement or its items goes through here -
 * mirrors ActivityBudgetService's shape exactly. This first phase is
 * scoped to data entry only: start() seeds one ActivityRetirementItem per
 * approved ActivityBudgetItem (the FK is mandatory and unique per
 * retirement - see that model's own docblock), and saveItems() lets the
 * creator fill in what was actually spent against each approved line,
 * plus upload receipts (handled by ActivityRetirementController's
 * document endpoints, not this class). Submit/assign/review/approve/
 * reject/cancel - the workflow half, mirroring
 * ActivityBudgetWorkflowService - is a later phase, exactly like the
 * Budget module shipped its builder (Phase 4) well before its own
 * workflow (Phase 6).
 *
 * total_advanced is taken directly from the approved budget's
 * requested_advance_amount, NOT summed from activity_advance_disbursements
 * - per the user's own decision, Payment Request integration for
 * recording formal disbursement transactions is not being built; an
 * Approved budget already means "the requested advance has been
 * provided" (the same business meaning established when Approve was
 * built), so that figure is what a retirement reconciles against.
 */
class ActivityRetirementService
{
    /**
     * Creates the retirement shell (Draft) for one approved budget
     * version, seeded with one item row per approved budget line so every
     * approved line must be accounted for. Guarded against double-
     * creation (a double form submit, or two tabs) with lockForUpdate()
     * inside a transaction, same pattern as
     * ActivityBudgetService::startDraft().
     *
     * @throws DomainException if the budget isn't Approved, or a
     *         non-cancelled retirement already exists for it.
     */
    public function start(ActivityBudget $budget, User $creator): ActivityRetirement
    {
        return DB::transaction(function () use ($budget, $creator) {
            $budget = ActivityBudget::whereKey($budget->id)->lockForUpdate()->firstOrFail();

            if ($budget->status !== ActivityBudgetStatus::Approved) {
                throw new DomainException('Only an approved budget can be retired.');
            }

            $existing = $budget->retirements()
                ->where('status', '!=', ActivityRetirementStatus::Cancelled->value)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new DomainException('This budget already has a retirement in progress.');
            }

            $reference = ReferenceGenerator::next(
                'activity_retirement',
                config('activity_budgets.retirement_reference_prefix'),
                config('activity_budgets.reference_seq_length')
            );

            $retirement = ActivityRetirement::create([
                'reference' => $reference,
                'activity_budget_id' => $budget->id,
                'status' => ActivityRetirementStatus::Draft,
                'created_by' => $creator->id,
                'total_advanced' => $budget->requested_advance_amount,
            ]);

            // Seed one line per approved budget item, pre-filled with the
            // approved figures as a starting point - the creator only
            // needs to change what actually differs, rather than
            // re-entering everything from a blank row.
            foreach ($budget->items()->get() as $approvedItem) {
                $retirement->items()->create([
                    'activity_budget_item_id' => $approvedItem->id,
                    'actual_qty' => $approvedItem->qty,
                    'actual_unit_cost' => $approvedItem->unit_cost,
                    'actual_frequency' => $approvedItem->frequency,
                    'actual_payment_mode' => $approvedItem->payment_mode,
                    'actual_cash_amount' => $approvedItem->cash_amount,
                    'actual_invoice_amount' => $approvedItem->invoice_amount,
                    'actual_total' => $approvedItem->total,
                ]);
            }

            $this->recalculateTotals($retirement);

            $retirement->history()->create([
                'action' => 'started',
                'performed_by' => $creator->id,
                'to_status' => ActivityRetirementStatus::Draft->value,
                'comment' => "Retirement {$reference} started.",
            ]);

            return $retirement->fresh(['items']);
        });
    }

    /**
     * Updates the ACTUAL figures on each of the retirement's existing
     * item rows - never creates or deletes rows, unlike
     * ActivityBudgetService::saveItems()'s "diff and replace", since every
     * row is a mandatory 1:1 accounting of one approved budget line (see
     * ActivityRetirementItem's own docblock). Recomputes each line's
     * actual_total/split exactly like a budget line, then re-persists the
     * three retirement-level actual totals plus the running unspent/
     * reimbursement preview.
     *
     * @param  array<int, array{id: int, actual_qty: float, actual_frequency: int, actual_unit_cost: float, actual_payment_mode: string, actual_cash_amount: ?float, actual_invoice_amount: ?float, payment_mode_change_justification: ?string, variance_justification: ?string, notes: ?string}>  $itemsData
     *
     * @throws DomainException if the retirement is no longer in an
     *         editable-by-creator state.
     */
    public function saveItems(ActivityRetirement $retirement, array $itemsData, User $actor): ActivityRetirement
    {
        return DB::transaction(function () use ($retirement, $itemsData, $actor) {
            $retirement = ActivityRetirement::whereKey($retirement->id)->lockForUpdate()->firstOrFail();

            if (! $retirement->status->isEditableByCreator() && ! $actor->isAdmin()) {
                throw new DomainException('This retirement can no longer be edited in its current status.');
            }

            foreach ($itemsData as $row) {
                $item = ActivityRetirementItem::where('activity_retirement_id', $retirement->id)->find($row['id']);

                if (! $item) {
                    continue;
                }

                $mode = BudgetItemPaymentMode::from($row['actual_payment_mode']);
                $total = ActivityBudgetCalculator::itemTotal(
                    (float) $row['actual_qty'],
                    (float) $row['actual_unit_cost'],
                    (int) $row['actual_frequency']
                );
                [$cash, $invoice] = ActivityBudgetCalculator::splitAmounts(
                    $mode,
                    $total,
                    (float) ($row['actual_cash_amount'] ?? 0),
                    (float) ($row['actual_invoice_amount'] ?? 0)
                );

                $item->update([
                    'actual_qty' => $row['actual_qty'],
                    'actual_unit_cost' => $row['actual_unit_cost'],
                    'actual_frequency' => $row['actual_frequency'],
                    'actual_payment_mode' => $mode,
                    'actual_cash_amount' => $cash,
                    'actual_invoice_amount' => $invoice,
                    'actual_total' => $total,
                    'payment_mode_change_justification' => $row['payment_mode_change_justification'] ?? null,
                    'variance_justification' => $row['variance_justification'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            $this->recalculateTotals($retirement);

            $retirement->history()->create([
                'action' => 'items_updated',
                'performed_by' => $actor->id,
                'to_status' => $retirement->status->value,
                'comment' => 'Retirement expenses saved.',
            ]);

            return $retirement->fresh(['items']);
        });
    }

    /**
     * Re-persists the three total_actual_* columns plus a running
     * unspent/reimbursement preview, every time items are seeded or
     * saved while the retirement is still editable. This is NOT the
     * "frozen" figure the activity_retirements migration's docblock
     * describes (that freezing happens once a later phase's workflow
     * moves the retirement to Approved/ReconciliationPending) - until
     * then, recomputing on every save is exactly what should happen, the
     * same way a budget's totals are recalculated on every
     * ActivityBudgetService::saveItems() call.
     */
    private function recalculateTotals(ActivityRetirement $retirement): void
    {
        $items = $retirement->items()->get();
        $totals = ActivityBudgetCalculator::retirementTotals($items);

        $totalAdvanced = (float) $retirement->total_advanced;
        $totalActual = $totals['total_actual_overall'];

        $retirement->update($totals + [
            'unspent_advance_amount' => round(max($totalAdvanced - $totalActual, 0), 2),
            'reimbursement_due_amount' => round(max($totalActual - $totalAdvanced, 0), 2),
        ]);
    }
}
