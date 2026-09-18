<?php

namespace App\Http\Requests\Activities;

use App\Enums\BudgetItemPaymentMode;
use App\Models\ActivityRetirementItem;
use App\Support\ActivityBudgetCalculator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveActivityRetirementItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $retirement = $this->route('activity')?->currentBudget?->currentRetirement;

        return $retirement !== null && (bool) $this->user()?->can('update', $retirement);
    }

    /**
     * actual_qty/actual_unit_cost/actual_frequency all allow 0, unlike
     * SaveActivityBudgetItemsRequest's own min:0.01/min:1 - an approved
     * line that ended up costing nothing (never actually used) is a
     * legitimate outcome to report here, whereas a planned budget line
     * must represent a genuine planned expense.
     */
    public function rules(): array
    {
        $retirement = $this->route('activity')->currentBudget->currentRetirement;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('activity_retirement_items', 'id')->where('activity_retirement_id', $retirement->id),
            ],
            'items.*.actual_qty' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'items.*.actual_frequency' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.actual_unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.actual_payment_mode' => ['required', Rule::enum(BudgetItemPaymentMode::class)],
            'items.*.actual_cash_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.actual_invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.payment_mode_change_justification' => ['nullable', 'string', 'max:1000'],
            'items.*.variance_justification' => ['nullable', 'string', 'max:1000'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Three cross-field checks per line, mirroring
     * SaveActivityBudgetItemsRequest's Split-sum check plus two more that
     * only make sense for a retirement: a justification is required
     * whenever the actual figures depart from what was approved -
     * exceeding the approved total, or paying by a different mode -
     * per the activity_retirement_items migration's own docblock. Under-
     * budget never requires justification.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $rows = (array) $this->input('items', []);

            $retirementItems = ActivityRetirementItem::with('activityBudgetItem')
                ->whereIn('id', array_filter(array_column($rows, 'id')))
                ->get()
                ->keyBy('id');

            foreach ($rows as $index => $row) {
                $qty = (float) ($row['actual_qty'] ?? 0);
                $unitCost = (float) ($row['actual_unit_cost'] ?? 0);
                $frequency = (int) ($row['actual_frequency'] ?? 0);
                $total = ActivityBudgetCalculator::itemTotal($qty, $unitCost, $frequency);
                $mode = $row['actual_payment_mode'] ?? null;

                if ($mode === BudgetItemPaymentMode::Split->value) {
                    $cash = (float) ($row['actual_cash_amount'] ?? 0);
                    $invoice = (float) ($row['actual_invoice_amount'] ?? 0);

                    if (abs(($cash + $invoice) - $total) > 0.01) {
                        $validator->errors()->add(
                            "items.{$index}.actual_cash_amount",
                            'Cash + Invoice must add up to the actual total for a Split line.'
                        );
                    }
                }

                $approvedItem = $retirementItems->get($row['id'] ?? null)?->activityBudgetItem;

                if (! $approvedItem) {
                    continue;
                }

                if ($total > (float) $approvedItem->total + 0.01 && blank($row['variance_justification'] ?? null)) {
                    $validator->errors()->add(
                        "items.{$index}.variance_justification",
                        'Explain why actual spend exceeds the approved amount for this line.'
                    );
                }

                if ($mode !== null && $mode !== $approvedItem->payment_mode->value && blank($row['payment_mode_change_justification'] ?? null)) {
                    $validator->errors()->add(
                        "items.{$index}.payment_mode_change_justification",
                        'Explain why the payment mode changed from what was approved for this line.'
                    );
                }
            }
        });
    }
}
