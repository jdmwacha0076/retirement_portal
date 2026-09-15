<?php

namespace App\Http\Requests\PaymentRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('assign', $this->route('payment_request'));
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'staff'])->where('status', 'active')),
                // Can't assign a request to itself sitting with the same person.
                Rule::notIn([$this->route('payment_request')->current_assignee_id]),
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.not_in' => 'This request is already assigned to that person.',
        ];
    }
}
