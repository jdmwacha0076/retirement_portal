@extends('layouts.admin')

@section('title')
Budget Components | Finance Settings | Retirement Portal
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

                <h1>Budget Components</h1>

                <p>The specific line items staff pick from within a category - e.g. "Airport Transfer" under Transport. Mark a component as requiring a supporting document to block retirement submission until a receipt is attached for it.</p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li>Finance Settings</li>
                        <li>Budget Components</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="dashboard-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                @include('budget-settings._subnav')

                <div class="form-section-title-row mb-4">
                    <div class="section-title form-section-title mb-0">
                        <h2>Budget Components</h2>
                        <p>Deactivated components stay visible on historical budget lines but can no longer be selected for new ones.</p>
                    </div>

                    @if ($activeCategories->isEmpty())
                        <span class="form-input-help">Add an active Budget Category first before adding components.</span>
                    @else
                        <button type="button" class="form-btn form-btn-primary form-back-btn" data-bs-toggle="modal" data-bs-target="#createBudgetComponentModal">
                            <i class="bi bi-plus-lg"></i>
                            New Component
                        </button>
                    @endif
                </div>

                <div class="web-table-wrapper">
                    <div class="web-table-view">

                        <div class="web-table-header">
                            <div class="web-table-header-left">
                                <div class="web-table-title-icon">
                                    <i class="bi bi-list-check"></i>
                                </div>

                                <div>
                                    <h3 class="web-table-title">Components</h3>
                                    <div class="web-table-subtitle">{{ $budgetComponents->count() }} total.</div>
                                </div>
                            </div>
                        </div>

                        <div class="web-table-card">
                            <div class="table-responsive portal-table-inner">
                                <table class="table web-data-table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Name</th>
                                            <th>Default Unit</th>
                                            <th>Default Mode</th>
                                            <th>Document Required</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($budgetComponents as $budgetComponent)
                                            <tr>
                                                <td>{{ $budgetComponent->category->name ?? '—' }}</td>
                                                <td class="web-id-cell">{{ $budgetComponent->name }}</td>
                                                <td>{{ $budgetComponent->default_unit ?: '—' }}</td>
                                                <td>{{ $budgetComponent->default_payment_mode?->label() ?? '—' }}</td>
                                                <td>
                                                    @if ($budgetComponent->requires_supporting_document)
                                                        <span class="status-badge status-warning"><i class="bi bi-paperclip"></i> Required</span>
                                                    @else
                                                        <span class="status-badge status-secondary"><i class="bi bi-dash"></i> Optional</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="status-badge {{ $budgetComponent->is_active ? 'status-success' : 'status-archived' }}">
                                                        <i class="bi {{ $budgetComponent->is_active ? 'bi-check-circle-fill' : 'bi-slash-circle-fill' }}"></i>
                                                        {{ $budgetComponent->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="text-end table-actions-cell">
                                                    <button type="button" class="portal-action-btn" title="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editBudgetComponentModal{{ $budgetComponent->id }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>

                                                    <form method="POST" action="{{ route('budget-settings.budget-components.toggle-active', $budgetComponent) }}" class="d-inline"
                                                        data-confirm="{{ $budgetComponent->is_active ? 'Deactivate this budget component?' : 'Activate this budget component?' }}">
                                                        @csrf
                                                        <button type="submit" class="portal-action-btn" title="{{ $budgetComponent->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="bi {{ $budgetComponent->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editBudgetComponentModal{{ $budgetComponent->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route('budget-settings.budget-components.update', $budgetComponent) }}">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Budget Component</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="form-field">
                                                                    <label class="form-label">Category <span>*</span></label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-folder-fill"></i>
                                                                        <select name="budget_category_id" class="form-control-custom" required>
                                                                            @foreach ($allCategories as $category)
                                                                                <option value="{{ $category->id }}" @selected($budgetComponent->budget_category_id === $category->id)>
                                                                                    {{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Code</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-upc-scan"></i>
                                                                        <input type="text" name="code" value="{{ $budgetComponent->code }}" class="form-control-custom" maxlength="50">
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Name <span>*</span></label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-tag-fill"></i>
                                                                        <input type="text" name="name" value="{{ $budgetComponent->name }}" class="form-control-custom" maxlength="255" required>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Default Unit</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-rulers"></i>
                                                                        <input type="text" name="default_unit" value="{{ $budgetComponent->default_unit }}" class="form-control-custom" maxlength="50" placeholder="e.g. Trip, Night, Day">
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Default Payment Mode</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-cash-coin"></i>
                                                                        <select name="default_payment_mode" class="form-control-custom">
                                                                            <option value="">—</option>
                                                                            @foreach ($paymentModes as $value => $label)
                                                                                <option value="{{ $value }}" @selected($budgetComponent->default_payment_mode?->value === $value)>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Description</label>
                                                                    <div class="form-textarea-wrap">
                                                                        <textarea name="description" rows="2" class="form-control-custom" maxlength="2000">{{ $budgetComponent->description }}</textarea>
                                                                    </div>
                                                                </div>

                                                                <div class="form-field">
                                                                    <div class="form-check">
                                                                        <input type="checkbox" name="requires_supporting_document" value="1" class="form-check-input" id="editReqDoc{{ $budgetComponent->id }}" @checked($budgetComponent->requires_supporting_document)>
                                                                        <label class="form-check-label" for="editReqDoc{{ $budgetComponent->id }}">
                                                                            Requires a supporting document at retirement
                                                                        </label>
                                                                    </div>
                                                                    <span class="form-input-help">Blocks retirement submission for this line until at least one receipt is attached.</span>
                                                                </div>

                                                                <div class="form-field">
                                                                    <label class="form-label">Sort Order</label>
                                                                    <div class="form-input-wrap">
                                                                        <i class="bi bi-sort-numeric-down"></i>
                                                                        <input type="number" name="sort_order" value="{{ $budgetComponent->sort_order }}" class="form-control-custom" min="0">
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
                                                <td colspan="7" class="web-table-empty web-table-empty-cell">
                                                    <div class="web-table-empty-state">
                                                        <div class="web-table-empty-icon"><i class="bi bi-list-check"></i></div>
                                                        <h6>No Budget Components Yet</h6>
                                                        <p>Add the first one (e.g. "Airport Transfer" under Transport) once you have at least one category.</p>
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

        @if ($activeCategories->isNotEmpty())
            <div class="modal fade" id="createBudgetComponentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('budget-settings.budget-components.store') }}">
                            @csrf

                            <div class="modal-header">
                                <h5 class="modal-title">New Budget Component</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-field">
                                    <label class="form-label">Category <span>*</span></label>
                                    <div class="form-input-wrap">
                                        <i class="bi bi-folder-fill"></i>
                                        <select name="budget_category_id" class="form-control-custom" required>
                                            <option value="">Select a category</option>
                                            @foreach ($activeCategories as $category)
                                                <option value="{{ $category->id }}" @selected(old('budget_category_id') == $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Code</label>
                                    <div class="form-input-wrap">
                                        <i class="bi bi-upc-scan"></i>
                                        <input type="text" name="code" value="{{ old('code') }}" class="form-control-custom" maxlength="50">
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Name <span>*</span></label>
                                    <div class="form-input-wrap">
                                        <i class="bi bi-tag-fill"></i>
                                        <input type="text" name="name" value="{{ old('name') }}" class="form-control-custom" maxlength="255" required placeholder="e.g. Airport Transfer">
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Default Unit</label>
                                    <div class="form-input-wrap">
                                        <i class="bi bi-rulers"></i>
                                        <input type="text" name="default_unit" value="{{ old('default_unit') }}" class="form-control-custom" maxlength="50" placeholder="e.g. Trip, Night, Day">
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Default Payment Mode</label>
                                    <div class="form-input-wrap">
                                        <i class="bi bi-cash-coin"></i>
                                        <select name="default_payment_mode" class="form-control-custom">
                                            <option value="">—</option>
                                            @foreach ($paymentModes as $value => $label)
                                                <option value="{{ $value }}" @selected(old('default_payment_mode') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Description</label>
                                    <div class="form-textarea-wrap">
                                        <textarea name="description" rows="2" class="form-control-custom" maxlength="2000">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <div class="form-check">
                                        <input type="checkbox" name="requires_supporting_document" value="1" class="form-check-input" id="createReqDoc" @checked(old('requires_supporting_document'))>
                                        <label class="form-check-label" for="createReqDoc">
                                            Requires a supporting document at retirement
                                        </label>
                                    </div>
                                    <span class="form-input-help">Blocks retirement submission for this line until at least one receipt is attached.</span>
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
                                <button type="submit" class="form-btn form-btn-primary">Add Component</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

@endsection
