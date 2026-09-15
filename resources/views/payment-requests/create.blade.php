@extends('layouts.admin')

@section('title')
New {{ $type->shortLabel() }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi {{ $type->icon() }}"></i>
                    New Payment Request
                </div>

                <h1>{{ $type->shortLabel() }}</h1>

                <p>{{ $type->description() }}</p>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('payment-requests.index') }}">Payment Requests</a></li>
                        <li><a href="{{ route('payment-requests.type-picker') }}">New Request</a></li>
                        <li>{{ $type->shortLabel() }}</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">New Request</span>
                        <h2>{{ $type->shortLabel() }}</h2>
                        <p>Fill in the details below, then save as a draft or submit it right away.</p>
                    </div>

                    <a href="{{ route('payment-requests.type-picker') }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Change Type
                    </a>
                </div>

                <div class="form-feedback">
                    @include('components.form-alerts')
                </div>

                <div class="form-layout">

                    <aside class="form-guide-card portal-sticky-card" data-aos="fade-up" data-aos-delay="120">
                        <div class="form-guide-badge">
                            <i class="bi bi-lightbulb-fill"></i>
                            Quick Guide
                        </div>

                        <h3>Before you submit</h3>

                        <div class="form-guide-list">
                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span><strong>Save Draft</strong> keeps this request editable - nothing is final yet.</span>
                            </div>

                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span><strong>Submit</strong> generates the official reference number and starts the review workflow.</span>
                            </div>

                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Once submitted, the financial fields lock - you'd need it returned for correction to edit them again.</span>
                            </div>
                        </div>

                        <div class="form-guide-note">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Required fields are marked with a red star.</span>
                        </div>
                    </aside>

                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="160">

                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi {{ $type->icon() }}"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-plus-circle-fill"></i>
                                    New
                                </div>

                                <h3>{{ $type->shortLabel() }}</h3>
                                <p>Complete the voucher information below.</p>
                            </div>
                        </div>

                        <form action="{{ route('payment-requests.store', $type->routeSegment()) }}" method="POST"
                            class="form-body" autocomplete="off" novalidate>
                            @csrf

                            @include('payment-requests._form-fields', ['paymentRequest' => null])

                            <div class="form-actions portal-form-actions">
                                <button type="submit" name="form_action" value="draft" class="form-btn form-btn-light">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-save"></i>
                                    Save Draft
                                </button>

                                <button type="submit" name="form_action" value="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-send-check-fill"></i>
                                    Submit Request
                                </button>
                            </div>
                        </form>

                    </div>

                </div>

            </div>
        </section>

@endsection
