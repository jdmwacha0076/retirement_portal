{{--
    Generic save-in-progress guard for any POST/PUT/PATCH form that
    doesn't already ship its own bespoke submit-lock script (the admin
    create/edit forms with their own client-side validation keep their
    own setSubmittingState logic untouched - this one backs off for
    them automatically).

    - Disables the submit button and swaps its leading icon (if any) for
      a Bootstrap spinner-border, so a slow request or an impatient
      extra click can't double-submit. Works for any button style
      (.form-btn, .auth-btn, .portal-thread-composer-send, a plain
      .btn-primary, ...) since it only depends on Bootstrap - already
      loaded everywhere this partial is included - not on any one
      button component's own CSS.
    - Uses its own dataset key (autoSubmitting) so it never collides
      with a page's own "submitting" flag.
    - Backs off entirely - via event.defaultPrevented - when a page's
      own script already blocked the submit (e.g. failed client-side
      validation), so the two never fight over the same button.
    - Skips GET forms (search/filter bars), anything already handled by
      the admin delete/restore confirm guard ([data-confirm]), and any
      form explicitly opting out with data-auto-lock="false".
    - Only ever inserts a spinner when the button doesn't already have
      one (a page like the delete-confirmation modal pattern already
      carries its own static spinner-border, just toggling its
      visibility) - and only ever removes/restores what it itself
      inserted or hid, marked via data-auto-spinner / data-auto-hidden,
      so an already-working bespoke loading state is never touched.
--}}
<script>
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.method.toLowerCase() === 'get') return;
        if (form.matches('[data-confirm]')) return;
        if (form.dataset.autoLock === 'false') return;
        if (event.defaultPrevented) return;

        if (form.dataset.autoSubmitting === 'true') {
            event.preventDefault();
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;

        form.dataset.autoSubmitting = 'true';
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');

        if (!submitBtn.querySelector('.spinner-border')) {
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm';
            spinner.setAttribute('aria-hidden', 'true');
            spinner.dataset.autoSpinner = 'true';

            const icon = submitBtn.querySelector('i.bi');
            if (icon && !icon.classList.contains('d-none')) {
                icon.classList.add('d-none');
                icon.dataset.autoHidden = 'true';
                submitBtn.insertBefore(spinner, icon);
            } else {
                submitBtn.insertBefore(spinner, submitBtn.firstChild);
            }
        }
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.matches('[data-confirm]')) return;

            form.dataset.autoSubmitting = 'false';

            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            submitBtn.disabled = false;
            submitBtn.classList.remove('is-loading');

            const spinner = submitBtn.querySelector('.spinner-border[data-auto-spinner="true"]');
            if (spinner) spinner.remove();

            const hiddenIcon = submitBtn.querySelector('i.bi[data-auto-hidden="true"]');
            if (hiddenIcon) {
                hiddenIcon.classList.remove('d-none');
                delete hiddenIcon.dataset.autoHidden;
            }
        });
    });
</script>
