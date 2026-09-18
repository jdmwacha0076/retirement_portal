@extends('layouts.admin')

@section('title')
Budget Categories | Finance Settings | Retirement Portal
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

                <h1>Budget Categories</h1>

                <p>Transport, Meals &amp; Credit, Visa, Accommodation, and the other top-level groupings a budget's line items fall under. The A/B/C/D letters on a printed budget are assigned per-budget by category order - not fixed here.</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Finance Settings</li>
                        <li>Budget Categories</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                @include('budget-settings._subnav')

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Budget Categories</h2>
                        <p>Deactivated categories stay visible on historical budget lines but can no longer be selected for new ones.</p>
                    </div>

                    <button type="button" class="form-btn form-btn-primary form-back-btn" data-bs-toggle="modal" data-bs-target="#createBudgetCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                        New Category
                    </button>
                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-folder-fill"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Categories</h3>
                                    <div class="web-table-subtitle">{{ $budgetCategories->count() }} total.</div>
                                </div>
                            </div>
                        </div>

                        <div class="web-table-card">
                            <div class="table-responsive portal-table-inner">
                                <table class="table web-data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Components</th>
                                            <th>Sort Order</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($budgetCategories as $budgetCategory)
                                            <tr>
                                                <td class="web-id-cell">{{ $budgetCategory->code ?: '—' }}</td>
                                                <td>{{ $budgetCategory->name }}</td>
                                                <td>{{ $budgetCategory->components_count }}</td>
                                                <td>{{ $budgetCategory->sort_order }}</td>
                                                <td>
                                                    <span class="status-badge {{ $budgetCategory->is_active ? 'status-success' : 'status-archived' }}">
                                                        <i class="bi {{ $budgetCategory->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }}"></i>
                                                        {{ $budgetCategory->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-end table-actions-cell">
                                                    <button type="button" class="portal-action-btn" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editBudgetCategoryModal{{ $budgetCategory->id }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>

                                                    <form method="POST" action="{{ route('budget-settings.budget-categories.toggle-active', $budgetCategory) }}" class="d-inline"
                                                        data-confirm="{{ $budgetCategory->is_active ? 'Deactivate this budget category?' : 'Activate this budget category?' }}">
                                                        @csrf
                                                        <button type="submit" class="portal-action-btn" title="{{ $budgetCategory->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="bi {{ $budgetCategory->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editBudgetCategoryModal{{ $budgetCategory->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('budget-settings.budget-categories.update', $budgetCategory) }}">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Budget Category</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="form-field">
                                                                    <label class="form-label">Code</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-upc-scan"></i>
                                                                        <input type="text" name="code" value="{{ $budgetCategory->code }}" class="form-control-custom" maxlength="50">
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Name <span>*</span></label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-tag-fill"></i>
                                                                        <input type="text" name="name" value="{{ $budgetCategory->name }}" class="form-control-custom" maxlength="255" required>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Description</label>
                                                                    <div class="form-textarea-wrap">
                                                                        <textarea name="description" rows="2" class="form-control-custom" maxlength="2000">{{ $budgetCategory->description }}</textarea>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Sort Order</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-sort-numeric-down"></i>
                                                                        <input type="number" name="sort_order" value="{{ $budgetCategory->sort_order }}" class="form-control-custom" min="0">
                                                                    </div>
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
                                                <td colspan="6" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-folder-fill"></i></div>
                                                        <h6>No Budget Categories Yet</h6>
                                                        <p>Add the first one (e.g. Transport, Accommodation) before building any activity budgets.</p>
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

        <div class="modal fade" id="createBudgetCategoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('budget-settings.budget-categories.store') }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title">New Budget Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-field">
                                <label class="form-label">Code</label>
                                <div class="form-input-wrap">
                                    <i class="bi bi-upc-scan"></i>
                                    <input type="text" name="code" value="{{ old('code') }}" class="form-control-custom" maxlength="50" placeholder="e.g. TRA">
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Name <span>*</span></label>
                                <div class="form-input-wrap">
                                    <i class="bi bi-tag-fill"></i>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control-custom" maxlength="255" required placeholder="e.g. Transport">
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Description</label>
                                <div class="form-textarea-wrap">
                                    <textarea name="description" rows="2" class="form-control-custom" maxlength="2000">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Sort Order</label>
                                <div class="form-input-wrap">
                                    <i class="bi bi-sort-numeric-down"></i>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control-custom" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="form-btn form-btn-primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@endsection
