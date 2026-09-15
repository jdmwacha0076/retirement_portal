@extends('layouts.admin')

@section('title')
My Tasks | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-person-arms-up"></i>
                    My Tasks
                </div>

                <h1>My Tasks</h1>

                <p>Requests currently assigned to you and awaiting your action - different from "My Requests", which is everything you originally created.</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>My Tasks</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-list-check"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Awaiting Your Action</h3>
                                    <div class="web-table-subtitle">Assigned to you, returned to you, or otherwise waiting on you.</div>
                                </div>
                            </div>

                            <div class="web-table-actions">
                                <div class="web-table-stat">
                                    <i class="bi bi-database-check"></i>
                                    {{ $tasks->total() }} record(s)
                                </div>
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
                                            <th>Requested By</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($tasks as $paymentRequest)
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
                                                <td>{{ $paymentRequest->creator->name ?? '—' }}</td>
                                                <td class="text-end table-actions-cell">
                                                    <a href="{{ route('payment-requests.show', $paymentRequest) }}" class="portal-action-btn" title="Open">
                                                        <i class="bi bi-box-arrow-in-right"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-check2-circle"></i></div>
                                                        <h6>Nothing Waiting on You</h6>
                                                        <p>Requests assigned or returned to you will show up here.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($tasks->hasPages())
                            <div class="web-table-pagination">
                                <div class="web-table-summary">
                                    Showing {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} of {{ $tasks->total() }}
                                </div>
                                <div class="web-table-pages">
                                    {{ $tasks->withQueryString()->links('pagination::simple-bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>

@endsection
