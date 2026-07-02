<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$showFields = ($t['type'] ?? '') === 'SWISS';
$formatFieldsLocked = !empty($format_fields_locked);
$isTeam = ($t['format'] ?? '') === 'TEAM';
$participantsLimit = (int) ($t['participantsLimit'] ?? 0);
$previewRoundsLabel = $participantsLimit >= 2
    ? arenagamer_swiss_rounds_count_label($participantsLimit, $isTeam)
    : '';
$participantUnit = $isTeam ? 'equipes' : 'participantes';
?>
<div class="row mtop15" id="swiss_fields"<?php echo $showFields ? '' : ' style="display:none;"'; ?>>
    <div class="col-md-12">
        <div class="well well-sm mbot0" id="swiss_rounds_preview"<?php echo $previewRoundsLabel === '' ? ' style="display:none;"' : ''; ?>>
            <i class="fa fa-flag-checkered"></i>
            <strong>Com <span id="swiss_participants_count"><?php echo $participantsLimit >= 2 ? (int) $participantsLimit : '—'; ?></span> <?php echo htmlspecialchars($participantUnit); ?>:</strong>
            <span class="label label-primary mleft5" id="swiss_rounds_count"><?php echo $previewRoundsLabel !== '' ? htmlspecialchars($previewRoundsLabel) : ''; ?></span>
            <small class="text-muted mleft5">(ceil(log₂ <?php echo htmlspecialchars($participantUnit); ?>))</small>
        </div>
    </div>
</div>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var formatEl = document.getElementById('format');
    var fields = document.getElementById('swiss_fields');
    var participantsLimitEl = document.getElementById('participants_limit');
    var previewEl = document.getElementById('swiss_rounds_preview');
    var participantsCountEl = document.getElementById('swiss_participants_count');
    var roundsCountEl = document.getElementById('swiss_rounds_count');

    if (!typeEl || !fields) {
        return;
    }

    function swissTotalRounds(participants) {
        participants = parseInt(participants, 10);
        if (!participants || participants < 2) {
            return 0;
        }
        return Math.max(1, Math.ceil(Math.log(participants) / Math.log(2)));
    }

    function isTeamFormat() {
        return formatEl && formatEl.value === 'TEAM';
    }

    function participantUnit() {
        return isTeamFormat() ? 'equipes' : 'participantes';
    }

    function roundsCountLabel(participants) {
        var rounds = swissTotalRounds(participants);
        if (!rounds) {
            return '';
        }
        return participants + ' ' + participantUnit() + ' → ' + rounds + ' rodada' + (rounds === 1 ? '' : 's');
    }

    function syncRoundsPreview() {
        if (!previewEl || !participantsCountEl || !roundsCountEl) {
            return;
        }

        var participantsLimit = participantsLimitEl ? parseInt(participantsLimitEl.value, 10) : 0;
        var label = roundsCountLabel(participantsLimit);

        if (!label) {
            previewEl.style.display = 'none';
            participantsCountEl.textContent = '—';
            roundsCountEl.textContent = '';
            return;
        }

        previewEl.style.display = '';
        participantsCountEl.textContent = String(participantsLimit);
        roundsCountEl.textContent = label;
    }

    function syncVisibility() {
        fields.style.display = typeEl.value === 'SWISS' ? '' : 'none';
        syncRoundsPreview();
    }

    typeEl.addEventListener('change', syncVisibility);
    if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
        jQuery(typeEl).on('changed.bs.select', syncVisibility);
    }
    if (formatEl) {
        formatEl.addEventListener('change', syncRoundsPreview);
        if (typeof jQuery !== 'undefined' && jQuery(formatEl).hasClass('selectpicker')) {
            jQuery(formatEl).on('changed.bs.select', syncRoundsPreview);
        }
    }
    if (participantsLimitEl) {
        participantsLimitEl.addEventListener('input', syncRoundsPreview);
        participantsLimitEl.addEventListener('change', syncRoundsPreview);
    }

    syncVisibility();
})();
</script>
