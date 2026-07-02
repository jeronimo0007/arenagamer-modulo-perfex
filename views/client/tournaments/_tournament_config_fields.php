<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isEdit = !empty($is_edit);
$formatFieldsLocked = $isEdit && arenagamer_tournament_format_fields_locked($t);
$formatLockedHelp = arenagamer_tournament_format_locked_message();
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$defaultLimit = (int) ($default_participants_limit ?? $t['participantsLimit'] ?? 20);
$includedForBilling = (int) ($included_for_billing ?? 0);
$extraPrice = (float) ($extra_participant_price ?? 0);
$planParticipantBenefitsActive = !empty($plan_participant_benefits_active);
$planMaxParticipants = (int) ($plan_max_participants ?? 0);
$useSelectpicker = !empty($use_selectpicker);
$selectClass = 'form-control' . ($useSelectpicker ? ' selectpicker' : '');
$systems = is_array($tournament_systems ?? null) ? $tournament_systems : arenagamer_tournament_systems_default();
$typeOptions = is_array($tournament_type_options ?? null)
    ? $tournament_type_options
    : arenagamer_tournament_type_options_for_form($systems, $t['type'] ?? null);
$typeLabels = is_array($tournament_type_labels ?? null)
    ? $tournament_type_labels
    : arenagamer_tournament_type_labels_from_systems($systems);
