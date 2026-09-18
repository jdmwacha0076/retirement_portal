<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reference Number Formats
    |--------------------------------------------------------------------------
    |
    | Activities and Retirements each get their own permanent, atomically
    | generated reference (ACT/{year}/{seq}, RET/{year}/{seq}) via the
    | shared reference_counters table - see App\Support\ReferenceGenerator
    | (added in a later phase) and App\Models\ReferenceCounter.
    |
    */

    'activity_reference_prefix' => env('ACTIVITY_REFERENCE_PREFIX', 'ACT'),
    'retirement_reference_prefix' => env('ACTIVITY_RETIREMENT_REFERENCE_PREFIX', 'RET'),

    'reference_seq_length' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default / Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Kept as its own list (rather than reusing payment_requests.php)
    | because activity budgets are commonly prepared in donor-grant
    | currencies (USD in the sample budget) that may not match Praxis's
    | day-to-day payment-voucher currencies. Extend this array only -
    | nothing in the schema needs to change to support EUR/GBP/KES/NGN
    | later.
    |
    */

    'default_currency' => env('ACTIVITY_BUDGET_DEFAULT_CURRENCY', 'USD'),

    'supported_currencies' => ['TZS', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Supporting Documents / Receipts
    |--------------------------------------------------------------------------
    */

    'documents' => [
        'disk' => env('ACTIVITY_RETIREMENT_DOCUMENTS_DISK', 'local'),
        'max_size_kb' => 10240, // 10 MB
        'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'types' => [
            'receipt' => 'Receipt',
            'invoice' => 'Invoice',
            'boarding_pass' => 'Boarding Pass',
            'attendance_sheet' => 'Attendance Sheet',
            'activity_report' => 'Activity Report',
            'allowance_sheet' => 'Signed Allowance Sheet',
            'deposit_slip' => 'Deposit / Return Slip',
            'other' => 'Other Supporting Document',
        ],
    ],

];
