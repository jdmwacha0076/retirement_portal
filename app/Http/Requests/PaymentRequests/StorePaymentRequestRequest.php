<?php

namespace App\Http\Requests\PaymentRequests;

use App\Enums\PaymentType;
use App\Models\PaymentRequest;

class StorePaymentRequestRequest extends PaymentRequestFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', PaymentRequest::class);
    }

    protected function paymentType(): PaymentType
    {
        // The type-picker step puts the type on the URL
        // (/payment-requests/create/{type}); store() reads it from a
        // hidden field on the form itself, both funnel through here.
        return PaymentType::fromRouteSegment((string) $this->route('type'));
    }
}
