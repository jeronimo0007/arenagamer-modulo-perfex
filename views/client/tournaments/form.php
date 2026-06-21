<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isEdit = !empty($is_edit) && !empty($t['slug']);
$formAction = $isEdit
    ? arenagamer_client_url('tournament_edit/' . $t['slug'])
    : arenagamer_client_url('tournament');
$formId = $isEdit ? 'tournament-edit-form' : 'tournament-create-form';
$presetsData = arenagamer_api_data($presets ?? null, []);
$authUser = $auth_user ?? null;
$currentPlan = is_array($current_plan ?? null) ? $current_plan : arenagamer_contact_plan($authUser);
$pricing = $tournament_pricing ?? arenagamer_tournament_pricing_local();
$tournamentLimitReachedPreview = arenagamer_plan_tournament_limit_reached($currentPlan);
$defaultLimit = (int) ($t['participantsLimit'] ?? 0);
if ($defaultLimit <= 0) {
    $defaultLimit = arenagamer_plan_included_participants($currentPlan, $pricing, !$tournamentLimitReachedPreview);
}
$costBreakdown = arenagamer_tournament_creation_cost_breakdown($currentPlan, $defaultLimit, $pricing);
$includedForBilling = (int) ($costBreakdown['includedParticipants'] ?? arenagamer_pricing_included_participants($pricing));
$basePrice = (float) ($pricing['baseTournamentPrice'] ?? 0);
$extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
$defaultLimit = (int) ($t['participantsLimit'] ?? $includedForBilling);
$planIncludedParticipants = arenagamer_plan_free_max_participants($currentPlan);
$allowsEntryFeeByPlan = arenagamer_plan_allows_entry_fee($currentPlan);
$entryFeeMinCreationCost = arenagamer_entry_fee_min_creation_cost();
$allowsEntryFee = $allowsEntryFeeByPlan || arenagamer_creation_cost_allows_entry_fee($costBreakdown['total'] ?? 0);
$tournamentsUsed = (int) ($costBreakdown['tournamentsUsed'] ?? 0);
$freeTournamentsMonth = (int) ($costBreakdown['freeTournamentsMonth'] ?? 0);
$maxTournamentsMonth = (int) ($costBreakdown['maxTournamentsMonth'] ?? 0);
$tournamentLimitReached = !empty($costBreakdown['tournamentLimitReached']);
$hasFreeTournamentSlot = !empty($costBreakdown['hasFreeTournamentSlot']);
$planParticipantBenefitsActive = !empty($costBreakdown['planParticipantBenefitsActive']);
$standardIncludedForBilling = (int) ($costBreakdown['standardIncludedParticipants'] ?? arenagamer_pricing_included_participants($pricing));
$walletData = is_array($wallet ?? null) ? $wallet : [];
$walletAvailable = (float) ($walletData['availableBalance'] ?? 0);
$tournamentsRemaining = $maxTournamentsMonth > 0 ? max(0, $maxTournamentsMonth - $tournamentsUsed) : 0;
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

            <?php if ($maxTournamentsMonth > 0): ?>
                <?php if ($tournamentLimitReached): ?>
            <br>
            <span class="text-danger"><i class="fa fa-exclamation-circle"></i>
                <strong>Limite atingido.</strong>
                Novos torneios seguem o preço padrão do campeonato.
            </span>
                <?php else: ?>
            <br>
            Torneios inclusos consumidos: <strong><?php echo $tournamentsUsed; ?> de <?php echo $maxTournamentsMonth; ?></strong>
                    <?php if ($tournamentsRemaining > 0): ?>
            <br>
            <span class="text-muted">Restam <?php echo $tournamentsRemaining; ?> torneio(s) incluso(s) neste mês.</span>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>

            <br>
            <?php if ($planParticipantBenefitsActive && $planIncludedParticipants > 0): ?>
            Participantes inclusos no plano (sem custo avulso): <strong><?php echo $planIncludedParticipants; ?></strong>
            <br>
            <?php endif; ?>
            Preço padrão: taxa base <?php echo arenagamer_format_credits($basePrice); ?>
            + <?php echo arenagamer_format_credits($extraPrice); ?> por participante acima de <?php echo $includedForBilling; ?>.
            <br>
            Taxa de inscrição:
            <strong id="entry-fee-plan-status">
                <?php if ($allowsEntryFeeByPlan): ?>
                permitida pelo plano
                <?php elseif (arenagamer_creation_cost_allows_entry_fee($costBreakdown['total'] ?? 0)): ?>
                liberada (a partir de <?php echo arenagamer_format_credits($entryFeeMinCreationCost); ?>)
                <?php else: ?>
                liberada a partir de <?php echo arenagamer_format_credits($entryFeeMinCreationCost); ?> na criação
                <?php endif; ?>
            </strong>
        </div>
        <?php endif; ?>

        <?php echo form_open($formAction, ['id' => $formId, 'enctype' => 'multipart/form-data']); ?>

        <div class="form-group">
            <label>Nome *</label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($t['name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Nome do jogo</label>
            <input type="text" name="game_name" id="game_name" class="form-control" maxlength="100"
                   placeholder="Ex.: Counter-Strike 2"
                   value="<?php echo htmlspecialchars($t['gameName'] ?? ''); ?>">
            <small class="text-muted">Nome do jogo que será disputado no torneio.</small>
        </div>

        <div class="form-group">
            <label>Descrição</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($t['description'] ?? ''); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="type" class="form-control">
                        <?php foreach (arenagamer_tournament_type_options() as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo ($t['type'] ?? 'SINGLE_ELIMINATION') === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars(arenagamer_tournament_type_label($type)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Formato</label>
                    <select name="format" id="format" class="form-control">
                        <?php foreach (arenagamer_tournament_format_options() as $format): ?>
                        <option value="<?php echo $format; ?>" <?php echo ($t['format'] ?? 'SOLO') === $format ? 'selected' : ''; ?>><?php echo htmlspecialchars(arenagamer_tournament_format_label($format)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Visibilidade</label>
                    <select name="visibility" class="form-control">
                        <?php foreach (arenagamer_tournament_visibility_options() as $visibility): ?>
                        <option value="<?php echo $visibility; ?>" <?php echo ($t['visibility'] ?? 'PUBLIC') === $visibility ? 'selected' : ''; ?>><?php echo htmlspecialchars(arenagamer_tournament_visibility_label($visibility)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Limite de participantes</label>
                    <input type="number"
                           min="2"
                           name="participants_limit"
                           id="participants_limit"
                           class="form-control"
                           value="<?php echo $defaultLimit; ?>"
                           <?php echo $isEdit ? 'readonly' : ''; ?>>
                    <small class="text-muted">
                        <?php if ($isEdit): ?>
                        Não pode ser alterado após a criação do torneio.
                        <?php elseif ($planParticipantBenefitsActive): ?>
                        Até <?php echo $includedForBilling; ?> inclusos no plano;
                        acima disso: <?php echo arenagamer_format_credits($extraPrice); ?> por participante avulso.
                        <?php else: ?>
                        Preço padrão: <?php echo arenagamer_format_credits($extraPrice); ?> por participante acima de <?php echo $includedForBilling; ?>.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Preset / Jogo</label>
                    <select name="preset_id" id="preset_id" class="form-control">
                        <option value="">Nenhum</option>
                        <?php foreach ($presetsData as $p): ?>
                        <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) ($t['presetId'] ?? 0) === (int) $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['gameName'] ?? ('Preset #' . $p['id'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao selecionar ou trocar o preset, os campos do jogo são preenchidos automaticamente.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Taxa de entrada (créditos)</label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="entry_fee_credits"
                           id="entry_fee_credits"
                           class="form-control"
                           value="<?php echo htmlspecialchars($t['entryFeeCredits'] ?? '0'); ?>"
                           <?php echo !$allowsEntryFee ? 'readonly' : ''; ?>>
                    <small class="text-muted" id="entry-fee-help">
                        <?php if (!$allowsEntryFeeByPlan && !$allowsEntryFee): ?>
                        Liberada a partir de <?php echo arenagamer_format_credits($entryFeeMinCreationCost); ?> no custo de criação.
                        <?php elseif (!$allowsEntryFeeByPlan && $allowsEntryFee): ?>
                        Liberada — custo de criação a partir de <?php echo arenagamer_format_credits($entryFeeMinCreationCost); ?>.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_date_fields', [
            'tournament' => $t,
            'is_create'  => !$isEdit,
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_media_fields', [
            'tournament' => $t,
            'presets'    => $presets ?? null,
        ]); ?>

        <div class="form-group">
            <label>Regras</label>
            <textarea name="rules" id="rules" class="form-control" rows="3"><?php echo htmlspecialchars($t['rules'] ?? ''); ?></textarea>
            <small class="text-muted">Preenchidas automaticamente ao escolher um preset (se o preset tiver modelo de regras).</small>
        </div>

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
    var basePrice = <?php echo json_encode($basePrice); ?>;
    var extraPrice = <?php echo json_encode($extraPrice); ?>;
    var tournamentLimitReached = <?php echo $tournamentLimitReached ? 'true' : 'false'; ?>;
    var hasFreeTournamentSlot = <?php echo $hasFreeTournamentSlot ? 'true' : 'false'; ?>;
    var planAllowsEntryFee = <?php echo $allowsEntryFeeByPlan ? 'true' : 'false'; ?>;
    var entryFeeMinCreationCost = <?php echo json_encode($entryFeeMinCreationCost); ?>;
    var walletAvailable = <?php echo json_encode($walletAvailable); ?>;

    var form = document.getElementById(<?php echo json_encode($formId); ?>);
    var input = document.getElementById('participants_limit');
    var entryFeeInput = document.getElementById('entry_fee_credits');
    var entryFeeHelp = document.getElementById('entry-fee-help');
    var entryFeePlanStatus = document.getElementById('entry-fee-plan-status');
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

    function computeBreakdown(limit) {
        var included = getIncludedForBilling();
        limit = Math.max(0, parseInt(limit, 10) || 0);
        var extraCount = Math.max(0, limit - included);
        var extraTotal = extraCount * extraPrice;
        var normalSubtotal = basePrice + extraTotal;
        var baseWaived = hasFreeTournamentSlot && !tournamentLimitReached;
        var planDiscount = baseWaived ? basePrice : 0;
        var total = Math.max(0, normalSubtotal - planDiscount);
        var participantsOver = extraCount > 0;

        return {
            limit: limit,
            included: included,
            extraCount: extraCount,
            extraTotal: extraTotal,
            normalSubtotal: normalSubtotal,
            baseWaived: baseWaived,
            planDiscount: planDiscount,
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

    function updateEntryFeeAvailability(b) {
        var allowed = planAllowsEntryFee || b.total >= entryFeeMinCreationCost;

        if (entryFeeInput) {
            entryFeeInput.readOnly = !allowed;
            if (!allowed) {
                entryFeeInput.value = '0';
            }
        }

        if (entryFeePlanStatus) {
            if (planAllowsEntryFee) {
                entryFeePlanStatus.textContent = 'permitida pelo plano';
            } else if (b.total >= entryFeeMinCreationCost) {
                entryFeePlanStatus.textContent = 'liberada (a partir de ' + formatCredits(entryFeeMinCreationCost) + ')';
            } else {
                entryFeePlanStatus.textContent = 'liberada a partir de ' + formatCredits(entryFeeMinCreationCost) + ' na criação';
            }
        }

        if (entryFeeHelp) {
            if (planAllowsEntryFee) {
                entryFeeHelp.textContent = '';
            } else if (b.total >= entryFeeMinCreationCost) {
                entryFeeHelp.textContent = 'Liberada — custo de criação a partir de ' + formatCredits(entryFeeMinCreationCost) + '.';
            } else {
                entryFeeHelp.textContent = 'Liberada a partir de ' + formatCredits(entryFeeMinCreationCost) + ' no custo de criação.';
            }
        }
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
        if (noteEl) {
            noteEl.textContent = getCostNote(b);
        }

        updateEntryFeeAvailability(b);

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

        var insufficient = b.total > 0 && walletAvailable < b.total;
        if (insufficientEl) {
            insufficientEl.style.display = insufficient ? '' : 'none';
        }
        if (confirmBtn) {
            confirmBtn.disabled = insufficient;
        }
    }

    if (input) {
        input.addEventListener('input', updatePreview);
        updatePreview();
    }

    if (form && !isEdit) {
        form.addEventListener('submit', function (event) {
            if (chargeConfirmed) {
                return;
            }

            if (!form.checkValidity()) {
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
