<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isEdit = !empty($is_edit) && !empty($t['slug']);
$formAction = $isEdit
    ? arenagamer_client_url('tournament_edit/' . $t['slug'])
    : arenagamer_client_url('tournament');
$formId = $isEdit ? 'tournament-edit-form' : 'tournament-create-form';
$authUser = $auth_user ?? null;
$currentPlan = is_array($current_plan ?? null) ? $current_plan : arenagamer_contact_plan($authUser);
$pricing = $tournament_pricing ?? arenagamer_tournament_pricing_local();
$tournamentLimitReachedPreview = arenagamer_plan_tournament_limit_reached($currentPlan);
$planMaxParticipants = arenagamer_plan_participants_hard_limit($currentPlan) ?? 0;
$defaultLimit = (int) ($t['participantsLimit'] ?? 0);
if ($defaultLimit <= 0) {
    $defaultLimit = arenagamer_plan_included_participants($currentPlan, $pricing, !$tournamentLimitReachedPreview);
}
$costBreakdown = arenagamer_tournament_creation_cost_breakdown($currentPlan, $defaultLimit, $pricing);
$includedForBilling = (int) ($costBreakdown['includedParticipants'] ?? arenagamer_pricing_included_participants($pricing));
$basePrice = (float) ($pricing['baseTournamentPrice'] ?? 0);
$extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
$defaultLimit = (int) ($t['participantsLimit'] ?? $includedForBilling);
if ($planMaxParticipants > 0 && !$isEdit && $defaultLimit > $planMaxParticipants) {
    $defaultLimit = $planMaxParticipants;
}
$planIncludedParticipants = arenagamer_plan_free_max_participants($currentPlan);
$allowsEntryFeeByPlan = arenagamer_plan_allows_entry_fee($currentPlan);
$entryFeeMinCreationCost = arenagamer_entry_fee_min_creation_cost();
$allowsEntryFee = $allowsEntryFeeByPlan || arenagamer_tournament_allows_entry_fee($currentPlan, $defaultLimit, $pricing);
$tournamentsUsed = (int) ($costBreakdown['tournamentsUsed'] ?? 0);
$freeTournamentsMonth = (int) ($costBreakdown['freeTournamentsMonth'] ?? 0);
$tournamentLimitReached = !empty($costBreakdown['tournamentLimitReached']);
$hasFreeTournamentSlot = !empty($costBreakdown['hasFreeTournamentSlot']);
$planParticipantBenefitsActive = !empty($costBreakdown['planParticipantBenefitsActive']);
$standardIncludedForBilling = (int) ($costBreakdown['standardIncludedParticipants'] ?? arenagamer_pricing_included_participants($pricing));
$walletData = is_array($wallet ?? null) ? $wallet : [];
$walletAvailable = (float) ($walletData['availableBalance'] ?? 0);
$tournamentsRemaining = (int) ($costBreakdown['freeTournamentsRemaining'] ?? 0);
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-trophy"></i> <?php echo $isEdit ? 'Editar Torneio' : 'Novo Torneio'; ?></h4>
        <a href="<?php echo arenagamer_client_url('tournaments'); ?>" class="btn btn-default btn-xs mbot15">&larr; Voltar</a>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($api_error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="fa fa-building"></i> Vinculado à sua empresa
            <?php if (!empty($authUser['clientUserId'])): ?>
            — Cliente #<?php echo (int) $authUser['clientUserId']; ?>
            <?php endif; ?>
        </div>

        <?php if (!$isEdit): ?>
        <div class="alert <?php echo $tournamentLimitReached ? 'alert-danger' : 'alert-info'; ?>" id="plan-limit-status">
            <i class="fa fa-credit-card"></i>
            <strong>Plano:</strong>
            <?php echo htmlspecialchars($currentPlan['name'] ?? '—'); ?>
            <?php echo arenagamer_plan_status_badge($currentPlan); ?>

            <?php if ($freeTournamentsMonth > 0): ?>
                <?php if ($tournamentLimitReached): ?>
            <br>
            <span class="text-danger"><i class="fa fa-exclamation-circle"></i>
                <strong>Limite atingido.</strong>
                Novos torneios seguem o preço padrão do campeonato.
            </span>
                <?php else: ?>
            <br>
            Torneios criados inclusos no plano: <strong><?php echo $tournamentsUsed; ?> de <?php echo $freeTournamentsMonth; ?></strong>
                    <?php if ($tournamentsRemaining > 0): ?>
            <br>
            <span class="text-muted">Restam <?php echo $tournamentsRemaining; ?> torneio(s) incluso(s) neste mês.</span>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            <br>
            <?php if ($planParticipantBenefitsActive && $planIncludedParticipants > 0): ?>
            Máximo de participantes no plano: <strong><?php echo $planIncludedParticipants; ?></strong>
            <br>
            <?php endif; ?>
            <br>
            Taxa de inscrição:
            <strong id="entry-fee-plan-status"><?php echo $allowsEntryFee ? 'liberada' : 'não liberada'; ?></strong>
            <?php if (!$allowsEntryFeeByPlan && $extraPrice > 0): ?>
            <button type="button" class="btn btn-default btn-xs mleft5" id="unlock-entry-fee-btn" style="display:none;"
                    title="Ajusta participantes avulsos para atingir o mínimo de créditos na criação. Haverá cobrança extra.">
                Liberar taxa de inscrição
            </button>
            <br class="unlock-entry-fee-hint-break" id="unlock-entry-fee-hint-break" style="display:none;">
            <small class="text-muted unlock-entry-fee-hint" id="unlock-entry-fee-hint" style="display:none;">
                Será cobrado extra em participantes avulsos para liberar a taxa de inscrição.
            </small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php echo form_open($formAction, ['id' => $formId, 'enctype' => 'multipart/form-data']); ?>

        <?php
        $participantsSoloHelp = $isEdit
            ? 'Não pode ser alterado após a criação do torneio.'
            : ($planMaxParticipants > 0
                ? 'Seu plano permite até ' . $planMaxParticipants . ' participantes por torneio.'
                : ($planParticipantBenefitsActive
                    ? 'Até ' . $includedForBilling . ' inclusos no plano; acima disso: ' . arenagamer_format_credits($extraPrice) . ' por participante avulso.'
                    : 'Preço padrão: ' . arenagamer_format_credits($extraPrice) . ' por participante acima de ' . $includedForBilling . '.'));
        $participantsTeamHelp = $isEdit
            ? 'Não pode ser alterado após a criação do torneio.'
            : ($planMaxParticipants > 0
                ? 'Seu plano permite até ' . $planMaxParticipants . ' equipes por torneio.'
                : 'Número máximo de equipes inscritas neste campeonato.');
        $participantsHelpText = $isEdit
            ? 'Não pode ser alterado após a criação do torneio.'
            : ($planMaxParticipants > 0
                ? 'Seu plano permite até ' . $planMaxParticipants . ' participantes por torneio.'
                : ($planParticipantBenefitsActive
                    ? 'Até ' . $includedForBilling . ' inclusos no plano; acima disso: ' . arenagamer_format_credits($extraPrice) . ' por participante avulso.'
                    : 'Preço padrão: ' . arenagamer_format_credits($extraPrice) . ' por participante acima de ' . $includedForBilling . '.'));

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_basic_info_fields', [
            'tournament'  => $t,
            'presets'     => $presets ?? null,
            'search_url'  => arenagamer_client_url('search_presets'),
        ]);

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_tournament_config_fields', [
            'tournament'                      => $t,
            'is_edit'                         => $isEdit,
            'tournament_systems'              => $tournament_systems ?? null,
            'tournament_type_options'         => $tournament_type_options ?? null,
            'tournament_type_labels'          => $tournament_type_labels ?? null,
            'default_participants_limit'      => $defaultLimit,
            'included_for_billing'            => $includedForBilling,
            'extra_participant_price'         => $extraPrice,
            'plan_participant_benefits_active'=> $planParticipantBenefitsActive,
            'plan_max_participants'           => $planMaxParticipants,
            'participants_solo_help'          => $participantsSoloHelp,
            'participants_team_help'          => $participantsTeamHelp,
            'participants_help_text'          => $participantsHelpText,
            'show_entry_fee_unlock_button'    => !$isEdit && !$allowsEntryFeeByPlan,
        ]);

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_prize_fee_fields', [
            'tournament'              => $t,
            'is_edit'                 => $isEdit,
            'label_class'             => '',
            'help_class'              => 'text-muted',
            'allows_entry_fee_by_plan'=> $allowsEntryFeeByPlan,
            'entry_fee_min_cost'      => $entryFeeMinCreationCost,
            'entry_fee_help'          => '',
        ]);

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_date_fields', [
            'tournament' => $t,
            'is_create'  => !$isEdit,
        ]);

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_media_fields', [
            'tournament' => $t,
            'presets'    => $presets ?? null,
        ]);

        $this->load->view('../../modules/arenagamer/views/client/tournaments/_rules_fields', [
            'tournament' => $t,
        ]);
        ?>

        <?php if (!$isEdit): ?>
        <div class="panel_s">
            <div class="panel-body" id="tournament-pricing-info">
                <h5 class="bold mtop0 mbot10"><i class="fa fa-calculator"></i> Custo estimado</h5>
                <table class="table table-condensed mbot10" id="tournament-cost-breakdown">
                    <tbody>
                        <tr>
                            <td>Taxa base do campeonato</td>
                            <td class="text-right" id="cost-line-base"><?php echo arenagamer_format_credits($costBreakdown['basePrice']); ?></td>
                        </tr>
                        <tr id="cost-line-extra-row" style="<?php echo ($costBreakdown['extraParticipants'] ?? 0) > 0 ? '' : 'display:none;'; ?>">
                            <td>
                                Participantes avulsos
                                (<span id="cost-extra-count"><?php echo (int) ($costBreakdown['extraParticipants'] ?? 0); ?></span>
                                × <?php echo arenagamer_format_credits($extraPrice); ?>)
                            </td>
                            <td class="text-right" id="cost-line-extra"><?php echo arenagamer_format_credits($costBreakdown['extraTotal']); ?></td>
                        </tr>
                        <tr class="active">
                            <td><strong>Preço padrão do campeonato</strong></td>
                            <td class="text-right"><strong id="cost-line-normal"><?php echo arenagamer_format_credits($costBreakdown['normalSubtotal']); ?></strong></td>
                        </tr>
                        <tr id="cost-line-discount-row" style="<?php echo !empty($costBreakdown['baseWaived']) ? '' : 'display:none;'; ?>">
                            <td class="text-success">Benefício do plano (taxa base isenta)</td>
                            <td class="text-right text-success" id="cost-line-discount">− <?php echo arenagamer_format_credits($costBreakdown['planDiscount']); ?></td>
                        </tr>
                        <tr id="cost-line-prize-pool-row" style="display:none;">
                            <td>Prêmio fixo (reservado)</td>
                            <td class="text-right" id="cost-line-prize-pool">0,00 créditos</td>
                        </tr>
                        <tr class="success">
                            <td><strong>Total a debitar em créditos</strong></td>
                            <td class="text-right"><strong id="cost-line-total"><?php echo arenagamer_format_credits($costBreakdown['total']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-muted mbot0" id="tournament-cost-note">
                    <?php if ($tournamentLimitReached && ($costBreakdown['participantsOverPlanLimit'] ?? false)): ?>
                    Limite do plano atingido: cobrança normal (base + avulsos).
                    <?php elseif ($tournamentLimitReached): ?>
                    Limite de torneios inclusos atingido: taxa base cobrada normalmente.
                    <?php elseif ($hasFreeTournamentSlot && ($costBreakdown['total'] ?? 0) <= 0): ?>
                    Este torneio usa benefício incluso do plano (taxa base isenta).
                    <?php elseif ($costBreakdown['participantsOverPlanLimit'] ?? false): ?>
                    Participantes acima do incluso do plano: cobrança avulsa aplicada.
                    <?php else: ?>
                    Dentro dos limites inclusos do plano para participantes.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary" id="tournament-create-submit">
            <i class="fa fa-check"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar'; ?>
        </button>
        <?php echo form_close(); ?>
    </div>
</div>

<?php if (!$isEdit): ?>
<div class="modal fade" id="tournamentChargeConfirmModal" tabindex="-1" role="dialog" aria-labelledby="tournamentChargeConfirmModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="tournamentChargeConfirmModalLabel">
                    <i class="fa fa-credit-card"></i> Confirmar cobrança de créditos
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted mtop0">
                    Ao criar este torneio, os créditos abaixo serão debitados da carteira da empresa.
                </p>
                <p class="mbot10">
                    <strong>Torneio:</strong> <span id="confirm-tournament-name">—</span><br>
                    <strong>Limite de participantes:</strong> <span id="confirm-participants-limit">—</span>
                </p>
                <table class="table table-condensed table-bordered mbot10">
                    <tbody>
                        <tr>
                            <td>Taxa base do campeonato</td>
                            <td class="text-right" id="confirm-cost-base">—</td>
                        </tr>
                        <tr id="confirm-cost-extra-row" style="display:none;">
                            <td>
                                Participantes avulsos
                                (<span id="confirm-extra-count">0</span> × <?php echo arenagamer_format_credits($extraPrice); ?>)
                            </td>
                            <td class="text-right" id="confirm-cost-extra">—</td>
                        </tr>
                        <tr class="active">
                            <td><strong>Preço padrão</strong></td>
                            <td class="text-right"><strong id="confirm-cost-normal">—</strong></td>
                        </tr>
                        <tr id="confirm-cost-discount-row" style="display:none;">
                            <td class="text-success">Benefício do plano (taxa base isenta)</td>
                            <td class="text-right text-success" id="confirm-cost-discount">—</td>
                        </tr>
                        <tr id="confirm-cost-prize-pool-row" style="display:none;">
                            <td>Prêmio fixo (reservado)</td>
                            <td class="text-right" id="confirm-cost-prize-pool">—</td>
                        </tr>
                        <tr class="success">
                            <td><strong>Total a debitar</strong></td>
                            <td class="text-right"><strong id="confirm-cost-total">—</strong></td>
                        </tr>
                    </tbody>
                </table>
                <p class="mbot5">
                    Saldo disponível: <strong id="confirm-wallet-balance"><?php echo arenagamer_format_credits($walletAvailable); ?></strong>
                </p>
                <p class="text-muted mbot0" id="confirm-cost-note"></p>
                <div class="alert alert-danger mtop10 mbot0" id="confirm-insufficient-balance" style="display:none;">
                    <i class="fa fa-exclamation-triangle"></i>
                    Saldo insuficiente para criar este torneio. Compre créditos antes de continuar.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="tournament-charge-confirm-btn">
                    <i class="fa fa-check"></i> Confirmar e criar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    var isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;
    var planIncluded = <?php echo (int) $includedForBilling; ?>;
    var standardIncluded = <?php echo (int) $standardIncludedForBilling; ?>;
    var planParticipantBenefitsActive = <?php echo $planParticipantBenefitsActive ? 'true' : 'false'; ?>;
    var planMaxParticipants = <?php echo (int) $planMaxParticipants; ?>;
    var basePrice = <?php echo json_encode($basePrice); ?>;
    var extraPrice = <?php echo json_encode($extraPrice); ?>;
    var tournamentLimitReached = <?php echo $tournamentLimitReached ? 'true' : 'false'; ?>;
    var hasFreeTournamentSlot = <?php echo $hasFreeTournamentSlot ? 'true' : 'false'; ?>;
    var planAllowsEntryFee = <?php echo $allowsEntryFeeByPlan ? 'true' : 'false'; ?>;
    var entryFeeMinCreationCost = <?php echo json_encode($entryFeeMinCreationCost); ?>;
    var walletAvailable = <?php echo json_encode($walletAvailable); ?>;

    var form = document.getElementById(<?php echo json_encode($formId); ?>);
    var input = document.getElementById('participants_limit');

    function getParticipantsLimitInput() {
        return document.getElementById('participants_limit');
    }

    document.addEventListener('arenagamer:participants-limit-constraint-changed', function () {
        input = getParticipantsLimitInput();
    });
    document.addEventListener('arenagamer:participants-fields-updated', function () {
        input = getParticipantsLimitInput();
        updatePreview();
    });
    var entryFeeInput = document.getElementById('entry_fee_credits');
    var entryFeeHelp = document.getElementById('entry-fee-help');
    var entryFeePlanStatus = document.getElementById('entry-fee-plan-status');
    var unlockEntryFeeBtn = document.getElementById('unlock-entry-fee-btn');
    var unlockEntryFeeParticipantsBtn = document.getElementById('unlock-entry-fee-participants-btn');
    var unlockEntryFeeHint = document.getElementById('unlock-entry-fee-hint');
    var unlockEntryFeeHintBreak = document.getElementById('unlock-entry-fee-hint-break');
    var unlockEntryFeeParticipantsHint = document.getElementById('unlock-entry-fee-participants-hint');
    var prizeTypeInput = document.getElementById('prize_type');
    var prizeFundingInput = document.getElementById('prize_funding');
    var prizePoolInput = document.getElementById('prize_pool');
    var prizePoolRow = document.getElementById('cost-line-prize-pool-row');
    var extraRow = document.getElementById('cost-line-extra-row');
    var discountRow = document.getElementById('cost-line-discount-row');
    var noteEl = document.getElementById('tournament-cost-note');
    var confirmModal = $('#tournamentChargeConfirmModal');
    var chargeConfirmed = false;

    function getIncludedForBilling() {
        return planParticipantBenefitsActive ? planIncluded : standardIncluded;
    }

    function formatCredits(value) {
        return Number(value).toFixed(2).replace('.', ',') + ' créditos';
    }

    function isEntryFeesMode() {
        return prizeFundingInput && prizeFundingInput.value === 'ENTRY_FEES';
    }

    function getPrizePoolCost() {
        if (!prizeTypeInput || !prizeFundingInput || !prizePoolInput) {
            return 0;
        }
        if (prizeTypeInput.value === 'AUTOMATIC' && prizeFundingInput.value === 'FIXED') {
            return Math.max(0, parseFloat(prizePoolInput.value) || 0);
        }
        return 0;
    }

    function computeBreakdown(limit) {
        var included = getIncludedForBilling();
        limit = Math.max(0, parseInt(limit, 10) || 0);
        var extraCount = Math.max(0, limit - included);
        var extraTotal = extraCount * extraPrice;
        var normalSubtotal = basePrice + extraTotal;
        var baseWaived = hasFreeTournamentSlot && !tournamentLimitReached;
        var planDiscount = baseWaived ? basePrice : 0;
        var prizePoolCost = getPrizePoolCost();
        var total = Math.max(0, normalSubtotal - planDiscount + prizePoolCost);
        var participantsOver = extraCount > 0;

        return {
            limit: limit,
            included: included,
            extraCount: extraCount,
            extraTotal: extraTotal,
            normalSubtotal: normalSubtotal,
            baseWaived: baseWaived,
            planDiscount: planDiscount,
            prizePoolCost: prizePoolCost,
            total: total,
            participantsOver: participantsOver
        };
    }

    function getCostNote(b) {
        if (tournamentLimitReached && b.participantsOver) {
            return 'Limite do plano atingido: cobrança normal (taxa base + participantes avulsos).';
        }
        if (tournamentLimitReached) {
            return 'Limite de torneios inclusos atingido: taxa base cobrada normalmente.';
        }
        if (b.baseWaived && b.total <= 0) {
            return 'Benefício incluso do plano aplicado (taxa base isenta).';
        }
        if (b.participantsOver) {
            return 'Participantes acima do incluso: cobrança avulsa aplicada.';
        }
        if (b.baseWaived) {
            return 'Taxa base isenta pelo plano.';
        }
        return 'Cobrança conforme preço padrão do campeonato.';
    }

    function entryFeeFieldRelevant() {
        return prizeFundingInput && (prizeFundingInput.value === 'ENTRY_FEES' || prizeFundingInput.value === 'FIXED');
    }

    function clampParticipantsLimit(value) {
        return snapParticipantsLimitValue(value, false);
    }

    function isEliminationTournamentType() {
        var typeEl = document.getElementById('type');
        if (!typeEl) {
            return false;
        }
        var type = typeEl.value;
        return type === 'SINGLE_ELIMINATION' || type === 'DOUBLE_ELIMINATION';
    }

    function isPowerOfTwo(value) {
        value = parseInt(value, 10);
        return value >= 2 && (value & (value - 1)) === 0;
    }

    function validateParticipantsLimitMessage() {
        input = getParticipantsLimitInput();
        if (!input) {
            return null;
        }
        var limit = parseInt(input.value, 10) || 0;
        var floor = configMinParticipants();
        if (limit < floor) {
            return 'O limite deve ser pelo menos ' + floor + '.';
        }
        if (isEliminationTournamentType()) {
            if (!isPowerOfTwo(limit)) {
                return 'Na eliminação, o limite deve ser 4, 8, 16, 32…';
            }
        } else {
            var step = getParticipantsLimitStep();
            if ((limit - floor) % step !== 0) {
                return 'O limite deve aumentar de ' + step + ' em ' + step + ' (ex.: ' + floor + ', ' + (floor + step) + '…).';
            }
        }
        if (planMaxParticipants > 0 && limit > planMaxParticipants) {
            return 'Seu plano permite no máximo ' + planMaxParticipants + ' participantes por torneio.';
        }
        return null;
    }

    function getMinParticipantsInput() {
        return document.getElementById('min_participants');
    }

    function validateMinParticipantsMessage() {
        var minInput = getMinParticipantsInput();
        var limitInput = getParticipantsLimitInput();
        if (!minInput || !limitInput) {
            return null;
        }
        var min = parseInt(minInput.value, 10) || 0;
        var limit = parseInt(limitInput.value, 10) || 0;
        if (min > limit) {
            return 'O mínimo de participantes não pode ser maior que o limite (' + limit + ').';
        }
        var floor = configMinParticipants();
        if (min < floor) {
            return 'O mínimo de participantes deve ser pelo menos ' + floor + '.';
        }
        if (isEliminationTournamentType()) {
            if (!isPowerOfTwo(min)) {
                return 'Na eliminação, o mínimo deve ser 4, 8, 16, 32…';
            }
        } else {
            var step = getParticipantsLimitStep();
            if ((min - floor) % step !== 0) {
                return 'O mínimo deve aumentar de ' + step + ' em ' + step + ' (ex.: ' + floor + ', ' + (floor + step) + '…).';
            }
        }
        return null;
    }

    function getParticipantsLimitStep() {
        if (window.arenagamerParticipantsLimitConstraint) {
            return window.arenagamerParticipantsLimitConstraint.getStep();
        }
        return 1;
    }

    function configMinParticipants() {
        return <?php echo (int) arenagamer_tournament_min_participants(); ?>;
    }

    function snapParticipantsLimitValue(value, roundUp) {
        if (window.arenagamerParticipantsLimitConstraint) {
            return window.arenagamerParticipantsLimitConstraint.snap(value, roundUp);
        }
        return Math.max(2, parseInt(value, 10) || 2);
    }

    function findParticipantsToUnlockEntryFee(startLimit) {
        if (extraPrice <= 0 || !input || input.readOnly) {
            return null;
        }

        var included = getIncludedForBilling();
        var target = entryFeeMinCreationCost;
        startLimit = snapParticipantsLimitValue(startLimit, false);
        startLimit = Math.max(configMinParticipants(), startLimit);

        var step = getParticipantsLimitStep();
        var maxLimit = planMaxParticipants > 0
            ? planMaxParticipants
            : Math.max(startLimit + 500, included + Math.ceil(target / extraPrice) + 50);

        if (window.arenagamerParticipantsLimitConstraint
            && typeof window.arenagamerParticipantsLimitConstraint.eachLimitFrom === 'function') {
            var found = null;
            window.arenagamerParticipantsLimitConstraint.eachLimitFrom(startLimit, maxLimit, function (limit) {
                var breakdown = computeBreakdown(limit);
                var eligibleCost = Math.max(0, breakdown.normalSubtotal - breakdown.planDiscount);
                if (eligibleCost >= target) {
                    found = {
                        limit: limit,
                        added: limit - startLimit,
                        breakdown: breakdown
                    };
                    return false;
                }
            });
            return found;
        }

        for (var limit = startLimit; limit <= maxLimit; limit += step) {
            var breakdown = computeBreakdown(limit);
            var eligibleCost = Math.max(0, breakdown.normalSubtotal - breakdown.planDiscount);
            if (eligibleCost >= target) {
                return {
                    limit: limit,
                    added: limit - startLimit,
                    breakdown: breakdown
                };
            }
        }

        return null;
    }

    function applyParticipantsToUnlockEntryFee() {
        if (!input || planAllowsEntryFee) {
            return;
        }

        var result = findParticipantsToUnlockEntryFee(input.value);
        if (!result) {
            window.alert('Não foi possível liberar a taxa de inscrição só com participantes avulsos. Ajuste o prêmio fixo ou contrate um plano com esse benefício.');
            return;
        }

        if (window.arenagamerParticipantsLimitConstraint && typeof window.arenagamerParticipantsLimitConstraint.setLimit === 'function') {
            window.arenagamerParticipantsLimitConstraint.setLimit(result.limit);
        } else if (input) {
            input.value = result.limit;
        }
        updatePreview();
    }

    function syncUnlockEntryFeeButtons(b) {
        var show = !isEdit && !planAllowsEntryFee && extraPrice > 0 && input && !input.readOnly;
        var result = show ? findParticipantsToUnlockEntryFee(input.value) : null;
        var label = 'Liberar taxa de inscrição';
        var hint = 'Será cobrado extra em participantes avulsos para liberar a taxa de inscrição.';

        if (result && result.added > 0) {
            var currentBreakdown = computeBreakdown(input.value);
            var extraCost = Math.max(0, result.breakdown.total - currentBreakdown.total);
            label = 'Liberar taxa de inscrição (+' + result.added + ' participante' + (result.added === 1 ? '' : 's') + ')';
            hint = 'Cobrança extra estimada de ' + formatCredits(extraCost)
                + ' para liberar a taxa de inscrição.';
        } else if (result && result.added === 0) {
            show = false;
        } else if (show && !result) {
            show = false;
        }

        [unlockEntryFeeBtn, unlockEntryFeeParticipantsBtn].forEach(function (btn) {
            if (!btn) {
                return;
            }
            btn.style.display = show ? '' : 'none';
            btn.textContent = label;
        });

        if (unlockEntryFeeHint) {
            unlockEntryFeeHint.style.display = show ? '' : 'none';
            unlockEntryFeeHint.textContent = hint;
        }
        if (unlockEntryFeeHintBreak) {
            unlockEntryFeeHintBreak.style.display = show ? '' : 'none';
        }
        if (unlockEntryFeeParticipantsHint) {
            unlockEntryFeeParticipantsHint.style.display = show ? '' : 'none';
            unlockEntryFeeParticipantsHint.textContent = hint;
        }
    }

    function updateEntryFeeAvailability(b, allowed) {
        var fundingAllowed = planAllowsEntryFee || Math.max(0, b.normalSubtotal - b.planDiscount) >= entryFeeMinCreationCost;
        if (typeof allowed !== 'boolean') {
            allowed = planAllowsEntryFee || b.total >= entryFeeMinCreationCost;
        }

        if (entryFeeInput && entryFeeFieldRelevant()) {
            var wantsEntryFee = parseFloat(entryFeeInput.value) > 0;
            var canUseEntryFee = isEntryFeesMode() ? fundingAllowed : allowed;
            entryFeeInput.readOnly = !canUseEntryFee && (isEntryFeesMode() || wantsEntryFee);
            if (!canUseEntryFee && isEntryFeesMode()) {
                entryFeeInput.value = '';
            }
        }

        if (entryFeePlanStatus && entryFeeFieldRelevant()) {
            entryFeePlanStatus.textContent = (allowed || fundingAllowed) ? 'liberada' : 'não liberada';
        }

        syncUnlockEntryFeeButtons(b);
    }

    function publishCreationCosts(b) {
        var entryFeeEligibleCost = Math.max(0, b.normalSubtotal - b.planDiscount);
        var entryFeeAllowed = planAllowsEntryFee || b.total >= entryFeeMinCreationCost;
        var entryFeesFundingAllowed = planAllowsEntryFee || entryFeeEligibleCost >= entryFeeMinCreationCost;

        window.arenagamerTournamentCosts = {
            total: b.total,
            entryFeeEligibleCost: entryFeeEligibleCost,
            entryFeeAllowed: entryFeeAllowed,
            entryFeesFundingAllowed: entryFeesFundingAllowed
        };

        document.dispatchEvent(new CustomEvent('arenagamer:creation-cost-changed', {
            detail: window.arenagamerTournamentCosts
        }));

        updateEntryFeeAvailability(b, entryFeeAllowed);
    }

    function updatePreview() {
        var b = computeBreakdown(input ? input.value : getIncludedForBilling());

        document.getElementById('cost-line-base').textContent = formatCredits(basePrice);
        document.getElementById('cost-extra-count').textContent = b.extraCount;
        document.getElementById('cost-line-extra').textContent = formatCredits(b.extraTotal);
        document.getElementById('cost-line-normal').textContent = formatCredits(b.normalSubtotal);
        document.getElementById('cost-line-total').textContent = formatCredits(b.total);

        if (extraRow) {
            extraRow.style.display = b.extraCount > 0 ? '' : 'none';
        }
        if (discountRow) {
            discountRow.style.display = b.baseWaived ? '' : 'none';
            document.getElementById('cost-line-discount').textContent = '− ' + formatCredits(b.planDiscount);
        }
        if (prizePoolRow) {
            prizePoolRow.style.display = b.prizePoolCost > 0 ? '' : 'none';
            document.getElementById('cost-line-prize-pool').textContent = formatCredits(b.prizePoolCost);
        }
        if (noteEl) {
            noteEl.textContent = getCostNote(b);
        }

        publishCreationCosts(b);

        return b;
    }

    function populateConfirmModal(b) {
        var nameInput = form ? form.querySelector('[name="name"]') : null;
        var tournamentName = nameInput && nameInput.value.trim() !== '' ? nameInput.value.trim() : '—';

        document.getElementById('confirm-tournament-name').textContent = tournamentName;
        document.getElementById('confirm-participants-limit').textContent = b.limit;
        document.getElementById('confirm-cost-base').textContent = formatCredits(basePrice);
        document.getElementById('confirm-extra-count').textContent = b.extraCount;
        document.getElementById('confirm-cost-extra').textContent = formatCredits(b.extraTotal);
        document.getElementById('confirm-cost-normal').textContent = formatCredits(b.normalSubtotal);
        document.getElementById('confirm-cost-total').textContent = formatCredits(b.total);
        document.getElementById('confirm-cost-note').textContent = getCostNote(b);
        document.getElementById('confirm-wallet-balance').textContent = formatCredits(walletAvailable);

        var extraConfirmRow = document.getElementById('confirm-cost-extra-row');
        var discountConfirmRow = document.getElementById('confirm-cost-discount-row');
        var insufficientEl = document.getElementById('confirm-insufficient-balance');
        var confirmBtn = document.getElementById('tournament-charge-confirm-btn');

        if (extraConfirmRow) {
            extraConfirmRow.style.display = b.extraCount > 0 ? '' : 'none';
        }
        if (discountConfirmRow) {
            discountConfirmRow.style.display = b.baseWaived ? '' : 'none';
            document.getElementById('confirm-cost-discount').textContent = '− ' + formatCredits(b.planDiscount);
        }

        var prizePoolConfirmRow = document.getElementById('confirm-cost-prize-pool-row');
        if (prizePoolConfirmRow) {
            prizePoolConfirmRow.style.display = b.prizePoolCost > 0 ? '' : 'none';
            document.getElementById('confirm-cost-prize-pool').textContent = formatCredits(b.prizePoolCost);
        }

        var insufficient = b.total > 0 && walletAvailable < b.total;
        if (insufficientEl) {
            insufficientEl.style.display = insufficient ? '' : 'none';
        }
        if (confirmBtn) {
            confirmBtn.disabled = insufficient;
        }
    }

    if (form) {
        form.addEventListener('input', function (event) {
            if (!event.target) {
                return;
            }
            if (event.target.id === 'participants_limit' || event.target.id === 'min_participants') {
                input = getParticipantsLimitInput();
                updatePreview();
            }
        });
    }

    input = getParticipantsLimitInput();
    updatePreview();

    [unlockEntryFeeBtn, unlockEntryFeeParticipantsBtn].forEach(function (btn) {
        if (btn) {
            btn.addEventListener('click', applyParticipantsToUnlockEntryFee);
        }
    });

    if (prizePoolInput) {
        prizePoolInput.addEventListener('input', updatePreview);
    }
    document.addEventListener('arenagamer:prize-settings-changed', updatePreview);

    if (form && !isEdit) {
        form.addEventListener('submit', function (event) {
            if (chargeConfirmed) {
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            var participantsError = validateParticipantsLimitMessage();
            if (participantsError) {
                window.alert(participantsError);
                event.preventDefault();
                return;
            }

            var minError = validateMinParticipantsMessage();
            if (minError) {
                window.alert(minError);
                event.preventDefault();
                return;
            }

            var breakdown = updatePreview();
            if (breakdown.total <= 0) {
                return;
            }

            event.preventDefault();
            populateConfirmModal(breakdown);
            confirmModal.modal('show');
        });
    }

    var confirmBtn = document.getElementById('tournament-charge-confirm-btn');
    if (confirmBtn && form) {
        confirmBtn.addEventListener('click', function () {
            chargeConfirmed = true;
            confirmModal.modal('hide');
            form.submit();
        });
    }
})();
</script>
<?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_preset_autofill', [
    'presets' => $presets ?? null,
]); ?>
