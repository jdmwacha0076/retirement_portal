@extends('layouts.auth')

@section('title')
Register | Retirement Portal
@endsection

@section('bodyClass', 'auth-register-page')

@section('content')

        <section class="auth-page section">
            <div class="container" data-aos="fade-up">
                <div class="auth-shell">
                    <div class="auth-wrap">
                        <div class="row g-0">

                            <div class="col-lg-5 auth-side-col d-none d-lg-flex">
                                <aside class="auth-side h-100">
                                    <div class="auth-side-badge">
                                        <i class="bi bi-person-plus-fill"></i>
                                        Join the Team
                                    </div>

                                    <h1>Create your account</h1>

                                    <p class="auth-side-text">
                                        New accounts get Staff access, so you can get started right away.
                                    </p>

                                    <div class="auth-side-list">
                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Review and manage retirement records and submissions.</span>
                                        </div>

                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Access your dashboard as soon as your account is created.</span>
                                        </div>

                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Admin access is granted separately by an existing administrator.</span>
                                        </div>
                                    </div>

                                    <div class="auth-side-note">
                                        <i class="bi bi-info-circle-fill"></i>
                                        <span>Already have an account? Sign in instead — no need to register twice.</span>
                                    </div>

                                    <div class="auth-side-actions">
                                        <a href="{{ route('login') }}" class="auth-side-btn">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                            Sign In Instead
                                        </a>
                                    </div>
                                </aside>
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="auth-card">

                                    <div class="auth-card-head">
                                        <div class="auth-card-head-main">
                                            <div class="auth-card-icon">
                                                <i class="bi bi-person-plus-fill"></i>
                                            </div>
                                            <div>
                                                <span class="auth-eyebrow">Register</span>
                                                <h2>Create a Retirement Portal Account</h2>
                                                <p>Fill in your details to set up a Staff account.</p>
                                            </div>
                                        </div>

                                        <a href="{{ route('login') }}" class="auth-back-btn">
                                            <i class="bi bi-arrow-left"></i>
                                            <span>Back</span>
                                        </a>
                                    </div>

                                    @if ($errors->any())
                                        <div class="alert alert-danger d-flex align-items-start gap-2">
                                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                            <div>
                                                <strong>Registration failed.</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('register') }}" class="auth-form"
                                        autocomplete="off" novalidate>
                                        @csrf

                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label for="name" class="auth-label">Full Name <span>*</span></label>
                                                <div class="auth-input-wrap @error('name') auth-input-error @enderror">
                                                    <i class="bi bi-person-fill"></i>
                                                    <input type="text" id="name" name="name" class="auth-input"
                                                        value="{{ old('name') }}" required autofocus
                                                        autocomplete="name">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label for="email" class="auth-label">Email <span>*</span></label>
                                                <div class="auth-input-wrap @error('email') auth-input-error @enderror">
                                                    <i class="bi bi-envelope-fill"></i>
                                                    <input type="email" id="email" name="email" class="auth-input"
                                                        value="{{ old('email') }}" placeholder="name@retirementportal.test"
                                                        required autocomplete="email">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label class="auth-label">Account Type</label>
                                                <div class="auth-select-wrap">
                                                    <i class="bi bi-person-badge-fill"></i>
                                                    <span class="auth-static-value">Staff — every self-registered account starts here</span>
                                                </div>
                                                <div class="text-muted mt-1 auth-field-hint">Admin accounts aren't
                                                    self-service — an existing Admin promotes accounts once a
                                                    Users screen exists.
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="password" class="auth-label">Password <span>*</span></label>
                                                <div
                                                    class="auth-password-wrap @error('password') auth-input-error @enderror">
                                                    <i class="bi bi-lock-fill"></i>
                                                    <input type="password" id="password" name="password"
                                                        class="auth-input" minlength="8" required
                                                        autocomplete="new-password">
                                                    <button type="button" class="auth-password-toggle"
                                                        data-target="password" aria-label="Show password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="password_confirmation" class="auth-label">Confirm Password
                                                    <span>*</span></label>
                                                <div class="auth-password-wrap">
                                                    <i class="bi bi-shield-lock-fill"></i>
                                                    <input type="password" id="password_confirmation"
                                                        name="password_confirmation" class="auth-input" minlength="8"
                                                        required autocomplete="new-password">
                                                    <button type="button" class="auth-password-toggle"
                                                        data-target="password_confirmation" aria-label="Show password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="auth-register-note">
                                                    <i class="bi bi-info-circle-fill"></i>
                                                    <span>Your password must be at least 8 characters long.</span>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="auth-actions">
                                            <button type="submit" class="auth-btn auth-btn-primary w-100">
                                                <i class="bi bi-person-plus-fill"></i>
                                                Create Account
                                            </button>
                                        </div>
                                    </form>

                                    <p class="auth-footer-text">
                                        Already have an account? <a href="{{ route('login') }}">Sign in</a>
                                    </p>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

@endsection
