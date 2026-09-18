<?php

namespace App\Support;

use App\Enums\BudgetItemPaymentMode;
use Illuminate\Support\Collection;

/**
 * The single place that computes budget-item and budget-level money
 * figures - referenced by both ActivityBudgetItem's and ActivityBudget's
 * Phase 1 docblocks as "a later phase's ActivityBudgetCalculator support
 * class". Nothing from the client is ever trusted for these numbers:
 * ActivityBudgetService always recalculates through here before saving,
 * even though the builder UI mirrors the same arithmetic in JS for a
 * live preview.
 *
 * ActivityRetirementService reuses itemTotal()/splitAmounts() unchanged
 * for a retirement line's ACTUAL figures - the arithmetic (qty * unit_cost
 * * frequency, then Cash/Invoice/Split) is identical whether the numbers
 * being crunched are what was approved or what was actually spent, per
 * BudgetItemPaymentMode's own docblock ("how a single budget/retirement
 * line is funded"). retirementTotals() below is the retirement-level
 * counterpart to budgetTotals(), reading the actual_* columns instead.
 */
class ActivityBudgetCalculator
{
    /**
     * qty * unit_cost * frequency, per the approved architecture. Rounded
     * to 2dp since every money column in the schema is DECIMAL(*, 2).
     */
    public static function itemTotal(float $qty, float $unitCost, int $frequency): float
    {
        return round($qty * $unitCost * $frequency, 2);
    }

    /**
     * Splits a line's total into [cash, invoice] for the given payment
     * mode. Cash/Invoice force the full amount onto their own side and
     * zero the other (never null - zero is the semantically correct
     * "no invoice component on this line", per the migration's own
     * docblock). Split trusts the caller-provided pair, which
     * SaveActivityBudgetItemsRequest has already validated sums to the
     * line's total.
     *
     * @return array{0: float, 1: float} [cash_amount, invoice_amount]
     */
    public static function splitAmounts(BudgetItemPaymentMode $mode, float $total, float $cashAmount, float $invoiceAmount): array
    {
        return match ($mode) {
            BudgetItemPaymentMode::Cash => [$total, 0.0],
            BudgetItemPaymentMode::Invoice => [0.0, $total],
            BudgetItemPaymentMode::Split => [round($cashAmount, 2), round($invoiceAmount, 2)],
        };
    }

    /**
     * Sums a set of (already-calculated) line items into the three
     * budget-level totals persisted on activity_budgets for fast
     * listing/dashboard queries.
     *
     * @param  Collection<int, array{cash_amount: float, invoice_amount: float, total: float}>|\Illuminate\Database\Eloquent\Collection  $items
     * @return array{total_cash: float, total_invoice: float, total_overall: float}
     */
    public static function budgetTotals(iterable $items): array
    {
        $items = Collection::make($items);

        $cash = round((float) $items->sum(fn ($item) => is_array($item) ? $item['cash_amount'] : $item->cash_amount), 2);
        $invoice = round((float) $items->sum(fn ($item) => is_array($item) ? $item['invoice_amount'] : $item->invoice_amount), 2);

        return [
            'total_cash' => $cash,
            'total_invoice' => $invoice,
            'total_overall' => round($cash + $invoice, 2),
        ];
    }

    /**
     * Sums a set of ActivityRetirementItem rows (already recalculated by
     * ActivityRetirementService) into the three retirement-level totals
     * persisted on activity_retirements - same shape as budgetTotals(),
     * just reading the actual_* columns instead of the approved ones.
     *
     * @param  Collection<int, array{actual_cash_amount: float, actual_invoice_amount: float, actual_total: float}>|\Illuminate\Database\Eloquent\Collection  $items
     * @return array{total_actual_cash: float, total_actual_invoice: float, total_actual_overall: float}
     */
    public static function retirementTotals(iterable $items): array
    {
        $items = Collection::make($items);

        $cash = round((float) $items->sum(fn ($item) => is_array($item) ? $item['actual_cash_amount'] : $item->actual_cash_amount), 2);
        $invoice = round((float) $items->sum(fn ($item) => is_array($item) ? $item['actual_invoice_amount'] : $item->actual_invoice_amount), 2);

        return [
            'total_actual_cash' => $cash,
            'total_actual_invoice' => $invoice,
            'total_actual_overall' => round($cash + $invoice, 2),
        ];
    }
}
