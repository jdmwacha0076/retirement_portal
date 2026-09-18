<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class ApproveActivityBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('activity')?->currentBudget;

        return $budget !== null && (bool) $this->user()?->can('approve', $budget);
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
