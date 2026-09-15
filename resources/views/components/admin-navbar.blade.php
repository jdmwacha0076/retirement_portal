{{-- navbar.css is loaded once, globally, from layouts/app.blade.php's
     <head> - this partial doesn't need its own duplicate <link>. --}}
<div id="preloader"></div>

@php
    $navUser = auth()->user();
    $navInitials = $navUser ? $navUser->initials() : 'U';
    $navUnreadNotifications = $navUser ? $navUser->unreadNotifications()->latest()->take(6)->get() : collect();
    $navUnreadCount = $navUser ? $navUser->unreadNotifications()->count() : 0;
@endphp

<header class="dc-command-header">
    <nav class="dc-command-nav">
        <div class="container">

            <a href="{{ route('dashboard') }}" class="dc-command-brand">
                <span class="dc-command-logo">
                    <i class="bi bi-piggy-bank-fill" aria-hidden="true"></i>
                </span>
                <span class="dc-command-brand-text">
                    <span class="dc-command-brand-title">Retirement <span>Portal</span></span>
                    <span class="dc-command-brand-subtitle">{{ $navUser?->isAdmin() ? 'Admin Panel' : 'Staff Panel' }}</span>
                </span>
            </a>

            {{-- Desktop menu (>=1200px) --}}
            <div class="dc-command-desktop">
                <ul class="dc-command-menu">
                    <li>
                        <a href="{{ route('dashboard') }}"
                            class="dc-command-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payment-requests.index') }}"
                            class="dc-command-link {{ request()->routeIs('payment-requests.*') && ! request()->routeIs('payment-requests.my-tasks') ? 'active' : '' }}">
                            Payment Requests
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payment-requests.my-tasks') }}"
                            class="dc-command-link {{ request()->routeIs('payment-requests.my-tasks') ? 'active' : '' }}">
                            My Tasks
                        </a>
                    </li>
                </ul>

                <div class="dropdown dc-command-dropdown me-2">
                    <button type="button" class="dc-command-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <span class="dc-command-avatar dc-command-notif-avatar">
                            <i class="bi bi-bell-fill"></i>
                            @if ($navUnreadCount > 0)
                                <span class="dc-command-notif-badge">{{ $navUnreadCount }}</span>
                            @endif
                        </span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end dc-command-notif-menu">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            @if ($navUnreadCount > 0)
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                                </form>
                            @endif
                        </div>

                        @forelse ($navUnreadNotifications as $notification)
                            <a class="dropdown-item" href="{{ route('notifications.open', $notification->id) }}">
                                <i class="bi bi-dot"></i>
                                {{ $notification->data['message'] ?? 'Update on a payment request.' }}
                            </a>
                        @empty
                            <span class="dropdown-item-text text-muted small">No new notifications.</span>
                        @endforelse

                        <div class="dc-command-divider"></div>
                        <a class="dropdown-item text-center small" href="{{ route('notifications.index') }}">View All Notifications</a>
                    </div>
                </div>

                <div class="dc-command-user dropdown dc-command-dropdown">
                    <button type="button" class="dc-command-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="dc-command-avatar">{{ $navInitials ?: 'U' }}</span>
                        <span class="dc-command-user-meta">
                            <span class="dc-command-user-name">{{ $navUser->name }}</span>
                            <span class="dc-command-user-role">{{ \App\Models\User::ROLES[$navUser->role] ?? ucfirst($navUser->role) }}</span>
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">Account Options</li>
                        <li><a class="dropdown-item" href="{{ route('profile.show') }}">My Profile</a></li>
                        <li><div class="dc-command-divider"></div></li>
                        <li>
                            <button type="button" class="dropdown-item dc-command-logout js-open-logout-modal">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Logout
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Mobile trigger (<1200px) --}}
            <button type="button" class="dc-command-mobile-btn" data-bs-toggle="offcanvas"
                data-bs-target="#adminMobileMenu" aria-controls="adminMobileMenu">
                <span class="dc-command-burger"></span>
                Menu
            </button>

        </div>
    </nav>
</header>

