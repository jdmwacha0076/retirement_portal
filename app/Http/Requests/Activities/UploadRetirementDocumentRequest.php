<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Covers both general (retirement-level) and per-item receipt uploads -
 * activity_retirement_item_id is the only thing that distinguishes them
 * (null = general), mirroring RetirementDocument's own docblock.
 */
class UploadRetirementDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $retirement = $this->route('activity')?->currentBudget?->currentRetirement;

        return $retirement !== null && (bool) $this->user()?->can('uploadDocument', $retirement);
    }

    public function rules(): array
    {
        $retirement = $this->route('activity')->currentBudget->currentRetirement;
        $maxKb = config('activity_budgets.documents.max_size_kb');
        $mimes = implode(',', config('activity_budgets.documents.allowed_mimes'));

        return [
            'activity_retirement_item_id' => [
                'nullable',
                'integer',
                Rule::exists('activity_retirement_items', 'id')->where('activity_retirement_id', $retirement->id),
            ],
            'document_type' => ['required', Rule::in(array_keys(config('activity_budgets.documents.types')))],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'receipt_date' => ['nullable', 'date'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'document_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
        ];
    }
}
