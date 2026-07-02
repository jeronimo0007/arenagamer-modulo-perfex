<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$brackets = is_array($double_elimination_brackets ?? null) ? $double_elimination_brackets : [];
$winners = is_array($brackets['winners'] ?? null) ? $brackets['winners'] : [];
$losers = is_array($brackets['losers'] ?? null) ? $brackets['losers'] : [];
$grandFinal = is_array($brackets['grand_final'] ?? null) ? $brackets['grand_final'] : null;
$canManage = !empty($can_manage);
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$finalTop3 = is_array($final_top3 ?? null) ? $final_top3 : [];

$hasWinners = !empty($winners['rounds']);
$hasLosers = !empty($losers['rounds']);
$hasGrandFinal = !empty($grandFinal);
$showSectionTitle = !isset($show_section_title) || $show_section_title;

if (!$hasWinners && !$hasLosers && !$hasGrandFinal) {
    return;
}
?>
<section class="ag-matches-section ag-matches-section--double-elimination">
    <?php if ($showSectionTitle): ?>
    <h5 class="ag-tournament-section-title"><i class="fa fa-code-fork"></i> Chaves — eliminação dupla</h5>
    <?php endif; ?>
    <?php if ($hasWinners): ?>
    <div class="ag-double-elimination-bracket ag-double-elimination-bracket--winners mtop15">
        <h6 class="bold mtop0 mbot10"><i class="fa fa-arrow-up"></i> Chave superior (winners)</h6>
        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_knockout_bracket', [
            'knockout_bracket'         => $winners,
            'can_manage'               => $canManage,
            'participant_standing_map' => $standingMap,
            'final_top3'               => [],
        ]); ?>
    </div>
    <?php endif; ?>

    <?php if ($hasLosers): ?>
    <div class="ag-double-elimination-bracket ag-double-elimination-bracket--losers mtop20">
        <h6 class="bold mtop0 mbot10"><i class="fa fa-arrow-down"></i> Repescagem</h6>
        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_knockout_bracket', [
            'knockout_bracket'         => $losers,
            'can_manage'               => $canManage,
            'participant_standing_map' => $standingMap,
            'final_top3'               => [],
        ]); ?>
    </div>
    <?php endif; ?>

    <?php if ($hasGrandFinal): ?>
    <div class="ag-double-elimination-bracket ag-double-elimination-bracket--grand-final mtop20">
        <h6 class="bold mtop0 mbot10"><i class="fa fa-trophy"></i> Grande final</h6>
        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_knockout_bracket', [
            'knockout_bracket'         => [
                'rounds' => [[
                    'label'       => 'Grande final',
                    'roundType'   => 'GRAND_FINAL',
                    'roundNumber' => 1,
                    'matches'     => [$grandFinal],
                ]],
                'third_place' => null,
            ],
            'can_manage'               => $canManage,
            'participant_standing_map' => $standingMap,
            'final_top3'               => $finalTop3,
        ]); ?>
    </div>
    <?php endif; ?>
</section>