$currentType = (string) ($t['type'] ?? '');
if ($currentType === '' && !empty($typeOptions)) {
    $currentType = (string) $typeOptions[0];
}
if ($currentType === '') {
    $currentType = 'SINGLE_ELIMINATION';
}
$currentFormat = (string) ($t['format'] ?? 'SOLO');
?>
<div class="panel_s mtop10 mbot15">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-cog"></i> Configurações do torneio</h4>
    </div>
    <div class="panel-body">
        <?php if ($formatFieldsLocked): ?>
        <p class="alert alert-warning mtop0 mbot15">
            <i class="fa fa-lock"></i> <?php echo htmlspecialchars($formatLockedHelp); ?>
        </p>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="type" class="<?php echo htmlspecialchars($labelClass); ?>">Modo de torneio</label>
                    <?php if ($formatFieldsLocked): ?>
                    <select id="type" class="<?php echo $selectClass; ?>" disabled>
                        <?php foreach ($typeOptions as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $currentType === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($typeLabels[$type] ?? arenagamer_tournament_type_label($type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($currentType); ?>">
                    <?php else: ?>
                    <select name="type" id="type" class="<?php echo $selectClass; ?>">
                        <?php foreach ($typeOptions as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $currentType === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($typeLabels[$type] ?? arenagamer_tournament_type_label($type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="format" class="<?php echo htmlspecialchars($labelClass); ?>">Formato</label>
                    <?php if ($formatFieldsLocked): ?>
                    <select id="format" class="<?php echo $selectClass; ?>" disabled>
                        <?php foreach (arenagamer_tournament_format_options() as $format): ?>
                        <option value="<?php echo $format; ?>" <?php echo $currentFormat === $format ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(arenagamer_tournament_format_label($format)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="format" value="<?php echo htmlspecialchars($currentFormat); ?>">
                    <?php else: ?>
                    <select name="format" id="format" class="<?php echo $selectClass; ?>">
                        <?php foreach (arenagamer_tournament_format_options() as $format): ?>
                        <option value="<?php echo $format; ?>" <?php echo $currentFormat === $format ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(arenagamer_tournament_format_label($format)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="visibility" class="<?php echo htmlspecialchars($labelClass); ?>">Visibilidade</label>
                    <select name="visibility" id="visibility" class="<?php echo $selectClass; ?>">
                        <?php foreach (arenagamer_tournament_visibility_options() as $visibility): ?>
                        <option value="<?php echo $visibility; ?>" <?php echo ($t['visibility'] ?? 'PUBLIC') === $visibility ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(arenagamer_tournament_visibility_label($visibility)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_tournament_type_how_it_works', [
            'tournament'              => $t,
            'tournament_systems'      => $systems,
            'tournament_type_options' => $typeOptions,
            'tournament_type_labels'  => $typeLabels,
        ]); ?>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label id="participants_limit_label" class="<?php echo htmlspecialchars($labelClass); ?>">Limite de participantes</label>
                    <div id="participants_limit_field">
                    <?php
                    $minParticipantsField = max(2, arenagamer_tournament_min_participants());
                    $participantsStep = arenagamer_tournament_participants_step();
                    $defaultMin = max($minParticipantsField, (int) ($t['minParticipants'] ?? $minParticipantsField));
                    if (!$isEdit && $defaultMin > $defaultLimit) {
                        $defaultMin = $defaultLimit;
                    }
                    ?>
                    <input type="number"
                           min="<?php echo $minParticipantsField; ?>"
                           step="<?php echo $participantsStep; ?>"
                           name="participants_limit"
                           id="participants_limit"
                           class="form-control"
                           value="<?php echo (int) $defaultLimit; ?>"
                           <?php if (!$isEdit && $planMaxParticipants > 0): ?>max="<?php echo (int) $planMaxParticipants; ?>"<?php endif; ?>
                           <?php echo $isEdit ? 'readonly' : ''; ?>>
                    </div>
                    <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="participants_limit_help"
                       data-solo-help="<?php echo htmlspecialchars($participants_solo_help ?? ''); ?>"
                       data-team-help="<?php echo htmlspecialchars($participants_team_help ?? ''); ?>"
                       data-group-stage-help="Escolha um valor múltiplo dos participantes por grupo (ex.: 4, 8, 12…).">
                        <?php echo htmlspecialchars($participants_help_text ?? ''); ?>
                    </p>
                    <?php if (!empty($show_entry_fee_unlock_button) && (float) ($extra_participant_price ?? 0) > 0): ?>
                    <button type="button" class="btn btn-default btn-xs mtop5" id="unlock-entry-fee-participants-btn" style="display:none;"
                            title="Ajusta participantes avulsos para atingir o mínimo de créditos na criação. Haverá cobrança extra.">
                        Liberar taxa de inscrição
                    </button>
                    <small class="text-muted mtop5 unlock-entry-fee-hint" id="unlock-entry-fee-participants-hint" style="display:none;">
                        Será cobrado extra em participantes avulsos para liberar a taxa de inscrição.
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="min_participants" class="<?php echo htmlspecialchars($labelClass); ?>" id="min_participants_label">Mín. participantes</label>
                    <div id="min_participants_field">
                    <input type="number"
                           min="<?php echo $minParticipantsField; ?>"
                           step="<?php echo $participantsStep; ?>"
                           max="<?php echo (int) $defaultLimit; ?>"
                           name="min_participants"
                           id="min_participants"
                           class="form-control"
                           value="<?php echo (int) $defaultMin; ?>"
                           <?php echo $isEdit ? 'readonly' : ''; ?>>
                    </div>
                    <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="min_participants_help"
                       data-solo-help="Mínimo para iniciar o torneio. Não pode ser maior que o limite de participantes."
                       data-team-help="Mínimo de equipes para iniciar o torneio. Não pode ser maior que o limite de equipes.">
                        Mínimo para iniciar o torneio. Não pode ser maior que o limite de participantes.
                    </p>
                </div>
            </div>
            <?php if (!empty($show_best_of)): ?>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="best_of" class="<?php echo htmlspecialchars($labelClass); ?>">Melhor de (MD)</label>
                    <input type="number" min="1" name="best_of" id="best_of" class="form-control"
                           value="<?php echo htmlspecialchars($t['bestOf'] ?? ''); ?>">
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_group_stage_fields', [
            'tournament'           => $t,
            'label_class'          => $labelClass,
            'help_class'           => $helpClass,
            'format_fields_locked' => $formatFieldsLocked,
            'plan_max_participants'=> $planMaxParticipants,
            'min_participants'     => arenagamer_tournament_min_participants(),
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_round_robin_elimination_fields', [
            'tournament'           => $t,
            'label_class'          => $labelClass,
            'help_class'           => $helpClass,
            'format_fields_locked' => $formatFieldsLocked,
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_swiss_fields', [
            'tournament'           => $t,
            'format_fields_locked' => $formatFieldsLocked,
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_format_fields', [
            'tournament'           => $t,
            'format_fields_locked' => $formatFieldsLocked,
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_participants_limit_constraints', [
            'plan_max_participants'     => $planMaxParticipants,
            'min_participants'          => arenagamer_tournament_min_participants(),
            'default_participants_limit'=> $defaultLimit,
            'default_min_participants'  => $defaultMin,
            'is_edit'                   => $isEdit,
        ]); ?>
    </div>
</div>
