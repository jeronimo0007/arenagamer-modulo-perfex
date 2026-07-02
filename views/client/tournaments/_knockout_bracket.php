<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$bracket = is_array($knockout_bracket ?? null) ? $knockout_bracket : [];
$rounds = is_array($bracket['rounds'] ?? null) ? $bracket['rounds'] : [];
$thirdPlace = is_array($bracket['third_place'] ?? null) ? $bracket['third_place'] : null;
$canManage = !empty($can_manage);
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$finalTop3 = is_array($final_top3 ?? null) ? $final_top3 : [];
$roundCount = count($rounds);

if ($roundCount === 0) {
    return;
}

$roundIcons = [
    'ROUND_OF_32'  => 'fa-th',
    'ROUND_OF_16'  => 'fa-th-large',
    'OITAVAS'      => 'fa-th-large',
    'QUARTERFINAL' => 'fa-sitemap',
    'SEMIFINAL'    => 'fa-flag',
    'SEMI_FINAL'   => 'fa-flag',
    'FINAL'        => 'fa-trophy',
    'KNOCKOUT'     => 'fa-sitemap',
];

$renderGame = function (array $match) use ($canManage, $standingMap) {
    $home = arenagamer_bracket_participant_row($match, 'home', $standingMap, ['context' => 'knockout']);
    $away = arenagamer_bracket_participant_row($match, 'away', $standingMap, ['context' => 'knockout']);
    $homeId = $match['homeParticipantId'] ?? null;
    $awayId = $match['awayParticipantId'] ?? null;
    $matchStatus = $match['status'] ?? '';
    $bothDefined = !empty($homeId) && !empty($awayId);
    $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
    $scheduled = !empty($match['scheduledAt'])
        ? arenagamer_format_date($match['scheduledAt'], 'd/m H:i')
        : '';
    ?>
    <div class="ag-knockout-bracket__game<?php echo $isFinished ? ' ag-knockout-bracket__game--done' : ''; ?>">
        <div class="ag-knockout-bracket__game-actions">
            <span class="ag-knockout-bracket__game-meta">
                <?php if ($scheduled !== ''): ?>
                <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($scheduled); ?>
                <?php endif; ?>
            </span>
            <?php if ($canManage && $bothDefined && !$isFinished): ?>
            <button type="button" class="ag-knockout-bracket__result-btn" data-toggle="modal" data-target="#result-modal-<?php echo (int) $match['id']; ?>" title="Registrar resultado" aria-label="Registrar resultado">
                <i class="fa fa-trophy"></i>
            </button>
            <?php endif; ?>
        </div>
        <div class="ag-knockout-bracket__team<?php echo $home['tierClass']; ?><?php echo $home['winner'] ? ' ag-knockout-bracket__team--winner' : ''; ?><?php echo $home['empty'] ? ' ag-knockout-bracket__team--empty' : ''; ?>">
            <?php if ($home['winner']): ?><i class="fa fa-trophy ag-knockout-bracket__trophy"></i><?php endif; ?>
            <span class="ag-knockout-bracket__team-name"><?php echo htmlspecialchars($home['label']); ?></span>
            <?php if ($home['score'] !== null): ?>
            <span class="ag-knockout-bracket__team-score"><?php echo (int) $home['score']; ?></span>
            <?php endif; ?>
            <?php echo arenagamer_render_participant_rank_badge($home); ?>
        </div>
        <div class="ag-knockout-bracket__vs">vs</div>
        <div class="ag-knockout-bracket__team<?php echo $away['tierClass']; ?><?php echo $away['winner'] ? ' ag-knockout-bracket__team--winner' : ''; ?><?php echo $away['empty'] ? ' ag-knockout-bracket__team--empty' : ''; ?>">
            <?php if ($away['winner']): ?><i class="fa fa-trophy ag-knockout-bracket__trophy"></i><?php endif; ?>
            <span class="ag-knockout-bracket__team-name"><?php echo htmlspecialchars($away['label']); ?></span>
            <?php if ($away['score'] !== null): ?>
            <span class="ag-knockout-bracket__team-score"><?php echo (int) $away['score']; ?></span>
            <?php endif; ?>
            <?php echo arenagamer_render_participant_rank_badge($away); ?>
        </div>
    </div>
    <?php
};
?>
<div class="ag-knockout-bracket<?php echo !empty($finalTop3) ? ' ag-knockout-bracket--has-final-top5' : ''; ?>">
    <div class="ag-knockout-bracket__scroll">
        <div class="ag-knockout-bracket__tree" style="--ag-round-count: <?php echo (int) $roundCount; ?>;">
            <?php foreach ($rounds as $roundIndex => $round): ?>
            <?php
                $isLast = ($roundIndex === $roundCount - 1);
                $isFinal = ($round['roundType'] ?? '') === 'FINAL' || $isLast;
                $matches = is_array($round['matches'] ?? null) ? $round['matches'] : [];
                $pairs = $isLast ? array_map(function ($m) { return [$m]; }, $matches) : array_chunk($matches, 2);
                $roundType = (string) ($round['roundType'] ?? '');
                $roundIcon = $roundIcons[$roundType] ?? 'fa-sitemap';
            ?>
            <div class="ag-knockout-bracket__column<?php echo $isFinal ? ' ag-knockout-bracket__column--final' : ''; ?>"
                 style="--ag-round-index: <?php echo (int) $roundIndex; ?>;">
                <div class="ag-knockout-bracket__column-title<?php echo $isFinal ? ' ag-knockout-bracket__column-title--final' : ''; ?>">
                    <i class="fa <?php echo htmlspecialchars($roundIcon); ?>"></i>
                    <?php echo htmlspecialchars($round['label'] ?? 'Rodada'); ?>
                </div>
                <div class="ag-knockout-bracket__column-body">
                    <?php foreach ($pairs as $pair): ?>
                    <div class="ag-knockout-bracket__pair<?php echo count($pair) === 1 ? ' ag-knockout-bracket__pair--single' : ''; ?>">
                        <?php foreach ($pair as $match): ?>
                        <?php $renderGame($match); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($thirdPlace !== null): ?>
    <div class="ag-knockout-bracket__third-place">
        <div class="ag-knockout-bracket__column-title">
            <i class="fa fa-medal"></i>
            <?php echo htmlspecialchars(arenagamer_knockout_round_label('THIRD_PLACE', $thirdPlace['phaseLabel'] ?? null)); ?>
        </div>
        <?php $renderGame($thirdPlace); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($finalTop3)): ?>
    <div class="ag-knockout-bracket__bottom">
        <div class="ag-knockout-bracket__top5" title="Classificação final do torneio (1º ao 3º)">
            <span class="ag-knockout-bracket__top5-label"><i class="fa fa-trophy"></i> Top 3</span>
            <ul class="ag-knockout-bracket__top5-list">
                <?php foreach ($finalTop3 as $entry): ?>
                <?php
                    $tier = (string) ($entry['tier'] ?? '');
                    $displayName = arenagamer_participant_display_label($entry['participantName'], 22);
                    $fullName = (string) ($entry['participantName'] ?? '');
                ?>
                <li class="ag-knockout-bracket__top5-item">
                    <?php if ($tier !== ''): ?>
                    <span class="ag-rank-badge ag-rank-badge--<?php echo htmlspecialchars($tier); ?>"><?php echo htmlspecialchars($entry['rankLabel']); ?></span>
                    <?php endif; ?>
                    <span class="ag-knockout-bracket__top5-name"<?php echo $fullName !== '' && $displayName !== $fullName ? ' title="' . htmlspecialchars($fullName) . '"' : ''; ?>>
                        <?php echo htmlspecialchars($displayName); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>
