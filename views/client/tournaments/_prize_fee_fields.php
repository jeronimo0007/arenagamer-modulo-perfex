<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$allowsEntryFeeByPlan = !empty($allows_entry_fee_by_plan);
$entryFeeMinCost = (float) ($entry_fee_min_cost ?? arenagamer_entry_fee_min_creation_cost());
$prizeFunding = arenagamer_tournament_resolve_prize_funding($t);
$prizeType = strtoupper((string) ($t['prizeType'] ?? 'MANUAL'));
$isEntryFees = $prizeFunding === 'ENTRY_FEES';
$isAutomaticFixed = $prizeType === 'AUTOMATIC' && $prizeFunding === 'FIXED';
$isManualFixed = $prizeType === 'MANUAL' && $prizeFunding === 'FIXED';
$entryFeeValue = (float) ($t['entryFeeCredits'] ?? 0);
$isCreate = empty($is_edit);
$feePercentage = max(0, min(100, (int) round(((float) ($t['feePercentage'] ?? 0)) / 5) * 5));
?>
<div class="panel_s mtop10 mbot15">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-gift"></i> Prêmio e taxas</h4>
    </div>
    <div class="panel-body">
<div id="tournament-prize-settings">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="prize_type" class="<?php echo htmlspecialchars($labelClass); ?>">Distribuição do prêmio</label>
                <select name="prize_type" id="prize_type" class="form-control<?php echo !empty($use_selectpicker) ? ' selectpicker' : ''; ?>">
                    <?php foreach (arenagamer_tournament_prize_type_options() as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $prizeType === $option ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(arenagamer_tournament_prize_type_label($option)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_type_help_manual"<?php echo $prizeType === 'MANUAL' ? '' : ' style="display:none;"'; ?>>
                    <strong>Manual:</strong> você define ganhadores e prêmios (dinheiro, créditos ou outro). Pode cobrar taxa de inscrição para você.
                </p>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_type_help_automatic"<?php echo $prizeType === 'AUTOMATIC' ? '' : ' style="display:none;"'; ?>>
                    <strong>Automático:</strong> o sistema distribui o prêmio entre os vencedores.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="prize_funding" class="<?php echo htmlspecialchars($labelClass); ?>">Fonte do prêmio</label>
                <select name="prize_funding" id="prize_funding" class="form-control<?php echo !empty($use_selectpicker) ? ' selectpicker' : ''; ?>">
                    <?php foreach (arenagamer_tournament_prize_funding_options() as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $prizeFunding === $option ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(arenagamer_tournament_prize_funding_label($option)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_funding_help_fixed"<?php echo $isEntryFees ? ' style="display:none;"' : ''; ?>>
                    Prêmio fixo: você define o valor. Taxa de inscrição (opcional) vai para o organizador.
                </p>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_funding_help_entry"<?php echo $isEntryFees ? '' : ' style="display:none;"'; ?>>
                    Arrecadação: prêmio formado pelas taxas de inscrição (exige distribuição automática).
                </p>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_funding_help_entry_locked" style="display:none;">
                    Por arrecadação exige <?php echo arenagamer_format_credits($entryFeeMinCost); ?> pagos na criação (além da isenção do plano) ou plano com esse benefício.
                </p>
            </div>
        </div>
        <div class="col-md-4" id="prize_pool_group"<?php echo $isEntryFees ? ' style="display:none;"' : ''; ?>>
            <div class="form-group">
                <label for="prize_pool" class="<?php echo htmlspecialchars($labelClass); ?>">Prêmio fixo (créditos)</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="prize_pool"
                       id="prize_pool"
                       class="form-control"
                       value="<?php echo htmlspecialchars($t['prizePool'] ?? '0'); ?>">
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_pool_help_manual"<?php echo $isManualFixed ? '' : ' style="display:none;"'; ?>>
                    Valor de referência do prêmio. Não é debitado na criação.
                </p>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_pool_help_automatic"<?php echo $isAutomaticFixed ? '' : ' style="display:none;"'; ?>>
                    Debitado na criação e reservado para os ganhadores.
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="entry_fee_credits" class="<?php echo htmlspecialchars($labelClass); ?>">
                    Taxa de inscrição (créditos)<span id="entry_fee_required_mark"<?php echo $isEntryFees ? '' : ' style="display:none;"'; ?>> *</span>
                </label>
                <input type="number"
                       step="0.25"
                       min="0"
                       name="entry_fee_credits"
                       id="entry_fee_credits"
                       class="form-control"
                       value="<?php echo $entryFeeValue > 0 ? htmlspecialchars($t['entryFeeCredits']) : ''; ?>"
                       placeholder="<?php echo $isEntryFees ? 'Ex.: 1,25' : 'Opcional'; ?>">
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="entry-fee-step-help">
                    Incrementos de 0,25 pelos controles do campo; você pode digitar outro valor manualmente (ex.: 1,32).
                </p>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="entry-fee-help">
                    <?php if (!empty($entry_fee_help)): ?>
                    <?php echo $entry_fee_help; ?>
                    <?php else: ?>
                    <span id="entry_fee_help_fixed"<?php echo $isEntryFees ? ' style="display:none;"' : ''; ?>>Cobrada dos participantes e creditada ao organizador. Não compõe o prêmio.</span>
                    <span id="entry_fee_help_entry"<?php echo $isEntryFees ? '' : ' style="display:none;"'; ?>>Cobrada de cada participante e somada ao prêmio arrecadado.</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="col-md-4" id="organizer_fee_group"<?php echo $isEntryFees ? '' : ' style="display:none;"'; ?>>
            <div class="form-group">
                <label for="fee_percentage" class="<?php echo htmlspecialchars($labelClass); ?>">Taxa do organizador (%)</label>
                <select name="fee_percentage"
                        id="fee_percentage"
                        class="form-control<?php echo !empty($use_selectpicker) ? ' selectpicker' : ''; ?>">
                    <?php for ($feeOption = 0; $feeOption <= 100; $feeOption += 5): ?>
                    <option value="<?php echo $feeOption; ?>" <?php echo $feePercentage === $feeOption ? 'selected' : ''; ?>>
                        <?php echo $feeOption; ?>%
                    </option>
                    <?php endfor; ?>
                </select>
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
                    Percentual do total arrecadado retido pelo organizador (de 5% em 5%). O restante vai aos ganhadores.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="prize_mode_summary" style="margin-top:28px;">
                    <?php if ($isEntryFees): ?>
                    O prêmio é formado pelas inscrições. Após a taxa do organizador, o saldo é distribuído automaticamente.
                    <?php elseif ($isManualFixed): ?>
                    No modo manual, a taxa de inscrição é receita do organizador. O prêmio é definido e entregue por você.
                    <?php elseif ($isAutomaticFixed): ?>
                    No automático com prêmio fixo, a taxa de inscrição vai para o organizador. O prêmio fixo é pago por você na criação.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
<script>
(function () {
    var prizeTypeEl = document.getElementById('prize_type');
    var prizeFundingEl = document.getElementById('prize_funding');
    var prizePoolGroup = document.getElementById('prize_pool_group');
    var prizePoolInput = document.getElementById('prize_pool');
    var entryFeeInput = document.getElementById('entry_fee_credits');
    var organizerFeeGroup = document.getElementById('organizer_fee_group');
    var feeInput = document.getElementById('fee_percentage');
    var entryFeeRequiredMark = document.getElementById('entry_fee_required_mark');
    var helpManual = document.getElementById('prize_type_help_manual');
    var helpAutomatic = document.getElementById('prize_type_help_automatic');
    var poolHelpManual = document.getElementById('prize_pool_help_manual');
    var poolHelpAutomatic = document.getElementById('prize_pool_help_automatic');
    var fundingHelpFixed = document.getElementById('prize_funding_help_fixed');
    var fundingHelpEntry = document.getElementById('prize_funding_help_entry');
    var fundingHelpEntryLocked = document.getElementById('prize_funding_help_entry_locked');
    var entryFeeHelpFixed = document.getElementById('entry_fee_help_fixed');
    var entryFeeHelpEntry = document.getElementById('entry_fee_help_entry');
    var modeSummary = document.getElementById('prize_mode_summary');
    var planAllowsEntryFee = <?php echo $allowsEntryFeeByPlan ? 'true' : 'false'; ?>;
    var entryFeeMinCost = <?php echo json_encode($entryFeeMinCost); ?>;
    var isCreate = <?php echo $isCreate ? 'true' : 'false'; ?>;

    if (!prizeTypeEl || !prizeFundingEl) {
        return;
    }

    function refreshSelectpicker(el) {
        if (typeof jQuery !== 'undefined' && el && jQuery(el).hasClass('selectpicker')) {
            jQuery(el).selectpicker('refresh');
        }
    }

    function setSelectValue(el, value) {
        if (!el || el.value === value) {
            return;
        }
        el.value = value;
        refreshSelectpicker(el);
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function getCreationCosts() {
        return window.arenagamerTournamentCosts || {};
    }

    function isEntryFeesMode() {
        return prizeFundingEl.value === 'ENTRY_FEES';
    }

    function isEntryFeesFundingAllowed() {
        if (!isCreate || planAllowsEntryFee) {
            return true;
        }

        var costs = getCreationCosts();
        if (typeof costs.entryFeesFundingAllowed === 'boolean') {
            return costs.entryFeesFundingAllowed;
        }

        if (typeof costs.entryFeeEligibleCost === 'number') {
            return costs.entryFeeEligibleCost >= entryFeeMinCost;
        }

        return false;
    }

    function isEntryFeeFieldAllowed() {
        if (planAllowsEntryFee) {
            return true;
        }

        var costs = getCreationCosts();
        if (isEntryFeesMode()) {
            if (typeof costs.entryFeesFundingAllowed === 'boolean') {
                return costs.entryFeesFundingAllowed;
            }
            if (typeof costs.entryFeeEligibleCost === 'number') {
                return costs.entryFeeEligibleCost >= entryFeeMinCost;
            }
            return false;
        }

        if (typeof costs.entryFeeAllowed === 'boolean') {
            return costs.entryFeeAllowed;
        }

        if (typeof costs.total === 'number') {
            return costs.total >= entryFeeMinCost;
        }

        return false;
    }

    function syncPrizeFundingAvailability() {
        var allowed = isEntryFeesFundingAllowed();
        var isAutomatic = prizeTypeEl.value === 'AUTOMATIC';

        if (fundingHelpEntryLocked) {
            fundingHelpEntryLocked.style.display = (isCreate && isAutomatic && !allowed) ? '' : 'none';
        }
    }

    function syncEntryFeeAvailability() {
        var isEntryFees = isEntryFeesMode();
        var allowed = isEntryFeeFieldAllowed();
        var wantsEntryFee = entryFeeInput && parseFloat(entryFeeInput.value) > 0;

        if (entryFeeInput) {
            entryFeeInput.readOnly = !allowed && (isEntryFees || wantsEntryFee);
            if (!allowed && isEntryFees) {
                entryFeeInput.value = '';
            }
        }

        syncPrizeFundingAvailability();
    }

    function updateModeSummary(isAutomatic, isEntryFees) {
        if (!modeSummary) {
            return;
        }
        if (isEntryFees) {
            modeSummary.textContent = 'O prêmio é formado pelas inscrições. Após a taxa do organizador, o saldo é distribuído automaticamente.';
        } else if (!isAutomatic) {
            modeSummary.textContent = 'No modo manual, a taxa de inscrição é receita do organizador. O prêmio é definido e entregue por você.';
        } else {
            modeSummary.textContent = 'No automático com prêmio fixo, a taxa de inscrição vai para o organizador. O prêmio fixo é pago por você na criação.';
        }
    }

    function syncPrizeFields() {
        var isAutomatic = prizeTypeEl.value === 'AUTOMATIC';
        var isEntryFees = isEntryFeesMode();
        var isManual = !isAutomatic;

        if (helpManual) {
            helpManual.style.display = isAutomatic ? 'none' : '';
        }
        if (helpAutomatic) {
            helpAutomatic.style.display = isAutomatic ? '' : 'none';
        }
        if (fundingHelpFixed) {
            fundingHelpFixed.style.display = isEntryFees ? 'none' : '';
        }
        if (fundingHelpEntry) {
            fundingHelpEntry.style.display = isEntryFees ? '' : 'none';
        }
        if (entryFeeHelpFixed) {
            entryFeeHelpFixed.style.display = isEntryFees ? 'none' : '';
        }
        if (entryFeeHelpEntry) {
            entryFeeHelpEntry.style.display = isEntryFees ? '' : 'none';
        }

        if (prizePoolGroup) {
            prizePoolGroup.style.display = isEntryFees ? 'none' : '';
        }
        if (organizerFeeGroup) {
            organizerFeeGroup.style.display = isEntryFees ? '' : 'none';
        }
        if (entryFeeRequiredMark) {
            entryFeeRequiredMark.style.display = isEntryFees ? '' : 'none';
        }

        if (poolHelpManual) {
            poolHelpManual.style.display = (isManual && !isEntryFees) ? '' : 'none';
        }
        if (poolHelpAutomatic) {
            poolHelpAutomatic.style.display = (isAutomatic && !isEntryFees) ? '' : 'none';
        }

        if (prizePoolInput) {
            if (isEntryFees) {
                prizePoolInput.value = '0';
                prizePoolInput.removeAttribute('required');
            } else if (isAutomatic) {
                prizePoolInput.setAttribute('required', 'required');
            } else {
                prizePoolInput.removeAttribute('required');
            }
        }

        if (entryFeeInput) {
            entryFeeInput.required = isEntryFees;
            entryFeeInput.min = '0';
            entryFeeInput.step = '0.25';
            entryFeeInput.placeholder = isEntryFees ? 'Ex.: 1,25' : 'Opcional';
            if (isEntryFees && (entryFeeInput.value === '0.01' || entryFeeInput.value === '0,01')) {
                entryFeeInput.value = '';
            }
        }

        if (feeInput && !isEntryFees) {
            feeInput.value = '0';
            refreshSelectpicker(feeInput);
        }

        updateModeSummary(isAutomatic, isEntryFees);
        syncEntryFeeAvailability();
        document.dispatchEvent(new CustomEvent('arenagamer:prize-settings-changed'));
    }

    function onPrizeFundingChange() {
        if (prizeFundingEl.value === 'ENTRY_FEES') {
            if (isCreate && !isEntryFeesFundingAllowed()) {
                setSelectValue(prizeFundingEl, 'FIXED');
                return;
            }
            if (prizeTypeEl.value !== 'AUTOMATIC') {
                setSelectValue(prizeTypeEl, 'AUTOMATIC');
                return;
            }
        }

        syncPrizeFields();
    }

    function validateEntryFeeOnSubmit() {
        if (!entryFeeInput || !isEntryFeesMode()) {
            entryFeeInput && entryFeeInput.setCustomValidity('');
            return true;
        }

        var fee = parseFloat(String(entryFeeInput.value).replace(',', '.'));
        if (!entryFeeInput.value || isNaN(fee) || fee <= 0) {
            entryFeeInput.setCustomValidity('Informe a taxa de inscrição para prêmio por arrecadação.');
            return false;
        }

        entryFeeInput.setCustomValidity('');
        return true;
    }

    function onPrizeTypeChange() {
        if (prizeTypeEl.value === 'MANUAL' && prizeFundingEl.value === 'ENTRY_FEES') {
            setSelectValue(prizeFundingEl, 'FIXED');
        }
        syncPrizeFields();
    }

    prizeTypeEl.addEventListener('change', onPrizeTypeChange);
    prizeFundingEl.addEventListener('change', onPrizeFundingChange);

    if (entryFeeInput) {
        entryFeeInput.addEventListener('input', function () {
            entryFeeInput.setCustomValidity('');
            syncEntryFeeAvailability();
        });
        entryFeeInput.addEventListener('change', function () {
            entryFeeInput.setCustomValidity('');
        });
        if (entryFeeInput.form) {
            entryFeeInput.form.addEventListener('submit', function (event) {
                entryFeeInput.setCustomValidity('');
                if (!validateEntryFeeOnSubmit()) {
                    event.preventDefault();
                    entryFeeInput.reportValidity();
                }
            });
        }
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(prizeTypeEl).on('changed.bs.select', onPrizeTypeChange);
        jQuery(prizeFundingEl).on('changed.bs.select', onPrizeFundingChange);
    }

    document.addEventListener('arenagamer:creation-cost-changed', function () {
        syncPrizeFundingAvailability();
        syncEntryFeeAvailability();
    });

    syncPrizeFields();
})();
</script>
