<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <link href="{{ asset('assets/css/form.css') }}" rel="stylesheet">

    {{-- Page-specific stylesheets (table.css on listing pages) or one-off
         <style> blocks. --}}
    @stack('styles')
</head>

<body class="@yield('bodyClass', 'light-header-page')">

    @include('components.admin-navbar')

    <main class="main">
        @yield('content')
    </main>

    @include('components.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.1/dist/aos.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.AOS) AOS.init({ once: true, duration: 500, easing: 'ease-out' });
        });

        // Generic destructive-action confirm + double-submit guard for any
        // <form data-confirm="..."> (row-level delete/restore/cancel forms
        // that don't have their own bespoke modal+spinner flow).
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-confirm]');
            if (!form) return;

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return;

            btn.disabled = true;

            const icon = btn.querySelector('i.bi');
            if (icon) icon.classList.add('d-none');

            if (!btn.querySelector('.spinner-border')) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm';
                spinner.setAttribute('aria-hidden', 'true');
                btn.appendChild(spinner);
            }
        });

        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.dataset.submitting = 'false';

                const btn = form.querySelector('button[type="submit"]');
                if (!btn) return;

                btn.disabled = false;

                const icon = btn.querySelector('i.bi');
                if (icon) icon.classList.remove('d-none');

                const spinner = btn.querySelector('.spinner-border');
                if (spinner) spinner.remove();
            });
        });
    </script>

    @include('components.auto-submit-lock')

    @stack('scripts')

</body>

</html>
