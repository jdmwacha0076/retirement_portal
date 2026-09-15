@php
    // $type (PaymentType) is always passed in. $paymentRequest is only
    // present on the edit form - null on create, so old(...) falls back
    // to the model's current value on edit and to a blank/default on
    // create, in one expression per field.
    $pr = $paymentRequest ?? null;
    $consultancy = $pr?->consultancyDetails;
@endphp

<div class="form-section-card">
    <div class="form-section-header">
        <div>
            <span class="portal-section-kicker">
                <i class="bi bi-info-circle-fill"></i>
                {{ $type->shortLabel() }}
            </span>
            <h4>Payment Details</h4>
            <p>Core information for this voucher.</p>
        </div>
    </div>

    <div class="form-grid">

        @if ($type->requiresConsultancyDetails())
            <div class="form-field">
                <label for="consultant_name" class="form-label">Consultant's Name <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" id="consultant_name" name="consultant_name"
                        value="{{ old('consultant_name', $consultancy?->consultant_name) }}"
                        class="form-control-custom @error('consultant_name') is-invalid @enderror"
                        maxlength="255" required>
                </div>
                @error('consultant_name')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-field">
                <label for="tin_number" class="form-label">TIN Number <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-upc-scan"></i>
                    <input type="text" id="tin_number" name="tin_number"
                        value="{{ old('tin_number', $consultancy?->tin_number) }}"
                        class="form-control-custom @error('tin_number') is-invalid @enderror"
                        maxlength="50" required>
                </div>
                @error('tin_number')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-field">
                <label for="client_name" class="form-label">Client's Name <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-building"></i>
                    <input type="text" id="client_name" name="client_name"
                        value="{{ old('client_name', $consultancy?->client_name) }}"
                        class="form-control-custom @error('client_name') is-invalid @enderror"
                        maxlength="255" required>
                </div>
                @error('client_name')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>
        @else
            <div class="form-field">
                <label for="payee_name" class="form-label">Payee's Name <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" id="payee_name" name="payee_name"
                        value="{{ old('payee_name', $pr?->payee_name) }}"
                        class="form-control-custom @error('payee_name') is-invalid @enderror"
                        maxlength="255" required>
                </div>
                @error('payee_name')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>
        @endif

        <div class="form-field">
            <label for="payment_date" class="form-label">Date <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-calendar-event-fill"></i>
                <input type="date" id="payment_date" name="payment_date"
                    value="{{ old('payment_date', optional($pr?->payment_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                    class="form-control-custom @error('payment_date') is-invalid @enderror" required>
            </div>
            @error('payment_date')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field">
            <label for="currency" class="form-label">Currency <span>*</span></label>
            <div class="form-input-wrap">
                <i class="bi bi-cash"></i>
                <select id="currency" name="currency" class="form-control-custom @error('currency') is-invalid @enderror" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency }}" @selected(old('currency', $pr?->currency ?? $defaultCurrency ?? $currencies[0]) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            @error('currency')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-field form-grid-full">
            <label for="description" class="form-label">Payment Description <span>*</span></label>
            <div class="form-textarea-wrap">
                <textarea id="description" name="description" rows="3"
                    class="form-control-custom @error('description') is-invalid @enderror"
                    maxlength="2000" required>{{ old('description', $pr?->description) }}</textarea>
            </div>
            @error('description')<span class="form-server-error">{{ $message }}</span>@enderror
        </div>

    </div>
</div>

@if ($type->requiresConsultancyDetails())
    <div class="form-section-card">
        <div class="form-section-header">
            <div>
                <span class="portal-section-kicker">
                    <i class="bi bi-calculator-fill"></i>
                    Consultancy Fee
                </span>
                <h4>Fee &amp; Withholding Tax</h4>
                <p>Withholding tax amount and the amount due are calculated automatically.</p>
            </div>
        </div>

        <div class="form-grid">

            <div class="form-field">
                <label for="consultancy_fee" class="form-label">Total Consultancy Fee <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-cash-stack"></i>
                    <input type="number" step="0.01" min="0.01" id="consultancy_fee" name="consultancy_fee"
                        value="{{ old('consultancy_fee', $consultancy?->consultancy_fee) }}"
                        class="form-control-custom @error('consultancy_fee') is-invalid @enderror"
                        data-wht-input="fee" required>
                </div>
                @error('consultancy_fee')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-field">
                <label for="withholding_tax_percentage" class="form-label">Withholding Tax % <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-percent"></i>
                    <input type="number" step="0.01" min="0" max="100" id="withholding_tax_percentage" name="withholding_tax_percentage"
                        value="{{ old('withholding_tax_percentage', $consultancy?->withholding_tax_percentage ?? $defaultWht ?? 5) }}"
                        class="form-control-custom @error('withholding_tax_percentage') is-invalid @enderror"
                        data-wht-input="percentage" required>
                </div>
                @error('withholding_tax_percentage')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-field">
                <label class="form-label">Withholding Tax Amount</label>
                <div class="form-input-wrap">
                    <i class="bi bi-dash-circle"></i>
                    <input type="text" class="form-control-custom" data-wht-output="tax_amount" readonly
                        value="{{ number_format((float) ($consultancy?->withholding_tax_amount ?? 0), 2) }}">
                </div>
                <span class="form-input-help">Fee &times; Withholding Tax % - calculated automatically.</span>
            </div>

            <div class="form-field">
                <label class="form-label">Amount Due to Consultant</label>
                <div class="form-input-wrap">
                    <i class="bi bi-cash-coin"></i>
                    <input type="text" class="form-control-custom" data-wht-output="amount_due" readonly
                        value="{{ number_format((float) ($consultancy?->amount_due ?? 0), 2) }}">
                </div>
                <span class="form-input-help">Fee minus withholding tax - this is the amount that gets disbursed and printed.</span>
            </div>

        </div>
    </div>
