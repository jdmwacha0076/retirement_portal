<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignActivityBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('activity')?->currentBudget;

        return $budget !== null && (bool) $this->user()?->can('assign', $budget);
    }

    public function rules(): array
    {
        $budget = $this->route('activity')->currentBudget;

        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'staff'])->where('status', 'active')),
                // Can't "assign" a budget to whoever already has it.
                Rule::notIn([$budget->current_assignee_id]),
            ],
            // Required - this single action now covers both "forward to
            // someone else" and "return to the creator for correction",
            // so every assignment carries a reason (see
            // ActivityBudgetWorkflowService::assign()'s docblock).
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.not_in' => 'This budget is already assigned to that person.',
        ];
    }
}
