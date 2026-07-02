<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$allMatches = is_array($all_matches ?? null) ? $all_matches : [];
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$canManage = !empty($can_manage);
$tournament = is_array($tournament ?? null) ? $tournament : [];
$roundBlocks = arenagamer_build_swiss_round_blocks(arenagamer_collect_swiss_matches($allMatches));

if (empty($roundBlocks)) {
    return;
}

$totalRounds = !empty($tournament)
    ? arenagamer_swiss_total_rounds_for_tournament($tournament)
    : count($roundBlocks);
$currentRound = count($roundBlocks);
$roundsLabel = !empty($tournament)
    ? arenagamer_swiss_rounds_count_label(arenagamer_swiss_participant_count($tournament), ($tournament['format'] ?? '') === 'TEAM')
    : '';
?>
<section class="ag-matches-section ag-matches-section--swiss">
    <h5 class="ag-tournament-section-title">
        <i class="fa fa-random"></i> Jogos — sistema suíço
        <?php if ($totalRounds > 0): ?>
        <small class="text-muted normal">(rodada <?php echo (int) $currentRound; ?> de <?php echo (int) $totalRounds; ?>)</small>
        <?php endif; ?>
    </h5>
    <?php if ($roundsLabel !== ''): ?>
    <p class="text-muted mbot10"><small><i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($roundsLabel); ?></small></p>
    <?php endif; ?>
    <?php foreach ($roundBlocks as $block): ?>
    <?php $roundMatches = is_array($block['matches'] ?? null) ? $block['matches'] : []; ?>
    <?php if (empty($roundMatches)) {
    continue;
} ?>
    <div class="ag-swiss-round mtop15">
        <h6 class="ag-swiss-round__title bold mtop0 mbot10">
            <i class="fa fa-flag-checkered"></i> <?php echo htmlspecialchars($block['label'] ?? 'Rodada'); ?>
        </h6>
        <div class="ag-matches-grid">
            <?php foreach ($roundMatches as $match): ?>
            <?php
                $homeId = $match['homeParticipantId'] ?? null;
                $awayId = $match['awayParticipantId'] ?? null;
                $isBye = !empty($homeId) && empty($awayId);
                $metaLabel = !empty($match['matchNumber']) ? 'Jogo #' . (int) $match['matchNumber'] : (string) ($block['label'] ?? 'Suíço');
                if ($isBye) {
                    $metaLabel .= ' · Bye';
                }
            ?>
            <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_card', [
                'match'        => $match,
                'standing_map' => $standingMap,
                'can_manage'   => $canManage,
                'meta_label'   => $metaLabel,
                'context'      => 'group',
            ]); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>
