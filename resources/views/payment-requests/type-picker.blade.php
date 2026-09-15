@extends('layouts.admin')

@section('title')
New Payment Request | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-file-earmark-plus-fill"></i>
                    New Payment Request
                </div>

                <h1>Choose a Payment Type</h1>

                <p>Pick the voucher type that matches this payment - the form on the next step is tailored to it.</p>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('payment-requests.index') }}">Payment Requests</a></li>
                        <li>New Request</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row g-4">
                    @foreach ($types as $type)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('payment-requests.create', $type->routeSegment()) }}"
                                class="form-card portal-form-card h-100 text-decoration-none d-block"
                                style="cursor:pointer;">

                                <div class="form-card-header">
                                    <div class="form-card-icon">
                                        <i class="bi {{ $type->icon() }}"></i>
                                    </div>

                                    <div class="form-card-heading">
                                        <div class="form-card-badge">
                                            <i class="bi bi-arrow-right-circle-fill"></i>
                                            Select
                                        </div>

                                        <h3>{{ $type->shortLabel() }}</h3>

                                        <p>{{ $type->description() }}</p>
                                    </div>
                                </div>

                            </a>
                        </div>
                    @endforeach
                </div>

            </div>
        </section>

@endsection
