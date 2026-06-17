/**
 * ArenaGamer Module JavaScript
 */
(function() {
    'use strict';

    // Auto-refresh for live tournaments (every 30s on detail page)
    if (document.querySelector('[data-arenagamer-live]')) {
        setInterval(function() {
            location.reload();
        }, 30000);
    }

    // Confirm dangerous actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
})();
