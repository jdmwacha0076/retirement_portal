{{--
    Reusable form-alert partial - mirrors Digital Cards Portal's
    components/form-alerts.blade.php exactly (same classes, same
    session keys, same $errors handling), so every Events Portal form
    page shows session flash + validation errors with the same
    polished .form-alert markup instead of a plain Bootstrap alert.

    Relies entirely on the .form-alert / .form-alert-* classes already
    shipped in assets/css/form.css - nothing new to add to any CSS file.

    Usage:
        <div class="form-feedback">
            @include('components.form-alerts')
        </div>

    Optional props:
        flush   (bool) - drop the card's side margins (matches DCP's dashboard hero usage)
        compact (bool) - tighter padding variant
--}}
@php
    $alertClasses = collect([
        ($flush ?? false) ? 'mx-0' : null,
        ($compact ?? false) ? 'form-alert-compact' : null,
    ])->filter()->implode(' ');
@endphp

@if (session('error'))
    <div class="form-alert form-alert-danger {{ $alertClasses }}" role="alert" aria-live="assertive">
        <span class="form-alert-icon" aria-hidden="true">
            <i class="bi bi-x-lg"></i>
        </span>

        <div class="form-alert-content">
            <strong class="form-alert-title">
                Unable to save changes
            </strong>

            <p class="form-alert-message">
                {{ session('error') }}
            </p>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="form-alert form-alert-success {{ $alertClasses }}" role="status" aria-live="polite">
        <span class="form-alert-icon" aria-hidden="true">
            <i class="bi bi-check-lg"></i>
        </span>

        <div class="form-alert-content">
            <strong class="form-alert-title">
                Changes saved successfully
            </strong>

            <p class="form-alert-message">
                {{ session('success') }}
            </p>
        </div>
    </div>
@endif

@if (session('warning'))
    <div class="form-alert form-alert-warning {{ $alertClasses }}" role="alert">
        <span class="form-alert-icon" aria-hidden="true">
            <i class="bi bi-exclamation-lg"></i>
        </span>

        <div class="form-alert-content">
            <strong class="form-alert-title">
                Review Needed
            </strong>

            <p class="form-alert-message">
                {{ session('warning') }}
            </p>
        </div>
    </div>
@endif

@if (session('info'))
    <div class="form-alert form-alert-info {{ $alertClasses }}" role="status">
        <span class="form-alert-icon" aria-hidden="true">
            <i class="bi bi-info-lg"></i>
        </span>

        <div class="form-alert-content">
            <strong class="form-alert-title">
                Note
            </strong>

            <p class="form-alert-message">
                {{ session('info') }}
            </p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="form-alert form-alert-danger {{ $alertClasses }}" role="alert" aria-live="assertive">
        <span class="form-alert-icon" aria-hidden="true">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </span>

        <div class="form-alert-content">
            <strong class="form-alert-title">
                Please correct the following {{ Str::plural('error', $errors->count()) }}
            </strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
