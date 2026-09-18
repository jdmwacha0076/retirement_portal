@extends('layouts.admin')

@section('title')
Activities | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-clipboard2-data-fill"></i>
                    Activity Budget &amp; Retirement
                </div>

                <h1>Activities</h1>

                <p>{{ auth()->user()->isAdmin() ? 'Every activity registered across the portal.' : 'Activities you registered, coordinate, or have been assigned a budget/retirement on.' }}</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Activities</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Activity Directory</h2>
                        <p>Register a new activity, or open an existing one to build its budget.</p>
                    </div>

                    <a href="{{ route('activities.create') }}" class="form-btn form-btn-primary form-back-btn">
                        <i class="bi bi-plus-lg"></i>
                        New Activity
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
                                    <h3 class="web-table-title">Activities</h3>
                                    <div class="web-table-subtitle">Search and filter registered activities.</div>
                                </div>
                            </div>

                            <div class="web-table-actions">
                                <div class="web-table-stat">
                                    <i class="bi bi-database-check"></i>
                                    {{ $activities->total() }} records
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('activities.index') }}" class="web-table-search mb-4">
                            <div class="row g-3 align-items-end">

                                <div class="col-md-4 col-lg-4">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" name="search" class="form-control input-round"
                                            placeholder="Reference, title, or code..." value="{{ request('search') }}">
                                    </div>
                                </div>

                                <div class="col-md-3 col-lg-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select search-select">
                                        <option value="">All Statuses</option>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (auth()->user()->isAdmin())
                                    <div class="col-md-3 col-lg-3 d-flex align-items-center pb-2">
                                        <div class="form-check">
                                            <input type="checkbox" name="mine" value="1" id="mineFilter" class="form-check-input" @checked($mine) onchange="this.form.submit()">
                                            <label for="mineFilter" class="form-check-label">Mine only</label>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-lg-1 d-flex gap-2">
                                    <button type="submit" class="btn-primary w-100" title="Filter">
                                        <i class="bi bi-funnel-fill"></i>
                                    </button>
                                </div>

                            </div>

                            @if (request()->anyFilled(['search', 'status', 'mine']))
                                <div class="mt-3">
                                    <a href="{{ route('activities.index') }}" class="btn-secondary">
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
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Dates</th>
                                            <th>Coordinator</th>
                                            <th>Assigned To</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($activities as $activity)
                                            <tr>
                                                <td class="web-id-cell">{{ $activity->reference }}</td>
                                                <td>
                                                    <div class="web-user-cell">
                                                        <div class="web-user-avatar"><i class="bi bi-clipboard2-data"></i></div>
                                                        <div>
                                                            <div class="web-user-name">{{ $activity->title }}</div>
                                                            <div class="web-user-meta">by {{ $activity->creator->name ?? '—' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $activity->activityType->name ?? '—' }}</td>
                                                <td>{{ $activity->start_date->format('d M Y') }} – {{ $activity->end_date->format('d M Y') }}</td>
                                                <td>{{ $activity->coordinator->name ?? '—' }}</td>
                                                <td>{{ $activity->currentlyAssignedTo()->name ?? '—' }}</td>
                                                <td>
                                                    <span class="status-badge {{ $activity->status->badgeClass() }}">
                                                        <i class="bi {{ $activity->status->icon() }}"></i>
                                                        {{ $activity->status->label() }}
                                                    </span>
                                                </td>
                                                <td class="text-end table-actions-cell">
                                                    <a href="{{ route('activities.show', $activity) }}" class="portal-action-btn" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-clipboard2-data"></i></div>
                                                        <h6>No Activities Found</h6>
                                                        <p>No activities match the selected search criteria.</p>
                                                        <a href="{{ route('activities.create') }}" class="web-table-empty-btn text-decoration-none">
                                                            <i class="bi bi-plus-lg"></i>
                                                            New Activity
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($activities->hasPages())
                            <div class="web-table-pagination">
                                <div class="web-table-summary">
                                    Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }} of {{ $activities->total() }} activities
                                </div>
                                <div class="web-table-pages">
                                    {{ $activities->withQueryString()->links('pagination::simple-bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>

@endsection
