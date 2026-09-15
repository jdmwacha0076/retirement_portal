<?php

namespace App\Http\Requests\PaymentRequests;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared rule-building for Store/Update - both create and edit use
 * exactly the same field set per type, so the rules live in one place.
 * Subclasses only need to say which PaymentType the request is for and
 * how to authorize it.
 */
abstract class PaymentRequestFormRequest extends FormRequest
{
    abstract protected function paymentType(): PaymentType;

    public function rules(): array
    {
        $type = $this->paymentType();

        $rules = [
            'payment_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:2000'],
            'currency' => ['required', 'string', 'in:'.implode(',', config('payment_requests.supported_currencies'))],
        ];

        if ($type->requiresConsultancyDetails()) {
            $rules += [
                'consultant_name' => ['required', 'string', 'max:255'],
                'tin_number' => ['required', 'string', 'max:50'],
                'client_name' => ['required', 'string', 'max:255'],
                'consultancy_fee' => ['required', 'numeric', 'min:0.01'],
                'withholding_tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ];
        } else {
            $rules += [
                'payee_name' => ['required', 'string', 'max:255'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ];
        }

        if ($type->usesChequeNumber()) {
            $rules['cheque_number'] = ['nullable', 'string', 'max:100'];
        }

        if ($type->usesTransactionReference()) {
            $rules['transaction_reference'] = ['nullable', 'string', 'max:100'];
        }

        if ($type->usesCashier()) {
            $rules['cashier_name'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'tin_number' => 'TIN number',
            'withholding_tax_percentage' => 'withholding tax percentage',
        ];
    }
}
