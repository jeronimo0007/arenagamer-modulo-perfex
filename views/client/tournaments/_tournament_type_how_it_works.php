<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$systems = is_array($tournament_systems ?? null) ? $tournament_systems : arenagamer_tournament_systems_default();
$enabledTypes = is_array($tournament_type_options ?? null)
    ? $tournament_type_options
    : arenagamer_tournament_type_options_for_form($systems, $t['type'] ?? null);
$currentType = (string) ($t['type'] ?? '');
if ($currentType === '' && !empty($enabledTypes)) {
    $currentType = (string) $enabledTypes[0];
}
if ($currentType === '') {
    $currentType = 'SINGLE_ELIMINATION';
}
$isTeam = ($t['format'] ?? 'SOLO') === 'TEAM';
$howItWorks = arenagamer_tournament_type_how_it_works($currentType, [
    'format'            => $t['format'] ?? 'SOLO',
    'participantsLimit' => (int) ($t['participantsLimit'] ?? 0),
    'advanceToKnockout' => (int) ($t['advanceToKnockout'] ?? 0),
]);
$catalog = array_intersect_key(
    arenagamer_tournament_type_how_it_works_catalog(),
    array_flip($enabledTypes)
);
$labels = array_intersect_key(
    is_array($tournament_type_labels ?? null)
        ? $tournament_type_labels
        : arenagamer_tournament_type_labels_from_systems($systems),
    array_flip($enabledTypes)
);
$catalogJson = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$groupStageTeamsPerGroup = arenagamer_group_stage_teams_per_group();
$groupStageAdvancePerGroup = arenagamer_group_stage_advance_per_group();
?>
<div class="ag-tournament-how-it-works mtop15 mbot15" id="tournament_type_how_it_works">
    <div class="alert alert-info mbot0">
        <div class="ag-tournament-how-it-works__header mbot10">
            <i class="fa <?php echo htmlspecialchars($howItWorks['icon']); ?>"></i>
            <strong>Como funciona</strong>
            <span class="text-muted">—</span>
            <span id="type_how_title"><?php echo htmlspecialchars($howItWorks['title']); ?></span>
        </div>
        <p class="ag-tournament-how-it-works__description mbot10" id="type_how_description">
            <?php echo htmlspecialchars($howItWorks['description']); ?>
        </p>
        <p class="mbot0" id="type_how_extra_wrap"<?php echo empty($howItWorks['extra']) ? ' style="display:none;"' : ''; ?>>
            <span class="label label-primary" id="type_how_extra"><?php echo htmlspecialchars((string) ($howItWorks['extra'] ?? '')); ?></span>
        </p>
        <p class="text-muted mtop10 mbot0" id="type_how_tip_wrap"<?php echo empty($howItWorks['tip']) ? ' style="display:none;"' : ''; ?>>
            <small><i class="fa fa-lightbulb-o"></i> <span id="type_how_tip"><?php echo htmlspecialchars((string) ($howItWorks['tip'] ?? '')); ?></span></small>
        </p>
    </div>
