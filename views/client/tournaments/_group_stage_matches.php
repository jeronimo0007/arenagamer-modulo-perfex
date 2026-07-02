<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$blocks = is_array($group_stage_blocks ?? null) ? $group_stage_blocks : [];
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$canManage = !empty($can_manage);

if (empty($blocks)) {
    return;
}

$hasMatches = false;
foreach ($blocks as $block) {
    if (!empty($block['matches'])) {
        $hasMatches = true;
        break;
    }
}

if (!$hasMatches) {
    return;
}
?>
<section class="ag-matches-section ag-matches-section--groups">
    <h5 class="ag-tournament-section-title"><i class="fa fa-gamepad"></i> Jogos da fase de grupos</h5>
    <?php foreach ($blocks as $block): ?>
    <?php
        $groupNumber = (int) ($block['number'] ?? 0);
        $matches = is_array($block['matches'] ?? null) ? $block['matches'] : [];
        if (empty($matches)) {
            continue;
        }
    ?>
    <div class="ag-matches-group">
        <h6 class="ag-matches-group__title"><span class="ag-matches-group__pill">Grupo <?php echo $groupNumber; ?></span></h6>
        <div class="ag-matches-grid">
            <?php foreach ($matches as $match): ?>
            <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_card', [
                'match'         => $match,
                'standing_map'  => $standingMap,
                'can_manage'    => $canManage,
                'meta_label'    => !empty($match['matchNumber']) ? 'Jogo #' . (int) $match['matchNumber'] : '',
                'context'       => 'group',
            ]); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>
