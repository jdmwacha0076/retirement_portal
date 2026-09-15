@extends('layouts.admin')

@section('title')
Payment Requests | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-cash-stack"></i>
                    Payment Requests
                </div>

                <h1>{{ $isAdmin ? 'All Payment Requests' : 'Payment Requests' }}</h1>

                <p>{{ $isAdmin ? 'Every voucher across the portal.' : "Requests you created, hold, or have handled." }}</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Payment Requests</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Request Directory</h2>
                        <p>Search, filter, and open any payment request.</p>
                    </div>

                    <a href="{{ route('payment-requests.type-picker') }}" class="form-btn form-btn-primary form-back-btn">
                        <i class="bi bi-plus-lg"></i>
                        New Payment Request
                    </a>
                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-table"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Requests</h3>
                                    <div class="web-table-subtitle">Search and filter payment requests.</div>
                                </div>
                            </div>

                            <div class="web-table-actions">
                                <div class="web-table-stat">
                                    <i class="bi bi-database-check"></i>
                                    {{ $paymentRequests->total() }} records
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('payment-requests.index') }}" class="web-table-search mb-4">
                            <div class="row g-3 align-items-end">

                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" name="search" class="form-control input-round"
                                            placeholder="Reference or payee..." value="{{ request('search') }}">
                                    </div>
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select search-select">
                                        <option value="">All Types</option>
                                        @foreach ($types as $value => $label)
                                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select search-select">
                                        <option value="">All Statuses</option>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control input-round" value="{{ request('date_from') }}">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control input-round" value="{{ request('date_to') }}">
                                </div>

                                <div class="col-lg-1 d-flex gap-2">
                                    <button type="submit" class="btn-primary w-100" title="Filter">
                                        <i class="bi bi-funnel-fill"></i>
                                    </button>
                                </div>

                            </div>

                            @if (request()->anyFilled(['search', 'type', 'status', 'date_from', 'date_to']))
                                <div class="mt-3">
                                    <a href="{{ route('payment-requests.index') }}" class="btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        Clear Filters
                                    </a>
                                </div>
                            @endif
                        </form>

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
                                            <th>Date</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($paymentRequests as $paymentRequest)
                                            <tr>
                                                <td class="web-id-cell">{{ $paymentRequest->displayReference() }}</td>
                                                <td>
                                                    <div class="web-user-cell">
                                                        <div class="web-user-avatar"><i class="bi bi-person"></i></div>
                                                        <div>
                                                            <div class="web-user-name">{{ $paymentRequest->payee_name }}</div>
                                                            <div class="web-user-meta">by {{ $paymentRequest->creator->name ?? '—' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $paymentRequest->payment_type->shortLabel() }}</td>
                                                <td>{{ $paymentRequest->formattedAmount() }}</td>
                                                <td>
                                                    <span class="status-badge {{ $paymentRequest->status->badgeClass() }}">
                                                        <i class="bi {{ $paymentRequest->status->icon() }}"></i>
                                                        {{ $paymentRequest->status->label() }}
                                                    </span>
                                                </td>
                                                <td>{{ $paymentRequest->currentAssignee->name ?? '—' }}</td>
                                                <td>{{ $paymentRequest->payment_date->format('d M Y') }}</td>
                                                <td class="text-end table-actions-cell">
                                                    <a href="{{ route('payment-requests.show', $paymentRequest) }}" class="portal-action-btn" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-cash-stack"></i></div>
                                                        <h6>No Payment Requests Found</h6>
                                                        <p>No requests match the selected search criteria.</p>
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

                        @if ($paymentRequests->hasPages())
                            <div class="web-table-pagination">
                                <div class="web-table-summary">
                                    Showing {{ $paymentRequests->firstItem() }}–{{ $paymentRequests->lastItem() }} of {{ $paymentRequests->total() }} requests
                                </div>
                                <div class="web-table-pages">
                                    {{ $paymentRequests->withQueryString()->links('pagination::simple-bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>

@endsection
