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

    // Compra de créditos — total estimado 1:1
    var creditsInput = document.getElementById('arenagamer_credits_qty');
    var creditsTotal = document.getElementById('arenagamer_credits_total');

    if (creditsInput && creditsTotal) {
        var formatMoney = function(value) {
            var amount = parseFloat(value);
            if (isNaN(amount) || amount < 0) {
                amount = 0;
            }
            return 'R$ ' + amount.toFixed(2).replace('.', ',');
        };

        var updateTotal = function() {
            creditsTotal.textContent = formatMoney(creditsInput.value);
        };

        creditsInput.addEventListener('input', updateTotal);
        creditsInput.addEventListener('change', updateTotal);
        updateTotal();
    }
})();
