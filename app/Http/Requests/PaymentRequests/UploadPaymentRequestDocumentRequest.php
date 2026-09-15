<?php

namespace App\Http\Requests\PaymentRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadPaymentRequestDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('uploadDocument', $this->route('payment_request'));
    }

    public function rules(): array
    {
        $maxKb = config('payment_requests.documents.max_size_kb');
        $mimes = implode(',', config('payment_requests.documents.allowed_mimes'));

        return [
            'document_type' => ['required', Rule::in(array_keys(config('payment_requests.documents.types')))],
            'file' => ['required', 'file', "mimes:{$mimes}", "max:{$maxKb}"],
        ];
    }
}
