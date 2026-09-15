@extends('layouts.admin')

@section('title')
Dashboard | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

    @php
        $user = auth()->user();
    @endphp

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </div>

                <h1>Welcome back, {{ $user->name }}</h1>

                <p>{{ $isAdmin ? 'An overview of every payment request across the portal.' : 'An overview of your payment requests and what needs your attention.' }}</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Payment Requests</h2>
                        <p>{{ $isAdmin ? 'Portal-wide status summary.' : 'Your requests, drafts, and outstanding tasks.' }}</p>
                    </div>

                    <a href="{{ route('payment-requests.type-picker') }}" class="form-btn form-btn-primary form-back-btn">
                        <i class="bi bi-plus-lg"></i>
                        New Payment Request
                    </a>
                </div>

                <div class="row g-4 mb-4">

                    @if ($isAdmin)
                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Total Requests</h4>
                                    <div class="web-top-card-value">{{ $stats['total'] }}</div>
                                    <p>Across the whole portal.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Awaiting Action</h4>
                                    <div class="web-top-card-value">{{ $stats['awaiting_action'] }}</div>
                                    <p>Submitted, assigned, or under review.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Ready for Payment</h4>
                                    <div class="web-top-card-value">{{ $stats['ready_for_payment'] }}</div>
                                    <p>Approved and waiting to be processed.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon web-top-card-success">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Paid This Month</h4>
                                    <div class="web-top-card-value">{{ $stats['paid_this_month'] }}</div>
                                    <p>Completed payments this month.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>My Requests</h4>
                                    <div class="web-top-card-value">{{ $stats['my_requests'] }}</div>
                                    <p>Requests you've created.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Drafts</h4>
                                    <div class="web-top-card-value">{{ $stats['my_drafts'] }}</div>
                                    <p>Not yet submitted.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon">
                                    <i class="bi bi-person-arms-up"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>My Tasks</h4>
                                    <div class="web-top-card-value">{{ $stats['my_tasks'] }}</div>
                                    <p>Awaiting your action.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="web-top-card h-100">
                                <div class="web-top-card-icon web-top-card-success">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="web-top-card-content">
                                    <h4>Paid</h4>
                                    <div class="web-top-card-value">{{ $stats['my_paid'] }}</div>
                                    <p>Requests you created that got paid.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Recent Activity</h3>
                                    <div class="web-table-subtitle">{{ $isAdmin ? 'The most recently updated requests portal-wide.' : "Requests you've created or currently hold." }}</div>
                                </div>
                            </div>

                            <div class="web-table-actions">
                                <a href="{{ route('payment-requests.index') }}" class="form-btn form-btn-light">
                                    <i class="bi bi-table"></i>
                                    View All
                                </a>
                            </div>
                        </div>

                        <div class="web-table-card">
                            <div class="table-responsive portal-table-inner">
                                <table class="table web-data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Payee</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($recent as $paymentRequest)
                                            <tr>
                                                <td class="web-id-cell">{{ $paymentRequest->displayReference() }}</td>
                                                <td>{{ $paymentRequest->payee_name }}</td>
                                                <td>{{ $paymentRequest->payment_type->shortLabel() }}</td>
                                                <td>{{ $paymentRequest->formattedAmount() }}</td>
                                                <td>
                                                    <span class="status-badge {{ $paymentRequest->status->badgeClass() }}">
                                                        <i class="bi {{ $paymentRequest->status->icon() }}"></i>
                                                        {{ $paymentRequest->status->label() }}
                                                    </span>
                                                </td>
                                                <td>{{ $paymentRequest->currentAssignee->name ?? '—' }}</td>
                                                <td class="text-end table-actions-cell">
                                                    <a href="{{ route('payment-requests.show', $paymentRequest) }}" class="portal-action-btn" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-cash-stack"></i></div>
                                                        <h6>Nothing Here Yet</h6>
                                                        <p>{{ $isAdmin ? 'No payment requests have been created yet.' : "You haven't created or been assigned any payment requests yet." }}</p>
                                                        <a href="{{ route('payment-requests.type-picker') }}" class="web-table-empty-btn text-decoration-none">
                                                            <i class="bi bi-plus-lg"></i>
                                                            New Payment Request
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </section>

@endsection
