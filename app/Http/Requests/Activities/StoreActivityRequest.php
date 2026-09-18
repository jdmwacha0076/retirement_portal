<?php

namespace App\Http\Requests\Activities;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Activity::class);
    }

    public function rules(): array
    {
        return [
            'activity_type_id' => ['required', Rule::exists('activity_types', 'id')->where('is_active', true)],
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