{{-- Mobile offcanvas menu --}}
<div class="offcanvas offcanvas-end dc-command-offcanvas" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuLabel">
    <div class="offcanvas-header">
        <span class="offcanvas-title" id="adminMobileMenuLabel">Retirement Portal</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <div class="dc-command-mobile-list">
            <a href="{{ route('dashboard') }}" class="dc-command-mobile-link">Dashboard</a>
            <a href="{{ route('payment-requests.index') }}" class="dc-command-mobile-link">Payment Requests</a>
            <a href="{{ route('payment-requests.my-tasks') }}" class="dc-command-mobile-link">My Tasks</a>
            <a href="{{ route('notifications.index') }}" class="dc-command-mobile-link">
                Notifications
                @if ($navUnreadCount > 0)
                    <span class="dc-command-notif-badge">{{ $navUnreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile.show') }}" class="dc-command-mobile-link">My Profile</a>
        </div>

        <div class="dc-command-mobile-user">
            <div class="dc-command-mobile-user-top">
                <span class="dc-command-avatar">{{ $navInitials ?: 'U' }}</span>
                <div>
                    <div class="dc-command-user-name dc-command-user-name--on-dark">{{ $navUser->name }}</div>
                    <div class="dc-command-user-role">{{ \App\Models\User::ROLES[$navUser->role] ?? ucfirst($navUser->role) }}</div>
                </div>
            </div>
            <div class="dc-command-mobile-actions">
                <button type="button" class="dc-command-logout js-open-logout-modal btn-reset">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Logout confirmation modal --}}
<div class="modal fade portal-logout-modal" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="portal-logout-top">
                <div class="portal-logout-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
            </div>

            <div class="portal-logout-body">
                <span class="portal-logout-badge">Confirm Logout</span>
                <h5>Log out of Retirement Portal?</h5>
                <p>You'll need to sign in again to access the panel.</p>

                <div class="portal-logout-user">
                    <span class="dc-command-avatar">{{ $navInitials ?: 'U' }}</span>
                    <div>
                        <strong>{{ $navUser->name }}</strong>
                        <span>{{ \App\Models\User::ROLES[$navUser->role] ?? ucfirst($navUser->role) }} &middot; {{ $navUser->email }}</span>
                    </div>
                </div>
            </div>

            <div class="portal-logout-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>

                <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="portal-logout-confirm" id="confirmLogoutBtn">
                        <span class="spinner-border spinner-border-sm d-none" id="logoutSpinner" aria-hidden="true"></span>
                        <span id="logoutBtnText">Yes, Log Out</span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('logoutModal');
        var logoutModal = modalEl
            ? bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false })
            : null;

        document.querySelectorAll('.js-open-logout-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Close the mobile offcanvas first (if open) so it isn't still
                // animating shut while the modal opens on top of it - avoids the
                // page's own light background flashing through between the two
                // (see navbar.css's "MOBILE LOGOUT - REMOVE WHITE FLASH" block).
                var offcanvasEl = document.getElementById('adminMobileMenu');
                var offcanvas = offcanvasEl ? bootstrap.Offcanvas.getInstance(offcanvasEl) : null;
                if (offcanvas) {
                    offcanvas.hide();
                    setTimeout(function () { if (logoutModal) logoutModal.show(); }, 180);
                } else if (logoutModal) {
                    logoutModal.show();
                }
            });
        });

        var logoutForm = document.getElementById('logoutForm');
        if (logoutForm) {
            logoutForm.addEventListener('submit', function () {
                var btn = document.getElementById('confirmLogoutBtn');
                var spinner = document.getElementById('logoutSpinner');
                var text = document.getElementById('logoutBtnText');
                if (btn) btn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (text) text.textContent = 'Logging out...';
            });
        }
    });
</script>

<script>
    (function () {
        const MAX_VISIBLE_MS = 1000;
        const FADE_MS = 300;

        let hidden = false;

        function hidePreloader() {
            if (hidden) return;
            hidden = true;

            const preloader = document.getElementById('preloader');
            if (!preloader) return;

            preloader.style.transition = 'opacity ' + FADE_MS + 'ms ease';
            preloader.style.opacity = '0';

            setTimeout(function () {
                preloader.remove();
            }, FADE_MS);
        }

        window.addEventListener('load', hidePreloader);
        setTimeout(hidePreloader, MAX_VISIBLE_MS);
    })();
</script>
