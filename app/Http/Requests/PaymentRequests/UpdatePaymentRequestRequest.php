<?php

namespace App\Http\Requests\PaymentRequests;

use App\Enums\PaymentType;

class UpdatePaymentRequestRequest extends PaymentRequestFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('payment_request'));
    }

    protected function paymentType(): PaymentType
    {
        // The type is fixed at creation and never changes on edit - read
        // from the model being edited, not from request input, so it
        // can't be tampered with via a stray form field.
        return $this->route('payment_request')->payment_type;
    }
}
