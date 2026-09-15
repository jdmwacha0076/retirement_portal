@extends('layouts.auth')

@section('title')
Welcome | Retirement Portal
@endsection

@section('bodyClass', 'welcome-page-body')

@push('styles')
    <style>
        /* Page-scoped: retirement_portal has no dedicated web.css, so the
           landing hero is built here reusing the same dark brand gradient
           and design tokens as .auth-side (auth.css), rather than adding
           a whole new stylesheet for a single page. */
        .welcome-hero {
            position: relative;
            min-height: calc(100vh - 84px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            overflow: hidden;
            isolation: isolate;
            color: #fff;
            background:
                radial-gradient(circle at top left, rgba(80, 197, 255, .18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(80, 197, 255, .12), transparent 40%),
                linear-gradient(160deg, #0c1630 0%, #111827 100%);
        }

        .welcome-card {
            position: relative;
            width: 100%;
            max-width: 620px;
            text-align: center;
        }

        .welcome-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 76px;
            height: 76px;
            margin: 0 auto 24px;
            border-radius: 22px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(80, 197, 255, .3);
            font-size: 2.1rem;
            color: var(--brand);
        }

        .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            margin-bottom: 22px;
            border-radius: var(--r-pill);
            background: rgba(80, 197, 255, .14);
            border: 1px solid rgba(80, 197, 255, .28);
            color: #bae6fd;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .welcome-title {
            margin: 0 0 16px;
            font-family: var(--font-display);
            font-size: clamp(1.9rem, 4vw, 2.6rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.02em;
            color: #fff;
        }

        .welcome-title span {
            color: var(--brand);
        }

        .welcome-text {
            max-width: 480px;
            margin: 0 auto 34px;
            color: rgba(255, 255, 255, .74);
            font-size: .95rem;
            line-height: 1.75;
        }

        .welcome-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 14px;
        }

        .welcome-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 50px;
            padding: 0 26px;
            border-radius: var(--r-lg);
            font-size: .92rem;
            font-weight: 700;
            text-decoration: none;
            transition: all var(--t-base);
        }

        .welcome-btn-primary {
            background: var(--brand);
            border: 1px solid var(--brand);
            color: #06121f;
        }

        .welcome-btn-primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #06121f;
        }

        .welcome-btn-ghost {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(80, 197, 255, .3);
            color: #fff;
        }

        .welcome-btn-ghost:hover {
            background: rgba(255, 255, 255, .14);
            border-color: rgba(80, 197, 255, .5);
            color: #fff;
        }

        .welcome-note {
            display: inline-flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 460px;
            margin: 32px auto 0;
            padding: 14px 16px;
            border-radius: var(--r-lg);
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .78);
            font-size: .82rem;
            line-height: 1.6;
            text-align: left;
        }

        .welcome-note i {
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--brand);
        }
    </style>
@endpush

@section('content')

    <section class="welcome-hero" data-aos="fade-up">
        <div class="welcome-card">

            <div class="welcome-logo">
                <i class="bi bi-piggy-bank-fill" aria-hidden="true"></i>
            </div>

            <div class="welcome-badge">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>Retirement Portal</span>
            </div>

            <h1 class="welcome-title">
                Welcome to Retirement <span>Portal</span>
            </h1>

            <p class="welcome-text">
                Sign in to manage retirement records, review submissions, and keep the portal running — or create a
                Staff account to get started.
            </p>

            <div class="welcome-actions">
                <a href="{{ route('login') }}" class="welcome-btn welcome-btn-primary">
                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                    <span>Sign In</span>
                </a>

                <a href="{{ route('register') }}" class="welcome-btn welcome-btn-ghost">
                    <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                    <span>Create an Account</span>
                </a>
            </div>

            <div class="welcome-note">
                <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                <span>New accounts default to Staff access. Admin access is granted separately by an existing
                    administrator.</span>
            </div>

        </div>
    </section>

@endsection
