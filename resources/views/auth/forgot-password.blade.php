@extends('layouts.auth')

@section('title')
Forgot Password | Retirement Portal
@endsection

@section('bodyClass', 'auth-password-page')

@section('content')

        <section class="auth-page section">
            <div class="container" data-aos="fade-up">
                <div class="auth-shell auth-shell-narrow">
                    <div class="auth-wrap">
                        <div class="auth-card">

                            <div class="auth-card-head">
                                <div class="auth-card-head-main">
                                    <div class="auth-card-icon">
                                        <i class="bi bi-envelope-paper-fill"></i>
                                    </div>
                                    <div>
                                        <span class="auth-eyebrow">Account Recovery</span>
                                        <h2>Reset your password</h2>
                                        <p>Enter your account's email address and we'll send you a link to reset your password.</p>
                                    </div>
                                </div>

                                <a href="{{ route('login') }}" class="auth-back-btn">
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
                                    <div>{{ $errors->first() }}</div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}" class="auth-form" autocomplete="off" novalidate>
                                @csrf

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="email" class="auth-label">Email Address <span>*</span></label>
                                        <div class="auth-input-wrap @error('email') auth-input-error @enderror">
                                            <i class="bi bi-envelope-fill"></i>
                                            <input type="email" id="email" name="email" class="auth-input"
                                                value="{{ old('email') }}" placeholder="name@retirementportal.test"
                                                required autofocus autocomplete="username">
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-actions">
                                    <button type="submit" class="auth-btn auth-btn-primary w-100">
                                        <i class="bi bi-send-fill"></i>
                                        Email Password Reset Link
                                    </button>
                                </div>
                            </form>

                            <p class="auth-footer-text">
                                Remembered it after all? <a href="{{ route('login') }}">Back to sign in</a>
                            </p>

                        </div>
                    </div>
                </div>
            </div>
        </section>

@endsection
