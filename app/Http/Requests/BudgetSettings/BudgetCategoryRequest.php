<?php

namespace App\Http\Requests\BudgetSettings;

use App\Models\BudgetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budgetCategory = $this->route('budget_category');

        return (bool) $this->user()?->can($budgetCategory ? 'update' : 'create', $budgetCategory ?? BudgetCategory::class);
    }

    public function rules(): array
    {
        $budgetCategory = $this->route('budget_category');

        return [
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('budget_categories', 'code')->ignore($budgetCategory?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
