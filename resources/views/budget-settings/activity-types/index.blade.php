@extends('layouts.admin')

@section('title')
Activity Types | Finance Settings | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page table-page data-table-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/form.css') }}" rel="stylesheet">
@endpush

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-gear-fill"></i>
                    Finance Settings
                </div>

                <h1>Activity Types</h1>

                <p>Training, Workshop, International Travel, and the other activity categories staff choose from when registering a new activity.</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Finance Settings</li>
                        <li>Activity Types</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                @include('budget-settings._subnav')

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Activity Types</h2>
                        <p>Deactivated types stay visible on historical activities but can no longer be selected for new ones.</p>
                    </div>

                    <button type="button" class="form-btn form-btn-primary form-back-btn" data-bs-toggle="modal" data-bs-target="#createActivityTypeModal">
                        <i class="bi bi-plus-lg"></i>
                        New Activity Type
                    </button>
                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-tags-fill"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Types</h3>
                                    <div class="web-table-subtitle">{{ $activityTypes->count() }} total.</div>
                                </div>
                            </div>
                        </div>

                        <div class="web-table-card">
                            <div class="table-responsive portal-table-inner">
                                <table class="table web-data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Activities</th>
                                            <th>Sort Order</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($activityTypes as $activityType)
                                            <tr>
                                                <td class="web-id-cell">{{ $activityType->name }}</td>
                                                <td>{{ $activityType->activities_count }}</td>
                                                <td>{{ $activityType->sort_order }}</td>
                                                <td>
                                                    <span class="status-badge {{ $activityType->is_active ? 'status-success' : 'status-archived' }}">
                                                        <i class="bi {{ $activityType->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }}"></i>
                                                        {{ $activityType->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-end table-actions-cell">
                                                    <button type="button" class="portal-action-btn" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editActivityTypeModal{{ $activityType->id }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>

                                                    <form method="POST" action="{{ route('budget-settings.activity-types.toggle-active', $activityType) }}" class="d-inline"
                                                        data-confirm="{{ $activityType->is_active ? 'Deactivate this activity type?' : 'Activate this activity type?' }}">
                                                        @csrf
                                                        @method('POST')
                                                        <button type="submit" class="portal-action-btn" title="{{ $activityType->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="bi {{ $activityType->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            {{-- Edit modal - server-rendered per row, pre-filled, no JS needed. --}}
                                            <div class="modal fade" id="editActivityTypeModal{{ $activityType->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('budget-settings.activity-types.update', $activityType) }}">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Activity Type</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="form-field">
                                                                    <label class="form-label">Name <span>*</span></label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-tag-fill"></i>
                                                                        <input type="text" name="name" value="{{ $activityType->name }}" class="form-control-custom" maxlength="255" required>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Sort Order</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-sort-numeric-down"></i>
                                                                        <input type="number" name="sort_order" value="{{ $activityType->sort_order }}" class="form-control-custom" min="0">
                                                                    </div>
                                                                    <span class="form-input-help">Lower numbers appear first in the picker.</span>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="form-btn form-btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-tags-fill"></i></div>
                                                        <h6>No Activity Types Yet</h6>
                                                        <p>Add the first one to let staff start registering activities.</p>
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

        {{-- Create modal --}}
        <div class="modal fade" id="createActivityTypeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('budget-settings.activity-types.store') }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title">New Activity Type</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-field">
                                <label class="form-label">Name <span>*</span></label>
                                <div class="form-input-wrap">
                                    <i class="bi bi-tag-fill"></i>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control-custom" maxlength="255" required placeholder="e.g. International Travel">
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Sort Order</label>
                                <div class="form-input-wrap">
                                    <i class="bi bi-sort-numeric-down"></i>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control-custom" min="0">
                                </div>
                                <span class="form-input-help">Lower numbers appear first in the picker.</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="form-btn form-btn-primary">Add Activity Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@endsection
