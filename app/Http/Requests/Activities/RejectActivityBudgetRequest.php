<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class RejectActivityBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('activity')?->currentBudget;

        return $budget !== null && (bool) $this->user()?->can('reject', $budget);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
