{{--
    Renders one <tr> of the retirement-item builder table. Unlike
    budget's _item-row, rows here are never added/removed - every row is
    a mandatory 1:1 accounting of one approved budget line (see
    ActivityRetirementItem's own docblock), so there's no <template>/"Add
    Line" counterpart to this partial.

    Expects: $row (array, from ActivityRetirementController::edit(), now
    including 'category' and 'component' from the approved budget item),
    $paymentModes, $currency, $canUpload, $activity.

    Receipts are a plain <details> disclosure per row (no JS, no shared
    modal) - it lists what's attached, shows the running total actually
    receipted for this line, and - when $canUpload - a small upload form
    that posts straight to the existing documents.store route with this
    row's id fixed in a hidden field. Replaces the earlier shared-modal +
    JSON-in-data-attribute approach per direct user feedback that it
    should be simpler and more direct.

    Every items[...] field below carries form="retirementBuilderForm"
    rather than relying on physical nesting inside that <form> - the
    parent view (edit.blade.php) deliberately closes that form tag right
    after its CSRF/method inputs, precisely so the per-row receipts
    <form> further down in this same <tr> isn't illegally nested inside
    another <form> (browsers silently drop a nested <form> and hand its
    fields to the outer one instead).
--}}
@php
    $index = $row['id'];
    $actualPaymentMode = old("items.$index.actual_payment_mode", $row['actual_payment_mode']);
    $approvedTotal = (float) $row['approved_total'];
    $rowDocuments = $row['documents'] ?? [];
    $rowReceiptedTotal = collect($rowDocuments)->sum('amount');
@endphp
<tr class="retirement-item-row" data-row data-approved-total="{{ $approvedTotal }}" data-approved-mode="{{ $row['approved_payment_mode'] }}">
    <td class="budget-item-code">
        <span class="budget-item-code-badge">{{ $row['item_code'] ?: '—' }}</span>
        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $row['id'] }}" form="retirementBuilderForm">
    </td>

    <td class="retirement-description-cell">
        <strong class="d-block">{{ $row['description'] }}</strong>
        @if ($row['category'] || $row['component'])
            <small class="text-muted">
                {{ $row['category'] ?: '—' }}
                @if ($row['component'])
                    &middot; {{ $row['component'] }}
                @endif
            </small>
        @endif
    </td>

    <td class="retirement-approved-cell text-muted small">
        {{ rtrim(rtrim(number_format((float) $row['approved_qty'], 2), '0'), '.') }}
        &times; {{ $row['approved_frequency'] }}
        &times; {{ $currency }} {{ number_format((float) $row['approved_unit_cost'], 2) }}
        <strong class="d-block text-body">= {{ $currency }} {{ number_format($approvedTotal, 2) }}</strong>
        <span class="status-badge {{ \App\Enums\BudgetItemPaymentMode::from($row['approved_payment_mode'])->badgeClass() }} mt-1">
            {{ \App\Enums\BudgetItemPaymentMode::from($row['approved_payment_mode'])->label() }}
        </span>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][actual_qty]" value="{{ old("items.$index.actual_qty", $row['actual_qty']) }}"
            class="form-control form-control-sm" data-role="actual-qty" step="0.01" min="0" required form="retirementBuilderForm">
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][actual_frequency]" value="{{ old("items.$index.actual_frequency", $row['actual_frequency']) }}"
            class="form-control form-control-sm" data-role="actual-frequency" step="1" min="0" required form="retirementBuilderForm">
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][actual_unit_cost]" value="{{ old("items.$index.actual_unit_cost", $row['actual_unit_cost']) }}"
            class="form-control form-control-sm" data-role="actual-unit-cost" step="0.01" min="0" required form="retirementBuilderForm">
    </td>

    <td class="budget-num-cell">
        <input type="text" class="form-control form-control-sm budget-total-display" data-role="actual-total" value="0.00" disabled tabindex="-1">
    </td>

    <td>
        <select name="items[{{ $index }}][actual_payment_mode]" class="form-select form-select-sm" data-role="actual-payment-mode" form="retirementBuilderForm">
            @foreach ($paymentModes as $value => $label)
                <option value="{{ $value }}" @selected($actualPaymentMode === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][actual_cash_amount]" value="{{ old("items.$index.actual_cash_amount", $row['actual_cash_amount']) }}"
            class="form-control form-control-sm" data-role="actual-cash" step="0.01" min="0" form="retirementBuilderForm">
        <span class="form-field-error d-block">{{ $errors->first("items.$index.actual_cash_amount") }}</span>
    </td>

    <td class="budget-num-cell">
        <input type="number" name="items[{{ $index }}][actual_invoice_amount]" value="{{ old("items.$index.actual_invoice_amount", $row['actual_invoice_amount']) }}"
            class="form-control form-control-sm" data-role="actual-invoice" step="0.01" min="0" form="retirementBuilderForm">
    </td>

    <td class="retirement-justification-cell">
        <div data-role="variance-justification-wrap" hidden>
            <label class="form-label d-block mb-1">Variance justification <span>*</span></label>
            <textarea name="items[{{ $index }}][variance_justification]" rows="2" class="form-control form-control-sm"
                maxlength="1000" placeholder="Why did this line cost more than approved?" form="retirementBuilderForm">{{ old("items.$index.variance_justification", $row['variance_justification']) }}</textarea>
            <span class="form-field-error d-block">{{ $errors->first("items.$index.variance_justification") }}</span>
        </div>

        <div data-role="mode-change-justification-wrap" hidden class="mt-2">
            <label class="form-label d-block mb-1">Payment mode change justification <span>*</span></label>
            <textarea name="items[{{ $index }}][payment_mode_change_justification]" rows="2" class="form-control form-control-sm"
                maxlength="1000" placeholder="Why was this paid differently than approved?" form="retirementBuilderForm">{{ old("items.$index.payment_mode_change_justification", $row['payment_mode_change_justification']) }}</textarea>
            <span class="form-field-error d-block">{{ $errors->first("items.$index.payment_mode_change_justification") }}</span>
        </div>
    </td>

    <td>
        <input type="text" name="items[{{ $index }}][notes]" value="{{ old("items.$index.notes", $row['notes']) }}"
            class="form-control form-control-sm" maxlength="1000" placeholder="Optional" form="retirementBuilderForm">
    </td>

    <td class="retirement-receipts-cell">
        <details class="retirement-receipts-details">
            <summary class="retirement-receipts-summary">
                <i class="bi bi-paperclip"></i>
                {{ count($rowDocuments) }} receipt{{ count($rowDocuments) === 1 ? '' : 's' }}
            </summary>

            <div class="retirement-receipts-panel">
                @forelse ($rowDocuments as $document)
                    <div class="retirement-receipt-line">
                        <span class="retirement-receipt-line-label">{{ $document['label'] }}: {{ $document['filename'] }}</span>
                        @if ($document['amount'] !== null)
                            <span class="text-muted">{{ $currency }} {{ number_format($document['amount'], 2) }}</span>
                        @endif
                        <a href="{{ $document['download_url'] }}" class="retirement-receipt-download" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                @empty
                    <p class="text-muted small mb-2">No receipts yet.</p>
                @endforelse

                <p class="retirement-receipts-total small">
                    Receipted so far: <strong>{{ $currency }} {{ number_format($rowReceiptedTotal, 2) }}</strong>
                </p>

                @if ($canUpload ?? false)
                    <form action="{{ route('activities.retirement.documents.store', $activity) }}" method="POST" enctype="multipart/form-data" class="retirement-receipt-upload-form">
                        @csrf
                        <input type="hidden" name="activity_retirement_item_id" value="{{ $row['id'] }}">
                        <select name="document_type" class="form-select form-select-sm mb-2" required>
                            @foreach (config('activity_budgets.documents.types') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="file" name="file" class="form-control form-control-sm mb-2" required>
                        <button type="submit" class="form-btn form-btn-light retirement-receipt-upload-btn">
                            <i class="bi bi-upload"></i>
                            Upload
                        </button>
                    </form>
                @endif
            </div>
        </details>
    </td>
</tr>
