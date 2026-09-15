@extends('layouts.admin')

@section('title')
Notifications | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-bell-fill"></i>
                    Notifications
                </div>

                <h1>Notifications</h1>

                <p>Everything you've been alerted about, newest first.</p>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Notifications</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>All Notifications</h2>
                        <p>{{ $notifications->total() }} total.</p>
                    </div>

                    <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                        @csrf
                        <button type="submit" class="form-btn form-btn-light">
                            <i class="bi bi-check2-all"></i>
                            Mark All Read
                        </button>
                    </form>
                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-card">
                            <div class="table-responsive portal-table-inner">
                                <table class="table web-data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Message</th>
                                            <th>Reference</th>
                                            <th>When</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($notifications as $notification)
                                            <tr>
                                                <td>
                                                    @if ($notification->read_at)
                                                        <span class="status-badge status-secondary">
                                                            <i class="bi bi-check2"></i>
                                                            Read
                                                        </span>
                                                    @else
                                                        <span class="status-badge status-info">
                                                            <i class="bi bi-envelope-fill"></i>
                                                            New
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>{{ $notification->data['message'] ?? '—' }}</td>
                                                <td>{{ $notification->data['reference_number'] ?? '—' }}</td>
                                                <td>{{ $notification->created_at->diffForHumans() }}</td>
                                                <td class="text-end table-actions-cell">
                                                    <a href="{{ route('notifications.open', $notification->id) }}" class="portal-action-btn" title="Open">
                                                        <i class="bi bi-box-arrow-in-right"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-bell-slash"></i></div>
                                                        <h6>No Notifications Yet</h6>
                                                        <p>You'll see updates about your payment requests here.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($notifications->hasPages())
                            <div class="web-table-pagination">
                                <div class="web-table-summary">
                                    Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }}
                                </div>
                                <div class="web-table-pages">
                                    {{ $notifications->links('pagination::simple-bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>

@endsection
