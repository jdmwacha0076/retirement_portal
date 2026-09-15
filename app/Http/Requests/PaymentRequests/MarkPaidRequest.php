<?php

namespace App\Http\Requests\PaymentRequests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('markPaid', $this->route('payment_request'));
    }

    public function rules(): array
    {
        /** @var \App\Models\PaymentRequest $paymentRequest */
        $paymentRequest = $this->route('payment_request');
        $type = $paymentRequest->payment_type;

        $rules = [
            'payment_date' => ['required', 'date'],
        ];

        if ($type->usesChequeNumber()) {
            $rules['cheque_number'] = ['required', 'string', 'max:100'];
        }

        if ($type->usesTransactionReference()) {
            $rules['transaction_reference'] = ['required', 'string', 'max:100'];
        }

        if ($type->usesCashier()) {
            $rules['cashier_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
