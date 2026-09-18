{{--
    A4-landscape print/export report for one Activity Retirement -
    approved vs actual per line, plus every receipt (general and
    per-line) in a second supporting table. "Save as PDF" in the
    browser's own print dialog is the actual PDF output - see
    ActivityRetirementController::print()'s docblock. Same standalone-
    document shape as activities/budget/print.blade.php, sharing its
    assets/css/report-print.css.

    Expects: $activity, $budget, $retirement (with
    items.activityBudgetItem.category and items.activityBudgetItem.
    component eager-loaded), $documents (both general and per-line
    RetirementDocument rows, via allDocuments()).
--}}
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $retirement->reference }} | Retirement Report</title>
    <link href="{{ asset('assets/css/report-print.css') }}" rel="stylesheet">
</head>

<body class="report-body">

    <div class="report-toolbar">
        <button type="button" class="report-print-btn" onclick="window.print()">
            Print / Save as PDF
        </button>
        <a href="{{ route('activities.retirement.export', $activity) }}">
            Download CSV
        </a>
        <a href="{{ route('activities.retirement.edit', $activity) }}">
            Back to Retirement
        </a>
    </div>

    <div class="report-sheet">

        <div class="report-header">
            <div>
                <p class="report-org-name">Praxis for Health and Development</p>
                <p class="report-org-sub">Activity Retirement / Expense Report</p>
            </div>

            <div class="report-title-block">
                <p class="report-title">Retirement</p>
                <p class="report-ref">{{ $retirement->reference }}</p>
                <p class="report-status-line">{{ $retirement->status->label() }}</p>
            </div>
        </div>

        <div class="report-meta-grid">
            <div class="report-meta-item">
                <span class="report-meta-label">Activity</span>
                <span class="report-meta-value">{{ $activity->reference }}</span>
            </div>

            <div class="report-meta-item report-meta-item--full">
                <span class="report-meta-label">Title</span>
                <span class="report-meta-value">{{ $activity->title }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Budget</span>
                <span class="report-meta-value">{{ $budget->budget_code ?: $activity->displayAccountingCode() }} (v{{ $budget->version }})</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Currency</span>
                <span class="report-meta-value">{{ $budget->currency }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Amount Provided</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $retirement->total_advanced, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Amount Spent</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $retirement->total_actual_overall, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Due to Praxis</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $retirement->unspent_advance_amount, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Due to the Creator</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $retirement->reimbursement_due_amount, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Total Dues</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $retirement->unspent_advance_amount + (float) $retirement->reimbursement_due_amount, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Prepared By</span>
                <span class="report-meta-value">{{ $retirement->creator->name ?? '—' }}</span>
            </div>
        </div>

        <p class="report-section-heading">Expense Lines — Approved vs Actual</p>

        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">Description</th>
                    <th class="text-left">Category</th>
                    <th class="text-left">Component</th>
                    <th>Approved Total</th>
                    <th class="text-left">Approved Mode</th>
                    <th>Actual Qty</th>
                    <th>Actual Freq.</th>
                    <th>Actual Unit Cost</th>
                    <th>Actual Total</th>
                    <th class="text-left">Actual Mode</th>
                    <th>Cash</th>
                    <th>Invoice</th>
                    <th class="text-left">Notes</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($retirement->items as $item)
                    @php $budgetItem = $item->activityBudgetItem; @endphp
                    <tr>
                        <td class="text-left">{{ $budgetItem->item_code ?: '—' }}</td>
                        <td class="text-left">{{ $budgetItem->description }}</td>
                        <td class="text-left">{{ $budgetItem->category->name ?? '—' }}</td>
                        <td class="text-left">{{ $budgetItem->component->name ?? '—' }}</td>
                        <td>{{ number_format((float) $budgetItem->total, 2) }}</td>
                        <td class="text-left">{{ $budgetItem->payment_mode->label() }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->actual_qty, 2), '0'), '.') }}</td>
                        <td>{{ $item->actual_frequency }}</td>
                        <td>{{ number_format((float) $item->actual_unit_cost, 2) }}</td>
                        <td>{{ number_format((float) $item->actual_total, 2) }}</td>
                        <td class="text-left">{{ $item->actual_payment_mode?->label() ?? '—' }}</td>
                        <td>{{ number_format((float) $item->actual_cash_amount, 2) }}</td>
                        <td>{{ number_format((float) $item->actual_invoice_amount, 2) }}</td>
                        <td class="text-left">{{ $item->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="9" class="text-left">Totals</td>
                    <td>{{ $budget->currency }} {{ number_format((float) $retirement->total_actual_overall, 2) }}</td>
                    <td></td>
                    <td>{{ number_format((float) $retirement->total_actual_cash, 2) }}</td>
                    <td>{{ number_format((float) $retirement->total_actual_invoice, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @if ($documents->isNotEmpty())
            <p class="report-section-heading">Receipts &amp; Supporting Documents</p>

            <table class="report-table">
                <thead>
                    <tr>
                        <th class="text-left">Line</th>
                        <th class="text-left">Type</th>
                        <th class="text-left">Vendor</th>
                        <th class="text-left">Receipt #</th>
                        <th class="text-left">Date</th>
                        <th>Amount</th>
                        <th class="text-left">File</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td class="text-left">{{ $document->activityRetirementItem?->activityBudgetItem?->item_code ?? 'General' }}</td>
                            <td class="text-left">{{ $document->typeLabel() }}</td>
                            <td class="text-left">{{ $document->vendor ?: '—' }}</td>
                            <td class="text-left">{{ $document->receipt_number ?: '—' }}</td>
                            <td class="text-left">{{ $document->receipt_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $document->document_amount !== null ? number_format((float) $document->document_amount, 2) : '—' }}</td>
                            <td class="text-left">{{ $document->original_filename }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="report-footer-note">
            Generated from the Retirement Portal &middot; {{ now()->format('d M Y, g:ia') }}
            &middot; {{ $retirement->reference }}
        </p>

    </div>

</body>

</html>
