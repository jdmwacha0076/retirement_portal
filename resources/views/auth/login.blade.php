@extends('layouts.auth')

@section('title')
Sign In | Retirement Portal
@endsection

@section('bodyClass', 'auth-login-page')

@section('content')

        <section class="auth-page section">
            <div class="container" data-aos="fade-up">
                <div class="auth-shell">
                    <div class="auth-wrap">
                        <div class="row g-0">

                            <div class="col-lg-5 auth-side-col d-none d-lg-flex">
                                <aside class="auth-side h-100">
                                    <div class="auth-side-badge">
                                        <i class="bi bi-shield-check"></i>
                                        Secure Portal Access
                                    </div>

                                    <h1>Welcome back</h1>

                                    <p class="auth-side-text">
                                        Sign in to manage retirement records, review submissions, and keep the portal running — all from one place.
                                    </p>

                                    <div class="auth-side-list">
                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Use the email address and password registered with your account.</span>
                                        </div>

                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Your role — Admin or Staff — decides which parts of the portal you can manage.</span>
                                        </div>

                                        <div class="auth-side-item">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Access your dashboard once signed in.</span>
                                        </div>
                                    </div>

                                    <div class="auth-side-note">
                                        <i class="bi bi-info-circle-fill"></i>
                                        <span>New to the platform? Register a Staff account and get started today.</span>
                                    </div>

                                    <div class="auth-side-actions">
                                        <a href="{{ route('register') }}" class="auth-side-btn">
                                            <i class="bi bi-person-plus-fill"></i>
                                            Create an Account
                                        </a>
                                    </div>
                                </aside>
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="auth-card">

                                    <div class="auth-card-head">
                                        <div class="auth-card-head-main">
                                            <div class="auth-card-icon">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </div>
                                            <div>
                                                <span class="auth-eyebrow">Login</span>
                                                <h2>Sign In to Retirement Portal</h2>
                                                <p>Use your credentials to access the portal.</p>
                                            </div>
                                        </div>

                                        <a href="{{ route('home') }}" class="auth-back-btn">
                                            <i class="bi bi-arrow-left"></i>
                                            <span>Back</span>
                                        </a>
                                    </div>

                                    @if (session('status'))
                                        <div class="alert alert-success d-flex align-items-start gap-2">
                                            <i class="bi bi-check-circle-fill mt-1"></i>
                                            <div>{{ session('status') }}</div>
                                        </div>
                                    @endif

                                    @if ($errors->any())
                                        <div class="alert alert-danger d-flex align-items-start gap-2">
                                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                            <div>
                                                <strong>Sign in failed.</strong>
                                                <div>{{ $errors->first() }}</div>
                                            </div>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('login') }}" class="auth-form" autocomplete="off" novalidate>
                                        @csrf

                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label for="email" class="auth-label">Email Address <span>*</span></label>
                                                <div class="auth-input-wrap @error('email') auth-input-error @enderror">
                                                    <i class="bi bi-envelope-fill"></i>
                                                    <input type="email" id="email" name="email" class="auth-input"
                                                        value="{{ old('email') }}" placeholder="name@retirementportal.test"
                                                        required autofocus autocomplete="email">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label for="password" class="auth-label">Password <span>*</span></label>
                                                <div class="auth-password-wrap @error('password') auth-input-error @enderror">
                                                    <i class="bi bi-lock-fill"></i>
                                                    <input type="password" id="password" name="password" class="auth-input"
                                                        required autocomplete="current-password">
                                                    <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="auth-meta-row">
                                                    <label class="auth-check" for="remember">
                                                        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                        <span>Remember me</span>
                                                    </label>

                                                    <a href="{{ route('password.request') }}" class="auth-inline-link">
                                                        Forgot password?
                                                    </a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="auth-register-note mt-3">
                                            <i class="bi bi-info-circle-fill"></i>
                                            <span>Keep your credentials confidential and use a strong password to protect resident and retirement data.</span>
                                        </div>

                                        <div class="auth-actions">
                                            <button type="submit" class="auth-btn auth-btn-primary w-100">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                                Access Portal
                                            </button>
                                        </div>
                                    </form>

                                    <p class="auth-footer-text">
                                        Don't have an account? <a href="{{ route('register') }}">Create one here</a>
                                    </p>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

@endsection
