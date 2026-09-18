@extends('layouts.admin')

@section('title')
{{ $activity->reference }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

    @php
        $status = $activity->status;
    @endphp

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi {{ $activity->activityType->icon ?? 'bi-clipboard2-data-fill' }}"></i>
                    {{ $activity->activityType->name ?? 'Activity' }}
                </div>

                <h1>{{ $activity->reference }}</h1>

                <p>
                    <span class="status-badge {{ $status->badgeClass() }}">
                        <i class="bi {{ $status->icon() }}"></i>
                        {{ $status->label() }}
                    </span>
                </p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('activities.index') }}">Activities</a></li>
                        <li>{{ $activity->reference }}</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">Activity Details</span>
                        <h2>{{ $activity->title }}</h2>
                        <p>{{ $activity->activityType->name ?? '—' }} &middot; {{ $activity->displayAccountingCode() }}</p>
                    </div>

                    <a href="{{ route('activities.index') }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Go Back
                    </a>
                </div>

                {{-- =========================================================
                    ACTION BAR - every button is policy-gated, so the visible
                    set already matches what the backend would actually allow.
                ========================================================== --}}
                <div class="form-card portal-form-card mb-4" data-aos="fade-up" data-aos-delay="120">
                    <div class="form-body">
                        <div class="form-actions portal-form-actions flex-wrap">

                            @can('update', $activity)
                                <a href="{{ route('activities.edit', $activity) }}" class="form-btn form-btn-light">
                                    <i class="bi bi-pencil-square"></i>
                                    Edit
                                </a>
                            @endcan

                            @can('cancel', $activity)
                                <button type="button" class="form-btn form-btn-danger" data-bs-toggle="modal" data-bs-target="#cancelActivityModal">
                                    <i class="bi bi-slash-circle-fill"></i>
                                    Cancel
                                </button>
                            @endcan

                        </div>
                    </div>
                </div>

                @if ($status === \App\Enums\ActivityStatus::Cancelled && $activity->cancellation_reason)
                    <div class="form-alert form-alert-danger mb-4">
                        <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                        <div class="form-alert-content">
                            <strong class="form-alert-title">Cancelled</strong>
                            <p class="form-alert-message">{{ $activity->cancellation_reason }}</p>
                        </div>
                    </div>
                @endif

                <div class="form-layout">

                    {{-- =====================================================
                        SIDEBAR - who/when at a glance
                    ====================================================== --}}
                    <aside class="portal-profile-aside" data-aos="fade-up" data-aos-delay="140">

                        <div class="form-guide-card portal-profile-card">
                            <div class="form-guide-badge">
                                <i class="bi bi-people-fill"></i>
                                People
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-person-fill"></i></div>
                                <div>
                                    <h4>Registered By</h4>
                                    <p>{{ $activity->creator->name ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-person-workspace"></i></div>
                                <div>
                                    <h4>Currently Assigned To</h4>
                                    <p>{{ $activity->currentlyAssignedTo()->name ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-person-badge-fill"></i></div>
                                <div>
                                    <h4>Coordinator</h4>
                                    <p>{{ $activity->coordinator->name ?? '—' }}</p>
                                </div>
                            </div>

                            @if ($activity->cancelledBy)
                                <div class="form-guide-mini-card">
                                    <div class="form-guide-mini-icon"><i class="bi bi-x-circle-fill"></i></div>
                                    <div>
                                        <h4>Cancelled By</h4>
                                        <p>{{ $activity->cancelledBy->name }} &middot; {{ $activity->cancelled_at?->format('d M Y') }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <h4>Registered</h4>
                                    <p>{{ $activity->created_at->format('d M Y, g:ia') }}</p>
                                </div>
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-calendar-range"></i></div>
                                <div>
                                    <h4>Dates</h4>
                                    <p>{{ $activity->start_date->format('d M Y') }} – {{ $activity->end_date->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>

                    </aside>

                    {{-- =====================================================
                        MAIN DETAIL PANEL
                    ====================================================== --}}
                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="180">

                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi bi-clipboard2-data-fill"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-eye-fill"></i>
                                    Details
                                </div>

                                <h3>{{ $activity->title }}</h3>
                                <p>{{ $activity->purpose }}</p>
                            </div>
                        </div>

                        <div class="form-body">

                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <h4>Overview</h4>
                                    <p>Core activity information.</p>
                                </div>

                                <div class="detail-view-grid">

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-hash"></i> Reference</span>
                                        <strong>{{ $activity->reference }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-upc-scan"></i> Budget / Accounting Code</span>
                                        <strong>{{ $activity->accounting_code ?: '— (uses reference)' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-tags-fill"></i> Activity Type</span>
                                        <strong>{{ $activity->activityType->name ?? '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-diagram-3-fill"></i> Project / Program</span>
                                        <strong>{{ $activity->program ?: '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-geo-alt-fill"></i> Location</span>
                                        <strong>{{ $activity->location ?: '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-flag-fill"></i> Country</span>
                                        <strong>{{ $activity->country ?: '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-building"></i> Venue</span>
                                        <strong>{{ $activity->venue ?: '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-people-fill"></i> Participants</span>
                                        <strong>{{ $activity->participant_count ?? '—' }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-cash"></i> Budget Currency</span>
                                        <strong>{{ $activity->currency }}</strong>
                                    </div>

                                    <div class="detail-view-item detail-view-item--full">
                                        <span><i class="bi bi-card-text"></i> Purpose / Objective</span>
                                        <strong>{{ $activity->purpose }}</strong>
                                    </div>

                                    @if ($activity->description)
                                        <div class="detail-view-item detail-view-item--full">
                                            <span><i class="bi bi-journal-text"></i> Description / Notes</span>
                                            <strong>{{ $activity->description }}</strong>
                                        </div>
                                    @endif

                                </div>
                            </div>

                            @php
                                $currentBudget = $activity->currentBudget;
                            @endphp

                            <div class="form-section-card">
                                <div class="form-section-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h4>Budget</h4>
                                        <p>{{ $currentBudget ? 'Version '.$currentBudget->version.' — '.($currentBudget->budget_code ?: $activity->reference) : 'No budget has been built for this activity yet.' }}</p>
                                    </div>

                                    @if ($currentBudget)
                                        <span class="status-badge {{ $currentBudget->status->badgeClass() }}">
                                            <i class="bi {{ $currentBudget->status->icon() }}"></i>
                                            {{ $currentBudget->status->label() }}
                                        </span>
                                    @endif
                                </div>

                                @if ($currentBudget)
                                    <div class="detail-view-grid">
                                        <div class="detail-view-item">
                                            <span><i class="bi bi-cash-coin"></i> Cash Total</span>
                                            <strong>{{ $currentBudget->currency }} {{ number_format((float) $currentBudget->total_cash, 2) }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-receipt"></i> Invoice Total</span>
                                            <strong>{{ $currentBudget->currency }} {{ number_format((float) $currentBudget->total_invoice, 2) }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-cash-stack"></i> Overall Total</span>
                                            <strong>{{ $currentBudget->currency }} {{ number_format((float) $currentBudget->total_overall, 2) }}</strong>
                                        </div>
                                    </div>

                                    <a href="{{ route('activities.budget.edit', $activity) }}" class="form-btn form-btn-light mt-3">
                                        <i class="bi bi-pencil-square"></i>
                                        Open Budget Builder
                                    </a>
                                @elseif (auth()->user()->can('create', [\App\Models\ActivityBudget::class, $activity]))
                                    <p class="text-muted">This activity doesn't have a budget yet.</p>

                                    <a href="{{ route('activities.budget.edit', $activity) }}" class="form-btn form-btn-primary">
                                        <i class="bi bi-plus-lg"></i>
                                        Build Budget
                                    </a>
                                @else
                                    <p class="text-muted mb-0">No budget has been built for this activity yet.</p>
                                @endif
                            </div>

                            @php
                                $currentRetirement = $currentBudget?->currentRetirement;
                            @endphp

                            @if ($currentBudget && $currentBudget->status === \App\Enums\ActivityBudgetStatus::Approved)
                                <div class="form-section-card">
                                    <div class="form-section-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <h4>Retirement</h4>
                                            <p>{{ $currentRetirement ? $currentRetirement->reference : 'Accounting for how the advance was actually spent.' }}</p>
                                        </div>

                                        @if ($currentRetirement)
                                            <span class="status-badge {{ $currentRetirement->status->badgeClass() }}">
                                                <i class="bi {{ $currentRetirement->status->icon() }}"></i>
                                                {{ $currentRetirement->status->label() }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($currentRetirement)
                                        <div class="detail-view-grid">
                                            <div class="detail-view-item">
                                                <span><i class="bi bi-cash-coin"></i> Advance Provided</span>
                                                <strong>{{ $currentBudget->currency }} {{ number_format((float) $currentRetirement->total_advanced, 2) }}</strong>
                                            </div>

                                            <div class="detail-view-item">
                                                <span><i class="bi bi-receipt"></i> Actual Spent</span>
                                                <strong>{{ $currentBudget->currency }} {{ number_format((float) $currentRetirement->total_actual_overall, 2) }}</strong>
                                            </div>

                                            <div class="detail-view-item">
                                                <span><i class="bi bi-cash-stack"></i> Unspent / Due</span>
                                                <strong>
                                                    @if ((float) $currentRetirement->reimbursement_due_amount > 0)
                                                        {{ $currentBudget->currency }} {{ number_format((float) $currentRetirement->reimbursement_due_amount, 2) }} reimbursement due
                                                    @else
                                                        {{ $currentBudget->currency }} {{ number_format((float) $currentRetirement->unspent_advance_amount, 2) }} unspent
                                                    @endif
                                                </strong>
                                            </div>
                                        </div>

                                        <a href="{{ route('activities.retirement.edit', $activity) }}" class="form-btn form-btn-light mt-3">
                                            <i class="bi bi-pencil-square"></i>
                                            Open Retirement
                                        </a>
                                    @elseif (auth()->user()->can('create', [\App\Models\ActivityRetirement::class, $currentBudget]))
                                        <p class="text-muted">This activity's advance hasn't been retired yet.</p>

                                        <a href="{{ route('activities.retirement.edit', $activity) }}" class="form-btn form-btn-primary">
                                            <i class="bi bi-plus-lg"></i>
                                            Start Retirement
                                        </a>
                                    @else
                                        <p class="text-muted mb-0">This activity's advance hasn't been retired yet.</p>
                                    @endif
                                </div>
                            @endif

                            @include('activities._timeline', ['activity' => $activity])

                        </div>
                    </div>

                </div>

            </div>
        </section>

    {{-- =========================================================
        CANCEL MODAL
    ========================================================== --}}
    @can('cancel', $activity)
        <div class="modal fade" id="cancelActivityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('activities.cancel', $activity) }}" method="POST">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title">Cancel {{ $activity->reference }}?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <p>This marks the activity as cancelled. It cannot be undone from here.</p>

                            <label for="cancellation_reason" class="form-label">Reason <span>*</span></label>
                            <textarea id="cancellation_reason" name="cancellation_reason" rows="3" class="form-control" required maxlength="2000"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
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

@endsection
