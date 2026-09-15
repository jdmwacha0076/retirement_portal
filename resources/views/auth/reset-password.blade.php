@extends('layouts.auth')

@section('title')
Reset Password | Retirement Portal
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
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </div>
                                    <div>
                                        <span class="auth-eyebrow">Account Recovery</span>
                                        <h2>Choose a new password</h2>
                                        <p>Pick something strong you haven't used before.</p>
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
                                    <div>{{ $errors->first() }}</div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.update') }}" class="auth-form" autocomplete="off" novalidate>
                                @csrf

                                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                <div class="row g-3">

                                    <div class="col-12">
                                        <label for="email" class="auth-label">Email Address <span>*</span></label>
                                        <div class="auth-input-wrap @error('email') auth-input-error @enderror">
                                            <i class="bi bi-envelope-fill"></i>
                                            <input type="email" id="email" name="email" class="auth-input"
                                                value="{{ old('email', $request->email) }}"
                                                required autofocus autocomplete="username">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="password" class="auth-label">New Password <span>*</span></label>
                                        <div class="auth-password-wrap @error('password') auth-input-error @enderror">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" id="password" name="password" class="auth-input"
                                                required autocomplete="new-password">
                                            <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="password_confirmation" class="auth-label">Confirm New Password <span>*</span></label>
                                        <div class="auth-password-wrap @error('password_confirmation') auth-input-error @enderror">
                                            <i class="bi bi-lock-fill"></i>
                                            <input type="password" id="password_confirmation" name="password_confirmation" class="auth-input"
                                                required autocomplete="new-password">
                                            <button type="button" class="auth-password-toggle" data-target="password_confirmation" aria-label="Show password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                <div class="auth-actions">
                                    <button type="submit" class="auth-btn auth-btn-primary w-100">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Reset Password
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </section>

@endsection
