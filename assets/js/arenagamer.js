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

    // Assistente em lote de resultados
    var bulkModal = document.getElementById('ag-bulk-results-wizard');
    var bulkDataEl = document.getElementById('ag-bulk-results-data');

    if (bulkModal && bulkDataEl) {
        var items = [];
        try {
            items = JSON.parse(bulkDataEl.textContent || '[]');
        } catch (e) {
            items = [];
        }

        if (items.length > 0) {
            var index = 0;
            var savedCount = 0;
            var urlBase = bulkModal.getAttribute('data-record-url-base') || '';
            var csrfName = bulkModal.getAttribute('data-csrf-name') || '';
            var csrfHash = bulkModal.getAttribute('data-csrf-hash') || '';

            var elCurrent = bulkModal.querySelector('.ag-bulk-results-wizard__current');
            var elTotal = bulkModal.querySelector('.ag-bulk-results-wizard__total');
            var elProgressBar = bulkModal.querySelector('.ag-bulk-results-wizard__progress-bar');
            var elContext = bulkModal.querySelector('.ag-bulk-results-wizard__context');
            var elMatchup = bulkModal.querySelector('.ag-bulk-results-wizard__matchup');
            var elForm = bulkModal.querySelector('.ag-bulk-results-wizard__form');
            var elStep = bulkModal.querySelector('.ag-bulk-results-wizard__step');
            var elDone = bulkModal.querySelector('.ag-bulk-results-wizard__done');
            var elFooter = bulkModal.querySelector('.ag-bulk-results-wizard__footer');
            var elAlert = bulkModal.querySelector('.ag-bulk-results-wizard__alert');
            var elSuccess = bulkModal.querySelector('.ag-bulk-results-wizard__success');
            var elSubmit = bulkModal.querySelector('.ag-bulk-results-wizard__submit');
            var elSubmitLabel = bulkModal.querySelector('.ag-bulk-results-wizard__submit-label');
            var elSkip = bulkModal.querySelector('.ag-bulk-results-wizard__skip');
            var elCheatBlock = bulkModal.querySelector('.ag-bulk-results-wizard__cheat-block');
            var elCheatRadios = bulkModal.querySelector('.ag-bulk-results-wizard__cheat-radios');
            var elAutoWinner = bulkModal.querySelector('.ag-bulk-results-wizard__auto-winner');
            var elHomeScore = bulkModal.querySelector('.ag-bulk-results-wizard__home-score');
            var elAwayScore = bulkModal.querySelector('.ag-bulk-results-wizard__away-score');
            var elCsrf = bulkModal.querySelector('.ag-bulk-results-csrf');

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function hideAlerts() {
                if (elAlert) { elAlert.style.display = 'none'; }
                if (elSuccess) { elSuccess.style.display = 'none'; }
            }

            function showError(message) {
                hideAlerts();
                if (elAlert) {
                    elAlert.textContent = message;
                    elAlert.style.display = 'block';
                }
            }

            function showSuccessFlash(message) {
                hideAlerts();
                if (elSuccess) {
                    elSuccess.textContent = message;
                    elSuccess.style.display = 'block';
                }
            }

            function updateProgress() {
                var current = Math.min(index + 1, items.length);
                var pct = items.length > 0 ? Math.round((current / items.length) * 100) : 100;
                if (elCurrent) { elCurrent.textContent = String(current); }
                if (elTotal) { elTotal.textContent = String(items.length); }
                if (elProgressBar) {
                    elProgressBar.style.width = pct + '%';
                    elProgressBar.setAttribute('data-percent', String(pct));
                    elProgressBar.setAttribute('aria-valuenow', String(pct));
                    elProgressBar.textContent = '';
                }
                if (elSubmitLabel) {
                    elSubmitLabel.textContent = (index >= items.length - 1)
                        ? 'Salvar e concluir'
                        : 'Salvar e próximo';
                }
            }

            function updateAutoWinner() {
                if (!elHomeScore || !elAwayScore || !elAutoWinner) { return; }
                var item = items[index];
                if (!item) { return; }
                var home = parseInt(elHomeScore.value, 10);
                var away = parseInt(elAwayScore.value, 10);
                if (isNaN(home) || isNaN(away)) {
                    elAutoWinner.textContent = 'defina o placar';
                } else if (home > away) {
                    elAutoWinner.textContent = item.homeName;
                } else if (away > home) {
                    elAutoWinner.textContent = item.awayName;
                } else {
                    elAutoWinner.textContent = 'Empate — use Trapacear para escolher';
                }
            }

            function resetFormFields() {
                if (!elForm) { return; }
                elForm.reset();
                if (elCsrf && csrfName) {
                    elCsrf.name = csrfName;
                    elCsrf.value = csrfHash;
                }
                var ajaxInput = elForm.querySelector('input[name="ajax"]');
                if (ajaxInput) { ajaxInput.value = '1'; }
                if (elCheatBlock) { elCheatBlock.style.display = 'none'; }
                updateAutoWinner();
            }

            function renderStep(options) {
                options = options || {};
                hideAlerts();
                if (index >= items.length) {
                    if (elStep) { elStep.style.display = 'none'; }
                    if (elDone) { elDone.style.display = 'block'; }
                    if (elFooter) { elFooter.style.display = 'none'; }
                    if (elProgressBar) {
                        elProgressBar.style.width = '100%';
                        elProgressBar.setAttribute('data-percent', '100');
                        elProgressBar.setAttribute('aria-valuenow', '100');
                        elProgressBar.textContent = '';
                    }
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }

                var item = items[index];
                if (!item) { return; }
                if (elStep) { elStep.style.display = 'block'; }
                if (elDone) { elDone.style.display = 'none'; }
                if (elFooter) { elFooter.style.display = 'block'; }

                var homeName = item.homeName || 'Casa';
                var awayName = item.awayName || 'Visitante';

                var contextParts = [item.context];
                if (item.matchNumber) {
                    contextParts.push('Jogo #' + item.matchNumber);
                }
                if (item.scheduledAt) {
                    contextParts.push(item.scheduledAt);
                }
                if (elContext) {
                    elContext.textContent = contextParts.join(' · ');
                }

                if (elMatchup) {
                    elMatchup.innerHTML =
                        '<div class="ag-bulk-results-wizard__versus">' +
                        '<span class="ag-bulk-results-wizard__participant">' + escapeHtml(homeName) + '</span>' +
                        '<span class="ag-bulk-results-wizard__vs-pill">vs</span>' +
                        '<span class="ag-bulk-results-wizard__participant">' + escapeHtml(awayName) + '</span>' +
                        '</div>';
                }

                bulkModal.setAttribute('data-home-name', homeName);
                bulkModal.setAttribute('data-away-name', awayName);

                resetFormFields();

                var homeNameEl = bulkModal.querySelector('.ag-bulk-results-wizard__home-name');
                var awayNameEl = bulkModal.querySelector('.ag-bulk-results-wizard__away-name');
                if (homeNameEl) { homeNameEl.textContent = homeName; }
                if (awayNameEl) { awayNameEl.textContent = awayName; }

                if (elCheatRadios) {
                    elCheatRadios.innerHTML =
                        '<label><input type="radio" name="winner_participant_id" value="' + item.homeId + '"> ' + escapeHtml(homeName) + '</label><br>' +
                        '<label><input type="radio" name="winner_participant_id" value="' + item.awayId + '"> ' + escapeHtml(awayName) + '</label>';
                }

                updateProgress();

                if (elHomeScore && !options.skipFocus) {
                    setTimeout(function () { elHomeScore.focus(); }, 200);
                }
            }

            function setSubmitting(isSubmitting) {
                if (!elSubmit) { return; }
                elSubmit.disabled = isSubmitting;
                if (elSkip) { elSkip.disabled = isSubmitting; }
            }

            function submitCurrent() {
                if (index >= items.length || !elForm) { return; }
                var item = items[index];

                if (!elForm.reportValidity()) {
                    return;
                }

                hideAlerts();
                setSubmitting(true);

                var formData = new FormData(elForm);
                formData.set('ajax', '1');
                if (csrfName && csrfHash) {
                    formData.set(csrfName, csrfHash);
                }

                fetch(urlBase + item.id, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        setSubmitting(false);
                        if (result.data && result.data.csrfHash) {
                            csrfHash = result.data.csrfHash;
                            bulkModal.setAttribute('data-csrf-hash', csrfHash);
                            if (elCsrf) { elCsrf.value = csrfHash; }
                        }
                        if (!result.ok || !result.data.success) {
                            showError((result.data && result.data.message) || 'Não foi possível salvar o resultado.');
                            return;
                        }
                        savedCount += 1;
                        showSuccessFlash(result.data.message || 'Resultado salvo.');
                        index += 1;
                        setTimeout(function () {
                            renderStep();
                        }, 350);
                    })
                    .catch(function () {
                        setSubmitting(false);
                        showError('Erro de comunicação. Tente novamente.');
                    });
            }

            if (elSubmit) {
                elSubmit.addEventListener('click', submitCurrent);
            }

            if (elSkip) {
                elSkip.addEventListener('click', function () {
                    hideAlerts();
                    index += 1;
                    renderStep();
                });
            }

            if (elHomeScore) {
                elHomeScore.addEventListener('input', updateAutoWinner);
            }
            if (elAwayScore) {
                elAwayScore.addEventListener('input', updateAutoWinner);
            }

            bulkModal.addEventListener('click', function (e) {
                var toggle = e.target.closest ? e.target.closest('.ag-cheat-toggle') : null;
                if (!toggle || !bulkModal.contains(toggle)) { return; }
                if (!elCheatBlock) { return; }
                if (elCheatBlock.style.display === 'none' || elCheatBlock.style.display === '') {
                    elCheatBlock.style.display = 'block';
                } else {
                    elCheatBlock.style.display = 'none';
                    elCheatBlock.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
                }
            });

            function onBulkModalShown() {
                index = 0;
                savedCount = 0;
                csrfHash = bulkModal.getAttribute('data-csrf-hash') || csrfHash;
                renderStep();
            }

            function onBulkModalHidden() {
                if (savedCount > 0) {
                    window.location.reload();
                }
            }

            // Bootstrap 3 dispara eventos via jQuery; addEventListener nativo não recebe shown.bs.modal.
            if (typeof jQuery !== 'undefined') {
                jQuery(bulkModal)
                    .on('shown.bs.modal', onBulkModalShown)
                    .on('hidden.bs.modal', onBulkModalHidden);
            } else {
                bulkModal.addEventListener('shown.bs.modal', onBulkModalShown);
                bulkModal.addEventListener('hidden.bs.modal', onBulkModalHidden);
            }

            renderStep({ skipFocus: true });

            elForm.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && e.target && e.target.classList.contains('ag-score')) {
                    e.preventDefault();
                    submitCurrent();
                }
            });
        }
    }
})();
