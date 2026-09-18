<?php

namespace App\Http\Requests\BudgetSettings;

use App\Enums\BudgetItemPaymentMode;
use App\Models\BudgetComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budgetComponent = $this->route('budget_component');

        return (bool) $this->user()?->can($budgetComponent ? 'update' : 'create', $budgetComponent ?? BudgetComponent::class);
    }

    public function rules(): array
    {
        $budgetComponent = $this->route('budget_component');

        return [
            'budget_category_id' => ['required', 'exists:budget_categories,id'],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('budget_components', 'code')
                    ->where('budget_category_id', $this->input('budget_category_id'))
                    ->ignore($budgetComponent?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_unit' => ['nullable', 'string', 'max:50'],
            'default_payment_mode' => ['nullable', Rule::enum(BudgetItemPaymentMode::class)],
            'requires_supporting_document' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * A checkbox that's left unchecked simply isn't present in the
     * request at all - normalize it to false rather than leaving the
     * field untouched, so unchecking it in the edit modal actually turns
     * the requirement off.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_supporting_document' => $this->boolean('requires_supporting_document'),
        ]);
    }
}
