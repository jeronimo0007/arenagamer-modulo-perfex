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
$useSelectpicker = !empty($use_selectpicker);
$selectClass = 'form-control' . ($useSelectpicker ? ' selectpicker' : '');
$currentType = (string) ($t['type'] ?? 'SINGLE_ELIMINATION');
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
                        <?php foreach (arenagamer_tournament_type_options() as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $currentType === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(arenagamer_tournament_type_label($type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($currentType); ?>">
                    <?php else: ?>
                    <select name="type" id="type" class="<?php echo $selectClass; ?>">
                        <?php foreach (arenagamer_tournament_type_options() as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $currentType === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(arenagamer_tournament_type_label($type)); ?>
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

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label id="participants_limit_label" class="<?php echo htmlspecialchars($labelClass); ?>">Limite de participantes</label>
                    <input type="number"
                           min="2"
                           name="participants_limit"
                           id="participants_limit"
                           class="form-control"
                           value="<?php echo $defaultLimit; ?>"
                           <?php echo $isEdit ? 'readonly' : ''; ?>>
                    <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="participants_limit_help"
                       data-solo-help="<?php echo htmlspecialchars($participants_solo_help ?? ''); ?>"
                       data-team-help="<?php echo htmlspecialchars($participants_team_help ?? ''); ?>">
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
                    <input type="number" min="<?php echo arenagamer_tournament_min_participants(); ?>" name="min_participants" id="min_participants" class="form-control"
                           value="<?php echo max(arenagamer_tournament_min_participants(), (int) ($t['minParticipants'] ?? arenagamer_tournament_min_participants())); ?>">
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
        ]); ?>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_format_fields', [
            'tournament'           => $t,
            'format_fields_locked' => $formatFieldsLocked,
        ]); ?>
    </div>
</div>
