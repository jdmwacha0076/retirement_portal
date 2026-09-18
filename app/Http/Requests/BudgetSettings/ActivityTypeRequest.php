<?php

namespace App\Http\Requests\BudgetSettings;

use App\Models\ActivityType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by store() and update() - the route parameter's presence tells
 * us which one we're in, same technique used for the Payment Request
 * module's authorize() checks.
 */
class ActivityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activityType = $this->route('activity_type');

        return (bool) $this->user()?->can($activityType ? 'update' : 'create', $activityType ?? ActivityType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