</div>
<script>
(function () {
    var catalog = <?php echo $catalogJson ?: '{}'; ?>;
    var typeLabels = <?php echo $labelsJson ?: '{}'; ?>;
    var groupStageTeamsPerGroup = <?php echo (int) $groupStageTeamsPerGroup; ?>;
    var groupStageAdvancePerGroup = <?php echo (int) $groupStageAdvancePerGroup; ?>;
    var typeEl = document.getElementById('type');
    var formatEl = document.getElementById('format');
    var participantsLimitEl = document.getElementById('participants_limit');
    var advanceKnockoutEl = document.getElementById('advance_to_knockout');
    var titleEl = document.getElementById('type_how_title');
    var descriptionEl = document.getElementById('type_how_description');
    var extraWrapEl = document.getElementById('type_how_extra_wrap');
    var extraEl = document.getElementById('type_how_extra');
    var tipWrapEl = document.getElementById('type_how_tip_wrap');
    var tipEl = document.getElementById('type_how_tip');
    var panelEl = document.getElementById('tournament_type_how_it_works');
    var headerIconEl = panelEl ? panelEl.querySelector('.ag-tournament-how-it-works__header .fa') : null;

    if (!typeEl || !descriptionEl) {
        return;
    }

    function isTeamFormat() {
        return formatEl && formatEl.value === 'TEAM';
    }

    function participantUnit() {
        return isTeamFormat() ? 'equipes' : 'participantes';
    }

    function swissTotalRounds(participants) {
        participants = parseInt(participants, 10);
        if (!participants || participants < 2) {
            return 0;
        }
        return Math.max(1, Math.ceil(Math.log(participants) / Math.log(2)));
    }

    function buildExtra(type) {
        var participantsLimit = participantsLimitEl ? parseInt(participantsLimitEl.value, 10) : 0;
        var unit = participantUnit();

        if (type === 'SWISS' && participantsLimit >= 2) {
            var rounds = swissTotalRounds(participantsLimit);
            return participantsLimit + ' ' + unit + ' → ' + rounds + ' rodada' + (rounds === 1 ? '' : 's');
        }

        if (type === 'DOUBLE_ELIMINATION' && participantsLimit >= 2) {
            var superiorRounds = Math.max(1, Math.ceil(Math.log(participantsLimit) / Math.log(2)));
            var inferiorRounds = 2 * Math.max(0, superiorRounds - 1);
            return participantsLimit + ' ' + unit
                + ' → superior ' + superiorRounds + ' rodada' + (superiorRounds === 1 ? '' : 's')
                + ', repescagem ' + inferiorRounds
                + ', grande final';
        }

        if (type === 'ROUND_ROBIN_ELIMINATION' && advanceKnockoutEl) {
            var advance = parseInt(advanceKnockoutEl.value, 10);
            if (advance >= 2 && participantsLimit > advance) {
                return advance + ' classificam para o mata-mata (de ' + participantsLimit + ' inscritos).';
            }
        }

        if (type === 'GROUP_STAGE') {
            var extra = groupStageTeamsPerGroup + ' ' + unit + ' por grupo, '
                + groupStageAdvancePerGroup + ' classificam por grupo para o mata-mata.';
            if (participantsLimit >= groupStageTeamsPerGroup && participantsLimit % groupStageTeamsPerGroup === 0) {
                var groups = participantsLimit / groupStageTeamsPerGroup;
                extra += ' → ' + groups + ' grupo' + (groups === 1 ? '' : 's');
            }
            return extra;
        }

        return '';
    }

    function render() {
        var type = typeEl.value;
        var entry = catalog[type] || null;
        var title = typeLabels[type] || type;

        if (titleEl) {
            titleEl.textContent = title;
        }

        if (headerIconEl) {
            headerIconEl.className = 'fa ' + (entry && entry.icon ? entry.icon : 'fa-info-circle');
        }

        descriptionEl.textContent = entry && entry.description
            ? entry.description
            : 'Selecione um modo de torneio para ver como funciona.';

        var extra = buildExtra(type);
        if (extraWrapEl && extraEl) {
            if (extra) {
                extraWrapEl.style.display = '';
                extraEl.textContent = extra;
            } else {
                extraWrapEl.style.display = 'none';
                extraEl.textContent = '';
            }
        }

        if (tipWrapEl && tipEl) {
            var tip = entry && entry.tip ? entry.tip : '';
            if (tip) {
                tipWrapEl.style.display = '';
                tipEl.textContent = tip;
            } else {
                tipWrapEl.style.display = 'none';
                tipEl.textContent = '';
            }
        }
    }

    typeEl.addEventListener('change', render);
    if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
        jQuery(typeEl).on('changed.bs.select', render);
    }

    [formatEl, participantsLimitEl, advanceKnockoutEl].forEach(function (el) {
        if (!el) {
            return;
        }
        el.addEventListener('input', render);
        el.addEventListener('change', render);
        if (typeof jQuery !== 'undefined' && jQuery(el).hasClass('selectpicker')) {
            jQuery(el).on('changed.bs.select', render);
        }
    });

    render();
})();
</script>
