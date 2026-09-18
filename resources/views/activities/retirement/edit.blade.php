@extends('layouts.admin')

@section('title')
Retirement — {{ $activity->reference }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
<style>
    .budget-builder-fieldset { border: 0; padding: 0; margin: 0; min-width: 0; }
    .budget-builder-table-wrap { overflow-x: auto; }
    .budget-builder-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; min-width: 1360px; }
    .budget-builder-table th {
        text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
        color: var(--text-muted); font-weight: 700; padding: 0 8px 6px;
    }
    .budget-builder-table td { background: var(--surface); padding: 8px; vertical-align: top; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
    .budget-builder-table tr.retirement-item-row td:first-child { border-left: 1px solid var(--border); border-radius: var(--r-md) 0 0 var(--r-md); }
    .budget-builder-table tr.retirement-item-row td:last-child { border-right: 1px solid var(--border); border-radius: 0 var(--r-md) var(--r-md) 0; }
    .budget-num-cell { min-width: 96px; }
    .budget-item-code { min-width: 48px; text-align: center; }
    .budget-item-code-badge {
        display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 26px;
        border-radius: 999px; background: var(--n-50); color: var(--text-muted); font-weight: 700; font-size: .78rem;
    }
    .budget-total-display { font-weight: 700; color: var(--text-head); background: var(--n-50) !important; }
    .budget-footer-row td { background: transparent; border: none !important; font-weight: 700; padding-top: 4px; }
    .budget-footer-totals { display: flex; gap: 24px; flex-wrap: wrap; justify-content: flex-end; padding: 14px 8px 0; }
    .budget-footer-totals div { text-align: right; }
    .budget-footer-totals span { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); font-weight: 700; }
    .budget-footer-totals strong { font-size: 1.05rem; color: var(--text-head); }

    .retirement-description-cell { min-width: 180px; }
    .retirement-approved-cell { min-width: 150px; }
    .retirement-justification-cell { min-width: 220px; }
    .retirement-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
    .retirement-summary-item span { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); font-weight: 700; }
    .retirement-summary-item strong { font-size: 1.15rem; color: var(--text-head); }

    .retirement-receipts-cell { min-width: 220px; }
    .retirement-receipts-details { font-size: .82rem; }
    .retirement-receipts-summary {
        cursor: pointer; list-style: none; display: inline-flex; align-items: center; gap: 6px;
        font-weight: 600; color: var(--text-head); user-select: none;
    }
    .retirement-receipts-summary::-webkit-details-marker { display: none; }
    .retirement-receipts-panel { margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); }
    .retirement-receipt-line {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding: 4px 0; border-bottom: 1px solid var(--border);
    }
    .retirement-receipt-line:last-of-type { border-bottom: none; }
    .retirement-receipt-line-label { overflow-wrap: anywhere; }
    .retirement-receipt-download { color: var(--text-muted); }
    .retirement-receipts-total { margin: 6px 0; }
    .retirement-receipt-upload-form { margin-top: 6px; }
    .retirement-receipt-upload-btn { width: 100%; justify-content: center; }
