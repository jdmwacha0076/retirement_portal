<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Retirement Portal'))</title>

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/navbar.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/auth.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body class="@yield('bodyClass', 'auth-page')">

    {{-- Auth pages get the simple, guest-only header - never the
         authenticated dashboard navbar (components.admin-navbar), which
         has nowhere useful to send a signed-out visitor. --}}
    @include('components.auth-header')

    <main class="main">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Generic submit-once guard shared by every .auth-form (login,
        // forgot/reset password): disables the submit button and shows
        // the .form-btn-spinner already defined in styles.css, so an
        // impatient extra click (or a slow network) can't double-submit.
        document.addEventListener('DOMContentLoaded', function () {
            if (window.AOS) AOS.init({ once: true, duration: 500, easing: 'ease-out' });

            document.querySelectorAll('form.auth-form').forEach(function (form) {
                if (form.dataset.autoLock === 'false') return;

                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) return;

                if (!submitBtn.querySelector('.form-btn-spinner')) {
                    const spinner = document.createElement('span');
                    spinner.className = 'form-btn-spinner';
                    submitBtn.insertBefore(spinner, submitBtn.firstChild);
                }

                form.addEventListener('submit', function (event) {
                    if (form.dataset.submitting === 'true') {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = 'true';
                    submitBtn.disabled = true;
                    submitBtn.classList.add('is-loading');
                });
            });

            document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(this.dataset.target);
                    const icon = this.querySelector('i');
                    if (!input) return;

                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('bi-eye', 'bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('bi-eye-slash', 'bi-eye');
                    }
                });
            });
        });

        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form.auth-form').forEach(function (form) {
                form.dataset.submitting = 'false';

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('is-loading');
                }
            });
        });
    </script>

    @stack('scripts')

</body>

</html>
