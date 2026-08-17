import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// ═══════════════════════════════════════════════════════════════
// FORM LOADING STATE
// Automatically disables submit buttons and shows a spinner
// when a form is submitted, preventing double-submissions.
// Add data-no-loading to a submit button to opt out.
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const submitBtn = form.querySelector('[type="submit"]:not([data-no-loading])');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...`;

                // Safety net: re-enable after 15 seconds in case of validation error
                setTimeout(function () {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.innerHTML = originalText;
                }, 15000);
            }
        });
    });
});
