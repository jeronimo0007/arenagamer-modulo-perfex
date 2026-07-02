<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$showGroups = ($t['type'] ?? '') === 'GROUP_STAGE';
$isTeamFormat = ($t['format'] ?? 'SOLO') === 'TEAM';
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$teamsPerGroup = arenagamer_group_stage_teams_per_group();
$advancePerGroup = arenagamer_group_stage_advance_per_group();
$participantUnit = $isTeamFormat ? 'equipes' : 'participantes';
$participantsLimit = (int) ($t['participantsLimit'] ?? 0);
$groupsCount = arenagamer_tournament_group_stage_groups_count($t);
$groupsCountLabel = $groupsCount > 0 ? arenagamer_group_stage_groups_count_label($groupsCount) : '';
$groupsCountPersisted = (int) ($t['groupsCount'] ?? 0) > 0;
?>
<div class="row mtop15" id="group_stage_fields"<?php echo $showGroups ? '' : ' style="display:none;"'; ?>>
    <div class="col-md-12">
        <div class="well well-sm mbot0">
            <i class="fa fa-th-large"></i>
            <strong>Fase de grupos:</strong>
            <span class="label label-primary mleft5"><?php echo $teamsPerGroup; ?> <?php echo htmlspecialchars($participantUnit); ?> por grupo</span>
            <span class="label label-info mleft5"><?php echo $advancePerGroup; ?> classificam por grupo</span>
            <span class="label label-default mleft5" id="group_stage_groups_count_wrap"<?php echo $groupsCountLabel === '' ? ' style="display:none;"' : ''; ?>>
                <span id="group_stage_groups_count"><?php echo htmlspecialchars($groupsCountLabel); ?></span>
            </span>
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mtop10 mbot0">
                Configuração fixa: cada grupo tem <?php echo $teamsPerGroup; ?> <?php echo htmlspecialchars($participantUnit); ?>
                e os <?php echo $advancePerGroup; ?> melhores avançam para o mata-mata.
                O limite de inscritos deve ser múltiplo de <?php echo $teamsPerGroup; ?> (ex.: 4, 8, 12, 16…).
                <?php if ($groupsCountPersisted): ?>
                Grupos já formados na geração das chaves.
                <?php else: ?>
                Com <strong><span id="group_stage_participants_hint"><?php echo $participantsLimit >= $teamsPerGroup ? (int) $participantsLimit : '—'; ?></span></strong>
                <?php echo htmlspecialchars($participantUnit); ?> inscrit<?php echo $isTeamFormat ? 'as' : 'os'; ?>, haverá
                <strong><span id="group_stage_groups_hint"><?php echo $groupsCount > 0 ? (int) $groupsCount : '—'; ?></span></strong> grupo(s).
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var formatEl = document.getElementById('format');
    var groupFields = document.getElementById('group_stage_fields');
    var participantsLimitEl = document.getElementById('participants_limit');
    var groupsCountWrap = document.getElementById('group_stage_groups_count_wrap');
    var groupsCountEl = document.getElementById('group_stage_groups_count');
    var participantsHintEl = document.getElementById('group_stage_participants_hint');
    var groupsHintEl = document.getElementById('group_stage_groups_hint');
    var teamsPerGroup = <?php echo (int) $teamsPerGroup; ?>;
    var groupsCountPersisted = <?php echo $groupsCountPersisted ? 'true' : 'false'; ?>;

    if (!typeEl || !groupFields) {
        return;
    }

    function isTeamFormat() {
        return formatEl && formatEl.value === 'TEAM';
    }

    function participantUnit() {
        return isTeamFormat() ? 'equipes' : 'participantes';
    }

    function groupsCountFromParticipants(total) {
        total = parseInt(total, 10);
        if (!total || total < teamsPerGroup || total % teamsPerGroup !== 0) {
            return 0;
        }
        return Math.floor(total / teamsPerGroup);
    }

    function groupsCountLabel(count) {
        count = parseInt(count, 10);
        if (!count || count < 1) {
            return '';
        }
        return count + ' grupo' + (count === 1 ? '' : 's');
    }

    function updateGroupsPreview() {
        if (groupsCountPersisted || typeEl.value !== 'GROUP_STAGE') {
            return;
        }

        var total = participantsLimitEl ? parseInt(participantsLimitEl.value, 10) : 0;
        var groups = groupsCountFromParticipants(total);
        var label = groupsCountLabel(groups);

        if (participantsHintEl) {
            participantsHintEl.textContent = total >= teamsPerGroup ? String(total) : '—';
        }
        if (groupsHintEl) {
            groupsHintEl.textContent = groups > 0 ? String(groups) : '—';
        }
        if (groupsCountWrap && groupsCountEl) {
            if (label) {
                groupsCountWrap.style.display = '';
                groupsCountEl.textContent = label;
            } else {
                groupsCountWrap.style.display = 'none';
                groupsCountEl.textContent = '';
            }
        }
    }

    function syncGroupFieldsVisibility() {
        var isGroupStage = typeEl.value === 'GROUP_STAGE';
        groupFields.style.display = isGroupStage ? '' : 'none';
        if (isGroupStage) {
            updateGroupsPreview();
        }
    }

    typeEl.addEventListener('change', syncGroupFieldsVisibility);
    if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
        jQuery(typeEl).on('changed.bs.select', syncGroupFieldsVisibility);
    }

    if (participantsLimitEl) {
        participantsLimitEl.addEventListener('input', updateGroupsPreview);
        participantsLimitEl.addEventListener('change', updateGroupsPreview);
    }

    document.addEventListener('arenagamer:format-changed', function () {
        syncGroupFieldsVisibility();
    });
    document.addEventListener('arenagamer:participants-fields-updated', updateGroupsPreview);
    document.addEventListener('arenagamer:participants-limit-constraint-changed', updateGroupsPreview);

    syncGroupFieldsVisibility();
})();
</script>
