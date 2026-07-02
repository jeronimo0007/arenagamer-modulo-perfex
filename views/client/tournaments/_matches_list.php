<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$matches = is_array($matches_data ?? null) ? $matches_data : [];
$pagination = is_array($matches_pagination ?? null) ? $matches_pagination : [];
$baseUrl = (string) ($matches_base_url ?? '');
$matchView = (string) ($match_view ?? 'pending');
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$canManage = !empty($can_manage);
$skipKnockout = !empty($skip_knockout);
$skipGroupStage = !empty($skip_group_stage);
$skipRoundRobin = !empty($skip_round_robin);
$skipSwiss = !empty($skip_swiss);
$title = (string) ($title ?? 'Todas as partidas');
$showSectionTitle = !isset($show_section_title) || $show_section_title;

$visibleMatches = [];
foreach ($matches as $match) {
    if (!is_array($match)) {
        continue;
    }
    if ($skipKnockout && arenagamer_is_knockout_match($match)) {
        continue;
    }
    if ($skipGroupStage && arenagamer_is_group_stage_match($match)) {
        continue;
    }
    if ($skipRoundRobin && arenagamer_is_round_robin_match($match)) {
        continue;
    }
    if ($skipSwiss && arenagamer_is_swiss_phase_match($match)) {
        continue;
    }
    $visibleMatches[] = $match;
}

$hasPagination = $baseUrl !== '' && (!empty($pagination['totalElements']) || !empty($matches));
if (!$hasPagination && empty($visibleMatches)) {
    return;
}
?>
<section class="ag-matches-section ag-matches-section--list">
    <?php if ($showSectionTitle): ?>
    <h5 class="ag-tournament-section-title"><i class="fa fa-calendar"></i> <?php echo htmlspecialchars($title); ?></h5>
    <?php endif; ?>

    <?php if ($baseUrl !== ''): ?>
    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_matches_filters', [
        'match_view'         => $matchView,
        'matches_pagination' => $pagination,
        'matches_base_url'   => $baseUrl,
    ]); ?>
    <?php endif; ?>

    <?php if (!empty($visibleMatches)): ?>
    <div class="ag-matches-grid">
        <?php foreach ($visibleMatches as $match): ?>
        <?php
            $metaParts = [];
            if (!empty($match['matchNumber'])) {
                $metaParts[] = '#' . (int) $match['matchNumber'];
            }
            if (!empty($match['phaseLabel'])) {
                $metaParts[] = (string) $match['phaseLabel'];
            } elseif (arenagamer_match_bracket_side($match) !== null) {
                $metaParts[] = arenagamer_match_result_context_label($match);
            }
            if (arenagamer_is_group_stage_match($match)) {
                $groupNumber = (int) arenagamer_match_group_number($match);
                if ($groupNumber > 0) {
                    $metaParts[] = 'Grupo ' . $groupNumber;
                }
            }
            $context = (arenagamer_is_knockout_match($match) || arenagamer_is_knockout_placement_match($match))
                ? 'knockout'
                : 'group';
        ?>
        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_card', [
            'match'        => $match,
            'standing_map' => $standingMap,
            'can_manage'   => $canManage,
            'meta_label'   => implode(' · ', $metaParts),
            'context'      => $context,
            'show_reschedule' => !empty($show_reschedule),
        ]); ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="ag-matches-empty">Nenhuma partida encontrada para este filtro.</p>
    <?php endif; ?>
</section>