</style>
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-receipt-cutoff"></i>
                    Retirement
                </div>

                <h1>{{ $activity->reference }}</h1>

                <p>{{ $activity->title }}</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('activities.index') }}">Activities</a></li>
                        <li><a href="{{ route('activities.show', $activity) }}">{{ $activity->reference }}</a></li>
                        <li>Retirement</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                @if (! $budget || $budget->status !== \App\Enums\ActivityBudgetStatus::Approved)

                    {{-- =================================================
                        NOT RETIREABLE YET - no budget, or one that isn't
                        Approved. Retirement only exists once the advance
                        has actually been provided (Approve's own
                        business meaning).
                    ================================================== --}}
                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="120">
                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi bi-receipt-cutoff"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-info-circle-fill"></i>
                                    Not Available
                                </div>

                                <h3>This activity can't be retired yet</h3>
                                <p>
                                    @if (! $budget)
                                        {{ $activity->reference }} doesn't have a budget yet.
                                    @else
                                        The budget must be Approved (the advance provided) before it can be retired. It's currently {{ $budget->status->label() }}.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="form-body">
                            <a href="{{ route('activities.show', $activity) }}" class="form-btn form-btn-light">
                                <i class="bi bi-arrow-left"></i>
                                Back to Activity
                            </a>
                        </div>
                    </div>

                @elseif (! $retirement)

                    {{-- =================================================
                        NO RETIREMENT YET - single "Start Retirement"
                        prompt, same shape as the Budget module's own
                        "Start Budget" prompt.
                    ================================================== --}}
                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="120">
                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi bi-receipt-cutoff"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-info-circle-fill"></i>
                                    Not Started
                                </div>

                                <h3>No retirement yet for {{ $activity->reference }}</h3>
                                <p>Starting a retirement seeds one line per approved budget item, ready for you to record what was actually spent and attach receipts.</p>
                            </div>
                        </div>

                        <div class="form-body">
                            @if ($canStart)
                                <form method="POST" action="{{ route('activities.retirement.store', $activity) }}">
                                    @csrf
                                    <div class="form-actions portal-form-actions">
                                        <a href="{{ route('activities.show', $activity) }}" class="form-btn form-btn-light">
                                            <i class="bi bi-arrow-left"></i>
                                            Back to Activity
                                        </a>

                                        <button type="submit" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-plus-lg"></i>
                                            Start Retirement
                                        </button>
                                    </div>
                                </form>
                            @else
                                <p class="text-muted mb-0">You don't have permission to start a retirement for this activity.</p>
                            @endif
                        </div>
                    </div>

                @else

                    {{-- =================================================
                        BUILDER - record actual amounts per approved
                        line, plus general and per-line receipt uploads.
                        Submit/Assign/Review/Approve/Reject/Cancel
                        workflow for the retirement itself is a later
                        phase.
                    ================================================== --}}
                    <div class="form-section-title-row">
                        <div class="section-title form-section-title mb-0">
                            <span class="portal-section-eyebrow">{{ $retirement->reference }}</span>
                            <h2>Retirement</h2>
                            <p>
                                <span class="status-badge {{ $retirement->status->badgeClass() }}">
                                    <i class="bi {{ $retirement->status->icon() }}"></i>
                                    {{ $retirement->status->label() }}
                                </span>
                                &middot; {{ $budget->currency }}
                                @if ($retirement->currentAssignee)
                                    &middot; Assigned to {{ $retirement->currentAssignee->name }}
                                @endif
                            </p>
                        </div>

                        <div class="form-actions portal-form-actions">
                            @can('print', $retirement)
                                <a href="{{ route('activities.retirement.print', $activity) }}" target="_blank" class="form-btn form-btn-light" title="Print or save as PDF">
                                    <i class="bi bi-printer"></i>
                                    Print / PDF
                                </a>

                                <a href="{{ route('activities.retirement.export', $activity) }}" class="form-btn form-btn-light" title="Download as CSV (Excel)">
                                    <i class="bi bi-file-earmark-spreadsheet"></i>
                                    Export CSV
                                </a>
                            @endcan

                            <a href="{{ route('activities.show', $activity) }}" class="form-btn form-btn-light form-back-btn">
                                <i class="bi bi-arrow-left"></i>
                                Back to Activity
                            </a>
                        </div>
                    </div>

                    <div class="form-card portal-form-card mb-4" data-aos="fade-up" data-aos-delay="110">
                        <div class="form-body">
                            <div class="retirement-summary-grid">
                                <div class="retirement-summary-item">
                                    <span>Amount Provided</span>
                                    <strong>{{ $budget->currency }} {{ number_format((float) $retirement->total_advanced, 2) }}</strong>
                                </div>
                                <div class="retirement-summary-item">
                                    <span>Amount Spent</span>
                                    <strong id="summaryActualOverall">{{ $budget->currency }} {{ number_format((float) $retirement->total_actual_overall, 2) }}</strong>
                                </div>
                                <div class="retirement-summary-item">
                                    <span>Due to Praxis</span>
                                    <strong id="summaryUnspent">{{ $budget->currency }} {{ number_format((float) $retirement->unspent_advance_amount, 2) }}</strong>
                                </div>
                                <div class="retirement-summary-item">
                                    <span>Due to the Creator</span>
                                    <strong id="summaryReimbursement">{{ $budget->currency }} {{ number_format((float) $retirement->reimbursement_due_amount, 2) }}</strong>
                                </div>
                                <div class="retirement-summary-item">
                                    <span>Total Dues</span>
                                    <strong id="summaryTotalDues">{{ $budget->currency }} {{ number_format((float) $retirement->unspent_advance_amount + (float) $retirement->reimbursement_due_amount, 2) }}</strong>
                                </div>
                            </div>
                            <p class="text-muted small mb-0 mt-2">Amount spent, dues, and total dues update live as you edit below and are recalculated again on save.</p>
                        </div>
                    </div>

                    @unless ($canEdit)
                        <div class="form-alert form-alert-warning mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Read-only</strong>
                                <p class="form-alert-message">This retirement is {{ $retirement->status->label() }} and can no longer be edited here.</p>
                            </div>
                        </div>
                    @endunless

                    {{-- =================================================
                        GENERAL RECEIPTS - retirement-level documents not
                        tied to a specific line (attendance sheet,
                        activity report, ...).
                    ================================================== --}}
                    <div class="form-section-card mb-4" id="receipts">
                        <div class="form-section-header">
                            <h4>General Receipts</h4>
                            <p>{{ $generalDocuments->count() }} file(s) attached that aren't tied to a specific line.</p>
                        </div>

                        @forelse ($generalDocuments as $document)
                            <div class="detail-view-item detail-view-item--full mb-2">
                                <span><i class="bi bi-paperclip"></i> {{ $document->typeLabel() }}</span>
                                <strong class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span>
                                        {{ $document->original_filename }}
                                        <small class="text-muted">(by {{ $document->uploader->name ?? '—' }})</small>
                                    </span>
                                    <a href="{{ route('activities.retirement.documents.download', [$activity, $document]) }}" class="form-btn form-btn-light">
                                        <i class="bi bi-download"></i>
                                        Download
                                    </a>
                                </strong>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No general receipts attached yet.</p>
                        @endforelse

                        @if ($canUpload)
                            <form action="{{ route('activities.retirement.documents.store', $activity) }}" method="POST" enctype="multipart/form-data" class="form-body mt-3">
                                @csrf
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="document_type" class="form-label">Document Type <span>*</span></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-tag-fill"></i>
                                            <select id="document_type" name="document_type" class="form-control-custom" required>
                                                @foreach (config('activity_budgets.documents.types') as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label for="vendor" class="form-label">Vendor <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-shop"></i>
                                            <input type="text" id="vendor" name="vendor" class="form-control-custom" maxlength="255">
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label for="receipt_number" class="form-label">Receipt # <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-hash"></i>
                                            <input type="text" id="receipt_number" name="receipt_number" class="form-control-custom" maxlength="100">
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label for="receipt_date" class="form-label">Receipt Date <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-calendar-event"></i>
                                            <input type="date" id="receipt_date" name="receipt_date" class="form-control-custom">
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label for="document_amount" class="form-label">Amount <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-cash"></i>
                                            <input type="number" id="document_amount" name="document_amount" step="0.01" min="0" class="form-control-custom">
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label for="file" class="form-label">File <span>*</span></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-file-earmark-arrow-up-fill"></i>
                                            <input type="file" id="file" name="file" class="form-control-custom" required>
                                        </div>
                                        <span class="form-input-help">PDF or image, up to 10 MB.</span>
                                    </div>
                                </div>

                                <div class="form-actions portal-form-actions">
                                    <button type="submit" class="form-btn form-btn-light">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-upload"></i>
                                        Upload Receipt
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>

                    {{-- =================================================
                        EXPENSE LINES - one row per approved budget item,
                        never addable/removable.

                        This form tag is deliberately left empty (just the
                        CSRF/method spoofing inputs) and closed immediately
                        - every actual field below lives OUTSIDE it and
                        points back in via form="retirementBuilderForm",
                        rather than being physically nested inside it. That
                        is what lets each row's own per-line receipt
                        upload <form> (see _item-row.blade.php) sit inside
                        a <td> of this same table without illegally
                        nesting one <form> inside another, which browsers
                        silently break (the inner form is dropped and its
                        controls get swallowed by the outer form instead).
                    ================================================== --}}
                    <form method="POST" action="{{ route('activities.retirement.update', $activity) }}" id="retirementBuilderForm" autocomplete="off" novalidate>
                        @csrf
                        @method('PUT')
                    </form>

                        <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="160">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <i class="bi bi-list-columns-reverse"></i>
                                </div>

                                <div class="form-card-heading">
                                    <div class="form-card-badge">
                                        <i class="bi bi-grid-3x3-gap-fill"></i>
                                        Expense Lines
                                    </div>

                                    <h3>Actual Expenses</h3>
                                    <p>One line per approved budget item. Totals recalculate as you type; a justification is required only when actual spend exceeds what was approved, or the payment mode changed.</p>
                                </div>
                            </div>

                            <div class="form-body">

                                @error('items')
                                    <div class="form-alert form-alert-danger mb-3">
                                        <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                        <div class="form-alert-content">
                                            <p class="form-alert-message mb-0">{{ $message }}</p>
                                        </div>
                                    </div>
                                @enderror

                                <fieldset @disabled(! $canEdit) class="budget-builder-fieldset">
                                <div class="budget-builder-table-wrap">
                                    <table class="budget-builder-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Description</th>
                                                <th>Approved</th>
                                                <th>Actual Qty</th>
                                                <th>Actual Freq.</th>
                                                <th>Actual Unit Cost</th>
                                                <th>Actual Total</th>
                                                <th>Payment</th>
                                                <th>Cash</th>
                                                <th>Invoice</th>
                                                <th>Justification</th>
                                                <th>Notes</th>
                                                <th>Receipts</th>
                                            </tr>
                                        </thead>

                                        <tbody id="retirementItemsBody">
                                            @foreach ($rows as $row)
                                                @include('activities.retirement._item-row', [
                                                    'row' => $row,
                                                    'paymentModes' => \App\Enums\BudgetItemPaymentMode::options(),
                                                    'currency' => $budget->currency,
                                                    'canUpload' => $canUpload,
                                                    'activity' => $activity,
                                                ])
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                </fieldset>

                                <div class="budget-footer-totals">
                                    <div>
                                        <span>Cash</span>
                                        <strong>{{ $budget->currency }} <span id="footerCashTotal">0.00</span></strong>
                                    </div>
                                    <div>
                                        <span>Invoice</span>
                                        <strong>{{ $budget->currency }} <span id="footerInvoiceTotal">0.00</span></strong>
                                    </div>
                                    <div>
                                        <span>Overall</span>
                                        <strong>{{ $budget->currency }} <span id="footerOverallTotal">0.00</span></strong>
                                    </div>
                                </div>

                            </div>

                            @if ($canEdit)
                                <div class="form-body pt-0">
                                    <div class="form-actions portal-form-actions">
                                        <button type="submit" form="retirementBuilderForm" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-save"></i>
                                            Save Expenses
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                    <div class="mt-4">
                        @include('activities.retirement._timeline', ['retirement' => $retirement])
                    </div>

                @endif

            </div>
        </section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('retirementItemsBody');
    if (! tbody) return;

    var totalAdvanced = {{ (float) ($retirement->total_advanced ?? 0) }};

    function toFloat(value) {
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function recalcRow(row) {
        var qty = toFloat(row.querySelector('[data-role="actual-qty"]').value);
        var freq = toFloat(row.querySelector('[data-role="actual-frequency"]').value);
        var unitCost = toFloat(row.querySelector('[data-role="actual-unit-cost"]').value);
        var total = Math.round(qty * freq * unitCost * 100) / 100;

        row.querySelector('[data-role="actual-total"]').value = total.toFixed(2);

        var modeSelect = row.querySelector('[data-role="actual-payment-mode"]');
        var cashInput = row.querySelector('[data-role="actual-cash"]');
        var invoiceInput = row.querySelector('[data-role="actual-invoice"]');
        var mode = modeSelect.value;

        if (mode === 'cash') {
            cashInput.value = total.toFixed(2);
            invoiceInput.value = '0.00';
            cashInput.disabled = true;
            invoiceInput.disabled = true;
        } else if (mode === 'invoice') {
            cashInput.value = '0.00';
            invoiceInput.value = total.toFixed(2);
            cashInput.disabled = true;
            invoiceInput.disabled = true;
        } else {
            cashInput.disabled = false;
            invoiceInput.disabled = false;
        }

        var approvedTotal = toFloat(row.dataset.approvedTotal);
        var approvedMode = row.dataset.approvedMode;

        var varianceWrap = row.querySelector('[data-role="variance-justification-wrap"]');
        if (varianceWrap) {
            varianceWrap.hidden = total <= approvedTotal + 0.01;
        }

        var modeChangeWrap = row.querySelector('[data-role="mode-change-justification-wrap"]');
        if (modeChangeWrap) {
            modeChangeWrap.hidden = mode === approvedMode;
        }

        return total;
    }

    function recalcFooter() {
        var cashTotal = 0;
        var invoiceTotal = 0;

        tbody.querySelectorAll('.retirement-item-row').forEach(function (row) {
            cashTotal += toFloat(row.querySelector('[data-role="actual-cash"]').value);
            invoiceTotal += toFloat(row.querySelector('[data-role="actual-invoice"]').value);
        });

        var overallTotal = cashTotal + invoiceTotal;

        var footerCash = document.getElementById('footerCashTotal');
        var footerInvoice = document.getElementById('footerInvoiceTotal');
        var footerOverall = document.getElementById('footerOverallTotal');

        if (footerCash) footerCash.textContent = cashTotal.toFixed(2);
        if (footerInvoice) footerInvoice.textContent = invoiceTotal.toFixed(2);
        if (footerOverall) footerOverall.textContent = overallTotal.toFixed(2);

        var summaryActual = document.getElementById('summaryActualOverall');
        var summaryUnspent = document.getElementById('summaryUnspent');
        var summaryReimbursement = document.getElementById('summaryReimbursement');
        var summaryTotalDues = document.getElementById('summaryTotalDues');
        var unspent = Math.max(totalAdvanced - overallTotal, 0);
        var reimbursement = Math.max(overallTotal - totalAdvanced, 0);

        if (summaryActual) summaryActual.textContent = '{{ $budget->currency ?? '' }} ' + overallTotal.toFixed(2);
        if (summaryUnspent) summaryUnspent.textContent = '{{ $budget->currency ?? '' }} ' + unspent.toFixed(2);
        if (summaryReimbursement) summaryReimbursement.textContent = '{{ $budget->currency ?? '' }} ' + reimbursement.toFixed(2);
        if (summaryTotalDues) summaryTotalDues.textContent = '{{ $budget->currency ?? '' }} ' + (unspent + reimbursement).toFixed(2);
    }

    tbody.querySelectorAll('.retirement-item-row').forEach(recalcRow);
    recalcFooter();

    tbody.addEventListener('input', function (event) {
        var row = event.target.closest('.retirement-item-row');
        if (! row) return;

        if (event.target.matches('[data-role="actual-qty"], [data-role="actual-frequency"], [data-role="actual-unit-cost"], [data-role="actual-cash"], [data-role="actual-invoice"]')) {
            if (! event.target.matches('[data-role="actual-cash"], [data-role="actual-invoice"]')) {
                recalcRow(row);
            }
            recalcFooter();
        }
    });

    tbody.addEventListener('change', function (event) {
        var row = event.target.closest('.retirement-item-row');
        if (! row) return;

        if (event.target.matches('[data-role="actual-payment-mode"]')) {
            recalcRow(row);
            recalcFooter();
        }
    });
});
</script>
@endpush
