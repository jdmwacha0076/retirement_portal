@extends('layouts.admin')

@section('title')
Edit {{ $paymentRequest->displayReference() }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi {{ $type->icon() }}"></i>
                    Edit Payment Request
                </div>

                <h1>{{ $paymentRequest->displayReference() }}</h1>

                <p>{{ $type->shortLabel() }}</p>

                @if ($paymentRequest->status->value === 'returned')
                    <div class="form-feedback mt-3">
                        <div class="form-alert form-alert-warning">
                            <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-reply-fill"></i></span>
                            <div class="form-alert-content">
                                <strong class="form-alert-title">Returned for correction</strong>
                                <p class="form-alert-message">{{ $paymentRequest->returned_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('payment-requests.index') }}">Payment Requests</a></li>
                        <li><a href="{{ route('payment-requests.show', $paymentRequest) }}">{{ $paymentRequest->displayReference() }}</a></li>
                        <li>Edit</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">Edit</span>
                        <h2>{{ $type->shortLabel() }}</h2>
                        <p>Update the details below, then save your changes or submit.</p>
                    </div>

                    <a href="{{ route('payment-requests.show', $paymentRequest) }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Go Back
                    </a>
                </div>

                <div class="form-feedback">
                    @include('components.form-alerts')
                </div>

                <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="160">

                    <div class="form-card-header">
                        <div class="form-card-icon">
                            <i class="bi {{ $type->icon() }}"></i>
                        </div>

                        <div class="form-card-heading">
                            <div class="form-card-badge">
                                <i class="bi bi-pencil-square"></i>
                                Edit
                            </div>

                            <h3>{{ $type->shortLabel() }}</h3>
                            <p>{{ $paymentRequest->displayReference() }}</p>
                        </div>
                    </div>

                    <form action="{{ route('payment-requests.update', $paymentRequest) }}" method="POST"
                        class="form-body" autocomplete="off" novalidate>
                        @csrf
                        @method('PUT')

                        @include('payment-requests._form-fields')

                        <div class="form-actions portal-form-actions">
                            <button type="submit" name="form_action" value="draft" class="form-btn form-btn-light">
                                <span class="form-btn-spinner"></span>
                                <i class="bi bi-save"></i>
                                Save Changes
                            </button>

                            <button type="submit" name="form_action" value="submit" class="form-btn form-btn-primary">
                                <span class="form-btn-spinner"></span>
                                <i class="bi bi-send-check-fill"></i>
                                {{ $paymentRequest->status->value === 'returned' ? 'Resubmit Request' : 'Submit Request' }}
                            </button>
                        </div>
                    </form>

                </div>

            </div>
        </section>

@endsection
