{{--
    Shared A4 voucher body, included by both print/standard.blade.php and
    print/consultancy.blade.php. The two wrapper views exist separately
    (matching PaymentType::printView()) because the paper forms they
    reproduce are genuinely different documents - this partial only
    factors out the header/footer/signature chrome that both share, the
    field-grid and amount sections below branch on
    $type->requiresConsultancyDetails() so each still renders its own
    paper form's fields.
--}}
@php
    $type = $paymentRequest->payment_type;
    $consultancy = $paymentRequest->consultancyDetails;
@endphp

<div class="voucher-toolbar">
    <button type="button" class="voucher-print-btn" onclick="window.print()">
        Print Voucher
    </button>
    <a href="{{ route('payment-requests.show', $paymentRequest) }}">
        Back to Request
    </a>
</div>

<div class="voucher-sheet">

    <div class="voucher-header">
        <div>
            <p class="voucher-org-name">Praxis for Health and Development</p>
            <p class="voucher-org-sub">Payment Request &amp; Voucher</p>
        </div>

        <div class="voucher-title-block">
            <p class="voucher-title">{{ $type->shortLabel() }} Voucher</p>
            <p class="voucher-ref">{{ $paymentRequest->displayReference() }}</p>
            <p class="voucher-status-line">{{ $paymentRequest->status->label() }}</p>
        </div>
    </div>

    <div class="voucher-field-grid">

        @if ($type->requiresConsultancyDetails() && $consultancy)
            <div class="voucher-field">
                <span class="voucher-field-label">Consultant's Name</span>
                <span class="voucher-field-value">{{ $consultancy->consultant_name }}</span>
            </div>

            <div class="voucher-field">
                <span class="voucher-field-label">TIN Number</span>
                <span class="voucher-field-value">{{ $consultancy->tin_number }}</span>
            </div>

            <div class="voucher-field">
                <span class="voucher-field-label">Client's Name</span>
                <span class="voucher-field-value">{{ $consultancy->client_name }}</span>
            </div>

            <div class="voucher-field">
                <span class="voucher-field-label">Date</span>
                <span class="voucher-field-value">{{ $paymentRequest->payment_date->format('d M Y') }}</span>
            </div>
        @else
            <div class="voucher-field">
                <span class="voucher-field-label">Payee's Name</span>
                <span class="voucher-field-value">{{ $paymentRequest->payee_name }}</span>
            </div>

            <div class="voucher-field">
                <span class="voucher-field-label">Date</span>
                <span class="voucher-field-value">{{ $paymentRequest->payment_date->format('d M Y') }}</span>
            </div>
        @endif

        @if ($type->usesChequeNumber())
            <div class="voucher-field">
                <span class="voucher-field-label">Cheque Number</span>
                <span class="voucher-field-value">{{ $paymentRequest->cheque_number ?: '—' }}</span>
            </div>
        @endif

        @if ($type->usesTransactionReference())
            <div class="voucher-field">
                <span class="voucher-field-label">Transaction Reference</span>
                <span class="voucher-field-value">{{ $paymentRequest->transaction_reference ?: '—' }}</span>
            </div>
        @endif

        @if ($type->usesCashier())
            <div class="voucher-field">
                <span class="voucher-field-label">Cashier</span>
                <span class="voucher-field-value">{{ $paymentRequest->cashier_name ?: '—' }}</span>
            </div>
        @endif

        <div class="voucher-field voucher-field--full">
            <span class="voucher-field-label">Payment Description</span>
            <span class="voucher-field-value">{{ $paymentRequest->description }}</span>
        </div>

    </div>

    @if ($type->requiresConsultancyDetails() && $consultancy)
        <table class="voucher-calc-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount ({{ $paymentRequest->currency }})</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Consultancy Fee</td>
                    <td>{{ number_format((float) $consultancy->consultancy_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Withholding Tax ({{ number_format((float) $consultancy->withholding_tax_percentage, 2) }}%)</td>
                    <td>&minus; {{ number_format((float) $consultancy->withholding_tax_amount, 2) }}</td>
                </tr>
                <tr class="voucher-calc-total">
                    <td>Amount Due to Consultant</td>
                    <td>{{ number_format((float) $consultancy->amount_due, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="voucher-amount-box">
        <span class="voucher-amount-label">Amount Payable</span>
        <span class="voucher-amount-value">{{ $paymentRequest->formattedAmount() }}</span>
        <span class="voucher-words">{{ $paymentRequest->amount_in_words }}</span>
    </div>

    <p class="voucher-section-heading">Authorization</p>

    <div class="voucher-signatures">
        <div class="voucher-signature-box">
            <div class="voucher-signature-line"></div>
            <div class="voucher-signature-label">Prepared By</div>
            <div class="voucher-signature-name">{{ $paymentRequest->creator->name ?? '—' }}</div>
        </div>

        <div class="voucher-signature-box">
            <div class="voucher-signature-line"></div>
            <div class="voucher-signature-label">Reviewed By</div>
            <div class="voucher-signature-name">{{ $paymentRequest->currentAssignee->name ?? '—' }}</div>
        </div>

        <div class="voucher-signature-box">
            <div class="voucher-signature-line"></div>
            <div class="voucher-signature-label">Approved By</div>
            <div class="voucher-signature-name">{{ $paymentRequest->approver->name ?? '—' }}</div>
        </div>

        <div class="voucher-signature-box">
            <div class="voucher-signature-line"></div>
            <div class="voucher-signature-label">Received By</div>
            <div class="voucher-signature-name">&nbsp;</div>
        </div>
    </div>

    <p class="voucher-footer-note">
        Generated from the Retirement Portal &middot; {{ now()->format('d M Y, g:ia') }}
        @if ($paymentRequest->reference_number)
            &middot; {{ $paymentRequest->displayReference() }}
        @endif
    </p>

</div>