@else
    <div class="form-section-card">
        <div class="form-section-header">
            <div>
                <span class="portal-section-kicker">
                    <i class="bi bi-cash-coin"></i>
                    Amount
                </span>
                <h4>Amount to Pay</h4>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label for="amount" class="form-label">Amount <span>*</span></label>
                <div class="form-input-wrap">
                    <i class="bi bi-cash-stack"></i>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount"
                        value="{{ old('amount', $pr?->amount) }}"
                        class="form-control-custom @error('amount') is-invalid @enderror" required>
                </div>
                @error('amount')<span class="form-server-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
@endif

@if ($type->usesChequeNumber() || $type->usesTransactionReference() || $type->usesCashier())
    <div class="form-section-card">
        <div class="form-section-header">
            <div>
                <span class="portal-section-kicker">
                    <i class="bi bi-receipt"></i>
                    Payment Method
                </span>
                <h4>{{ $type->usesChequeNumber() ? 'Cheque Details' : ($type->usesTransactionReference() ? 'Transfer Details' : 'Cashier Details') }}</h4>
                <p>These can also be filled in later, when the payment is actually processed.</p>
            </div>
        </div>

        <div class="form-grid">

            @if ($type->usesChequeNumber())
                <div class="form-field">
                    <label for="cheque_number" class="form-label">Cheque Number <small class="text-muted">(optional for now)</small></label>
                    <div class="form-input-wrap">
                        <i class="bi bi-vector-pen"></i>
                        <input type="text" id="cheque_number" name="cheque_number"
                            value="{{ old('cheque_number', $pr?->cheque_number) }}"
                            class="form-control-custom @error('cheque_number') is-invalid @enderror" maxlength="100">
                    </div>
                    @error('cheque_number')<span class="form-server-error">{{ $message }}</span>@enderror
                </div>
            @endif

            @if ($type->usesTransactionReference())
                <div class="form-field">
                    <label for="transaction_reference" class="form-label">Transaction Reference <small class="text-muted">(optional for now)</small></label>
                    <div class="form-input-wrap">
                        <i class="bi bi-bank2"></i>
                        <input type="text" id="transaction_reference" name="transaction_reference"
                            value="{{ old('transaction_reference', $pr?->transaction_reference) }}"
                            class="form-control-custom @error('transaction_reference') is-invalid @enderror" maxlength="100">
                    </div>
                    @error('transaction_reference')<span class="form-server-error">{{ $message }}</span>@enderror
                </div>
            @endif

            @if ($type->usesCashier())
                <div class="form-field">
                    <label for="cashier_name" class="form-label">Cashier <small class="text-muted">(optional for now)</small></label>
                    <div class="form-input-wrap">
                        <i class="bi bi-person-badge-fill"></i>
                        <input type="text" id="cashier_name" name="cashier_name"
                            value="{{ old('cashier_name', $pr?->cashier_name) }}"
                            class="form-control-custom @error('cashier_name') is-invalid @enderror" maxlength="255">
                    </div>
                    @error('cashier_name')<span class="form-server-error">{{ $message }}</span>@enderror
                </div>
            @endif

        </div>
    </div>
@endif

@if ($type->requiresConsultancyDetails())
    <script>
        // Mirrors PaymentRequestConsultancyDetail::calculate() purely for
        // instant feedback - the server recalculates and is the source of
        // truth, this just avoids a round-trip to see the numbers update.
        document.addEventListener('DOMContentLoaded', function () {
            const feeInput = document.querySelector('[data-wht-input="fee"]');
            const pctInput = document.querySelector('[data-wht-input="percentage"]');
            const taxOutput = document.querySelector('[data-wht-output="tax_amount"]');
            const dueOutput = document.querySelector('[data-wht-output="amount_due"]');

            function recalculate() {
                const fee = parseFloat(feeInput.value) || 0;
                const pct = parseFloat(pctInput.value) || 0;
                const tax = Math.round((fee * (pct / 100)) * 100) / 100;
                const due = Math.round((fee - tax) * 100) / 100;

                taxOutput.value = tax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                dueOutput.value = due.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            if (feeInput && pctInput) {
                feeInput.addEventListener('input', recalculate);
                pctInput.addEventListener('input', recalculate);
                recalculate();
            }
        });
    </script>
@endif
