<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reference Number Format
    |--------------------------------------------------------------------------
    |
    | Applied at first submission only (see PaymentRequestWorkflowService::
    | submit()) - a draft never has a permanent reference. {year} and
    | {seq} are replaced; {seq} is zero-padded to seq_length digits.
    |
    */

    'reference_prefix' => env('PAYMENT_REQUEST_REFERENCE_PREFIX', 'PRX/PAY'),

    'reference_seq_length' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */

    'default_currency' => env('PAYMENT_REQUEST_DEFAULT_CURRENCY', 'TZS'),

    'supported_currencies' => ['TZS', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Withholding Tax
    |--------------------------------------------------------------------------
    |
    | Default percentage pre-filled on consultancy forms. Stored per-request
    | (payment_request_consultancy_details.withholding_tax_percentage), so
    | changing this later never rewrites the rate on an existing request -
    | it only changes the default offered on the *next* new form.
    |
    */

    'default_withholding_tax_percentage' => env('PAYMENT_REQUEST_DEFAULT_WHT_PERCENTAGE', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Supporting Documents
    |--------------------------------------------------------------------------
    */

    'documents' => [
        'disk' => env('PAYMENT_REQUEST_DOCUMENTS_DISK', 'local'),
        'max_size_kb' => 10240, // 10 MB
        'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'types' => [
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'quotation' => 'Quotation',
            'contract' => 'Contract',
            'purchase_order' => 'Purchase Order',
            'approval' => 'Approval Document',
            'other' => 'Other Supporting Document',
        ],
    ],

];
