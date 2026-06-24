<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$showGroups = ($t['type'] ?? '') === 'GROUP_STAGE';
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
?>
<div class="row mtop15" id="group_stage_fields"<?php echo $showGroups ? '' : ' style="display:none;"'; ?>>
    <div class="col-md-4">
        <div class="form-group">
            <label for="groups_count" class="<?php echo htmlspecialchars($labelClass); ?>">Grupos</label>
            <input type="number" min="1" name="groups_count" id="groups_count" class="form-control"
                   value="<?php echo htmlspecialchars($t['groupsCount'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="teams_per_group" class="<?php echo htmlspecialchars($labelClass); ?>">Times por grupo</label>
            <input type="number" min="1" name="teams_per_group" id="teams_per_group" class="form-control"
                   value="<?php echo htmlspecialchars($t['teamsPerGroup'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="advance_per_group" class="<?php echo htmlspecialchars($labelClass); ?>">Avançam por grupo</label>
            <input type="number" min="1" name="advance_per_group" id="advance_per_group" class="form-control"
                   value="<?php echo htmlspecialchars($t['advancePerGroup'] ?? ''); ?>">
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
                Quantos classificam de cada grupo para a próxima fase.
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var groupFields = document.getElementById('group_stage_fields');

    if (!typeEl || !groupFields) {
        return;
    }

    function syncGroupFields() {
        var isGroupStage = typeEl.value === 'GROUP_STAGE';
        groupFields.style.display = isGroupStage ? '' : 'none';

        ['groups_count', 'teams_per_group', 'advance_per_group'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            if (isGroupStage) {
                el.removeAttribute('disabled');
            } else {
                el.setAttribute('disabled', 'disabled');
            }
        });
    }

    typeEl.addEventListener('change', syncGroupFields);

    if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
        jQuery(typeEl).on('changed.bs.select', syncGroupFields);
    }

    syncGroupFields();
})();
</script>
