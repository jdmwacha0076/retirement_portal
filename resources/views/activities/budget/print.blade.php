{{--
    A4-landscape print/export report for one Activity Budget version -
    "Save as PDF" in the browser's own print dialog is the actual PDF
    output (see ActivityBudgetController::print()'s docblock for why no
    server-side PDF library is involved). Deliberately a standalone
    document (own <html>/<head>), same shape as
    resources/views/payment-requests/print/standard.blade.php, styled by
    its own assets/css/report-print.css rather than the app layout.

    Expects: $activity, $budget (with items.category/creator/approver
    eager-loaded by the controller).
--}}
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $activity->reference }} | Budget Report</title>
    <link href="{{ asset('assets/css/report-print.css') }}" rel="stylesheet">
</head>

<body class="report-body">

    <div class="report-toolbar">
        <button type="button" class="report-print-btn" onclick="window.print()">
            Print / Save as PDF
        </button>
        <a href="{{ route('activities.budget.export', $activity) }}">
            Download CSV
        </a>
        <a href="{{ route('activities.budget.edit', $activity) }}">
            Back to Budget
        </a>
    </div>

    <div class="report-sheet">

        <div class="report-header">
            <div>
                <p class="report-org-name">Praxis for Health and Development</p>
                <p class="report-org-sub">Activity Budget Report</p>
            </div>

            <div class="report-title-block">
                <p class="report-title">Budget v{{ $budget->version }}</p>
                <p class="report-ref">{{ $budget->budget_code ?: $activity->displayAccountingCode() }}</p>
                <p class="report-status-line">{{ $budget->status->label() }}</p>
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
                <span class="report-meta-label">Activity Type</span>
                <span class="report-meta-value">{{ $activity->activityType->name ?? '—' }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Coordinator</span>
                <span class="report-meta-value">{{ $activity->coordinator->name ?? '—' }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Dates</span>
                <span class="report-meta-value">{{ $activity->start_date->format('d M Y') }} – {{ $activity->end_date->format('d M Y') }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Currency</span>
                <span class="report-meta-value">{{ $budget->currency }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Requested Advance</span>
                <span class="report-meta-value">{{ $budget->currency }} {{ number_format((float) $budget->requested_advance_amount, 2) }}</span>
            </div>

            <div class="report-meta-item">
                <span class="report-meta-label">Prepared By</span>
                <span class="report-meta-value">{{ $budget->creator->name ?? '—' }}</span>
            </div>

            @if ($budget->approver)
                <div class="report-meta-item">
                    <span class="report-meta-label">Approved By</span>
                    <span class="report-meta-value">{{ $budget->approver->name }}{{ $budget->approved_at ? ' · '.$budget->approved_at->format('d M Y') : '' }}</span>
                </div>
            @endif
        </div>

        <p class="report-section-heading">Budget Line Items</p>

        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">Description</th>
                    <th class="text-left">Category</th>
                    <th class="text-left">Component</th>
                    <th>Qty</th>
                    <th>Freq.</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                    <th class="text-left">Mode</th>
                    <th>Cash</th>
                    <th>Invoice</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($budget->items as $item)
                    <tr>
                        <td class="text-left">{{ $item->item_code ?: '—' }}</td>
                        <td class="text-left">{{ $item->description }}</td>
                        <td class="text-left">{{ $item->category->name ?? '—' }}</td>
                        <td class="text-left">{{ $item->component->name ?? '—' }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2), '0'), '.') }}</td>
                        <td>{{ $item->frequency }}</td>
                        <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                        <td>{{ number_format((float) $item->total, 2) }}</td>
                        <td class="text-left">{{ $item->payment_mode->label() }}</td>
                        <td>{{ number_format((float) $item->cash_amount, 2) }}</td>
                        <td>{{ number_format((float) $item->invoice_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="7" class="text-left">Totals</td>
                    <td>{{ $budget->currency }} {{ number_format((float) $budget->total_overall, 2) }}</td>
                    <td></td>
                    <td>{{ number_format((float) $budget->total_cash, 2) }}</td>
                    <td>{{ number_format((float) $budget->total_invoice, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <p class="report-footer-note">
            Generated from the Retirement Portal &middot; {{ now()->format('d M Y, g:ia') }}
            &middot; {{ $activity->reference }}
        </p>

    </div>

</body>

</html>
