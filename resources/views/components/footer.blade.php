{{--
    Minimal internal-tool footer for the admin shell (layouts.admin).
    Unlike events_portal's public-marketing footer (social links,
    newsletter subscribe form, public route links) this portal has no
    public site - it's an internal Payment Request & Voucher system, so
    the footer is just a quiet copyright line.
--}}
<footer class="dc-command-footer">
    <div class="container text-center">
        <p class="mb-0">
            &copy; {{ date('Y') }} Praxis for Health and Development &middot; Retirement Portal
        </p>
    </div>
</footer>

<style>
    .dc-command-footer {
        padding: 18px 0;
        margin-top: 40px;
        border-top: 1px solid var(--border, #e5e7eb);
        color: var(--text-faint, #94a3b8);
        font-size: .8rem;
    }
</style>
