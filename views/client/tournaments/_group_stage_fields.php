<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$showGroups = ($t['type'] ?? '') === 'GROUP_STAGE';
$formatFieldsLocked = !empty($format_fields_locked);
$isTeamFormat = ($t['format'] ?? 'SOLO') === 'TEAM';
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$groupsHelpTeam = 'As equipes inscritas serão divididas igualmente entre os grupos na geração da chave.';
$groupsHelpSolo = 'Os participantes inscritos serão divididos igualmente entre os grupos na geração da chave.';
?>
<div class="row mtop15" id="group_stage_fields"<?php echo $showGroups ? '' : ' style="display:none;"'; ?>>
    <div class="col-md-4">
        <div class="form-group">
            <label for="groups_count" class="<?php echo htmlspecialchars($labelClass); ?>" id="groups_count_label">Quantidade de grupos</label>
            <input type="number"
                   min="1"
                   name="groups_count"
                   id="groups_count"
                   class="form-control"
                   value="<?php echo htmlspecialchars($t['groupsCount'] ?? ''); ?>"
                   <?php echo $formatFieldsLocked ? 'readonly' : ''; ?>>
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="groups_count_help"
               data-solo-help="<?php echo htmlspecialchars($groupsHelpSolo); ?>"
               data-team-help="<?php echo htmlspecialchars($groupsHelpTeam); ?>">
                <?php echo htmlspecialchars($isTeamFormat ? $groupsHelpTeam : $groupsHelpSolo); ?>
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="advance_per_group" class="<?php echo htmlspecialchars($labelClass); ?>">Classificados por grupo</label>
            <input type="number"
                   min="1"
                   name="advance_per_group"
                   id="advance_per_group"
                   class="form-control"
                   value="<?php echo htmlspecialchars($t['advancePerGroup'] ?? ''); ?>"
                   <?php echo $formatFieldsLocked ? 'readonly' : ''; ?>>
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
                Quantos de cada grupo avançam para o mata-mata. Use 2 para gerar disputa de 3º lugar.
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var groupFields = document.getElementById('group_stage_fields');
    var groupsCountEl = document.getElementById('groups_count');
    var advancePerGroupEl = document.getElementById('advance_per_group');
    var formatFieldsLocked = <?php echo $formatFieldsLocked ? 'true' : 'false'; ?>;

    if (!typeEl || !groupFields) {
        return;
    }

    function syncGroupFieldsVisibility() {
        var isGroupStage = typeEl.value === 'GROUP_STAGE';
        groupFields.style.display = isGroupStage ? '' : 'none';

        if (!formatFieldsLocked) {
            [groupsCountEl, advancePerGroupEl].forEach(function (el) {
                if (!el) { return; }
                if (isGroupStage) {
                    el.removeAttribute('disabled');
                } else {
                    el.setAttribute('disabled', 'disabled');
                }
            });
        }
    }

    if (!formatFieldsLocked) {
        typeEl.addEventListener('change', syncGroupFieldsVisibility);
        if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
            jQuery(typeEl).on('changed.bs.select', syncGroupFieldsVisibility);
        }
    }

    document.addEventListener('arenagamer:format-changed', syncGroupFieldsVisibility);

    syncGroupFieldsVisibility();
})();
</script>
