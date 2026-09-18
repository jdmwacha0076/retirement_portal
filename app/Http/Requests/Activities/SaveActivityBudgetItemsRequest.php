<?php

namespace App\Http\Requests\Activities;

use App\Enums\BudgetItemPaymentMode;
use App\Support\ActivityBudgetCalculator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveActivityBudgetItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('activity')?->currentBudget;

        return $budget !== null && (bool) $this->user()?->can('update', $budget);
    }

    public function rules(): array
    {
        return [
            'budget_code' => ['nullable', 'string', 'max:100'],
            'requested_advance_amount' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            // No is_active filter here (unlike the "which options does the
            // builder offer for a NEW line" query in the controller) - a
            // line already pointing at a category/component that's since
            // been deactivated must still be re-saveable untouched. Only
            // existence is required; steering new choices toward active
            // master data is the UI's job, not this rule's.
            'items.*.id' => ['nullable', 'integer'],
            'items.*.budget_category_id' => ['required', Rule::exists('budget_categories', 'id')],
            'items.*.budget_component_id' => ['nullable', Rule::exists('budget_components', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'items.*.frequency' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.payment_mode' => ['required', Rule::enum(BudgetItemPaymentMode::class)],
            'items.*.cash_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one budget line before saving.',
            'items.*.description.required' => 'Every line needs a description.',
            'items.*.budget_category_id.required' => 'Every line needs a category.',
            'items.*.unit_cost.required' => 'Every line needs a unit cost.',
        ];
    }

    /**
     * A Split line's cash+invoice must add up to the (server-computed,
     * never client-trusted) line total - Cash/Invoice lines don't need
     * this check since ActivityBudgetCalculator::splitAmounts() forces
     * their split regardless of what was posted.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            foreach ((array) $this->input('items', []) as $index => $row) {
                if (($row['payment_mode'] ?? null) !== BudgetItemPaymentMode::Split->value) {
                    continue;
                }

                $qty = (float) ($row['qty'] ?? 0);
                $unitCost = (float) ($row['unit_cost'] ?? 0);
                $frequency = (int) ($row['frequency'] ?? 0);
                $total = ActivityBudgetCalculator::itemTotal($qty, $unitCost, $frequency);

                $cash = (float) ($row['cash_amount'] ?? 0);
                $invoice = (float) ($row['invoice_amount'] ?? 0);

                if (abs(($cash + $invoice) - $total) > 0.01) {
                    $validator->errors()->add(
                        "items.{$index}.cash_amount",
                        'Cash + Invoice must add up to the line total for a Split line.'
                    );
                }
            }
        });
    }
}
