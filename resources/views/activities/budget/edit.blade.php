@extends('layouts.admin')

@section('title')
Budget — {{ $activity->reference }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
<style>
    .budget-builder-fieldset { border: 0; padding: 0; margin: 0; min-width: 0; }
    .budget-builder-table-wrap { overflow-x: auto; }
    .budget-builder-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; min-width: 1180px; }
    .budget-builder-table th {
        text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
        color: var(--text-muted); font-weight: 700; padding: 0 8px 6px;
    }
    .budget-builder-table td { background: var(--surface); padding: 8px; vertical-align: top; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
    .budget-builder-table tr.budget-item-row td:first-child { border-left: 1px solid var(--border); border-radius: var(--r-md) 0 0 var(--r-md); }
    .budget-builder-table tr.budget-item-row td:last-child { border-right: 1px solid var(--border); border-radius: 0 var(--r-md) var(--r-md) 0; }
    .budget-num-cell { min-width: 96px; }
    .budget-item-code { min-width: 48px; text-align: center; }
    .budget-item-code-badge {
        display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 26px;
        border-radius: 999px; background: var(--n-50); color: var(--text-muted); font-weight: 700; font-size: .78rem;
    }
    .budget-total-display { font-weight: 700; color: var(--text-head); background: var(--n-50) !important; }
    .budget-remove-row { color: var(--red-dark, #b91c1c); }
    .budget-footer-row td { background: transparent; border: none !important; font-weight: 700; padding-top: 4px; }
    .budget-footer-totals { display: flex; gap: 24px; flex-wrap: wrap; justify-content: flex-end; padding: 14px 8px 0; }
    .budget-footer-totals div { text-align: right; }
    .budget-footer-totals span { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); font-weight: 700; }
    .budget-footer-totals strong { font-size: 1.05rem; color: var(--text-head); }
</style>
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-calculator-fill"></i>
                    Budget Builder
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
                        <li>Budget</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                @if (! $budget)

                    {{-- =================================================
                        NO BUDGET YET - single "Start Budget" prompt.
                    ================================================== --}}
                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="120">
                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi bi-calculator-fill"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-info-circle-fill"></i>
                                    Not Started
                                </div>

                                <h3>No budget yet for {{ $activity->reference }}</h3>
                                <p>Starting a budget creates its version 1, in Draft, ready for you to add line items.</p>
                            </div>
                        </div>

                        <div class="form-body">
                            @if ($canStart)
                                <form method="POST" action="{{ route('activities.budget.store', $activity) }}">
                                    @csrf
                                    <div class="form-actions portal-form-actions">
                                        <a href="{{ route('activities.show', $activity) }}" class="form-btn form-btn-light">
                                            <i class="bi bi-arrow-left"></i>
                                            Back to Activity
                                        </a>

                                        <button type="submit" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-plus-lg"></i>
                                            Start Budget
                                        </button>
                                    </div>
                                </form>
                            @else
                                <p class="text-muted mb-0">You don't have permission to start a budget for this activity.</p>
                            @endif
                        </div>
                    </div>

                @else

                    {{-- =================================================
                        BUILDER - items editable per
                        ActivityBudget::canBeEditedBy() (Draft/Rejected
                        always, or Assigned while it's currently assigned
                        back to the creator). The action bar below drives
                        Submit/Assign/Review/Approve/Reject/Cancel - Assign
                        covers both "forward it on" and "return it for
                        correction" (assigning it back to the creator) in
                        one action. A rejected budget is revisable and
                        resubmittable exactly like being assigned back -
                        see ActivityBudgetStatus's class docblock.
                    ================================================== --}}
                    <div class="form-section-title-row">
                        <div class="section-title form-section-title mb-0">
                            <span class="portal-section-eyebrow">Budget v{{ $budget->version }}</span>
                            <h2>{{ $budget->budget_code ?: $activity->displayAccountingCode() }}</h2>
                            <p>
                                <span class="status-badge {{ $budget->status->badgeClass() }}">
                                    <i class="bi {{ $budget->status->icon() }}"></i>
                                    {{ $budget->status->label() }}
                                </span>
                                &middot; {{ $budget->currency }}
                                @if ($budget->currentAssignee)
                                    &middot; Assigned to {{ $budget->currentAssignee->name }}
                                @endif
                            </p>
                        </div>

                        <div class="form-actions portal-form-actions">
                            @can('print', $budget)
                                <a href="{{ route('activities.budget.print', $activity) }}" target="_blank" class="form-btn form-btn-light" title="Print or save as PDF">
                                    <i class="bi bi-printer"></i>
                                    Print / PDF
                                </a>

                                <a href="{{ route('activities.budget.export', $activity) }}" class="form-btn form-btn-light" title="Download as CSV (Excel)">
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

                    {{-- =================================================
                        ACTION BAR - every button is policy-gated, so the
                        visible set already matches what the backend would
                        actually allow.
                    ================================================== --}}
                    <div class="form-card portal-form-card mb-4" data-aos="fade-up" data-aos-delay="110">
                        <div class="form-body">
                            <div class="form-actions portal-form-actions flex-wrap">

                                @php
                                    $isAssignedBackToCreator = $budget->status === \App\Enums\ActivityBudgetStatus::Assigned
                                        && $budget->current_assignee_id === $budget->created_by;
                                @endphp

                                @can('submit', $budget)
                                    <form action="{{ route('activities.budget.submit', $activity) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-send-check-fill"></i>
                                            {{ ($isAssignedBackToCreator || in_array($budget->status, [\App\Enums\ActivityBudgetStatus::Returned, \App\Enums\ActivityBudgetStatus::Rejected], true)) ? 'Resubmit' : 'Submit' }}
                                        </button>
                                    </form>
                                @endcan

                                @can('assign', $budget)
                                    <button type="button" class="form-btn form-btn-light" data-bs-toggle="modal" data-bs-target="#assignBudgetModal">
                                        <i class="bi bi-person-arms-up"></i>
                                        Assign
                                    </button>
                                @endcan

                                @can('review', $budget)
                                    @if (in_array($budget->status, [\App\Enums\ActivityBudgetStatus::Assigned], true))
                                        <form action="{{ route('activities.budget.review', $activity) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="form-btn form-btn-light">
                                                <span class="form-btn-spinner"></span>
                                                <i class="bi bi-search"></i>
                                                Mark Under Review
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @can('approve', $budget)
                                    <button type="button" class="form-btn form-btn-primary" data-bs-toggle="modal" data-bs-target="#approveBudgetModal">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Approve
                                    </button>
                                @endcan

                                @can('reject', $budget)
                                    <button type="button" class="form-btn form-btn-danger" data-bs-toggle="modal" data-bs-target="#rejectBudgetModal">
                                        <i class="bi bi-x-circle-fill"></i>
                                        Reject
                                    </button>
                                @endcan

                                @can('cancel', $budget)
                                    <button type="button" class="form-btn form-btn-danger" data-bs-toggle="modal" data-bs-target="#cancelBudgetModal">
                                        <i class="bi bi-slash-circle-fill"></i>
                                        Cancel
                                    </button>
                                @endcan

                            </div>
                        </div>
                    </div>

                    @if ($isAssignedBackToCreator && ($latestReason = $budget->assignments->firstWhere('assigned_to', $budget->created_by)?->comment))
                        <div class="form-alert form-alert-warning mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-reply-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Assigned back to you for correction</strong>
                                <p class="form-alert-message">{{ $latestReason }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Legacy only - a pre-existing row saved under the
                        old dedicated Returned status before this action
                        was merged into Assign; nothing produces this
                        anymore. --}}
                    @if ($budget->status === \App\Enums\ActivityBudgetStatus::Returned && $budget->returned_reason)
                        <div class="form-alert form-alert-warning mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-reply-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Returned for correction</strong>
                                <p class="form-alert-message">{{ $budget->returned_reason }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($budget->status === \App\Enums\ActivityBudgetStatus::Rejected && $budget->rejection_reason)
                        <div class="form-alert form-alert-danger mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Rejected</strong>
                                <p class="form-alert-message">{{ $budget->rejection_reason }}</p>
                                <p class="form-alert-message">You can revise the line items below and resubmit.</p>
                            </div>
                        </div>
                    @endif

                    @if ($budget->status === \App\Enums\ActivityBudgetStatus::Cancelled && $budget->cancellation_reason)
                        <div class="form-alert form-alert-danger mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-slash-circle-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Cancelled</strong>
                                <p class="form-alert-message">{{ $budget->cancellation_reason }}</p>
                            </div>
                        </div>
                    @endif

                    @unless ($canEdit)
                        <div class="form-alert form-alert-warning mb-4">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Read-only</strong>
                                <p class="form-alert-message">This budget is {{ $budget->status->label() }} and can no longer be edited here.</p>
                            </div>
                        </div>
                    @endunless

                    <form method="POST" action="{{ route('activities.budget.update', $activity) }}" id="budgetBuilderForm" autocomplete="off" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="form-card portal-form-card mb-4" data-aos="fade-up" data-aos-delay="120">
                            <div class="form-body">
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="budget_code" class="form-label">Budget / Print Code <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-upc-scan"></i>
                                            <input type="text" id="budget_code" name="budget_code"
                                                value="{{ old('budget_code', $budget->budget_code) }}"
                                                class="form-control-custom @error('budget_code') is-invalid @enderror"
                                                maxlength="100" placeholder="Defaults to the activity's accounting code"
                                                @disabled(! $canEdit)>
                                        </div>
                                        @error('budget_code')<span class="form-server-error">{{ $message }}</span>@enderror
                                    </div>

                                    <div class="form-field">
                                        <label for="requested_advance_amount" class="form-label">Requested Advance <small class="text-muted">(optional)</small></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-cash-coin"></i>
                                            <input type="number" id="requested_advance_amount" name="requested_advance_amount" step="0.01" min="0"
                                                value="{{ old('requested_advance_amount', $budget->requested_advance_amount) }}"
                                                class="form-control-custom @error('requested_advance_amount') is-invalid @enderror"
                                                @disabled(! $canEdit)>
                                        </div>
                                        <span class="form-input-help">How much should be disbursed upfront, in {{ $budget->currency }}. Leave blank if none.</span>
                                        @error('requested_advance_amount')<span class="form-server-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="160">
                            <div class="form-card-header">
                                <div class="form-card-icon">
                                    <i class="bi bi-list-columns-reverse"></i>
                                </div>

                                <div class="form-card-heading">
                                    <div class="form-card-badge">
                                        <i class="bi bi-grid-3x3-gap-fill"></i>
                                        Line Items
                                    </div>

                                    <h3>Budget Lines</h3>
                                    <p>Totals recalculate automatically as you type, and are always verified again on save.</p>
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
                                                <th>Category</th>
                                                <th>Component</th>
                                                <th>Description</th>
                                                <th>Unit</th>
                                                <th>Qty</th>
                                                <th>Freq.</th>
                                                <th>Unit Cost</th>
                                                <th>Total</th>
                                                <th>Payment</th>
                                                <th>Cash</th>
                                                <th>Invoice</th>
                                                <th>Notes</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody id="budgetItemsBody">
                                            @forelse ($rows as $index => $row)
                                                @include('activities.budget._item-row', [
                                                    'index' => $index,
                                                    'row' => $row,
                                                    'categories' => $categories,
                                                    'componentsByCategory' => $componentsByCategory,
                                                    'paymentModes' => \App\Enums\BudgetItemPaymentMode::options(),
                                                ])
                                            @empty
                                                {{-- No rows yet - the "Add Line" button below adds the first one via JS. --}}
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                </fieldset>

                                @if ($canEdit)
                                    <button type="button" id="addBudgetLineBtn" class="form-btn form-btn-light mt-3">
                                        <i class="bi bi-plus-lg"></i>
                                        Add Line
                                    </button>
                                @endif

                                <div class="budget-footer-totals">
                                    <div>
                                        <span>Cash</span>
                                        <strong><span id="footerCurrency">{{ $budget->currency }}</span> <span id="footerCashTotal">0.00</span></strong>
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
                                        <button type="submit" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-save"></i>
                                            Save Budget
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </form>

                    {{-- Blank-row template the "Add Line" button clones - kept
                         outside the <form> would still be fine since it's a
                         <template>, but it lives right after the table body
                         for readability. Its content is inert until JS
                         clones it, so items[__INDEX__][...] here never posts
                         anything on a real submit. --}}
                    @if ($canEdit)
                        <template id="budgetItemRowTemplate">
                            @include('activities.budget._item-row', [
                                'index' => '__INDEX__',
                                'row' => null,
                                'categories' => $categories,
                                'componentsByCategory' => $componentsByCategory,
                                'paymentModes' => \App\Enums\BudgetItemPaymentMode::options(),
                            ])
                        </template>
                    @endif

                    <div class="mt-4">
                        @include('activities.budget._timeline', ['budget' => $budget])
                    </div>

                @endif

            </div>
        </section>

        {{-- =============================================================
            MODALS - each posts to its own workflow route; opened via
            data-bs-toggle="modal" on the action bar buttons above.
            Mirrors payment-requests.show's modal pattern exactly.
        ============================================================== --}}
        @if ($budget)
            @can('assign', $budget)
                <div class="modal fade" id="assignBudgetModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('activities.budget.assign', $activity) }}" class="form-body">
                                @csrf
                                <div class="form-card-header">
                                    <div class="form-card-icon"><i class="bi bi-person-arms-up"></i></div>
                                    <div class="form-card-heading">
                                        <h3>Assign Budget</h3>
                                        <p>Choose who should act on this budget next - forward it to a reviewer, or assign it straight back to {{ $budget->creator->name ?? 'its creator' }} to send it back for correction.</p>
                                    </div>
                                </div>

                                <div class="form-section-card">
                                    <div class="form-grid">
                                        <div class="form-field form-grid-full">
                                            <label for="budget_assigned_to" class="form-label">Assign To <span>*</span></label>
                                            <div class="form-input-wrap">
                                                <i class="bi bi-person-fill"></i>
                                                <select id="budget_assigned_to" name="assigned_to" class="form-control-custom" required>
                                                    <option value="">Select a staff member...</option>
                                                    @foreach ($assignableUsers as $assignable)
                                                        <option value="{{ $assignable->id }}">{{ $assignable->name }} ({{ ucfirst($assignable->role) }}){{ $assignable->id === $budget->created_by ? ' — creator' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-field form-grid-full">
                                            <label for="budget_assign_reason" class="form-label">Reason <span>*</span></label>
                                            <div class="form-textarea-wrap">
                                                <textarea id="budget_assign_reason" name="reason" rows="2" class="form-control-custom" maxlength="2000" required placeholder="Why is this being assigned to them - e.g. 'please review', 'looks good, forwarding for approval', 'please fix the unit costs on lines A2-A4'..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions portal-form-actions">
                                    <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="form-btn form-btn-primary">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-send-check-fill"></i>
                                        Assign
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('approve', $budget)
                <div class="modal fade" id="approveBudgetModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('activities.budget.approve', $activity) }}" class="form-body">
                                @csrf
                                <div class="form-card-header">
                                    <div class="form-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                                    <div class="form-card-heading">
                                        <h3>Approve Budget</h3>
                                        <p>
                                            {{ $activity->reference }} &middot; {{ $budget->currency }} {{ number_format((float) $budget->total_overall, 2) }} overall
                                            @if ((float) $budget->requested_advance_amount > 0)
                                                &middot; Advance requested: {{ $budget->currency }} {{ number_format((float) $budget->requested_advance_amount, 2) }}
                                            @endif
                                        </p>
                                        <p>Approving confirms the requested advance has been provided. This is a terminal action for this budget version.</p>
                                    </div>
                                </div>

                                <div class="form-section-card">
                                    <div class="form-grid">
                                        <div class="form-field form-grid-full">
                                            <label for="budget_approve_comment" class="form-label">Comment</label>
                                            <div class="form-textarea-wrap">
                                                <textarea id="budget_approve_comment" name="comment" rows="2" class="form-control-custom" maxlength="2000"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions portal-form-actions">
                                    <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="form-btn form-btn-primary">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-check-circle-fill"></i>
                                        Confirm Approval
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('reject', $budget)
                <div class="modal fade" id="rejectBudgetModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('activities.budget.reject', $activity) }}" class="form-body">
                                @csrf
                                <div class="form-card-header">
                                    <div class="form-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                                    <div class="form-card-heading">
                                        <h3>Reject Budget</h3>
                                        <p>The creator will be able to revise the line items and resubmit, same as a return for correction.</p>
                                    </div>
                                </div>

                                <div class="form-section-card">
                                    <div class="form-grid">
                                        <div class="form-field form-grid-full">
                                            <label for="budget_reject_reason" class="form-label">Reason <span>*</span></label>
                                            <div class="form-textarea-wrap">
                                                <textarea id="budget_reject_reason" name="reason" rows="3" class="form-control-custom" maxlength="2000" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions portal-form-actions">
                                    <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="form-btn form-btn-danger">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-x-circle-fill"></i>
                                        Confirm Rejection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('cancel', $budget)
                <div class="modal fade" id="cancelBudgetModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('activities.budget.cancel', $activity) }}" class="form-body">
                                @csrf
                                <div class="form-card-header">
                                    <div class="form-card-icon"><i class="bi bi-slash-circle-fill"></i></div>
                                    <div class="form-card-heading">
                                        <h3>Cancel Budget</h3>
                                        <p>This marks the budget as cancelled. It cannot be undone from here.</p>
                                    </div>
                                </div>

                                <div class="form-section-card">
                                    <div class="form-grid">
                                        <div class="form-field form-grid-full">
                                            <label for="budget_cancel_reason" class="form-label">Reason <small class="text-muted">(optional)</small></label>
                                            <div class="form-textarea-wrap">
                                                <textarea id="budget_cancel_reason" name="reason" rows="3" class="form-control-custom" maxlength="2000"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions portal-form-actions">
                                    <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="form-btn form-btn-danger">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-slash-circle-fill"></i>
                                        Confirm Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tbody = document.getElementById('budgetItemsBody');
    var addBtn = document.getElementById('addBudgetLineBtn');
    var template = document.getElementById('budgetItemRowTemplate');
    if (! tbody) return;

    var nextIndex = {{ (int) count($rows) }};

    function toFloat(value) {
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function recalcRow(row) {
        var qty = toFloat(row.querySelector('[data-role="qty"]').value);
        var freq = toFloat(row.querySelector('[data-role="frequency"]').value);
        var unitCost = toFloat(row.querySelector('[data-role="unit-cost"]').value);
        var total = Math.round(qty * freq * unitCost * 100) / 100;

        row.querySelector('[data-role="total"]').value = total.toFixed(2);

        var modeSelect = row.querySelector('[data-role="payment-mode"]');
        var cashInput = row.querySelector('[data-role="cash-amount"]');
        var invoiceInput = row.querySelector('[data-role="invoice-amount"]');
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

        return total;
    }

    function recalcFooter() {
        var cashTotal = 0;
        var invoiceTotal = 0;

        tbody.querySelectorAll('.budget-item-row').forEach(function (row) {
            cashTotal += toFloat(row.querySelector('[data-role="cash-amount"]').value);
            invoiceTotal += toFloat(row.querySelector('[data-role="invoice-amount"]').value);
        });

        var footerCash = document.getElementById('footerCashTotal');
        var footerInvoice = document.getElementById('footerInvoiceTotal');
        var footerOverall = document.getElementById('footerOverallTotal');

        if (footerCash) footerCash.textContent = cashTotal.toFixed(2);
        if (footerInvoice) footerInvoice.textContent = invoiceTotal.toFixed(2);
        if (footerOverall) footerOverall.textContent = (cashTotal + invoiceTotal).toFixed(2);
    }

    function filterComponents(row) {
        var categorySelect = row.querySelector('[data-role="category"]');
        var componentSelect = row.querySelector('[data-role="component"]');
        var categoryId = categorySelect.value;
        var currentValue = componentSelect.value;
        var currentOptionStillValid = false;

        Array.prototype.forEach.call(componentSelect.options, function (option) {
            if (! option.value) return; // the blank "—" option always stays visible

            var matches = option.dataset.categoryId === categoryId;
            option.hidden = ! matches;

            if (matches && option.value === currentValue) {
                currentOptionStillValid = true;
            }
        });

        if (! currentOptionStillValid) {
            componentSelect.value = '';
        }
    }

    function applyComponentDefaultMode(row) {
        var componentSelect = row.querySelector('[data-role="component"]');
        var selected = componentSelect.options[componentSelect.selectedIndex];
        var defaultMode = selected ? selected.dataset.defaultMode : '';

        if (defaultMode) {
            var modeSelect = row.querySelector('[data-role="payment-mode"]');
            modeSelect.value = defaultMode;
        }
    }

    function wireRow(row) {
        recalcRow(row);
        filterComponents(row);
    }

    tbody.querySelectorAll('.budget-item-row').forEach(wireRow);
    recalcFooter();

    tbody.addEventListener('input', function (event) {
        var row = event.target.closest('.budget-item-row');
        if (! row) return;

        if (event.target.matches('[data-role="qty"], [data-role="frequency"], [data-role="unit-cost"], [data-role="cash-amount"], [data-role="invoice-amount"]')) {
            if (! event.target.matches('[data-role="cash-amount"], [data-role="invoice-amount"]')) {
                recalcRow(row);
            }
            recalcFooter();
        }
    });

    tbody.addEventListener('change', function (event) {
        var row = event.target.closest('.budget-item-row');
        if (! row) return;

        if (event.target.matches('[data-role="category"]')) {
            filterComponents(row);
        }

        if (event.target.matches('[data-role="component"]')) {
            applyComponentDefaultMode(row);
            recalcRow(row);
            recalcFooter();
        }

        if (event.target.matches('[data-role="payment-mode"]')) {
            recalcRow(row);
            recalcFooter();
        }
    });

    tbody.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('[data-role="remove"]');
        if (! removeBtn) return;

        var row = removeBtn.closest('.budget-item-row');
        if (row) {
            row.remove();
            recalcFooter();
        }
    });

    if (addBtn && template) {
        addBtn.addEventListener('click', function () {
            var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            nextIndex++;

            var wrapper = document.createElement('tbody');
            wrapper.innerHTML = html;
            var newRow = wrapper.querySelector('.budget-item-row');

            tbody.appendChild(newRow);
            wireRow(newRow);
            recalcFooter();
        });
    }
});
</script>
@endpush
