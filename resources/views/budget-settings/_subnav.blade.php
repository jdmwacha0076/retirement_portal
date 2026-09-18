{{-- Shared between the three master-data admin pages so there's one
     navbar entry ("Finance Settings") rather than three, with these tabs
     doing the switching between them. --}}
<div class="form-section-title-row mb-4 budget-settings-subnav">
    <div class="btn-group" role="group" aria-label="Finance Settings sections">
        <a href="{{ route('budget-settings.activity-types.index') }}"
            class="form-btn {{ request()->routeIs('budget-settings.activity-types.*') ? 'form-btn-primary' : 'form-btn-light' }}">
            <i class="bi bi-tags-fill"></i>
            Activity Types
        </a>
        <a href="{{ route('budget-settings.budget-categories.index') }}"
            class="form-btn {{ request()->routeIs('budget-settings.budget-categories.*') ? 'form-btn-primary' : 'form-btn-light' }}">
            <i class="bi bi-folder-fill"></i>
            Budget Categories
        </a>
        <a href="{{ route('budget-settings.budget-components.index') }}"
            class="form-btn {{ request()->routeIs('budget-settings.budget-components.*') ? 'form-btn-primary' : 'form-btn-light' }}">
            <i class="bi bi-list-check"></i>
            Budget Components
        </a>
    </div>
</div>
