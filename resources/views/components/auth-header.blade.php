{{--
    Guest-only header for the welcome / login / forgot-password /
    reset-password pages. retirement_portal has no public marketing site
    beyond the welcome page (unlike events_portal's shared public+auth
    navbar), so this is just the branded header bar - no nav links, no
    user menu, nothing to log out of. The brand links back to the welcome
    page. The authenticated area uses components.admin-navbar instead.
--}}
<header class="dc-command-header">
    <nav class="dc-command-nav">
        <div class="container">

            <a href="{{ route('home') }}" class="dc-command-brand">
                <span class="dc-command-logo">
                    <i class="bi bi-piggy-bank-fill" aria-hidden="true"></i>
                </span>
                <span class="dc-command-brand-text">
                    <span class="dc-command-brand-title">Retirement <span>Portal</span></span>
                    <span class="dc-command-brand-subtitle">Admin Panel</span>
                </span>
            </a>

        </div>
    </nav>
</header>
