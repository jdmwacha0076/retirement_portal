<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            // No is_active filter here (unlike Store) - editing must not
            // force the type to change just because it was deactivated
            // after this activity was first registered.
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'accounting_code' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'program' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'participant_count' => ['nullable', 'integer', 'min:1'],
            'coordinator_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:4000'],
            'currency' => ['required', Rule::in(config('activity_budgets.supported_currencies'))],
        ];
    }
}
